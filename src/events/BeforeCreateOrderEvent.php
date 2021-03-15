<?php

namespace QD\commerce\webshipper\events;

use yii\base\Event;

class BeforeCreateOrderEvent extends Event
{
	// Properties
	// =========================================================================

	public $order;
	public $shippingData;
}
