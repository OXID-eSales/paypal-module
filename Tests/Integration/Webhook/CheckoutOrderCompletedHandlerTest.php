<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutOrderCompletedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;

final class CheckoutOrderCompletedHandlerTest extends WebhookHandlerBaseTestCase
{
    public const FIXTURE_NAME = 'checkout_order_completed.json';

    public const WEBHOOK_EVENT = 'CHECKOUT.ORDER.COMPLETED';

    public function testRequestMissingData(): void
    {
        $event = new WebhookEvent([], static::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::mandatoryDataNotFound()->getMessage());

        $handler = oxNew(CheckoutOrderCompletedHandler::class);
        $handler->handle($event);
    }

    public function testEshopOrderNotFoundByPayPalOrderId(): void
    {
        $data = $this->getRequestData('checkout_order_completed_pui_v2.json');
        $payPalOrderId = $data['resource']['id'];

        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::byPayPalOrderId($payPalOrderId)->getMessage());

        $handler = oxNew(CheckoutOrderCompletedHandler::class);
        $handler->handle($event);
    }

    public function dataProviderWebhookEvent(): array
    {
        return [
            'api_v1' => [
                'checkout_order_completed.json'
            ],
            'api_v2' => [
                'checkout_order_completed_pui_v2.json'
            ]
        ];
    }

    /**
     * @dataProvider dataProviderWebhookEvent
     */
    public function testCheckoutOrderCompleted(string $fixture): void
    {
        $data = $this->getRequestData($fixture);
        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $payPalOrderId = $data['resource']['id'];
        $transactionId = $data['resource']['purchase_units'][0]['payments']['captures'][0]['id'];
        $this->prepareTestData($payPalOrderId);

        $handler = oxNew(CheckoutOrderCompletedHandler::class);
        $handler->handle($event);

        $this->assertPayPalOrderCount($payPalOrderId);

        $payPalOrder = oxNew(PayPalOrder::class);
        $payPalOrder->load(self::PAYPAL_OXID);

        $this->assertSame('COMPLETED', $payPalOrder->getStatus());
        $this->assertSame($transactionId, $payPalOrder->getTransactionId());

        $order = oxNew(EshopModelOrder::class);
        $order->load(self::SHOP_ORDER_ID);
        $this->assertSame('OK', $order->getFieldData('OXTRANSSTATUS'));
        $this->assertNotSame('0000-00-00 00:00:00', $order->getFieldData('OXPAID'));
    }
}
