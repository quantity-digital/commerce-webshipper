<?php

namespace QD\commerce\webshipper\queue;

use Craft;
use craft\commerce\elements\Order;
use craft\queue\BaseJob;
use QD\commerce\webshipper\helpers\Connector;
use yii\queue\RetryableJobInterface;
use QD\commerce\webshipper\helpers\Log;

class SyncOrder extends BaseJob implements RetryableJobInterface
{

	/**
	 * @var int Order ID
	 */
	public $orderId;

	public function canRetry($attempt, $error)
	{
		$attempts = 50;
		return $attempt < $attempts;
	}

	public function getTtr()
	{
		return 300;
	}

	public function execute($queue)
	{
		$this->setProgress($queue, 0.05, 'Finding order');
		$order = Order::find()->id($this->orderId)->one();

		if ($order) {

			// Create order in webshipper
			$this->setProgress($queue, 0.3, 'Creating order in webshipper');
			$webshipper = new Connector();
			$response = $webshipper->createOrder($order);

			// Response failed, readd to queu
			if (!$response) {
				Log::info('SyncOrder: Response returned false');
				$this->setProgress($queue, 1, 'Failed to create webshipper order, readding to que');
				$this->reAddToQueue();
				return;
			}

			// Check if response contains any errors
			$this->setProgress($queue, 0.4, 'Checking for errors');
			if (isset($response['errors'])) {
				$this->setProgress($queue, 0.5, 'Checking errors');

				if ($response['errors'][0]['code'] === '100') {
					$this->setProgress($queue, 0.6, 'Order was already created, fetching from webshipper');
					$response = $webshipper->getOrderByExtRef($order->reference);

					if (!$response) {
						Log::info('SyncOrder: getOrderByExtRef() returned false');
						$this->setProgress($queue, 1);
						$this->reAddToQueue();
						return;
					}
				} else {
					$this->setProgress($queue, 1);
					$this->reAddToQueue();
					return;
				}
			}
			$this->setProgress($queue, 0.7, 'Error checking done, continuing');

			//Ensure we have the webshipper Id
			$this->setProgress($queue, 0.8, 'Checking for webshipperId');
			if (!isset($response['data']['id'])) {
				Log::info('SyncOrder: Response was missing id');
				$this->setProgress($queue, 1, 'Webshipper id missing, readding to que');
				$this->reAddToQueue();
				return;
			}

			//Store webshipper Id in database
			$this->setProgress($queue, 0.95, 'Saving webshipper id to database');
			$order->webshipperId = (int)$response['data']['id'];

			try {
				Craft::$app->getElements()->saveElement($order);
			} catch (\Throwable $th) {
				Log::error('Failed to store webshipper ID');
				Log::error("'Failed to store webshipper ID'" . implode(', ', $order->getFirstErrors()));
				$this->setProgress($queue, 1);
				$this->reAddToQueue();
				return;
			}

			$this->reAddToQueue();
			return;
		}
	}

	// Protected Methods
	// =========================================================================

	protected function defaultDescription(): string
	{
		return 'Syncing orders to webshipper';
	}

	protected function reAddToQueue()
	{
		Craft::$app->getQueue()->delay(300)->push(new SyncOrder(
			[
				'orderId' => $this->orderId,
			]
		));
	}
}
