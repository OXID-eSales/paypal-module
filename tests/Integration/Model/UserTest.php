<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Model;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;

final class UserTest extends BaseTestCase
{
    private const TEST_USER_ID = '_testuser_paypal';

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user with all required fields
        $user = oxNew(EshopModelUser::class);
        $user->setId(self::TEST_USER_ID);
        $user->assign([
            'oxid' => self::TEST_USER_ID,
            'oxactive' => 1,
            'oxshopid' => 1,
            'oxusername' => 'testuser@example.com',
            'oxpassword' => md5('password'),
            'oxpasssalt' => '',
            'oxfname' => 'Test',
            'oxlname' => 'User',
            'oxstreet' => 'Test Street',
            'oxstreetnr' => '1',
            'oxcity' => 'Test City',
            'oxcountryid' => 'a7c40f631fc920687.20179984',
            'oxzip' => '12345',
            'oxsal' => 'MR',
            'oxcompany' => 'Test Company',
            'oxcreate' => date('Y-m-d H:i:s'),
            'oxregister' => date('Y-m-d H:i:s'),
            'oxbirthdate' => '1980-01-01',
        ]);
        $user->save();

        // Create an initial order
        $this->createInitialOrder();
    }

    protected function tearDown(): void
    {
        $this->cleanUpTable('oxorder');
        $this->cleanUpTable('oxuser');

        parent::tearDown();
    }

    public function testOrdersWithNumberZeroAreNotShownInUserOrderList(): void
    {
        $user = oxNew(EshopModelUser::class);
        $user->load(self::TEST_USER_ID);

        // Should have 1 order (the initial one)
        $orders = $user->getOrders();
        $this->assertCount(1, $orders);

        // Add 3 more orders with numbers > 0
        $this->prepareTestOrder(500);
        $this->prepareTestOrder(600);
        $this->prepareTestOrder(700);

        // Should now have 4 orders
        $orders = $user->getOrders();
        $this->assertCount(4, $orders);

        // Add an order with number 0
        $this->prepareTestOrder(0);

        // Should still be 4 (number 0 is filtered out)
        $orders = $user->getOrders();
        $this->assertCount(4, $orders);
    }

    private function createInitialOrder(): void
    {
        $order = oxNew(EshopModelOrder::class);
        $order->setId('_initial_test_order');

        // Set all fields that might be required
        $orderData = [
            'oxid' => '_initial_test_order',
            'oxshopid' => 1,
            'oxuserid' => self::TEST_USER_ID,
            'oxordernr' => 1000,
            'oxorderdate' => date('Y-m-d H:i:s'),
            'oxsenddate' => '0000-00-00 00:00:00',
            'oxpaid' => '0000-00-00 00:00:00',
            'oxbillcompany' => 'Test Company',
            'oxbillfname' => 'Test',
            'oxbilllname' => 'User',
            'oxbillstreet' => 'Test Street',
            'oxbillstreetnr' => '1',
            'oxbillcity' => 'Test City',
            'oxbillcountryid' => 'a7c40f631fc920687.20179984',
            'oxbillstateid' => '',
            'oxbillzip' => '12345',
            'oxbillfon' => '123456789',
            'oxbillfax' => '',
            'oxbillemail' => 'test@example.com',
            'oxbillsal' => 'MR',
            'oxdelcompany' => 'Test Company',
            'oxdelfname' => 'Test',
            'oxdellname' => 'User',
            'oxdelstreet' => 'Test Street',
            'oxdelstreetnr' => '1',
            'oxdelcity' => 'Test City',
            'oxdelcountryid' => 'a7c40f631fc920687.20179984',
            'oxdelstateid' => '',
            'oxdelzip' => '12345',
            'oxdelfon' => '123456789',
            'oxdelfax' => '',
            'oxdelsal' => 'MR',
            'oxpaymentid' => '',
            'oxpaymenttype' => 'oxidpayadvance',
            'oxremark' => '',
            'oxcurrency' => 'EUR',
            'oxcurrate' => 1,
            'oxtotalnetsum' => 100.00,
            'oxtotalbrutsum' => 119.00,
            'oxtotalordersum' => 119.00,
            'oxvoucherdiscount' => 0,
            'oxdiscount' => 0,
            'oxartvat1' => 19,
            'oxartvatprice1' => 19.00,
            'oxartvat2' => 0,
            'oxartvatprice2' => 0,
            'oxdelcost' => 0,
            'oxdelvat' => 0,
            'oxpaycost' => 0,
            'oxpayvat' => 0,
            'oxwrapcost' => 0,
            'oxwrapvat' => 0,
            'oxgiftcardcost' => 0,
            'oxgiftcardvat' => 0,
            'oxcardid' => '',
            'oxcardtext' => '',
            'oxstorno' => 0,
            'oxfolder' => 'ORDERFOLDER_NEW',
            'oxtransstatus' => 'OK',
            'oxlang' => 0,
            'oxdeltype' => 'oxidstandard',
            'oxtrackcode' => '',
            'oxbillnr' => '',
            'oxtrackurl' => '',
            'oxsendstore' => '',
            'oxip' => '',
            'oxtimestamp' => date('Y-m-d H:i:s'),
        ];

        $order->assign($orderData);
        $order->save();
    }

    private function prepareTestOrder(int $ordernumber): void
    {
        $order = oxNew(EshopModelOrder::class);
        $orderId = '_testorder' . $ordernumber;
        $order->setId($orderId);

        // Use the same complete data
        $orderData = [
            'oxid' => $orderId,
            'oxshopid' => 1,
            'oxuserid' => self::TEST_USER_ID,
            'oxordernr' => $ordernumber,
            'oxorderdate' => date('Y-m-d H:i:s'),
            'oxsenddate' => '0000-00-00 00:00:00',
            'oxpaid' => '0000-00-00 00:00:00',
            'oxbillcompany' => 'Test Company',
            'oxbillfname' => 'Test',
            'oxbilllname' => 'User',
            'oxbillstreet' => 'Test Street',
            'oxbillstreetnr' => '1',
            'oxbillcity' => 'Test City',
            'oxbillcountryid' => 'a7c40f631fc920687.20179984',
            'oxbillstateid' => '',
            'oxbillzip' => '12345',
            'oxbillfon' => '123456789',
            'oxbillfax' => '',
            'oxbillemail' => 'test@example.com',
            'oxbillsal' => 'MR',
            'oxdelcompany' => 'Test Company',
            'oxdelfname' => 'Test',
            'oxdellname' => 'User',
            'oxdelstreet' => 'Test Street',
            'oxdelstreetnr' => '1',
            'oxdelcity' => 'Test City',
            'oxdelcountryid' => 'a7c40f631fc920687.20179984',
            'oxdelstateid' => '',
            'oxdelzip' => '12345',
            'oxdelfon' => '123456789',
            'oxdelfax' => '',
            'oxdelsal' => 'MR',
            'oxpaymentid' => '',
            'oxpaymenttype' => 'oxidpayadvance',
            'oxremark' => '',
            'oxcurrency' => 'EUR',
            'oxcurrate' => 1,
            'oxtotalnetsum' => 100.00,
            'oxtotalbrutsum' => 119.00,
            'oxtotalordersum' => 119.00,
            'oxvoucherdiscount' => 0,
            'oxdiscount' => 0,
            'oxartvat1' => 19,
            'oxartvatprice1' => 19.00,
            'oxartvat2' => 0,
            'oxartvatprice2' => 0,
            'oxdelcost' => 0,
            'oxdelvat' => 0,
            'oxpaycost' => 0,
            'oxpayvat' => 0,
            'oxwrapcost' => 0,
            'oxwrapvat' => 0,
            'oxgiftcardcost' => 0,
            'oxgiftcardvat' => 0,
            'oxcardid' => '',
            'oxcardtext' => '',
            'oxstorno' => 0,
            'oxfolder' => 'ORDERFOLDER_NEW',
            'oxtransstatus' => 'OK',
            'oxlang' => 0,
            'oxdeltype' => 'oxidstandard',
            'oxtrackcode' => '',
            'oxbillnr' => '',
            'oxtrackurl' => '',
            'oxsendstore' => '',
            'oxip' => '',
            'oxtimestamp' => date('Y-m-d H:i:s'),
        ];

        $order->assign($orderData);
        $order->save();
    }
}
