<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Model;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidEsales\Eshop\Application\Model\Country as EshopModelCountry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Exception\UserPhone as UserPhoneException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Phone as ApiModelPhone;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;

final class UserTest extends BaseTestCase
{
    private const TEST_USER_ID = 'testuser';
    private const ORDER_TEMPLATE_ID = '7d090db46a124f48cb7e6836ceef3f66';

    protected function tearDown(): void
    {
        $this->cleanUpTable('oxorder');
        $this->cleanUpTable('oxcountry');
        $this->cleanUpTable('oxuser');

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
                 'oxorderdate' => date('Y-m-d h:i:s'),
                 'oxsenddate' => '',
             ]
         );

         $order->save();
    }

    public function testPuiPhone(): void
    {
        $puiRequired = [
            'phonenumber' => '040 111222333'
        ];

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->method('getRequestParameter')->willReturn($puiRequired);

        Registry::set(Request::class, $request);

        $user = oxNew(EshopModelUser::class);
        $user->setId('_test_user_id');
        $user->assign([
            'oxcountryid' => '_test_country_id'
        ]);

        $country = oxNew(EshopModelCountry::class);
        $country->setId('_test_country_id');
        $country->assign([
            'oxisoalpha2' => 'DE',
            'oxphone' => '49'
        ]);
        $country->save();

        /** @var ApiModelPhone $apiPhone */
        $apiPhone = $user->getPhoneNumberForPuiRequest();

        $this->assertInstanceOf(ApiModelPhone::class, $apiPhone);
        $this->assertEquals('49', $apiPhone->country_code);
        $this->assertEquals('40111222333', $apiPhone->national_number);
    }

    public function testPuiPhoneWithCountryPrefix(): void
    {
        $puiRequired = [
            'phonenumber' => '+49 40 111222333'
        ];

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->method('getRequestParameter')->willReturn($puiRequired);

        Registry::set(Request::class, $request);

        $user = oxNew(EshopModelUser::class);
        $user->setId('_test_user_id2');
        $user->assign([
            'oxcountryid' => 'a7c40f631fc920687.20179984'
        ]);

        /** @var ApiModelPhone $apiPhone */
        $apiPhone = $user->getPhoneNumberForPuiRequest();

        $this->assertInstanceOf(ApiModelPhone::class, $apiPhone);
        $this->assertEquals('49', $apiPhone->country_code);
        $this->assertEquals('40111222333', $apiPhone->national_number);
    }

    public function testPuiPhoneInvalid(): void
    {
        $puiRequired = [
            'phonenumber' => 'NO_PHONE'
        ];

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->method('getRequestParameter')->willReturn($puiRequired);

        Registry::set(Request::class, $request);

        $user = oxNew(EshopModelUser::class);
        $user->setId('_test_user_id3');
        $user->assign([
            'oxcountryid' => '_test_country_id'
        ]);

        $country = oxNew(EshopModelCountry::class);
        $country->setId('_test_country_id');
        $country->assign([
            'oxisoalpha2' => 'DE'
        ]);
        $country->save();

        $this->expectException(UserPhoneException::class);
        $this->expectExceptionMessage(UserPhoneException::byRequestData()->getMessage());

        $user->getPhoneNumberForPuiRequest();
    }
}
