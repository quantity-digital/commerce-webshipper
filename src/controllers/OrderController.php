<?php

namespace QD\commerce\webshipper\controllers;

use Craft;
use craft\web\Controller;
use QD\commerce\webshipper\queue\ReSyncOrder;

class OrderController extends Controller
{

	public function actionResync()
	{
		$orderId = Craft::$app->getRequest()->getBodyParam('orderId');
		Craft::$app->getQueue()->delay(10)->push(new ReSyncOrder(
			[
				'orderId' => $orderId,
			]
		));
	}
}
