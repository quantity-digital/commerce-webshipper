<?php
namespace QD\commerce\webshipper\assetsbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class WebshipperAsset extends AssetBundle
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
            'js/admin.js',
        ];

        parent::init();
    }
}
