<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Controller;

use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Controller\ProxyController;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use ReflectionMethod;

/**
 * Google Pay / Apple Pay for visitors who are not logged in (0008026).
 */
final class ProxyControllerTest extends BaseTestCase
{
    private const CUSTOMER_ID = '_test_wallet_customer';
    private const CUSTOMER_EMAIL = 'wallet-customer@example.test';
    private const CUSTOMER_STREET = 'Customer Street';

    protected function setUp(): void
    {
        parent::setUp();

        $user = oxNew(EshopModelUser::class);
        $user->setId(self::CUSTOMER_ID);
        $user->assign([
            'oxactive' => 1,
            'oxshopid' => 1,
            'oxrights' => 'user',
            'oxusername' => self::CUSTOMER_EMAIL,
            'oxpassword' => md5('password'),
            'oxfname' => 'Wallet',
            'oxlname' => 'Customer',
            'oxstreet' => self::CUSTOMER_STREET,
            'oxstreetnr' => '1',
            'oxcity' => 'Berlin',
            'oxzip' => '10115',
            'oxcountryid' => 'a7c40f631fc920687.20179984',
        ]);
        $user->save();

        // The automatic login is on by default - the request e-mail must not use it anyway.
        $this->updateModuleConfiguration('oscPayPalLoginWithPayPalEMail', true);

        $session = Registry::getSession();
        $session->deleteVariable('usr');
        $session->deleteVariable('oscpaypal_payment_redirect');
    }

    protected function tearDown(): void
    {
        $session = Registry::getSession();
        $session->deleteVariable('usr');
        $session->deleteVariable('oscpaypal_payment_redirect');
        $this->cleanUpTable('oxuser');

        parent::tearDown();
    }

    public function testEmailOfExistingCustomerFromRequestDoesNotLogIn(): void
    {
        $controller = oxNew(ProxyController::class);
        $response = new PayPalApiOrder();
        $response->id = 'TESTPAYPALORDERID';

        $accountExists = $this->prepareWalletVisitorWithoutLogin(
            $controller,
            $response,
            self::CUSTOMER_EMAIL
        );

        $session = Registry::getSession();
        $this->assertTrue($accountExists);
        $this->assertEmpty($session->getVariable('usr'));
        $this->assertFalse($controller->getUser());
        $this->assertTrue($session->getVariable('oscpaypal_payment_redirect'));

        // Nothing from the request was written into the customer account.
        $customer = oxNew(EshopModelUser::class);
        $customer->load(self::CUSTOMER_ID);
        $this->assertSame(self::CUSTOMER_STREET, $customer->getFieldData('oxstreet'));
    }

    public function testWalletDataIsPutIntoResponse(): void
    {
        $controller = oxNew(ProxyController::class);
        $response = new PayPalApiOrder();
        $response->id = 'TESTPAYPALORDERID';

        // Uses the existing customer, so no guest account is created here.
        $this->prepareWalletVisitorWithoutLogin($controller, $response, self::CUSTOMER_EMAIL);

        // Used to fail on PHP 8: shipping and phone are empty on new client objects.
        $this->assertSame('Test Visitor', $response->purchase_units[0]->shipping->name->full_name);
        $this->assertSame('Teststr 1', $response->purchase_units[0]->shipping->address->address_line_1);
        $this->assertSame(self::CUSTOMER_EMAIL, $response->payer->email_address);
        $this->assertSame('030123456', $response->payer->phone->phone_number->national_number);
    }

    private function prepareWalletVisitorWithoutLogin(
        ProxyController $controller,
        PayPalApiOrder $response,
        string $email
    ): bool {
        $shippingAddress = new AddressPortable();
        $shippingAddress->address_line_1 = 'Teststr 1';
        $shippingAddress->postal_code = '10115';
        $shippingAddress->admin_area_2 = 'Berlin';
        $shippingAddress->country_code = 'DE';

        $method = new ReflectionMethod(ProxyController::class, 'prepareWalletVisitorWithoutLogin');
        $method->setAccessible(true);

        return $method->invoke(
            $controller,
            $response,
            $email,
            $shippingAddress,
            'Test Visitor',
            '030123456'
        );
    }
}
