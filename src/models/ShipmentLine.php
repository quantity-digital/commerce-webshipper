<?php

namespace QD\commerce\webshipper\models;

use Craft;
use craft\base\Model;
use craft\commerce\Plugin as Commerce;
use craft\commerce\models\LineItem;
use craft\helpers\Html;
use craft\helpers\Json;
use QD\commerce\webshipper\Webshipper;

class ShipmentLine extends Model
{
	// Public Properties
	// =========================================================================

	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var int
	 */
	public $shipmentId;

	/**
	 * @var string
	 */
	public $lineItemId;

	/**
	 * @var int
	 */
	public $shippedQty;

	/**
	 * @var string
	 */
	public $uid;


	// Private Properties
	// =========================================================================

	/**
	 * @var Shipment
	 */
	private $shipment;

	/**
	 * @var LineItem
	 */
	private $lineItem;


	// Public Methods
	// =========================================================================

	/**
	 * Returns the purchasable name, or if there's no purchasable, the snapshot description.
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		$lineItem = $this->getLineItem();

		$purchasable = $lineItem->getPurchasable();
		if ($purchasable) {
			return $purchasable->getDescription();
		}

		$snapshot = Json::decodeIfJson($lineItem->snapshot);
		return Html::decode($snapshot['description']);
	}

	/**
	 * Gets the shipment.
	 *
	 * @return Shipment|null
	 */
	public function getShipment()
	{
		if (!$this->shipment) {
			$this->shipment = Webshipper::getInstance()->getShipments()->getShipmentById($this->shipmentId);
		}

		return $this->shipment;
	}

	/**
	 * Sets the shipment.
	 *
	 * @param Shipment $shipment
	 */
	public function setShipment(Shipment $shipment)
	{
		$this->shipmentId = $shipment->id;
		$this->shipment = $shipment;
	}

	/**
	 * Gets the line item.
	 *
	 * @return LineItem|null
	 */
	public function getLineItem()
	{
		if (!$this->lineItem) {
			$this->lineItem = Commerce::getInstance()->getLineItems()->getLineItemById($this->lineItemId);
		}

		return $this->lineItem;
	}

	/**
	 * Sets the shipment.
	 *
	 * @param LineItem $lineItem
	 */
	public function setLineItem(LineItem $lineItem)
	{
		$this->lineItemId = $lineItem->id;
		$this->lineItem = $lineItem;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['shipmentId', 'lineItemId'], 'required'],
		];
	}
}
