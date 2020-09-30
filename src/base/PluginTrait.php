<?php

namespace QD\commerce\webshipper\base;


use QD\commerce\webshipper\services\Orders;
use QD\commerce\webshipper\services\ShipmentLines;
use QD\commerce\webshipper\services\Shipments;

trait PluginTrait
{
	// Static Properties
	// =========================================================================

	public static $plugin;


	// Public Methods
	// =========================================================================

	public function getShipments()
	{
		return $this->get('shipments');
	}

	public function getShipmentLines()
	{
		return $this->get('shipmentLines');
	}

	public function getOrders(){
		return $this->get('orders');
	}

	// Private Methods
	// =========================================================================

	private function setPluginComponents()
	{
		$this->setComponents([
			'shipments' => Shipments::class,
			'shipmentLines' => ShipmentLines::class,
			'orders' => Orders::class
		]);
	}
}
