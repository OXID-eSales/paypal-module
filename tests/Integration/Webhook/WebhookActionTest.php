<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventHandlerMapping;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventTypeException;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher as PayPalWebhookActions;
use OxidSolutionCatalysts\PayPal\Service\Logger;

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
        /** @var WebhookEvent $webhookEvent */
        $eventType = 'CHECKOUT.ORDER.COMPLETED';
        $webhookEvent = new WebhookEvent(['resource' => ['bla' => 'foo']], $eventType);
/*
$handlers = EventHandlerMapping::MAPPING;
$handler = oxNew($handlers[$eventType]);
            $handler->handle($webhookEvent);*/


        /** @var PayPalWebhookActions $handler */
        $handler = oxNew(PayPalWebhookActions::class);

        $loggerMock = $this->getPsrLoggerMock();
        $loggerMock->expects($this->once())
            ->method('debug')
            ->with(
                "Not enough information to handle CHECKOUT.ORDER.COMPLETED with PayPal order_id '' and " .
                "PayPal transaction id ''"
            );
        EshopRegistry::set('logger', $loggerMock);

        $handler->dispatch($webhookEvent);
    }
}
