<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration;

use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use Psr\Log\LoggerInterface;

abstract class BaseTestCase extends UnitTestCase
{
    use ServiceContainer;

    protected function setUp(): void
    {
        parent::setUp();

        $dotenv = new \Symfony\Component\Dotenv\Dotenv();
        $dotenv->load(__DIR__.'/../../.env');

        $this->updateModuleConfiguration('sPayPalSandboxClientId', $_ENV['sPayPalSandboxClientId']);
        $this->updateModuleConfiguration('blPayPalSandboxMode', true);
        $this->updateModuleConfiguration('sPayPalSandboxClientSecret', $_ENV['sPayPalSandboxClientSecret']);
    }

    protected function getPsrLoggerMock(): LoggerInterface
    {
        $psrLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'emergency',
                    'alert',
                    'critical',
                    'error',
                    'warning',
                    'notice',
                    'info',
                    'debug',
                    'log'
                ]
            )
            ->getMock();

        return $psrLogger;
    }

    protected function updateModuleConfiguration(string $confName, $value): void
    {
        $this->getServiceFromContainer(ModuleSettings::class)->save($confName, $value);
    }
}