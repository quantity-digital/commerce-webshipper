<?php

namespace QD\commerce\webshipper\controllers;

use craft\web\Controller;
use QD\commerce\webshipper\helpers\Connector;
use craft\commerce\Plugin as CommercePlugin;

class FrontendController extends Controller
{
	/**
	 * @inheritdoc
	 */
	public $allowAnonymous = [
		'droppoint-locator' => self::ALLOW_ANONYMOUS_LIVE,
	];

	public function actionDroppointLocator()
	{
		$webshipper = new Connector();
		$order = CommercePlugin::getInstance()->getCarts()->getCart();
		$shippingObject = $order->getShippingAddress();

		//If no shipping address is set, return empty array
		if (!$shippingObject) {
			return json_encode([]);
		}

		return $this->asJson($webshipper->getDropPoints($shippingObject->zipCode, $shippingObject->country->iso, CommercePlugin::getInstance()->getShippingMethods()->getShippingMethodById($order->getShippingMethod()->id)->getWebshipperRateId(), $shippingObject->address1));
	}
}
