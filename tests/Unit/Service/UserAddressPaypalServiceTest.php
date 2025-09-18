<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Service;


use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;

class UserAddressPaypalServiceTest extends UnitTestCase
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
        $invoiceAddressOxid = $oAddress->getId();

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

        $addresses = $this->getAddressesFromDbByUserId($userId);

        $invoiceAddressIndex = 0;
        foreach ($addresses as $index => $address) {
            if ($address['OXID'] === $invoiceAddressOxid) {
                $invoiceAddressIndex = $index;
                break;
            }
        }

        if (empty($addresses)) {
            $this->fail('User address not found in database');
        }

        $this->assertEquals(2, count($addresses));

        $this->assertEquals($newInvoiceAddress['oxaddress__oxfname'], $addresses[$invoiceAddressIndex]['OXFNAME']);
        $this->assertEquals($newInvoiceAddress['oxaddress__oxlname'], $addresses[$invoiceAddressIndex]['OXLNAME']);
        $this->assertEquals($newInvoiceAddress['oxaddress__oxstreet'], $addresses[$invoiceAddressIndex]['OXSTREET']);
        $this->assertEquals($newInvoiceAddress['oxaddress__oxstreetnr'], $addresses[$invoiceAddressIndex]['OXSTREETNR']);
        $this->assertEquals($newInvoiceAddress['oxaddress__oxcity'], $addresses[$invoiceAddressIndex]['OXCITY']);
        $this->assertEquals($newInvoiceAddress['oxaddress__oxzip'], $addresses[$invoiceAddressIndex]['OXZIP']);
        $this->assertEquals($newInvoiceAddress['oxaddress__oxcountryid'], $addresses[$invoiceAddressIndex]['OXCOUNTRYID']);

        unset($addresses[$invoiceAddressIndex]);
        $deliveryAddress = array_shift($addresses);
        $this->assertEquals($newDeliveryAddress['oxaddress__oxfname'], $deliveryAddress['OXFNAME']);
        $this->assertEquals($newDeliveryAddress['oxaddress__oxlname'], $deliveryAddress['OXLNAME']);
        $this->assertEquals($newDeliveryAddress['oxaddress__oxstreet'], $deliveryAddress['OXSTREET']);
        $this->assertEquals($newDeliveryAddress['oxaddress__oxstreetnr'], $deliveryAddress['OXSTREETNR']);
        $this->assertEquals($newDeliveryAddress['oxaddress__oxcity'], $deliveryAddress['OXCITY']);
        $this->assertEquals($newDeliveryAddress['oxaddress__oxzip'], $deliveryAddress['OXZIP']);
        $this->assertEquals($newDeliveryAddress['oxaddress__oxcountryid'], $deliveryAddress['OXCOUNTRYID']);
    }

    private function getAddressesFromDbByUserId(string $userId)
    {
        $queryBuilder = $this->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder->select('*')
            ->from('oxaddress')
            ->where('oxuserid = :oxuserid');

        $result = $queryBuilder->setParameters([
            'oxuserid' => $userId
        ])->execute();

        return  $result->fetchAllAssociative();
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
