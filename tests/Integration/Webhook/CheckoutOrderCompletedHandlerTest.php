<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use Exception;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutOrderCompletedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;

final class CheckoutOrderCompletedHandlerTest extends WebhookHandlerBaseTestCase
{
    use ServiceContainer;

    public const FIXTURE_NAME = 'checkout_order_completed.json';

    public const WEBHOOK_EVENT = 'CHECKOUT.ORDER.COMPLETED';

    protected function tearDown(): void
    {
        $this->cleanUpTable('oscpaypal_order');
        $this->cleanUpTable('oxorder');

        parent::tearDown();
    }

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

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Order not found.");

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

        // this state is when the order is created by oxid but PayPal not yet acknowledged completed order
        $this->prepareTestData($payPalOrderId);

        // this state is when PayPal send the order completed webhook
        $handler = oxNew(CheckoutOrderCompletedHandler::class);
        $handler->handle($event);

        // we now have two PayPal order entries,
        $this->assertPayPalOrderCount($payPalOrderId, 2);

        // after CheckoutOrderCompletedHandler::handle there's one paypal order entry with status null
        // and one with status completed
        /** @var OrderRepository $orderRepo */
        $orderRepo = $this->get(OrderRepository::class);
        $payPalOrder = $orderRepo->paypalOrderByOrderIdAndPayPalId(
            self::SHOP_ORDER_ID,
            $payPalOrderId,
            $transactionId
        );
        // we assert that there is an entry with status completed
        $this->assertSame('COMPLETED', $payPalOrder->getStatus());
        $this->assertSame($transactionId, $payPalOrder->getTransactionId());

        $order = oxNew(EshopModelOrder::class);
        $order->load(self::SHOP_ORDER_ID);
        $this->assertSame('OK', $order->getFieldData('OXTRANSSTATUS'));
        $this->assertSame($transactionId, $order->getFieldData('OXTRANSID'));
        $this->assertStringStartsWith(date('Y-m-d'), $order->getFieldData('OXPAID'));
    }
}
