<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\TestingLibrary\UnitTestCase;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderResponse;
use Psr\Log\LoggerInterface;

class WebhookHandlerBaseTestCase extends UnitTestCase
{
    use ContainerTrait;

    public const TEST_RESOURCE_ID = 'PAYPALID123456789';
    public const PAYPAL_OXID = '_test_oxid';
    public const SHOP_ORDER_ID = '_shop_order_id';
    public const PAYMENT_METHOD = 'test_payment';
    
    protected function getRequestData(string $fixtureFileName): array
    {
        $json = file_get_contents(__DIR__ . '/../../Fixtures/' . $fixtureFileName);

        return json_decode($json, true);
    }

    protected function preparePaypalOrderMock(
        string $shopOrderId,
        string $payPalOrderId = 'ppid',
        string $transactionId = ''
    ): PayPalOrderModel {
        $mock = $this->getMockBuilder(PayPalOrderModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->any())
            ->method('load')
            ->willReturn(true);
        $mock->expects($this->any())
            ->method('getPayPalOrderId')
            ->willReturn($payPalOrderId);
        $mock->expects($this->any())
            ->method('getTransactionId')
            ->willReturn($transactionId);
        $mock->expects($this->any())
            ->method('getShopOrderId')
            ->willReturn($shopOrderId);
        $mock->expects($this->once())
            ->method('setStatus');
        $mock->expects($this->once())
            ->method('save');

        return $mock;
    }

    protected function prepareOrderMock(
        string $orderId = 'order_oxid',
        string $methodName = 'markOrderPaid',
        string $expectCalls = 'once'
    ): EshopModelOrder {
        $mock = $this->getMockBuilder(EshopModelOrder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->any())
            ->method('load')
            ->with($orderId)
            ->willReturn(true);
        $mock->expects($this->any())
            ->method('getId')
            ->willReturn($orderId);
        $mock->expects($this->$expectCalls())
            ->method($methodName);

        return $mock;
    }

    protected function getPsrLoggerMock(): LoggerInterface
    {
        $psrLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'emergency',
                    'alert',
                    'critical',
                    'error',
                    'warning',
                    'notice',
                    'info',
                    'debug',
                    'log'
                ]
            )
            ->getMock();

        return $psrLogger;
    }

    protected function getPuiOrderDetails(): ApiOrderResponse
    {
        $json = file_get_contents(__DIR__ . '/../../Fixtures/orderdetails_completed_with_pui.json');

        return new ApiOrderResponse(json_decode($json, true));
    }

    protected function assertPayPalOrderCount(string $payPalOrderId, int $expected = 1): void
    {
        $parameters = [
            'oxpaypalorderid' => $payPalOrderId,
        ];

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder->select('count(*)')
            ->from('oscpaypal_order')
            ->where('oxpaypalorderid = :oxpaypalorderid');

        $result = $queryBuilder->setParameters($parameters)
            ->execute();

        $this->assertEquals($expected, $result->fetchOne());
    }

    protected function prepareTestData(string $payPalOrderId): void
    {
        $shopOrder = oxNew(EshopModelOrder::class);
        $shopOrder->assign(
            [
                'oxid' => self::SHOP_ORDER_ID
            ]
        );
        $shopOrder->save();

        $payPalOrder = oxNew(PayPalOrderModel::class);
        $payPalOrder->assign(
            [
                'oxid' => self::PAYPAL_OXID,
                'oxshopid' => '1',
                'oxorderid' => self::SHOP_ORDER_ID,
                'oxpaypalorderid' => $payPalOrderId,
                'oscpaymentmethodid' => self::PAYMENT_METHOD
            ]
        );
        $payPalOrder->save();
    }
}
