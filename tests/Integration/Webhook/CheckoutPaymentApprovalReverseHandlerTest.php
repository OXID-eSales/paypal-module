<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutPaymentApprovalReverseHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;

final class CheckoutPaymentApprovalReverseHandlerTest extends WebhookHandlerBaseTestCase
{
    public const WEBHOOK_EVENT = 'CHECKOUT.PAYMENT-APPROVAL.REVERSED';

    public const HANDLER_CLASS = CheckoutPaymentApprovalReverseHandler::class;

    public function testRequestMissingData(): void
    {
        $event = new WebhookEvent([], static::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::mandatoryDataNotFound()->getMessage());

        $handler = oxNew(static::HANDLER_CLASS);
        $handler->handle($event);
    }

    public function testEshopOrderNotFoundByPayPalOrderId(): void
    {
        $data = $this->getRequestData('payment_approval_reversed_pui_v1.json');
        $payPalOrderId = $data['resource']['order_id'];

        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(
            WebhookEventException::byPayPalOrderId($payPalOrderId)->getMessage()
        );

        $handler = oxNew(static::HANDLER_CLASS);
        $handler->handle($event);
    }

    public function testPuiPaymentCaptureDenied(): void
    {
        $data = $this->getRequestData('payment_approval_reversed_pui_v1.json');
        $payPalOrderId = $data['resource']['order_id'];

        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $this->prepareTestData($payPalOrderId);

        $handler = oxNew(static::HANDLER_CLASS);
        $handler->handle($event);

        $this->assertPayPalOrderCount($payPalOrderId);

        $payPalOrder = oxNew(PayPalOrder::class);
        $payPalOrder->load(self::PAYPAL_OXID);
        $this->assertSame('CHECKOUT.PAYMENT-APPROVAL.REVERSED', $payPalOrder->getStatus());
        $this->assertSame('', $payPalOrder->getTransactionId());

        $order = oxNew(EshopModelOrder::class);
        $order->load(self::SHOP_ORDER_ID);
        $this->assertSame('ERROR', $order->getFieldData('OXTRANSSTATUS'));
        $this->assertSame('0000-00-00 00:00:00', $order->getFieldData('OXPAID'));
    }
}
