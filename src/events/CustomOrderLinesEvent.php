<?php

namespace QD\commerce\webshipper\events;

use yii\base\Event;

class CustomOrderLinesEvent extends Event
{
	// Properties
	// =========================================================================

	public $order;
	public $orderItems;
	public $useOnlyCustom;
}
