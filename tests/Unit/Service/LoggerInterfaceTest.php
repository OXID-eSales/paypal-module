<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Service;

use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Service\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * Unit tests for Logger interface implementation
 * @group osc_paypal
 */
class LoggerInterfaceTest extends TestCase
{
    public function testLoggerImplementsInterface(): void
    {
        $psrLogger = $this->createMock(PsrLoggerInterface::class);
        $logger = new Logger($psrLogger);
        
        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testLoggerHasRequiredMethods(): void
    {
        $psrLogger = $this->createMock(PsrLoggerInterface::class);
        $logger = new Logger($psrLogger);
        
        $this->assertTrue(method_exists($logger, 'log'));
        $this->assertTrue(method_exists($logger, 'isLogLevel'));
    }

    public function testLogMethodSignature(): void
    {
        $psrLogger = $this->createMock(PsrLoggerInterface::class);
        $logger = new Logger($psrLogger);
        
        // Test that log method can be called with interface signature
        $reflection = new \ReflectionMethod($logger, 'log');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(3, $parameters);
        $this->assertEquals('level', $parameters[0]->getName());
        $this->assertEquals('message', $parameters[1]->getName());
        $this->assertEquals('exception', $parameters[2]->getName());
        
        // Third parameter should have default value
        $this->assertTrue($parameters[2]->isDefaultValueAvailable());
    }

    public function testIsLogLevelMethodSignature(): void
    {
        $psrLogger = $this->createMock(PsrLoggerInterface::class);
        $logger = new Logger($psrLogger);
        
        // Test that isLogLevel method can be called with interface signature
        $reflection = new \ReflectionMethod($logger, 'isLogLevel');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('level', $parameters[0]->getName());
    }
}