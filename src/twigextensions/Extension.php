<?php
namespace QD\commerce\webshipper\twigextensions;

use QD\commerce\webshipper\helpers\WebshipperHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return 'getValueByKey';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get', [$this, 'getValueByKey']),
        ];
    }

    public function getValueByKey($array, $value)
    {
        if (is_array($array)) {
            return WebshipperHelper::getValueByKey($array, $value);
        }
    }
}
