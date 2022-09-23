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

final class OrderTest extends BaseTestCase
{
    private const TEST_USER_ID = 'e7af1c3b786fd02906ccd75698f4e6b9';
    private const ORDER_TEMPLATE_ID = '7d090db46a124f48cb7e6836ceef3f66';

    protected function tearDown(): void
    {
        $this->cleanUpTable('oxorder');

        parent::tearDown();
    }

    public function testOrdersWithNumberZeroAreNotShownInUserOrderList(): void
    {
        $user = oxNew(EshopModelUser::class);
        $user->load(self::TEST_USER_ID);

        $orders = $user->getOrders();
        $this->assertCount(1, $orders);

        $this->prepareTestOrder(500);
        $this->prepareTestOrder(600);
        $this->prepareTestOrder(700);

        $orders = $user->getOrders();
        $this->assertCount(4, $orders);

        $this->prepareTestOrder(0);
        $this->assertCount(4, $orders);
    }

    private function prepareTestOrder(int $ordernumber): void
    {
         $order = oxNew(EshopModelOrder::class);
         $order->load(self::ORDER_TEMPLATE_ID);
         $order->assign(
             [
                 'oxid' => '_testorder' . $ordernumber,
                 'oxuserid' => self::TEST_USER_ID,
                 'oxordernr' => $ordernumber,
                 'oxorderdate' => date('Y-m-d h:i:s')
             ]
         );

         $order->save();
    }
}
