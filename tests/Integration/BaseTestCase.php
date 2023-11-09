<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration;

use Codeception\Util\Fixtures;
use PHPUnit\Framework\TestCase;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidEsales\Eshop\Core\DatabaseProvider;
use Psr\Log\LoggerInterface;

abstract class BaseTestCase extends TestCase
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

    /**
     * Cleans up table
     *
     * @param string $table      Table name
     * @param string $columnName Column name
     */
    protected function cleanUpTable($table, $columnName = null)
    {
        $sCol = (!empty($columnName)) ? $columnName : 'oxid';

        //deletes allrecords where oxid or specified column name values starts with underscore(_)
        $sQ = "delete from `$table` where `$sCol` like '\_%' ";

        DatabaseProvider::getDB()->execute($sQ);
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
