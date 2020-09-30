<?php
namespace QD\commerce\webshipper\assetsbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class OrderAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
		$this->sourcePath = "@QD/commerce/webshipper/resources";

        $this->depends = [
            CpAsset::class,
        ];

		$this->js = [
            'js/order.js',
        ];

		$this->css = [
            'css/order.css',
        ];

        parent::init();
    }
}
