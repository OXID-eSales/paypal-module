<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Service;


use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use PHPUnit\Framework\TestCase;

class PaypalUserDataServiceTest extends TestCase
{
    public function testChangePayPalUserData(): void
    {
        $userService = Registry::get(ServiceFactory::class)
            ->getPaypalUserDataService();

        $this->assertInstanceOf(PaypalUserDataHelper::class, $userService);
        $userId = 'user_test_id';

        $aUserData = [
            'oxuserid' => $userId,
            'oxaddress__oxfname' => 'Marc',
            'oxaddress__oxlname' => 'Mustermann',
            'oxaddress__oxstreet' => 'Hugo-Junkers-Str',
            'oxaddress__oxstreetnr' => '27',
            'oxaddress__oxcity' => 'Frankfurt am Main',
            'oxaddress__oxzip' => '60314',
//            'oxaddress__oxcountryid' => ''
        ];

        $oUser = oxNew(\OxidEsales\Eshop\Application\Model\User::class);
        $oUser->setId($userId);
        $oUser->save();

        $oAddress = oxNew(\OxidEsales\Eshop\Application\Model\Address::class);
        $oAddress->setId('test_address_id');
        $oAddress->assign([]);
        $oAddress->assign($aUserData);
        $oAddress->save();

        $userFromDb = $this->getAddressFromDbByUserId($userId);

        foreach ($aUserData as $key => $value) {
            $this->assertEquals($value, $userFromDb[$key]);
        }

        $newInvoiceAddress = [
            'oxaddress__oxfname' => 'Marc',
            'oxaddress__oxlname' => 'Mustermann',
            'oxaddress__oxstreet' => 'Beethoven',
            'oxaddress__oxstreetnr' => '6',
            'oxaddress__oxcity' => 'Leiopzig',
            'oxaddress__oxzip' => '45612',
//            'oxaddress__oxcountryid' => ''
        ];

        $newDeliveryAddress = [
            'oxaddress__oxfname' => 'Marc',
            'oxaddress__oxlname' => 'Mustermann',
            'oxaddress__oxstreet' => 'Beethoven',
            'oxaddress__oxstreetnr' => '6',
            'oxaddress__oxcity' => 'Leiopzig',
            'oxaddress__oxzip' => '45612',
//            'oxaddress__oxcountryid' => ''
        ];

        $userService->changePayPalUserData($oUser, $newInvoiceAddress, $newDeliveryAddress);

        $userFromDb = $this->getAddressFromDbByUserId($userId);

        foreach ($aUserData as $key => $value) {
            $this->assertEquals($value, $userFromDb[$key]);
        }

    }
}
