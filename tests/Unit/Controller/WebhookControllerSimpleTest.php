<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Controller;

use OxidSolutionCatalysts\PayPal\Controller\WebhookController;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Service\LoggerInterface;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifier;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifierInterface;
use PHPUnit\Framework\TestCase;

/**
 * Simple unit tests for WebhookController dependency injection
 * @group osc_paypal
 * @group osc_paypal_webhook
 */
class WebhookControllerSimpleTest extends TestCase
{
    public function testConstructorAcceptsDependencies(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $eventVerifier = $this->createMock(EventVerifierInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);

        $controller = new WebhookController($logger, $eventVerifier, $eventDispatcher);
        
        $this->assertInstanceOf(WebhookController::class, $controller);
    }

    public function testConstructorRequiresDependencies(): void
    {
        // Test that constructor now requires all dependencies
        $reflection = new \ReflectionClass(WebhookController::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        // All parameters should be required (not nullable, no default values)
        foreach ($parameters as $parameter) {
            $this->assertFalse($parameter->allowsNull(), "Parameter {$parameter->getName()} should not allow null");
            $this->assertFalse($parameter->isDefaultValueAvailable(), "Parameter {$parameter->getName()} should not have default value");
        }
    }

    public function testConstructorWithAllRequiredParameters(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $eventVerifier = $this->createMock(EventVerifierInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        
        // Test constructor requires all parameters
        $controller = new WebhookController($logger, $eventVerifier, $eventDispatcher);
        
        $this->assertInstanceOf(WebhookController::class, $controller);
    }

    public function testWebhookControllerExtendsWidgetController(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $eventVerifier = $this->createMock(EventVerifierInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        
        $controller = new WebhookController($logger, $eventVerifier, $eventDispatcher);
        
        $this->assertInstanceOf(
            'OxidEsales\Eshop\Application\Component\Widget\WidgetController',
            $controller
        );
    }

    public function testWebhookControllerHasRequiredMethods(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $eventVerifier = $this->createMock(EventVerifierInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        
        $controller = new WebhookController($logger, $eventVerifier, $eventDispatcher);
        
        // Test that required methods exist
        $this->assertTrue(method_exists($controller, 'init'));
        
        // Test that sendErrorResponse method exists
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('sendErrorResponse'));
        
        // Verify that the old getter methods are removed
        $this->assertFalse($reflection->hasMethod('getLogger'));
        $this->assertFalse($reflection->hasMethod('getEventVerifier'));
        $this->assertFalse($reflection->hasMethod('getEventDispatcher'));
    }

    public function testDependencyInjectionStructure(): void
    {
        // Test the dependency injection structure
        $reflectionClass = new \ReflectionClass(WebhookController::class);
        $constructor = $reflectionClass->getConstructor();
        
        $this->assertNotNull($constructor);
        
        $parameters = $constructor->getParameters();
        $this->assertCount(3, $parameters);
        
        // Verify parameter names and types
        $this->assertEquals('logger', $parameters[0]->getName());
        $this->assertEquals('eventVerifier', $parameters[1]->getName());
        $this->assertEquals('eventDispatcher', $parameters[2]->getName());
        
        // Verify parameters are required (not nullable, no default values)
        $this->assertFalse($parameters[0]->allowsNull());
        $this->assertFalse($parameters[1]->allowsNull());
        $this->assertFalse($parameters[2]->allowsNull());
        
        // Verify no default values
        $this->assertFalse($parameters[0]->isDefaultValueAvailable());
        $this->assertFalse($parameters[1]->isDefaultValueAvailable());
        $this->assertFalse($parameters[2]->isDefaultValueAvailable());
    }

    public function testServiceGetterMethodsRemoved(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $eventVerifier = $this->createMock(EventVerifierInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        
        $controller = new WebhookController($logger, $eventVerifier, $eventDispatcher);
        $reflection = new \ReflectionClass($controller);
        
        // Test that getter methods are removed (no fallback to Registry)
        $this->assertFalse($reflection->hasMethod('getLogger'));
        $this->assertFalse($reflection->hasMethod('getEventVerifier'));
        $this->assertFalse($reflection->hasMethod('getEventDispatcher'));
    }

    public function testControllerRequiresAllDependencies(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $eventVerifier = $this->createMock(EventVerifierInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);

        // Test that controller can only be instantiated with all dependencies
        $controller = new WebhookController($logger, $eventVerifier, $eventDispatcher);
        $this->assertInstanceOf(WebhookController::class, $controller);
        
        // Test that private properties are properly set
        $reflection = new \ReflectionClass($controller);
        
        $loggerProperty = $reflection->getProperty('logger');
        $loggerProperty->setAccessible(true);
        $this->assertSame($logger, $loggerProperty->getValue($controller));
        
        $eventVerifierProperty = $reflection->getProperty('eventVerifier');
        $eventVerifierProperty->setAccessible(true);
        $this->assertSame($eventVerifier, $eventVerifierProperty->getValue($controller));
        
        $eventDispatcherProperty = $reflection->getProperty('eventDispatcher');
        $eventDispatcherProperty->setAccessible(true);
        $this->assertSame($eventDispatcher, $eventDispatcherProperty->getValue($controller));
    }
}