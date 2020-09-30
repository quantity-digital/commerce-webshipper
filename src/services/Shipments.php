<?php

namespace QD\commerce\webshipper\services;

use Craft;
use craft\commerce\Plugin as Commerce;
use craft\db\Query;
use craft\base\Component;
use craft\commerce\elements\Order;
use QD\commerce\webshipper\models\Shipment;
use QD\commerce\webshipper\records\Shipment as RecordsShipment;
use QD\commerce\webshipper\Webshipper;
use yii\base\Exception;
use Throwable;

class Shipments extends Component
{

	// Public Methods
	// =========================================================================

	/**
	 * Gets a shipment by its ID.
	 *
	 * @param int $shipmentId
	 * @return Shipment|null
	 */
	public function getShipmentById($shipmentId)
	{
		$result = $this->createShipmentQuery()
			->where(['id' => $shipmentId])
			->one();

		return $result ? new Shipment($result) : null;
	}

	/**
	 * Gets shipments by the order ID.
	 *
	 * @param int $orderId
	 * @return Shipment[]|null
	 */
	public function getShipmentsByOrderId($orderId)
	{
		$results = $this->createShipmentQuery()
			->where(['orderId' => $orderId])
			->all();

		$shipments = [];

		foreach ($results as $result) {
			$shipments[] = new Shipment($result);
		}

		return $shipments;
	}

	/**
	 * Gets shipments by the order.
	 *
	 * @param Order $order
	 * @return Shipment[]|null
	 */
	public function getShipmentsByOrder(Order $order)
	{
		return $this->getShipmentsByOrderId($order->id);
	}

	/**
	 * Create a shipment.
	 *
	 * @param int $orderId The ID of the order the shipment represents
	 *
	 * @return Shipment
	 */
	public function createShipment(int $orderId): Shipment
	{
		$shipment = new Shipment();
		$shipment->orderId = $orderId;

		return $shipment;
	}

	/**
	 * Save a shipment.
	 *
	 * @param Shipment $shipment The shipment to save.
	 * @param bool $runValidation Whether the shipment should be validated.
	 * @return bool
	 * @throws Exception
	 */
	public function saveShipment(Shipment $shipment, bool $runValidation = true): bool
	{
		$isNewShipment = !$shipment->id;

		if ($isNewShipment) {
			$shipmentRecord = new RecordsShipment();
		} else {
			$shipmentRecord = RecordsShipment::findOne($shipment->id);

			if (!$shipmentRecord) {
				throw new Exception(Craft::t(
					'auctions',
					'No shipments exists with the ID “{id}”',
					['id' => $shipmentRecord->id]
				));
			}
		}

		$shipmentRecord->orderId = $shipment->orderId;
		$shipmentRecord->trackingLink = $shipment->trackingLink;
		$shipmentRecord->webshipperId = $shipment->webshipperId;
		$shipmentRecord->packageCount = $shipment->packageCount;

		if (!$shipment->hasErrors()) {
			$db = Craft::$app->getDb();
			$transaction = $db->beginTransaction();

			try {
				$success = $shipmentRecord->save(false);

				if ($success) {
					if ($isNewShipment) {
						$shipment->id = $shipmentRecord->id;
					}

					$transaction->commit();
				}
			} catch (Throwable $e) {
				$transaction->rollBack();
				throw $e;
			}

			if ($success) {
				foreach ($shipment->getShipmentLines() as $shipmentLine) {
					if (!!$shipmentLine->id || $shipmentLine->shippedQty > 0) {
						$shipmentLine->shipmentId = $shipment->id;
						Webshipper::getInstance()->getShipmentLines()->saveShipmentLine($shipmentLine);
					}
				}

				// Update order status.
				$order = $shipment->getOrder();
				$settings = Webshipper::getInstance()->getSettings();
				$unfulfilled = Webshipper::getInstance()->getShipmentLines()->getUnshippedLineItems($order);
				$unfulfilledStatus = Commerce::getInstance()->getOrderStatuses()->getOrderStatusByHandle($settings->partiallyFulfilledStatus);
				$fulfilledStatus = Commerce::getInstance()->getOrderStatuses()->getOrderStatusByHandle($settings->fulfilledStatus);

				if(count($unfulfilled) && $unfulfilledStatus){
					$order->orderStatusId = $unfulfilledStatus->id;
					Craft::$app->getElements()->saveElement($order);
				}

				if(!count($unfulfilled) && $fulfilledStatus){
					$order->orderStatusId = $fulfilledStatus->id;
					Craft::$app->getElements()->saveElement($order);
				}

			}

			return $success;
		}

		return false;
	}

	/**
	 * Deletes a shipment by it's ID.
	 *
	 * @param int $shipmentId
	 * @return bool
	 */
	public function deleteShipmentById(int $shipmentId): bool
	{
		$result = (bool)RecordsShipment::deleteAll(['id' => $shipmentId]);

		return $result;
	}


	// Private methods
	// =========================================================================

	/**
	 * Returns a Query object prepped for retrieving shipments.
	 *
	 * @return Query The query object.
	 */
	private function createShipmentQuery(): Query
	{
		return (new Query())
			->select([
				'id',
				'orderId',
				'trackingLink',
				'packageCount',
				'dateCreated',
				'dateUpdated',
				'webshipperId',
				'uid',
			])
			->from(['{{%webshipper_shipments}}']);
	}
}
