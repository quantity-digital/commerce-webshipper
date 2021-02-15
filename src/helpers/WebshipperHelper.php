<?php

namespace QD\commerce\webshipper\helpers;

use Craft;
use QD\commerce\webshipper\Webshipper;
use yii\web\ForbiddenHttpException;

class WebshipperHelper
{
	// Public Methods
	// =========================================================================

	public static function verifyWebhook($request)
	{
		$headers = $request->getHeaders();

		//If X-Webshipper-Hmac-Sha256 is not set, throw 403 error on the request
		if (!isset($headers['X-Webshipper-Hmac-Sha256'])) {
			throw new ForbiddenHttpException('Request is not allowed - Secret missing');
		}

		//Get hookkey from request
		$webshipperKey = $headers['X-Webshipper-Hmac-Sha256'];
		$webshipperContent = $request->getRawBody();
		$settings = Webshipper::getInstance()->getSettings();
		$webhookSecret = Craft::parseEnv($settings->webhookSecret);

		$hmacHash = hash_hmac('SHA256', $webshipperContent, $webhookSecret, true);
		$base64Encoded = base64_encode($hmacHash);

		//Compare to key stored in settings
		if ($webshipperKey != $base64Encoded) {
			throw new ForbiddenHttpException('Request is not allowed - Secret not valid');
		}
	}
}
