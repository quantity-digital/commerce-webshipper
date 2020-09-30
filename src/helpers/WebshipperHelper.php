<?php

namespace QD\commerce\webshipper\helpers;

use QD\commerce\webshipper\Webshipper;
use yii\web\ForbiddenHttpException;

class WebshipperHelper
{
	// Public Methods
	// =========================================================================

	public static function getValueByKey(array $array, $key, $default = null)
	{
		if (is_null($key)) {
			return $array;
		}

		if (isset($array[$key])) {
			return $array[$key];
		}

		foreach (explode('.', $key) as $segment) {
			if (!is_array($array) || !array_key_exists($segment, $array)) {
				return $default;
			}

			$array = $array[$segment];
		}

		return $array;
	}

	public static function verifyWebhook($request)
	{
		$headers = $request->getHeaders();

		//If X-Webshipper-Hmac-Sha256 is not set, throw 403 error on the request
		if (!isset($headers['X-Webshipper-Hmac-Sha256'])) {
			throw new ForbiddenHttpException('Request is not allowed.');
		}

		//Get hookkey from request
        $webshipperKey = $headers['X-Webshipper-Hmac-Sha256'];
        $webshipperContent = $request->getRawBody();
        $settings = Webshipper::getInstance()->getSettings();
        $webhookSecret = $settings->webhookSecret;

        $hmacHash = hash_hmac('SHA256', $webshipperContent, $webhookSecret, true);
        $base64Encoded = base64_encode($hmacHash);

        //Compare to key stored in settings
        if ($webshipperKey != $base64Encoded) {
            throw new ForbiddenHttpException('Request is not allowed.');
        }
	}
}
