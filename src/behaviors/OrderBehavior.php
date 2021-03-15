<?php

namespace QD\commerce\webshipper\behaviors;

use Craft;
use craft\commerce\elements\Order;
use QD\commerce\webshipper\Webshipper;
use yii\base\Behavior;

class OrderBehavior extends Behavior
{
	/**
	 * @var string|null
	 */
	public $webshipperId;

	/**
	 * @var string|null
	 */
	public $droppointId = NULL;
	public $droppointSnapshot;

	private $shipments;

	/**
	 * @var string Table name where extra info is stored
	 */
	const EXTRAS_TABLE = '{{%webshipper_orderinfo}}';


	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		return [
			Order::EVENT_BEFORE_SAVE => [$this, 'setOrderInfo'],
			Order::EVENT_AFTER_SAVE => [$this, 'saveOrderInfo'],
		];
	}

	/**
	 * Returns the shipments for the order, or null if the order is not saved.
	 *
	 * @return Shipment[]|null
	 */
	public function getShipments()
	{
		if (!$this->shipments) {
			/* @var Order $order */
			$order = $this->owner;

			// Ensure the order is saved.
			if (!$order->id) {
				return null;
			}

			$this->shipments = Webshipper::getInstance()->getShipments()->getShipmentsByOrder($order);
		}

		return $this->shipments;
	}

	/**
	 * Returns the link for the order on the webshipper page
	 *
	 * @return void
	 */
	public function getWebshipperLink()
	{
		$webshipperId = $this->owner->webshipperId;
		$accountName = Craft::parseEnv(Webshipper::getInstance()->getSettings()->accountName);

		return "https://{$accountName}.webshipper.io/orders/{$webshipperId}";
	}

	/**
	 * Returns the most recent shipment for the order,
	 * or null if the order is not saved or has no shipments.
	 *
	 * @return Shipment|null
	 */
	public function getLastShipment()
	{
		$shipments = $this->getShipments();

		return $shipments ? $shipments[0] : null;
	}

	public function setOrderInfo()
	{
		$request = Craft::$app->getRequest();
		if (!$request->getIsConsoleRequest() && \method_exists($request, 'getParam')) {
			// If droppointId is set, store it on the order
			$droppointId = $request->getParam('droppointId');
			$droppointSnapshot = $request->getParam('droppointSnapshot');
			$webshipperId = $request->getParam('webshipperId');

			if ($droppointId !== NULL) {
				$this->droppointId = $droppointId;
			}

			if ($webshipperId !== NULL) {
				$this->webshipperId = $webshipperId;
			}

			if ($droppointSnapshot !== NULL) {
				$this->droppointSnapshot = $droppointSnapshot;
			}
		}
	}

	/**
	 * Saves extra attributes that the Behavior injects.
	 *
	 * @return void
	 */
	public function saveOrderInfo()
	{
		$data = [];
		if ($this->webshipperId) {
			$data['webshipperId'] = $this->webshipperId;
		}

		if ($this->droppointId !== null) {
			$data['droppointId'] = $this->droppointId;
		}

		if ($this->droppointSnapshot !== null) {
			$data['droppointSnapshot'] = $this->droppointSnapshot;
		}

		if ($data) {
			Craft::$app->getDb()->createCommand()
				->upsert(self::EXTRAS_TABLE, [
					'id' => $this->owner->id,
				], $data, [], false)
				->execute();
		}
	}
}
