<?php

namespace QD\commerce\webshipper\queue;

use Craft;
use craft\commerce\elements\Order;
use craft\queue\BaseJob;
use QD\commerce\webshipper\helpers\Connector;
use yii\queue\RetryableJobInterface;

class DeleteOrder extends BaseJob implements RetryableJobInterface
{

	/**
	 * @var int Order ID
	 */
	public $webshipperId;

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
		$this->setProgress($queue, 0.05);
		$webshipper = new Connector();
		$request = $webshipper->deleteOrder($this->webshipperId);

		if(!$request){
			$this->reAddToQue();
		}

		$this->setProgress($queue, 1);
	}

	// Protected Methods
	// =========================================================================

	protected function defaultDescription(): string
	{
		return 'Delete order in webshipper';
	}

	protected function reAddToQue()
	{
		Craft::$app->getQueue()->delay(300)->push(new DeleteOrder(
			[
				'webshipperId' => $this->webshipperId,
			]
		));
	}
}
