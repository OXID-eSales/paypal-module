<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Controller;

use OxidSolutionCatalysts\PayPal\Controller\WebhookController;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Service\LoggerInterface;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifier;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifierInterface;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher;
use PHPUnit\Framework\TestCase;

/**
 * Integration test to verify WebhookController service registration
 * @group osc_paypal
 * @group osc_paypal_webhook
 * @group osc_paypal_service
 */
class WebhookControllerServiceTest extends TestCase
{
    public function testWebhookControllerDependencyInjectionStructure(): void
    {
        // Test that the WebhookController has the correct structure for dependency injection
        $reflectionClass = new \ReflectionClass(WebhookController::class);
        $constructor = $reflectionClass->getConstructor();
        
        $this->assertNotNull($constructor);
        
        $parameters = $constructor->getParameters();
        $this->assertCount(3, $parameters);
        
        // Verify parameter names and types
        $this->assertEquals('logger', $parameters[0]->getName());
        $this->assertEquals('eventVerifier', $parameters[1]->getName());
        $this->assertEquals('eventDispatcher', $parameters[2]->getName());
        
        // Parameters are required for strict dependency injection
        $this->assertFalse($parameters[0]->allowsNull());
        $this->assertFalse($parameters[1]->allowsNull());
        $this->assertFalse($parameters[2]->allowsNull());
    }

    public function testWebhookControllerCanBeInstantiatedWithDependencies(): void
    {
        // Test that the controller can be instantiated with mock dependencies
        $logger = $this->createMock(LoggerInterface::class);
        $eventVerifier = $this->createMock(EventVerifierInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        
        $controller = new WebhookController($logger, $eventVerifier, $eventDispatcher);
        $this->assertInstanceOf(WebhookController::class, $controller);
    }

    public function testWebhookControllerServiceReadiness(): void
    {
        // Test that the controller is ready for service container registration
        // by verifying it follows strict dependency injection patterns
        
        $reflection = new \ReflectionClass(WebhookController::class);
        
        // Should have a constructor that requires dependencies
        $this->assertTrue($reflection->hasMethod('__construct'));
        
        // Should NOT have getter methods (no fallback to Registry)
        $this->assertFalse($reflection->hasMethod('getLogger'));
        $this->assertFalse($reflection->hasMethod('getEventVerifier'));
        $this->assertFalse($reflection->hasMethod('getEventDispatcher'));
        
        // Should have non-nullable properties for dependencies
        $properties = $reflection->getProperties();
        $dependencyProperties = ['logger', 'eventVerifier', 'eventDispatcher'];
        
        foreach ($properties as $property) {
            if (in_array($property->getName(), $dependencyProperties)) {
                $this->assertFalse($property->getType()->allowsNull(), 
                    "Property {$property->getName()} should not allow null");
            }
        }
    }

    public function testWebhookControllerRequiresDependencyInjection(): void
    {
        // Test that the controller now enforces strict dependency injection
        $logger = $this->createMock(LoggerInterface::class);
        $eventVerifier = $this->createMock(EventVerifierInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        
        $controller = new WebhookController($logger, $eventVerifier, $eventDispatcher);
        $this->assertInstanceOf(WebhookController::class, $controller);
        
        // Should still extend WidgetController
        $this->assertInstanceOf(
            'OxidEsales\Eshop\Application\Component\Widget\WidgetController',
            $controller
        );
    }

    public function testWebhookControllerStrictDependencyInjection(): void
    {
        // Test that the controller now requires strict dependency injection
        // (no Registry fallback, all dependencies must be provided)
        
        $logger = $this->createMock(LoggerInterface::class);
        $eventVerifier = $this->createMock(EventVerifierInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        
        // Only full dependency injection is supported
        $controller = new WebhookController($logger, $eventVerifier, $eventDispatcher);
        $this->assertInstanceOf(WebhookController::class, $controller);
        
        // Verify that the properties are properly set and non-nullable
        $reflection = new \ReflectionClass($controller);
        
        $loggerProperty = $reflection->getProperty('logger');
        $this->assertFalse($loggerProperty->getType()->allowsNull());
        
        $eventVerifierProperty = $reflection->getProperty('eventVerifier');
        $this->assertFalse($eventVerifierProperty->getType()->allowsNull());
        
        $eventDispatcherProperty = $reflection->getProperty('eventDispatcher');
        $this->assertFalse($eventDispatcherProperty->getType()->allowsNull());
    }

    public function testServiceConfigurationReadiness(): void
    {
        // Verify that the controller follows patterns expected by service containers
        
        // Should have type hints for all dependencies
        $reflection = new \ReflectionClass(WebhookController::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            $this->assertNotNull($type, "Parameter {$parameter->getName()} should have type hint");
            
            if ($type instanceof \ReflectionNamedType) {
                $this->assertFalse($type->isBuiltin(), "Parameter {$parameter->getName()} should be object type");
            }
        }
    }

    public function testWebhookControllerMetadataToServiceMigration(): void
    {
        // This test verifies the successful migration from metadata.php registration
        // to service.yaml registration by ensuring the controller maintains its interface
        
        $logger = $this->createMock(LoggerInterface::class);
        $eventVerifier = $this->createMock(EventVerifierInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        
        $controller = new WebhookController($logger, $eventVerifier, $eventDispatcher);
        
        // Should maintain the same public interface
        $this->assertTrue(method_exists($controller, 'init'));
        
        // Should still be a WidgetController (OXID requirement)
        $this->assertInstanceOf(
            'OxidEsales\Eshop\Application\Component\Widget\WidgetController',
            $controller
        );
        
        // Should use strict dependency injection (no Registry fallback methods)
        $reflection = new \ReflectionClass($controller);
        $this->assertFalse($reflection->hasMethod('getLogger'));
        $this->assertFalse($reflection->hasMethod('getEventVerifier'));
        $this->assertFalse($reflection->hasMethod('getEventDispatcher'));
    }
}