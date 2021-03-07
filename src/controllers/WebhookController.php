<?php

namespace QD\commerce\webshipper\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\web\Controller;
use QD\commerce\webshipper\helpers\WebshipperHelper;
use QD\commerce\webshipper\Webshipper;

class WebhookController extends Controller
{
	/**
	 * @inheritdoc
	 */
	public $allowAnonymous = [
		'shipment-created' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
		'order-deleted' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
	];

	/**
	 * @inheritdoc
	 */
	public $enableCsrfValidation = false;

	public function actionShipmentCreated()
	{
		$request = Craft::$app->getRequest();

		//Only allow webshipper webhook to call this action
		WebshipperHelper::verifyWebhook($request);

		$body = json_decode($request->getRawBody());
		$data = $body->data;

		$order = Order::find()->reference($data->attributes->reference)->one();

		$shipmentService = Webshipper::getInstance()->getShipments();
		$shipmentLineService = Webshipper::getInstance()->getShipmentLines();
		$shipment = $shipmentService->createShipment($order->id);

		//Set tracking no
		$shipment->webshipperId  = $data->id ? $data->id : 0;
		$shipment->trackingLink = isset($data->attributes->tracking_links[0]) ? $data->attributes->tracking_links[0]->url : '';
		$packagesCount = 0;

		//Add shipment lines
		foreach ($data->attributes->packages as $package) {
			foreach ($package->order_lines as $orderLine) {
				$lineItem = array_filter(
					$order->getLineItems(),
					function ($item) use (&$orderLine) {
						return $item->snapshot['sku'] == $orderLine->sku;
					}
				);
				$lineItem = \reset($lineItem);
				if (!$lineItem) {
					continue;
				}

				$shipmentLine = $shipmentLineService->createShipmentLine($lineItem, $orderLine->quantity);
				$shipment->addShipmentLine($shipmentLine);
			}
			$packagesCount++;
		}

		$shipment->packageCount = $packagesCount;

		//Save shipment
		$shipmentService->saveShipment($shipment);
	}

	public function actionOrderDeleted()
	{
		$request = Craft::$app->getRequest();

		//Only allow webshipper webhook to call this action
		WebshipperHelper::verifyWebhook($request);

		$body = json_decode($request->getRawBody());
		$data = $body->data;

		// Find order in commerce
		$order = Order::find()->reference($data->attributes->reference)->one();

		// Delete order in commerce if it exists
		if ($order) {
			Craft::$app->elements->deleteElement($order);
		}
	}
}
