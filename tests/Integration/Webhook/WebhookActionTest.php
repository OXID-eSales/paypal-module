<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutOrderCompletedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventTypeException;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher as PayPalWebhookActions;

final class WebhookActionTest extends WebhookHandlerBaseTestCase
{
    public function testWebhookEvent(): void
    {
        $webhookEvent = new WebhookEvent([], '');
        $this->assertSame('', $webhookEvent->getEventType());
        $this->assertSame([], $webhookEvent->getData());

        $webhookEvent = new WebhookEvent(['bla' => 'foo'], 'event-type');
        $this->assertSame('event-type', $webhookEvent->getEventType());
        $this->assertSame(['bla' => 'foo'], $webhookEvent->getData());
    }

    public function testInvalidAction(): void
    {
        $webhookEvent = new WebhookEvent(['bla' => 'foo'], 'unknown.type');

        $handler = oxNew(PayPalWebhookActions::class);

        $this->expectException(WebhookEventTypeException::class);
        $this->expectExceptionMessage(WebhookEventTypeException::handlerNotFound('unknown.type')->getMessage());

        $handler->dispatch($webhookEvent);
    }

    public function testValidActionWithInvalidRequestData(): void
    {
        $webhookEvent = new WebhookEvent(['resource' => ['bla' => 'foo']], 'CHECKOUT.ORDER.COMPLETED');

        $loggerMock = $this->getPsrLoggerMock();
        $loggerMock->expects($this->once())
            ->method('log')
            ->with(
                'debug',
                "Not enough information to handle CHECKOUT.ORDER.COMPLETED with PayPal order_id '' and " .
                "PayPal transaction id ''"
            );

        $handlerMock = $this->getMockBuilder(PayPalWebhookActions::class)
            ->onlyMethods(['oxNew'])
            ->getMock();

        $checkoutOrderCompletedHook = $this->getMockBuilder(CheckoutOrderCompletedHandler::class)
            ->onlyMethods(['getLogger'])
            ->getMock();

        $checkoutOrderCompletedHook->method('getLogger')->willReturn($loggerMock);

        $handlerMock->method('oxNew')->willReturn($checkoutOrderCompletedHook);

        $handlerMock->dispatch($webhookEvent);
    }
}
