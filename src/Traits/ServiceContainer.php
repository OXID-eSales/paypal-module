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
     * Used in Unit-Tests to mock services
     * There might be a cleaner way, but haven't found it yet
     *
     * @param string     $serviceName
     * @param MockObject $serviceMock
     * @return void
     */
    public function addServiceMock(string $serviceName, MockObject $serviceMock)
    {
        $this->serviceArray[$serviceName] = $serviceMock;
    }

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
