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
        $this->setRequestParameter('pui_required_birthdate_day', 1);
        $this->setRequestParameter('pui_required_birthdate_month', 4);
        $this->setRequestParameter('pui_required_birthdate_year', 2000);

        $user = oxNew(EshopModelUser::class);

        $this->assertInstanceOf(DateTimeImmutable::class, $user->getBirthDateForPuiRequest());
        $this->assertSame('2000-04-01', $user->getBirthDateForPuiRequest()->format('Y-m-d'));
    }

    public function testPuiBirthDateNotSet(): void
    {
        $this->setRequestParameter('pui_required_birthdate_day', null);
        $this->setRequestParameter('pui_required_birthdate_month', null);
        $this->setRequestParameter('pui_required_birthdate_year', null);

        $user = oxNew(EshopModelUser::class);

        $this->assertNull($user->getBirthDateForPuiRequest());
    }

    public function testPuiPhone(): void
    {
        $this->setRequestParameter('pui_required_phonenumber', '040 111222333');

        $user = oxNew(EshopModelUser::class);
        $user->assign(
            [
                'oxcountryid' => 'a7c40f631fc920687.20179984'
            ]
        );

        /** @var ApiModelPhone $apiPhone */
        $apiPhone = $user->getPhoneNumberForPuiRequest();

        $this->assertInstanceOf(ApiModelPhone::class, $apiPhone);
        $this->assertEquals('49',  $apiPhone->country_code);
        $this->assertEquals('40111222333',  $apiPhone->national_number);
    }

    public function testPuiPhoneWithCountryPrefix(): void
    {
        $this->setRequestParameter('pui_required_phonenumber', '+49 40 111222333');

        $user = oxNew(EshopModelUser::class);
        $user->assign(
            [
                'oxcountryid' => 'a7c40f631fc920687.20179984'
            ]
        );

        /** @var ApiModelPhone $apiPhone */
        $apiPhone = $user->getPhoneNumberForPuiRequest();

        $this->assertInstanceOf(ApiModelPhone::class, $apiPhone);
        $this->assertEquals('49',  $apiPhone->country_code);
        $this->assertEquals('40111222333',  $apiPhone->national_number);
    }

    public function testPuiPhoneInvalid(): void
    {
        $this->setRequestParameter('pui_required_phonenumber', 'NO_PHONE');

        $user = oxNew(EshopModelUser::class);

        $this->expectException(UserPhoneException::class);
        $this->expectExceptionMessage(UserPhoneException::byRequestData()->getMessage());

        $user->getPhoneNumberForPuiRequest();
    }
}