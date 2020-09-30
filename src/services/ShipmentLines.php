<?php

namespace QD\commerce\webshipper\services;

use Craft;
use craft\commerce\elements\Order;
use craft\db\Query;
use craft\base\Component;
use craft\commerce\Plugin as Commerce;
use craft\commerce\models\LineItem;
use QD\commerce\webshipper\models\Shipment;
use QD\commerce\webshipper\models\ShipmentLine;
use QD\commerce\webshipper\records\ShipmentLine as RecordsShipmentLine;
use Throwable;
use yii\base\Exception;

class ShipmentLines extends Component
{

	// Public Methods
	// =========================================================================

	/**
	 * Gets a shipment line by its ID.
	 *
	 * @param int $shipmentLineId
	 * @return ShipmentLine|null
	 */
	public function getShipmentLineById($shipmentLineId)
	{
		$result = $this->createShipmentLineQuery()
			->where(['id' => $shipmentLineId])
			->one();

		return $result ? new ShipmentLine($result) : null;
	}

	/**
	 * Gets all shipment lines by the shipping ID.
	 *
	 * @param $shipmentId
	 * @return ShipmentLine[]
	 */
	public function getShipmentLinesByShipmentId($shipmentId)
	{
		$results = $this->createShipmentLineQuery()
			->where(['shipmentId' => $shipmentId])
			->all();

		$lines = [];
		foreach ($results as $result) {
			$lines[] = new ShipmentLine($result);
		}

		return $lines;
	}

	/**
	 * Gets all shipment lines by the shipping.
	 *
	 * @param Shipment $shipment
	 * @return ShipmentLine[]
	 */
	public function getShipmentLinesByShipment(Shipment $shipment)
	{
		return $this->getShipmentLinesByShipmentId($shipment->id);
	}

	/**
	 * Gets all shipment lines by the line item id.
	 *
	 * @param int $lineItemId
	 * @return ShipmentLine[]
	 */
	public function getShipmentLinesByLineItemId($lineItemId)
	{
		$results = $this->createShipmentLineQuery()
			->where(['lineItemId' => $lineItemId])
			->all();

		$lines = [];
		foreach ($results as $result) {
			$lines[] = new ShipmentLine($result);
		}

		return $lines;
	}

	/**
	 * Gets all shipment lines by the line item.
	 *
	 * @param LineItem $lineItem
	 * @return ShipmentLine[]
	 */
	public function getShipmentLinesByLineItem($lineItem)
	{
		return $this->getShipmentLinesByLineItemId($lineItem->id);
	}

	/**
	 * Gets the shippable quantity for a line item.
	 *
	 * @param LineItem $lineItem
	 * @param Boolean $limitToStock
	 * @return int
	 */
	public function getShippableQty(LineItem $lineItem, $limitToStock = false): int
	{
		$shipmentItems = $this->getShipmentLinesByLineItem($lineItem);

		$quantity = $lineItem->qty;
		foreach ($shipmentItems as $shipmentItem) {
			$quantity -= $shipmentItem->shippedQty;
		}

		return $quantity;
	}

	/**
	 * Gets unshipped line items for an order.
	 *
	 * @param Order $order
	 * @return LineItem[]
	 */
	public function getUnshippedLineItems($order)
	{
		if (!$order) {
			return [];
		}

		$lineItems = [];

		foreach (Commerce::getInstance()->getLineItems()->getAllLineItemsByOrderId($order->id) as $lineItem) {
			if ($this->getShippableQty($lineItem) > 0) {
				$lineItems[] = $lineItem;
			}
		}

		return $lineItems;
	}

	/**
	 * Create a shipment line.
	 *
	 * @param LineItem $lineItem The line item.
	 *
	 * @param int $qty
	 * @return ShipmentLine
	 */
	public function createShipmentLine(LineItem $lineItem, int $qty): ShipmentLine
	{
		$shipmentLine = new ShipmentLine();
		$shipmentLine->lineItemId = $lineItem->id;
		$shipmentLine->shippedQty = $qty;

		return $shipmentLine;
	}

	/**
	 * Save a shipping line.
	 *
	 * @param ShipmentLine $shipmentLine The shipping line to save.
	 * @param bool $runValidation Whether the shipping line should be validated.
	 * @return bool
	 * @throws Throwable
	 */
	public function saveShipmentLine(ShipmentLine $shipmentLine, bool $runValidation = true): bool
	{
		$isNewShipmentLine = !$shipmentLine->id;

		if ($isNewShipmentLine) {
			$shipmentLineRecord = new RecordsShipmentLine();
		} else {
			$shipmentLineRecord = RecordsShipmentLine::findOne($shipmentLine->id);

			if (!$shipmentLineRecord) {
				throw new Exception(Craft::t(
					'auctions',
					'No shipping lines exists with the ID “{id}”',
					['id' => $shipmentLineRecord->id]
				));
			}
		}

		$shipmentLineRecord->shipmentId = $shipmentLine->shipmentId;
		$shipmentLineRecord->lineItemId = $shipmentLine->lineItemId;
		$shipmentLineRecord->shippedQty = $shipmentLine->shippedQty;

		if (!$shipmentLine->hasErrors()) {
			$db = Craft::$app->getDb();
			$transaction = $db->beginTransaction();

			try {
				$success = $shipmentLineRecord->save(false);

				if ($success) {
					if ($isNewShipmentLine) {
						$shipmentLine->id = $shipmentLineRecord->id;
					}

					$transaction->commit();
				}
			} catch (Throwable $e) {
				$transaction->rollBack();
				throw $e;
			}

			return $success;
		}

		return false;
	}


	// Private methods
	// =========================================================================

	/**
	 * Returns a Query object prepped for retrieving shippings.
	 *
	 * @return Query The query object.
	 */
	private function createShipmentLineQuery(): Query
	{
		return (new Query())
			->select([
				'id',
				'shipmentId',
				'lineItemId',
				'shippedQty',
				'uid',
			])
			->from(['{{%webshipper_shipment_lines}}']);
	}
}
