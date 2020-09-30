<?php

namespace QD\commerce\webshipper\controllers;

use craft\web\Controller;

use craft\commerce\Plugin as Commerce;
use QD\commerce\webshipper\helpers\Connector;
use QD\commerce\webshipper\Webshipper;

class PluginController extends Controller
{
	// Public Methods
	// =========================================================================

	public function actionSettings()
	{
		$settings = Webshipper::$plugin->getSettings();
		$webshipper = new Connector();

		//Get commerce order statuses
		$statuses = [
			'' => '---'
		];
		foreach (Commerce::getInstance()->getOrderStatuses()->getAllOrderStatuses() as $status) {
			$statuses[$status->handle] = $status->name;
		}

		//Get webhipper order channels
		$channels = [
			'' => '---'
		];
		if ($settings->secretToken && $settings->accountName) {
			$requestChannels = $webshipper->getOrderChannels();
			if ($requestChannels) {
				foreach ($requestChannels as $channel) {
					$channels[$channel['id']] = $channel['attributes']['channel_label'];
				}
			}
		}

		return $this->renderTemplate('commerce-webshipper/settings', array(
			'settings' => $settings,
			'statuses' => $statuses,
			'channels' => $channels,
		));
	}
}
