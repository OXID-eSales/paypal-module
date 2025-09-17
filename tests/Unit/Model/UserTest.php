<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Model;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use OxidSolutionCatalysts\PayPal\Model\User;
use OxidSolutionCatalysts\PayPal\Exception\UserPhone as UserPhoneException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Phone as ApiModelPhone;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\Country;

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
            'phonenumber' => '+49123456789'
        ];

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->method('getRequestParameter')->willReturn($puiRequired);

        Registry::set(Request::class, $request);

        $user = oxNew(User::class);

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
            'phonenumber' => '+49123456789'
        ];

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->method('getRequestParameter')->willReturn($puiRequired);

        Registry::set(Request::class, $request);

        $user = $this->getMockBuilder(User::class)
            ->onlyMethods([])
            ->getMock();

        $this->assertNull($user->getBirthDateForPuiRequest());
    }

    public function testPuiPhone(): void
    {
        $this->markTestSkipped('This test needs database access and should be moved to integration tests');
    }

    public function testPuiPhoneWithCountryPrefix(): void
    {
        $this->markTestSkipped('This test needs database access and should be moved to integration tests');
    }

    public function testPuiPhoneInvalid(): void
    {
        $this->markTestSkipped('This test needs database access and should be moved to integration tests');
    }
}
