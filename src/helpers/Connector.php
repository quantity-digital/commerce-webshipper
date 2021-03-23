<?php

namespace QD\commerce\webshipper\helpers;

use Craft;
use craft\helpers\Json;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use QD\commerce\webshipper\events\BeforeCreateOrderEvent;
use QD\commerce\webshipper\events\CustomOrderLinesEvent;
use QD\commerce\webshipper\Webshipper;
use craft\commerce\Plugin as CommercePlugin;
use yii\base\Component;

class Connector extends Component
{
	public $method = 'GET';
	public $client;
	public $response;
	public $options = [];

	const EVENT_BEFORE_CREATE_ORDER_ITEMS = 'beforeCreateOrderItems';
	const EVENT_BEFORE_CREATE_ORDER = 'beforeCreateOrder';

	// Public Methods
	// =========================================================================

	public function __construct()
	{
		$Webshipper = Webshipper::getInstance()->getSettings();
		$token = Craft::parseEnv($Webshipper->secretToken);
		$accountName = Craft::parseEnv($Webshipper->accountName);
		$useStaging = getenv('WEBSHIPPER_USESTAGING');

		if ($useStaging) {
			$this->client = new Client(array('base_uri' => "https://{$accountName}.api.staging.webshipper.io/v2/"));
		} else {
			$this->client = new Client(array('base_uri' => "https://{$accountName}.api.webshipper.io/v2/"));
		}


		$this->options['headers'] = [
			'Authorization' => 'Bearer ' . $token,
			'content-type'  => 'application/vnd.api+json',
			'Accept'        => 'application/vnd.api+json'
		];
	}

	/**
	 * Get webshipper order channels
	 *
	 * @return array|boolean
	 */
	public function getOrderChannels()
	{
		$request = $this->request('order_channels');

		if (!$request) {
			return false;
		}

		$decoded = $request->asArray();
		return $decoded['data'];
	}

	/**
	 * Get all shippingrates
	 *
	 * @return array|boolean
	 */
	public function getShippingRates()
	{
		$request = $this->request('shipping_rates?include=carrier');

		if (!$request) {
			return false;
		}

		$decoded = $request->asArray();
		return $decoded['data'];
	}


	/**
	 * Get droppoints
	 *
	 * @param string $zipCode
	 * @param string $country
	 * @param integer $shippingRateID
	 * @param string $address1
	 *
	 * @return array
	 */
	public function getDropPoints($zipCode, $country, $shippingRateID, $address1 = null)
	{
		$this->setMethod('POST');
		$this->options['json'] = [
			'data' => [
				'type'       => 'drop_point_locators',
				'attributes' => [
					'shipping_rate_id' => $shippingRateID,
					'delivery_address' => [
						'zip'          => $zipCode,
						'country_code' => $country,
						'address_1' => $address1
					]
				]
			]
		];

		$request = $this->request('drop_point_locators');

		if (!$request) {
			return [];
		}

		$decoded = $request->asArray();

		return $decoded['data']['attributes']['drop_points'];
	}

	/**
	 * Delete order from webshipper
	 *
	 * @param integer $webshipperId
	 *
	 * @return boolean
	 */
	public function deleteOrder($webshipperId)
	{
		$this->setMethod('DELETE');
		$request = $this->request('orders/' . $webshipperId);
		if (!$request) {
			return false;
		}
		return true;;
	}

	/**
	 * Create order in webshipper
	 *
	 * @param object $order
	 * @param string $customOrderId
	 *
	 * @return array
	 */
	public function createOrder($order, $customOrderId = null)
	{
		$settings = Webshipper::getInstance()->getSettings();
		$shippingAddress = null;
		$billingAddress = null;

		//Create delivery info
		$shippingObject = $order->getShippingAddress();
		if ($shippingObject) {
			$shippingAddress = [
				'address_1'    => $shippingObject->address1,
				'city'         => $shippingObject->city,
				'att_contact'  => $shippingObject->firstName . ' ' . $shippingObject->lastName,
				'country_code' => $shippingObject->country->iso,
				'phone'        => $shippingObject->phone,
				'zip'          => $shippingObject->zipCode,
				'state' 	   => $shippingObject->stateName,
				'email'        => $order->Email,
				'company_name' => $shippingObject->businessName
			];
		}

		//Create billing info
		$billingObject = $order->getBillingAddress();
		if ($billingObject) {
			$billingAddress = [
				'address_1'    => $billingObject->address1,
				'city'         => $billingObject->city,
				'att_contact'  => $billingObject->firstName . ' ' . $billingObject->lastName,
				'country_code' => $billingObject->country->iso,
				'phone'        => $billingObject->phone,
				'zip'          => $billingObject->zipCode,
				'state' 	   => $billingObject->stateName,
				'email'        => $order->Email,
				'company_name' => $billingObject->businessName
			];
		}

		if (!$billingAddress && $shippingAddress) {
			$billingAddress = $shippingAddress;
		}

		if (!$shippingAddress && $billingAddress) {
			$shippingAddress = $billingAddress;
		}

		//Create order items
		$orderItems = [];

		$orderItemsEvent = new CustomOrderLinesEvent([
			'order' => $order,
			'orderItems' => $orderItems,
			'useOnlyCustom' => false
		]);

		// Allow plugins to modify orderitems, and completely override the orders orderitems
		if ($this->hasEventHandlers(self::EVENT_BEFORE_CREATE_ORDER_ITEMS)) {
			$this->trigger(self::EVENT_BEFORE_CREATE_ORDER_ITEMS, $orderItemsEvent);
		}

		$orderItems = $orderItemsEvent->orderItems;

		if (!$orderItemsEvent->useOnlyCustom) {
			foreach ($order->getLineItems() as $orderItem) {
				$adjustments = $orderItem->getAdjustments();
				$amount = 0;
				foreach ($adjustments as $adjustment) {
					if ($adjustment->type === 'tax') {
						$amount = $adjustment->amount;
						break;
					}
				}

				$vatDecimal = $amount / (($orderItem->subtotal * $orderItem->qty) - $amount);
				$vatPercentage = $vatDecimal * 100;

				$orderItems[] = [
					"sku" => $orderItem->SKU,
					"description" => $orderItem->getDescription(),
					"quantity" => $orderItem->qty,
					"unit_price" => (($orderItem->total - $amount) / $orderItem->qty),
					"vat_percent" => $vatPercentage,
				];
			}
		}

		// Get shipping costs
		$shippingMethod = $order->getShippingMethod();
		$price = 0;

		foreach ($order->getAdjustments() as $adjustment) {
			if ($adjustment->type === 'shipping') {
				$price += $adjustment->amount;
			}
		}

		$originalShipping = [
			'price' => $price,
			'shipping_name' => isset($shippingMethod->name) ? $shippingMethod->name : '-',
			'shipping_code' => isset($shippingMethod->handle) ? $shippingMethod->handle : '-'
		];

		//Combine all shipping data
		$shippingData = [
			'data' => [
				'type'          => 'orders',
				'attributes'    => [
					'ext_ref'          => $customOrderId ? $customOrderId : $order->reference,
					'billing_address'  => $billingAddress,
					'delivery_address' => $shippingAddress,
					'order_lines'      => $orderItems,
					'currency'         => $order->paymentCurrency,
					'original_shipping' => $originalShipping
				],
				'relationships' => [
					'order_channel' => [
						'data' => [
							'id'   => $settings->channelId,
							'type' => 'order_channels'
						]
					],
					'shipping_rate' => [
						'data' => [
							'id'   => CommercePlugin::getInstance()->getShippingMethods()->getShippingMethodById($shippingMethod->id)->getWebshipperRateId(),
							'type' => 'shipping_rates'
						]
					]
				]
			]
		];

		//If order has a droppoint, attatch to shippingdata
		if ($order->droppointId) {

			if ($order->droppointSnapshot) {
				$dropPoint = Json::decode($order->droppointSnapshot);
			} else {
				$dropPoint = $this->getDropPointData($shippingObject->zipCode, $order->droppointId, $shippingObject->country->iso, CommercePlugin::getInstance()->getShippingMethods()->getShippingMethodById($shippingMethod->id)->getWebshipperRateId());
			}

			if ($dropPoint) {
				$dynamicAddress = array(
					'drop_point_id' => $order->droppointId,
					'name'          => $dropPoint['name'],
					'address_1'     => $dropPoint['address_1'],
					'zip'           => $dropPoint['zip'],
					'city'          => $dropPoint['city'],
					'country_code'  => $dropPoint['country_code']
				);
				//Insert into shipping data
				$shippingData['data']['attributes']['drop_point'] = $dynamicAddress;
			}
		}

		$createOrderEvent = new BeforeCreateOrderEvent([
			'order' => $order,
			'shippingData' => $shippingData
		]);

		// Allow plugins to modify orderitems, and completely override the orders orderitems
		if ($this->hasEventHandlers(self::EVENT_BEFORE_CREATE_ORDER)) {
			$this->trigger(self::EVENT_BEFORE_CREATE_ORDER, $createOrderEvent);
		}
		$this->setMethod('POST');
		$this->options['json'] = $createOrderEvent->shippingData;

		$request = $this->request('orders');

		if (!$request) {
			return false;
		}

		$decoded = $request->asArray();
		return $decoded;
	}

	public function getOrderByExtRef($extRef)
	{
		$this->setMethod('GET');
		$request = $this->request('orders?filter[ext_ref]=' . $extRef);

		if (!$request) {
			Log::info('Connector, getOrderByExtRef: Request failed');
			return false;
		}

		$decoded = $request->asArray();
		return [
			'data' => $decoded['data'][0]
		];
	}

	/**
	 * Get
	 *
	 * @param string $zipCode
	 * @param string $dropPointID
	 * @param string $country
	 * @param integer $shippingRateID
	 *
	 * @return array|boolean
	 */
	public function getDropPointData($zipCode, $dropPointID, $country, $shippingRateID = null)
	{
		$dropPoints = $this->getDropPoints($zipCode, $country, $shippingRateID);

		if (!$dropPoints) {
			return false;
		}

		$key = array_search($dropPointID, array_column($dropPoints, 'drop_point_id'));

		return $dropPoints[$key];
	}


	// Protected Methods
	// =========================================================================

	/**
	 * Set the curl method
	 *
	 * @param string $method
	 *
	 * @return $this
	 */
	protected function setMethod($method)
	{
		$this->method = \strtoupper($method);
		return $this;
	}

	/**
	 * Make request to specified uri
	 *
	 * @param string $uri
	 *
	 * @return $this|boolean
	 */
	protected function request($uri)
	{

		try {
			$request = $this->client->request($this->method, $uri, $this->options);
		} catch (BadResponseException $e) {
			$response = $e->getResponse();
			$responseBodyAsString = $response->getBody()->getContents();
			Log::error($responseBodyAsString);
			$this->response = $responseBodyAsString;
			return $this;
		}

		$this->response = $request->getBody()->getContents();

		return $this;
	}

	/**
	 * Return request as an array
	 *
	 * @return array
	 */
	protected function asArray()
	{
		return \json_decode($this->response, true);
	}

	/**
	 * Return request as object
	 *
	 * @return object
	 */
	protected function asObject()
	{
		return \json_decode($this->response);
	}
}
