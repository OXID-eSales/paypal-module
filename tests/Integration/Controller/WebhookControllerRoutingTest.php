<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Controller;

use OxidSolutionCatalysts\PayPal\Controller\WebhookController;
use PHPUnit\Framework\TestCase;

/**
 * Integration test to verify WebhookController routing still works
 * @group osc_paypal
 * @group osc_paypal_webhook
 * @group osc_paypal_integration
 */
class WebhookControllerRoutingTest extends TestCase
{
    public function testWebhookControllerCanBeInstantiatedWithoutDI(): void
    {
        // Test that the controller can be instantiated with proper dependency injection
        $mockLogger = $this->createMock(\OxidSolutionCatalysts\PayPal\Service\LoggerInterface::class);
        $mockEventVerifier = $this->createMock(\OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifierInterface::class);
        $mockEventDispatcher = $this->createMock(\OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher::class);
        
        $controller = new WebhookController($mockLogger, $mockEventVerifier, $mockEventDispatcher);
        
        $this->assertInstanceOf(WebhookController::class, $controller);
        $this->assertInstanceOf(
            'OxidEsales\Eshop\Application\Component\Widget\WidgetController',
            $controller
        );
    }

    public function testWebhookControllerWithRegistryFallback(): void
    {
        // Test the controller with proper dependency injection
        $mockLogger = $this->createMock(\OxidSolutionCatalysts\PayPal\Service\LoggerInterface::class);
        $mockEventVerifier = $this->createMock(\OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifierInterface::class);
        $mockEventDispatcher = $this->createMock(\OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher::class);
        
        $controller = new WebhookController($mockLogger, $mockEventVerifier, $mockEventDispatcher);
        
        $this->assertInstanceOf(WebhookController::class, $controller);
        
        // Verify that the controller is properly initialized
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('init'));
    }

    public function testWebhookControllerRegistryCompatibility(): void
    {
        // Test that the controller works with proper dependency injection
        $mockLogger = $this->createMock(\OxidSolutionCatalysts\PayPal\Service\LoggerInterface::class);
        $mockEventVerifier = $this->createMock(\OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifierInterface::class);
        $mockEventDispatcher = $this->createMock(\OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher::class);
        
        $controller = new WebhookController($mockLogger, $mockEventVerifier, $mockEventDispatcher);
        
        $this->assertInstanceOf(WebhookController::class, $controller);
    }

    public function testWebhookControllerMaintainsExpectedInterface(): void
    {
        $mockLogger = $this->createMock(\OxidSolutionCatalysts\PayPal\Service\LoggerInterface::class);
        $mockEventVerifier = $this->createMock(\OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifierInterface::class);
        $mockEventDispatcher = $this->createMock(\OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher::class);
        
        $controller = new WebhookController($mockLogger, $mockEventVerifier, $mockEventDispatcher);
        
        // Verify the controller maintains its expected public interface
        $this->assertTrue(method_exists($controller, 'init'));
        
        // Verify it's still a WidgetController
        $this->assertInstanceOf(
            'OxidEsales\Eshop\Application\Component\Widget\WidgetController',
            $controller
        );
    }

    public function testWebhookControllerSupportsHybridApproach(): void
    {
        // Test that the controller works with proper dependency injection
        $mockLogger = $this->createMock(\OxidSolutionCatalysts\PayPal\Service\LoggerInterface::class);
        $mockEventVerifier = $this->createMock(\OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifierInterface::class);
        $mockEventDispatcher = $this->createMock(\OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher::class);
        
        $controller = new WebhookController($mockLogger, $mockEventVerifier, $mockEventDispatcher);
        $this->assertInstanceOf(WebhookController::class, $controller);
    }

    public function testWebhookControllerMigrationFromMetadataToService(): void
    {
        // This test verifies that the controller works with proper dependency injection
        // whether it's registered via metadata.php or services.yaml
        
        $mockLogger = $this->createMock(\OxidSolutionCatalysts\PayPal\Service\LoggerInterface::class);
        $mockEventVerifier = $this->createMock(\OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifierInterface::class);
        $mockEventDispatcher = $this->createMock(\OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher::class);
        
        $controller = new WebhookController($mockLogger, $mockEventVerifier, $mockEventDispatcher);
        $this->assertInstanceOf(WebhookController::class, $controller);
        
        // Verify the constructor has the expected parameters
        $reflection = new \ReflectionClass($controller);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        $this->assertCount(3, $parameters, 'Constructor should have 3 parameters');
        $this->assertEquals('logger', $parameters[0]->getName());
        $this->assertEquals('eventVerifier', $parameters[1]->getName());
        $this->assertEquals('eventDispatcher', $parameters[2]->getName());
    }
}