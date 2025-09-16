<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Service;


use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopIdCalculator;
use OxidEsales\EshopCommunity\Core\Field as FieldAlias;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use PHPUnit\Framework\TestCase;

class UserAddressPaypalServiceTest extends TestCase
{
    use ContainerTrait;

    public function testChangePayPalUserData(): void
    {
        $userService = Registry::get(ServiceFactory::class)
            ->getUserAddressPaypalService();

        $userId = 'user_test_id';

        $oUser = $this->createUser($userId);

        $testUser = oxNew(User::class);

        $testUser->load($userId);

        $this->assertEquals($userId, $testUser->oxuser__oxid->value);

        $newInvoiceAddress = [
            'oxaddress__oxuserid' => $userId,
            'oxaddress__oxfname' => 'Marc',
            'oxaddress__oxlname' => 'Mustermann',
            'oxaddress__oxstreet' => 'Beethoven',
            'oxaddress__oxstreetnr' => '6',
            'oxaddress__oxcity' => 'Leiopzig',
            'oxaddress__oxzip' => '45612',
            'oxaddress__oxcountryid' => 'DE'
        ];

        $oAddress = oxNew(Address::class);
        $oAddress->assign($newInvoiceAddress);
        $oAddress->save();

        $newDeliveryAddress = [
            'oxaddress__oxuserid' => $userId,
            'oxaddress__oxfname' => 'Johan',
            'oxaddress__oxlname' => 'Mozart',
            'oxaddress__oxstreet' => 'Bluemenstrasse',
            'oxaddress__oxstreetnr' => '6',
            'oxaddress__oxcity' => 'Leiopzig',
            'oxaddress__oxzip' => '45612',
            'oxaddress__oxcountryid' => 'AT'
        ];

        $userService->changePayPalUserData($oUser, $newInvoiceAddress, $newDeliveryAddress);

        $addresses = $this->getAddressFromDbByUserId($userId);

        if (empty($addresses)) {
            $this->fail('User address not found in database');
        }

        $this->assertEquals($newInvoiceAddress['oxaddress__oxfname'], $addresses['OXFNAME']);
        $this->assertEquals($newInvoiceAddress['oxaddress__oxlname'], $addresses['OXLNAME']);
        $this->assertEquals($newInvoiceAddress['oxaddress__oxstreet'], $addresses['OXSTREET']);
        $this->assertEquals($newInvoiceAddress['oxaddress__oxstreetnr'], $addresses['OXSTREETNR']);
        $this->assertEquals($newInvoiceAddress['oxaddress__oxcity'], $addresses['OXCITY']);
        $this->assertEquals($newInvoiceAddress['oxaddress__oxzip'], $addresses['OXZIP']);
        $this->assertEquals($newInvoiceAddress['oxaddress__oxcountryid'], $addresses['OXCOUNTRYID']);

    }

    private function getAddressFromDbByUserId(string $userId)
    {
        $queryBuilder = $this->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder->select('*')
            ->from('oxaddress')
            ->where('oxuserid = :oxuserid');

        $result = $queryBuilder->setParameters([
            'oxuserid' => $userId
        ])->execute();
        $row = $result->fetchAssociative();

        return $row;
    }

    private function createUser(string $userId): User
    {
        $oUser = oxNew(User::class);
        $oUser->setId($userId);

        $userArray = [
            'oxuser__oxid' => $userId,
            'oxuser__oxusername' => 'email@oxiddev.de',
            'oxuser__oxcompany' => 'company',
            'oxuser__oxfname' => 'fname',
            'oxuser__oxlname' => 'lname',
            'oxuser__oxstreet' => 'street',
            'oxuser__oxstreetnr' => 'streetnr',
            'oxuser__oxaddinfo' => 'addinfo',
            'oxuser__oxustid' => 'ustid',
            'oxuser__oxcity' => 'city',
            'oxuser__oxcountryid' => 'countryid',
            'oxuser__oxstateid' => 'statid',
            'oxuser__oxzip' => 'zip',
            'oxuser__oxfon' => 'fon',
            'oxuser__oxfax' => 'fax',
            'oxuser__oxsal' => 'sal',
            'oxuser__oxpassword' => 'c630e7f6dd47f9ad60ece4492468149bfed3da3429940181464baae99941d0ffa55'
                . '62aaecd01eab71c4d886e5467c5fc4dd24a45819e125501f030f61b624d7d'

        ];

        $oUser->assign($userArray);

        $oUser->save();

        return $oUser;
    }
}
