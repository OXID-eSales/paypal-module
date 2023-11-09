<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventTypeException;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
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

    /*
     * Removed as logger is now a Service and I have no clue how to inject the mock there
     * Feel free to add if you know how
        public function testValidActionWithInvalidRequestData(): void
        {
            $webhookEvent = new WebhookEvent(['resource' => ['bla' => 'foo']], 'CHECKOUT.ORDER.COMPLETED');

            $handler = oxNew(PayPalWebhookActions::class);

            $loggerMock = $this->getPsrLoggerMock();
            $loggerMock->expects($this->once())
                ->method('debug')
                ->with(
                    "Not enough information to handle CHECKOUT.ORDER.COMPLETED with PayPal order_id '' and " .
                    "PayPal transaction id ''"
                );

            $handler->dispatch($webhookEvent);
        }
    */
}
