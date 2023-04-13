<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration;

use Codeception\Util\Fixtures;
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
        $dotenv->load(__DIR__ . '/../../tests/.env');

        $this->updateModuleConfiguration('oscPayPalSandboxClientId', $_ENV['oscPayPalSandboxClientId']);
        $this->updateModuleConfiguration('oscPayPalSandboxMode', true);
        $this->updateModuleConfiguration('oscPayPalSandboxClientSecret', $_ENV['oscPayPalSandboxClientSecret']);
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

    /**
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    protected function doAssertStringNotContainsString($needle, $haystack, $message = '')
    {
        if (method_exists($this, 'assertStringNotContainsString')) {
            parent::assertStringNotContainsString($needle, $haystack, $message);
        } else {
            parent::assertNotContains($needle, $haystack, $message);
        }
    }

    /**
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    protected function doAssertStringContainsString($needle, $haystack, $message = '')
    {
        if (method_exists($this, 'assertStringContainsString')) {
            parent::assertStringContainsString($needle, $haystack, $message);
        } else {
            parent::assertContains($needle, $haystack, $message);
        }
    }
}
