<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use Exception;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentCaptureRefundedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder;

final class PaymentCaptureRefundedHandlerTest extends WebhookHandlerBaseTestCase
{
    public const WEBHOOK_EVENT = 'PAYMENT.CAPTURE.REFUNDED';

    public const FIXTURE = 'payment_capture_refunded.json';

    protected function tearDown(): void
    {
        $this->cleanUpTable('oscpaypal_order', 'oxorderid');
        $this->cleanUpTable('oxorder');

        parent::tearDown();
    }

    public function testRequestMissingData(): void
    {
        $event = new WebhookEvent([], static::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::mandatoryDataNotFound()->getMessage());

        $handler = oxNew(PaymentCaptureRefundedHandler::class);
        $handler->handle($event);
    }

    public function testEshopOrderNotFoundByPayPalOrderId(): void
    {
        $data = $this->getRequestData(self::FIXTURE);
        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Order not found.');

        $handler = oxNew(PaymentCaptureRefundedHandler::class);
        $handler->handle($event);
    }

    public function dataProviderWebhookEvent(): array
    {
        return [
            'full' => [
                'ordertotal' => 7.0,
                'expected' => 'REFUNDED'
            ],
            'partial' => [
                'ordertotal' => 70.0,
                'expected' => 'PARTIALLY_REFUNDED'
            ]
        ];
    }

    /**
     * @dataProvider dataProviderWebhookEvent
     */
    public function testPaymentCaptureRefunded(float $orderTotal, string $expected): void
    {
        $data = $this->getRequestData(self::FIXTURE);
        $event = new WebhookEvent($data, static::WEBHOOK_EVENT);

        $refundId = $data['resource']['id'];
        $captureId = '5YH4578629195611S';
        $payPalOrderId = 'paypal_orderid';

        $this->prepareTestData($payPalOrderId);

        /** @var PayPalOrder $payPalOrder */
        $payPalOrder = oxNew(PayPalOrder::class);
        $payPalOrder->load(self::PAYPAL_OXID);
        $payPalOrder->setTransactionId($captureId);
        $payPalOrder->save();

        $order = oxNew(EshopModelOrder::class);
        $order->load(self::SHOP_ORDER_ID);
        $order->assign(['oxtotalordersum' => $orderTotal]);
        $order->save();

        $handler = oxNew(PaymentCaptureRefundedHandler::class);
        $handler->handle($event);

        $this->assertPayPalOrderCount($payPalOrderId, 2);

        $payPalOrder = oxNew(PayPalOrder::class);
        $payPalOrder->load(self::PAYPAL_OXID);
        $this->assertSame($expected, $payPalOrder->getStatus());

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder->select('oscpaypaltransactionid')
            ->from('oscpaypal_order')
            ->where('oscpaypaltransactiontype = :type');

        $result = $queryBuilder->setParameters(['type' => Constants::PAYPAL_TRANSACTION_TYPE_REFUND])
            ->execute();

        $this->assertEquals($refundId, $result->fetchOne());
    }
}
