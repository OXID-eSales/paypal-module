<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Model;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidSolutionCatalysts\PayPal\Exception\UserPhone as UserPhoneException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Phone as ApiModelPhone;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Registry;

final class UserTest extends TestCase
{
    public function testPuiBirthDate(): void
    {
        $puiRequired = [
            'birthdate' => [
                'day' => 1,
                'month' => 4,
                'year' => 2000
            ],
        ];

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->method('getRequestParameter')->willReturn($puiRequired);

        Registry::set(Request::class, $request);

        $user = oxNew(EshopModelUser::class);

        $this->assertSame('2000-04-01', $user->getBirthDateForPuiRequest());
    }

    public function testPuiBirthDateNotSet(): void
    {
        $puiRequired = [
            'birthdate' => [
                'day' => null,
                'month' => null,
                'year' => null
            ],
        ];

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->method('getRequestParameter')->willReturn($puiRequired);

        Registry::set(Request::class, $request);

        $user = oxNew(EshopModelUser::class);

        $this->assertNull($user->getBirthDateForPuiRequest());
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
        $user->assign([
            'oxcountryid' => 'a7c40f631fc920687.20179984'
        ]);

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
        $user->assign(
            [
                'oxcountryid' => 'a7c40f631fc920687.20179984'
            ]
        );

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

        $this->expectException(UserPhoneException::class);
        $this->expectExceptionMessage(UserPhoneException::byRequestData()->getMessage());

        $user->getPhoneNumberForPuiRequest();
    }
}
