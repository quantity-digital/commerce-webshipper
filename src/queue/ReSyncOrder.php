<?php

namespace QD\commerce\webshipper\queue;

use Craft;
use craft\commerce\elements\Order;
use craft\queue\BaseJob;
use QD\commerce\webshipper\helpers\Connector;
use QD\commerce\webshipper\helpers\Log;
use QD\commerce\webshipper\queue\SyncOrder;

class ReSyncOrder extends BaseJob
{
	/**
	 * @var int Order ID
	 */
	public $orderId;

	public function getTtr()
	{
		return 300;
	}

	public function execute($queue)
	{
		$order = Order::find()->id($this->orderId)->one();
		$webshipperId = $order->webshipperId;

		// If no webshipperid, try to create it
		if (!$order->webshipperId) {
			Craft::$app->getQueue()->delay(10)->push(new SyncOrder(
				[
					'orderId' => $order->id,
				]
			));
			return;
		}

		$webshipper = new Connector();
		$request = $webshipper->updateOrder($order, $webshipperId);

		if (!$request) {
			Log::info('ReSyncOrder: Response returned false');
			$this->setProgress($queue, 1, 'Failed to update webshipper order, readding to que');
			$this->reAddToQueue();
			return;
		}
	}

	// Protected Methods
	// =========================================================================

	protected function defaultDescription(): string
	{
		return 'Resync order to webshipper';
	}

	protected function reAddToQueue()
	{
		Craft::$app->getQueue()->delay(300)->push(new ReSyncOrder(
			[
				'orderId' => $this->orderId,
			]
		));
	}
}
