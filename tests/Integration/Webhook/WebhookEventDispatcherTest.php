<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use PHPUnit\Framework\TestCase;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventTypeException;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher as WebhookEventDispatcher;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutOrderCompletedHandler;

final class WebhookEventDispatcherTest extends TestCase
{
    public function testEventIsMappedToExpectedHandler(): void
    {
        $event = new WebhookEvent(['bla' => 'foo'], 'CHECKOUT.ORDER.COMPLETED');

        $mock = $this->getMockBuilder(CheckoutOrderCompletedHandler::class)
            ->getMock();
        $mock->expects($this->any())
            ->method('handle')
            ->with($event)
            ->willThrowException(new WebhookEventException('CheckoutOrderCompletedHandler_message'));

        EshopRegistry::getUtilsObject()->setClassInstance(CheckoutOrderCompletedHandler::class, $mock);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage('CheckoutOrderCompletedHandler_message');

        $dispatcher = oxNew(WebhookEventDispatcher::class);
        $dispatcher->dispatch($event);
    }

    public function testEventHandlerDoesNotExist(): void
    {
        $event = new WebhookEvent(['bla' => 'foo'], 'THIS.IS.UNKNOWN');

        $this->expectException(WebhookEventTypeException::class);
        $this->expectExceptionMessage(WebhookEventTypeException::handlerNotFound('THIS.IS.UNKNOWN')->getMessage());

        $dispatcher = oxNew(WebhookEventDispatcher::class);
        $dispatcher->dispatch($event);
    }
}
