<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPal\Core\Exception\WebhookEventTypeException;
use OxidSolutionCatalysts\PayPal\Core\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher as PayPalWebhookActions;

final class WebhookActionTest extends UnitTestCase
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

        $handler = oxNew(PayPalWebhookActions::class);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::mandatoryDataNotFound()->getMessage());

        $handler->dispatch($webhookEvent);
    }
}