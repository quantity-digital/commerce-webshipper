<?php
namespace QD\commerce\webshipper\variables;

use QD\commerce\webshipper\Webshipper;

class WebshipperVariable
{
    public function getPluginName()
    {
        return Webshipper::$plugin->getPluginName();
    }
}
