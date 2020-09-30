<?php

namespace QD\commerce\webshipper\services;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use QD\commerce\webshipper\queue\DeleteOrder;
use QD\commerce\webshipper\queue\SyncOrder;

class Orders extends Component
{
	// Public Methods
	// =========================================================================

	public function addSyncOrderQue($event)
	{
		$order = $event->sender;

		//Only add to queru if no webshipperID
		if (!$order->webshipperId) {
			Craft::$app->getQueue()->delay(10)->push(new SyncOrder(
				[
					'orderId' => $order->id,
				]
			));
		}
	}

	public function addDeleteOrderQue($event){

		// exit('test');

		if(get_class($event->element) != Order::class){
			return true;
		}

		$order = $event->element;
		Craft::$app->getQueue()->delay(10)->push(new DeleteOrder(
			[
				'webshipperId' => $order->webshipperId
			]
		));
	}
}
