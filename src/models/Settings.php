<?php

namespace QD\commerce\webshipper\models;

use craft\base\Model;

class Settings extends Model
{
	public $pluginName = 'Webshipper';
    public $hasCpSection = false;
    public $enableCaching = true;
    public $displayDebug = false;
    public $displayErrors = false;
	public $fulfilledStatus;
	public $partiallyFulfilledStatus;
	public $accountName;
	public $secretToken;
	public $webhookSecret;
	public $channelId;
}
