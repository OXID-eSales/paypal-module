<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Traits;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use PHPUnit\Framework\MockObject\MockObject;

trait ServiceContainer
{
    protected array $serviceArray = [];

    /**
     * @template T
     * @psalm-param class-string<T> $serviceName
     * @return T
     */
    protected function getServiceFromContainer(string $serviceName)
    {
        if (defined('OXID_PHP_UNIT') && isset($this->serviceArray[$serviceName])) {
            return $this->serviceArray[$serviceName];
        }
        return ContainerFactory::getInstance()
            ->getContainer()
            ->get($serviceName);
    }
}
