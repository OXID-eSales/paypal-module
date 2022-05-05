<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Model;

use DateTimeImmutable;
use OxidEsales\TestingLibrary\UnitTestCase;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidSolutionCatalysts\PayPal\Exception\UserPhone as UserPhoneException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Phone as ApiModelPhone;

final class UserTest extends UnitTestCase
{
    public function testPuiBirthDate(): void
    {
        $this->setRequestParameter(
            'pui_required',
            [
                    'birthdate' => [
                        'day' => 1,
                        'month' => 4,
                        'year' => 2000
                    ],
                ]
        );

        $user = oxNew(EshopModelUser::class);

        $this->assertSame('2000-04-01', $user->getBirthDateForPuiRequest());
    }

    public function testPuiBirthDateNotSet(): void
    {
        $this->setRequestParameter(
            'pui_required',
            [
                'birthdate' => [
                    'day' => null,
                    'month' => null,
                    'year' => null
                ],
            ]
        );

        $user = oxNew(EshopModelUser::class);

        $this->assertNull($user->getBirthDateForPuiRequest());
    }

    public function testPuiPhone(): void
    {
        $this->setRequestParameter(
            'pui_required',
            [
                'phonenumber' => '040 111222333'
            ]
        );

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

    public function testPuiPhoneWithCountryPrefix(): void
    {
        $this->setRequestParameter(
            'pui_required',
            [
                'phonenumber' => '+49 40 111222333'
            ]
        );

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
        $this->setRequestParameter(
            'pui_required',
            [
                'phonenumber' => 'NO_PHONE'
            ]
        );

        $user = oxNew(EshopModelUser::class);

        $this->expectException(UserPhoneException::class);
        $this->expectExceptionMessage(UserPhoneException::byRequestData()->getMessage());

        $user->getPhoneNumberForPuiRequest();
    }
}
