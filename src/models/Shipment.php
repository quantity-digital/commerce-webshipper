<?php

namespace QD\commerce\webshipper\models;

use craft\base\Model;
use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use QD\commerce\webshipper\Webshipper;

class Shipment extends Model
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
	public $orderId;

	/**
	 * @var int
	 */
	public $webshipperId;

	/**
	 * @var int
	 */
	public $packageCount;

	/**
	 * @var string
	 */
	public $trackingLink;

	/**
	 * @var string
	 */
	public $uid;

	/**
	 * @var DateTime|null
	 */
	public $dateCreated;

	/**
	 * @var DateTime|null
	 */
	public $dateUpdated;


	// Private Properties
	// =========================================================================

	/**
	 * @var Order
	 */
	private $order;

	/**
	 * @var ShipmentLine[]
	 */
	private $shipmentLines;


	// Public Methods
	// =========================================================================

	/**
	 * Gets the order.
	 *
	 * @return Order|null
	 */
	public function getOrder()
	{
		if (!$this->order) {
			$this->order = Commerce::getInstance()->getOrders()->getOrderById($this->orderId);
		}

		return $this->order;
	}

	/**
	 * Sets the order.
	 *
	 * @param Order $order
	 */
	public function setOrder(Order $order)
	{
		$this->orderId = $order->id;
		$this->order = $order;
	}

	/**
	 * Gets all shipping lines for this shipping.
	 *
	 * @return ShipmentLine[]
	 */
	public function getShipmentLines()
	{
		$this->fetchShipmentLines();
		return $this->shipmentLines;
	}

	/**
	 * Sets the shipping lines for this shipping.
	 *
	 * @param ShipmentLine[] $shipmentLines
	 */
	public function setShipmentLines($shipmentLines)
	{
		$this->shipmentLines = $shipmentLines;
	}

	/**
	 * Adds a shipping line to this shipping.
	 *
	 * @param ShipmentLine $shipmentLines
	 */
	public function addShipmentLine($shipmentLine)
	{
		$this->fetchShipmentLines();
		$this->shipmentLines[] = $shipmentLine;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = parent::rules();

		$rules[] = ['orderId', 'required'];

		return $rules;
	}


	// Private Methods
	// =========================================================================

	private function fetchShipmentLines()
	{
		if (!$this->shipmentLines) {
			$this->shipmentLines = Webshipper::getInstance()->getShipmentLines()->getShipmentLinesByShipment($this);
		}
	}
}
