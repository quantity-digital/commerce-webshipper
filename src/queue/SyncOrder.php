<?php

namespace QD\commerce\webshipper\queue;

use Craft;
use craft\commerce\elements\Order;
use craft\queue\BaseJob;
use QD\commerce\webshipper\helpers\Connector;
use yii\queue\RetryableJobInterface;

class SyncOrder extends BaseJob implements RetryableJobInterface
{

	/**
	 * @var int Order ID
	 */
	public $orderId;

	public function canRetry($attempt, $error)
	{
		$attempts = 5;
		return $attempt < $attempts;
	}

	public function getTtr()
	{
		return 3600;
	}

	public function execute($queue)
	{
		$order = Order::find()->id($this->orderId)->one();
		$this->setProgress($queue, 0.05);

		if($order){

			$webshipper = new Connector();
			$webshipperOrder = $webshipper->createOrder($order);
			$this->setProgress($queue, 0.9);

			if(!$webshipperOrder){
				$this->reAddToQueue();
			}

			$this->setProgress($queue, 0.95);

			if($webshipperOrder){
				$order->webshipperId = $webshipperOrder['data']['id'];
				Craft::$app->getElements()->saveElement($order);
			}

			$this->setProgress($queue, 1);

		}
	}

	// Protected Methods
	// =========================================================================

	protected function defaultDescription(): string
	{
		return 'Syncing orders to webshipper';
	}

	protected function reAddToQueue(){
		Craft::$app->getQueue()->delay(300)->push(new SyncOrder(
				[
					'orderId' => $this->orderId,
				]
			));
	}
}
