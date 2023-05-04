<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Service;

use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder;

final class OrderRepositoryTest extends BaseTestCase
{
    private const SHOP_ORDER_ID = 'shop_order_id';
    private const PAYMENT_METHOD = 'oscpaypal_pui';
    private const PAYPAL_ORDERID = '8S9298293T126730G';
    private const PAYPAL_TRANSACTIONID = '7JS1508358036693V';
    private const PAYPAL_STATUS = 'PENDING_APPROVAL';
    private const PAYPAL_OXID = '_test_oxid';

    protected function tearDown(): void
    {
        $this->cleanUpTable('oscpaypal_order');

        parent::tearDown();
    }

    public function testRetrievingTransactionPayPalOrderFromRepo(): void
    {
        $orderRepo = $this->getServiceFromContainer(OrderRepository::class);

        $this->prepareTestOrder();

        //search by shop order id and paypal order id and transaction id
        $fromRepo = $orderRepo->paypalOrderByOrderIdAndPayPalId(
            self::SHOP_ORDER_ID,
            self::PAYPAL_ORDERID,
            self::PAYPAL_TRANSACTIONID
        );
        $this->assertSame(self::PAYPAL_OXID, $fromRepo->getId());
        $this->assertSame(self::PAYMENT_METHOD, $fromRepo->getPaymentMethodId());
        $this->assertSame(self::PAYPAL_TRANSACTIONID, $fromRepo->getTransactionId());

        //search by shop order id and paypal order id and different transaction id
        $fromRepo = $orderRepo->paypalOrderByOrderIdAndPayPalId(
            self::SHOP_ORDER_ID,
            self::PAYPAL_ORDERID,
            'some_different_trans_id'
        );
        $this->assertEmpty($fromRepo->getId());
        $this->assertEmpty($fromRepo->getPaymentMethodId());
        $this->assertSame(self::PAYPAL_ORDERID, $fromRepo->getPayPalOrderId());
        $this->assertSame('some_different_trans_id', $fromRepo->getTransactionId());
    }

    public function testRetrievingPayPalOrderFromRepoWithoutSaveTransactionId(): void
    {
        $orderRepo = $this->getServiceFromContainer(OrderRepository::class);

        $this->prepareTestOrder('');

        //search by shop order id and paypal order id
        $fromRepo = $orderRepo->paypalOrderByOrderIdAndPayPalId(
            self::SHOP_ORDER_ID,
            self::PAYPAL_ORDERID
        );
        $this->assertSame(self::PAYPAL_OXID, $fromRepo->getId());
        $this->assertSame(self::PAYMENT_METHOD, $fromRepo->getPaymentMethodId());
        $this->assertEmpty($fromRepo->getTransactionId());

        //search by shop order id and paypal order id and transaction id (which is not yet in database)
        $fromRepo = $orderRepo->paypalOrderByOrderIdAndPayPalId(
            self::SHOP_ORDER_ID,
            self::PAYPAL_ORDERID,
            self::PAYPAL_TRANSACTIONID
        );
        $this->assertSame(self::SHOP_ORDER_ID, $fromRepo->getShopOrderId());
        $this->assertSame(self::PAYPAL_ORDERID, $fromRepo->getPayPalOrderId());
        $this->assertSame(self::PAYPAL_TRANSACTIONID, $fromRepo->getTransactionId()); //entry not found so trans id was not set

        $fromRepo = $orderRepo->paypalOrderByOrderIdAndPayPalId(
            self::SHOP_ORDER_ID,
            self::PAYPAL_ORDERID,
            'some_different_trans_id'
        );
        $this->assertSame(self::SHOP_ORDER_ID, $fromRepo->getShopOrderId());
        $this->assertSame(self::PAYPAL_ORDERID, $fromRepo->getPayPalOrderId());
        $this->assertSame('some_different_trans_id', $fromRepo->getTransactionId()); //entry not found so trans id was not set
    }

    public function testRetrievingPayPalOrderFromRepoForMultipleEntries(): void
    {
        $orderRepo = $this->getServiceFromContainer(OrderRepository::class);

        $this->prepareTestOrder('');
        $this->prepareTestOrder(self::PAYPAL_TRANSACTIONID, self::PAYPAL_OXID . '_2');

        //search by shop order id and paypal order id
        $fromRepo = $orderRepo->paypalOrderByOrderIdAndPayPalId(
            self::SHOP_ORDER_ID,
            self::PAYPAL_ORDERID
        );
        $this->assertSame(self::PAYPAL_OXID, $fromRepo->getId());
        $this->assertSame(self::PAYMENT_METHOD, $fromRepo->getPaymentMethodId());
        $this->assertEmpty($fromRepo->getTransactionId());

        //search by shop order id and paypal order id and transaction id (which is second entry in database)
        $fromRepo = $orderRepo->paypalOrderByOrderIdAndPayPalId(
            self::SHOP_ORDER_ID,
            self::PAYPAL_ORDERID,
            self::PAYPAL_TRANSACTIONID
        );
        $this->assertSame(self::PAYPAL_OXID . '_2', $fromRepo->getId());
        $this->assertSame(self::PAYMENT_METHOD, $fromRepo->getPaymentMethodId());
        $this->assertSame(self::PAYPAL_TRANSACTIONID, $fromRepo->getTransactionId());
    }

    public function testGetPayPalOrderIdByOrderId(): void
    {
        $orderRepo = $this->getServiceFromContainer(OrderRepository::class);

        $this->prepareTestOrder();

        $this->assertEquals(
            self::PAYPAL_ORDERID,
            $orderRepo->getPayPalOrderIdByShopOrderId(self::SHOP_ORDER_ID)
        );
    }

    private function prepareTestOrder(
        string $transId = self::PAYPAL_TRANSACTIONID,
        string $oxid = self::PAYPAL_OXID
    ): void {
        $payPalOrder = oxNew(PayPalOrder::class);
        $payPalOrder->assign(
            [
                'oxid' => $oxid,
                'oxshopid' => '1',
                'oxorderid' => self::SHOP_ORDER_ID,
                'oxpaypalorderid' => self::PAYPAL_ORDERID,
                'oscpaypalstatus' => self::PAYPAL_STATUS,
                'oscpaymentmethodid' => self::PAYMENT_METHOD,
                'oscpaypaltransactionid' => $transId,
                'oscpaypaltransactiontype' => Constants::PAYPAL_TRANSACTION_TYPE_CAPTURE
            ]
        );
        $payPalOrder->save();
    }
}
