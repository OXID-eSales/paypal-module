<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Core;

use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Utils\SessionDeliveryAddress;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;

final class SessionDeliveryAddressTest extends BaseTestCase
{
    private const USER_ID = '_testsessiondeladruser';
    private const ADDRESS_ID = '_testsessiondeladr';

    protected function tearDown(): void
    {
        $this->cleanUpTable('oxaddress');
        Registry::getSession()->deleteVariable('deladrid');
        Registry::getConfig()->setUser(null);

        parent::tearDown();
    }

    public function testReturnsNullWithoutDeliveryAddress(): void
    {
        $this->setActiveUser();
        Registry::getSession()->deleteVariable('deladrid');

        $this->assertNull(SessionDeliveryAddress::get());
    }

    public function testReturnsNullForAddressThatCannotBeLoaded(): void
    {
        // before OXID 7.6 the core hands out an empty address object in this case
        $this->setActiveUser();
        Registry::getSession()->setVariable('deladrid', '_testnonexistingaddress');

        $this->assertNull(SessionDeliveryAddress::get());
    }

    public function testReturnsAddressOfActiveUser(): void
    {
        $this->setActiveUser();
        $this->createAddress(self::USER_ID);
        Registry::getSession()->setVariable('deladrid', self::ADDRESS_ID);

        $address = SessionDeliveryAddress::get();

        $this->assertInstanceOf(Address::class, $address);
        $this->assertSame(self::ADDRESS_ID, $address->getId());
        $this->assertSame('Teststreet', $address->getFieldData('oxstreet'));
    }

    private function setActiveUser(): void
    {
        $user = oxNew(User::class);
        $user->setId(self::USER_ID);
        Registry::getConfig()->setUser($user);
    }

    private function createAddress(string $userId): void
    {
        $address = oxNew(Address::class);
        $address->setId(self::ADDRESS_ID);
        $address->assign([
            'oxuserid' => $userId,
            'oxfname' => 'Test',
            'oxlname' => 'User',
            'oxstreet' => 'Teststreet',
            'oxstreetnr' => '1',
            'oxzip' => '12345',
            'oxcity' => 'Testcity',
            'oxcountryid' => 'testcountry_de',
        ]);
        $address->save();
    }
}
