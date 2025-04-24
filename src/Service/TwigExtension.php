<?php

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidSolutionCatalysts\PayPal\Model\HateoasLink;
use OxidSolutionCatalysts\PayPal\Model\HateoasLinks;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtension  extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('getFileMTime', [$this, 'getFileMTime']),
        ];
    }
    public function getFunctions()
    {
        return [
            new TwigFunction('debug', [$this, 'debug'], ['is_safe' => ['html']]),
            new TwigFunction('getControllerName', [$this, 'getControllerName']),
        ];
    }

    public function debug($variable)
    {
        return '<pre>' . print_r($variable, true) . '</pre>';
    }
    public function getFileMTime($value)
    {
        return is_readable($value ) ? filemtime($value) : 0;
    }

    public function getClass($object): string
    {
        return get_class($object);
    }
    public function getControllerName($object): ?string
    {
        $parts = explode('\\', get_class($object));
        return in_array('Controller', $parts) ? array_pop($parts) : null;
    }
}
