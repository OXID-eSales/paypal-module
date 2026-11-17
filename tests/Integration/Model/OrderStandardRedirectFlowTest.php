<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Model;

use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Model\Order as PayPalOrder;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;

/**
 * PayPal Standard in the redirect flow (module setting oscPayPalStandardCheckoutFlow = redirect):
 * submitting the shop's order form creates the PayPal order and redirects the customer to PayPal.
 */
final class OrderStandardRedirectFlowTest extends BaseTestCase
{
    private const TEST_ORDER_ID = '_testredirectorder';
    private const PAYPAL_APPROVE_LINK = 'https://www.sandbox.paypal.com/checkoutnow?token=5O190127TN364715T';

    protected function setUp(): void
    {
        parent::setUp();

        // vaultPayment is read from $_POST via the request; earlier tests may have left a request mock
        Registry::set(Request::class, oxNew(Request::class));
        Registry::getSession()->deleteVariable('isPayPalPaymentCheckout');
        PayPalSession::unsetSessionRedirectLink();
        unset($_POST['vaultPayment'], $_POST['oscPayPalPaymentTypeForVaulting'], $_GET['vaultPayment']);
    }

    protected function tearDown(): void
    {
        Registry::getSession()->deleteVariable('isPayPalPaymentCheckout');
        PayPalSession::unsetSessionRedirectLink();
        unset($_POST['vaultPayment'], $_POST['oscPayPalPaymentTypeForVaulting'], $_GET['vaultPayment']);
        $this->cleanUpTable('oxorder');

        parent::tearDown();
    }

    public function testRedirectFlowStartsThePayPalPaymentAndRemembersTheApproveLink(): void
    {
        $order = $this->prepareOrder();
        $paymentService = $this->createPaymentServiceMock();
        $paymentService->expects($this->once())
            ->method('doExecuteStandardRedirectPayment')
            ->with($this->identicalTo($order), $this->isInstanceOf(EshopModelBasket::class))
            ->willReturn(self::PAYPAL_APPROVE_LINK);
        $order->setPaymentService($paymentService);
        $order->setModuleSettings($this->createModuleSettingsMock(true));

        $result = $this->executePayment($order, $this->createBasket());

        $this->assertSame(PayPalOrder::ORDER_STATE_SESSIONPAYMENT_INPROGRESS, $result);
        $this->assertSame(self::PAYPAL_APPROVE_LINK, PayPalSession::getSessionRedirectLink());
        // the PayPal order carries the order number, so it is set before the PayPal call
        $this->assertTrue($order->hasOrderNumber());
    }

    public function testAjaxCheckoutKeepsTheProxyControllerBranchInRedirectMode(): void
    {
        // a vaulted PayPal account is still paid via the AJAX flow, which sets the flag
        Registry::getSession()->setVariable('isPayPalPaymentCheckout', true);

        $order = $this->prepareOrder();
        $paymentService = $this->createPaymentServiceMock();
        $paymentService->expects($this->never())->method('doExecuteStandardRedirectPayment');
        $order->setPaymentService($paymentService);
        $order->setModuleSettings($this->createModuleSettingsMock(true));

        $this->assertTrue($this->executePayment($order, $this->createBasket()));
        $this->assertSame('', PayPalSession::getSessionRedirectLink());
    }

    public function testOrderFormSubmitIsRefusedInPopupMode(): void
    {
        $order = $this->prepareOrder();
        $paymentService = $this->createPaymentServiceMock();
        $paymentService->expects($this->never())->method('doExecuteStandardRedirectPayment');
        $order->setPaymentService($paymentService);
        $order->setModuleSettings($this->createModuleSettingsMock(false));

        $this->assertSame(
            PayPalOrder::ORDER_STATE_PAYMENTERROR,
            $this->executePayment($order, $this->createBasket())
        );
    }

    public function testFailingPayPalOrderCreationEndsInPaymentError(): void
    {
        $order = $this->prepareOrder();
        $paymentService = $this->createPaymentServiceMock();
        $paymentService->method('doExecuteStandardRedirectPayment')
            ->willThrowException(PayPalException::createPayPalOrderFail());
        $order->setPaymentService($paymentService);
        $order->setModuleSettings($this->createModuleSettingsMock(true));

        $result = $this->executePayment($order, $this->createBasket());

        $this->assertSame(PayPalOrder::ORDER_STATE_PAYMENTERROR, $result);
        $this->assertSame('', PayPalSession::getSessionRedirectLink());
    }

    public function testVaultingIsPassedOnForACustomerWhoMayVault(): void
    {
        $_POST['vaultPayment'] = 'true';

        $vaultRequestDuringPayPalCall = null;
        $order = $this->prepareOrder();
        $paymentService = $this->createPaymentServiceMock();
        $paymentService->method('doExecuteStandardRedirectPayment')
            ->willReturnCallback(function () use (&$vaultRequestDuringPayPalCall) {
                $vaultRequestDuringPayPalCall = [
                    $_POST['vaultPayment'] ?? null,
                    $_POST['oscPayPalPaymentTypeForVaulting'] ?? null
                ];
                return self::PAYPAL_APPROVE_LINK;
            });
        $order->setPaymentService($paymentService);
        $order->setModuleSettings($this->createModuleSettingsMock(true, true));

        $this->executePayment($order, $this->createBasket($this->createUser('password-hash')));

        $this->assertSame(['true', PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID], $vaultRequestDuringPayPalCall);
    }

    /**
     * @dataProvider vaultingNotPossibleProvider
     */
    public function testVaultingRequestIsDroppedWhenTheCustomerMayNotVault(
        string $password,
        bool $vaultingAllowed
    ): void {
        $_POST['vaultPayment'] = 'true';

        $vaultRequestDuringPayPalCall = 'not called';
        $order = $this->prepareOrder();
        $paymentService = $this->createPaymentServiceMock();
        $paymentService->method('doExecuteStandardRedirectPayment')
            ->willReturnCallback(function () use (&$vaultRequestDuringPayPalCall) {
                $vaultRequestDuringPayPalCall = $_POST['vaultPayment'] ?? null;
                return self::PAYPAL_APPROVE_LINK;
            });
        $order->setPaymentService($paymentService);
        $order->setModuleSettings($this->createModuleSettingsMock(true, $vaultingAllowed));

        $this->executePayment($order, $this->createBasket($this->createUser($password)));

        $this->assertNull($vaultRequestDuringPayPalCall);
    }

    public static function vaultingNotPossibleProvider(): array
    {
        return [
            'guest without password' => ['', true],
            'vaulting not allowed for PayPal' => ['password-hash', false],
        ];
    }

    /**
     * @return int|string|bool
     */
    private function executePayment(PayPalOrder $order, EshopModelBasket $basket)
    {
        $method = new ReflectionMethod($order, 'executePayment');
        $method->setAccessible(true);

        return $method->invoke($order, $basket, null);
    }

    private function prepareOrder(): PayPalOrder
    {
        $order = oxNew(EshopModelOrder::class);
        $order->setId(self::TEST_ORDER_ID);
        $order->assign(['oxpaymenttype' => PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID]);
        $order->save();
        $order->load(self::TEST_ORDER_ID);

        return $order;
    }

    private function createBasket(?EshopModelUser $user = null): EshopModelBasket
    {
        $basket = oxNew(EshopModelBasket::class);
        if ($user) {
            $basket->setBasketUser($user);
        }

        return $basket;
    }

    private function createUser(string $password): EshopModelUser
    {
        $user = oxNew(EshopModelUser::class);
        $user->assign([
            'oxpassword' => $password,
            // no PayPal customer id: the customer has no vaulted PayPal account yet
            'oscpaypalcustomerid' => ''
        ]);

        return $user;
    }

    /**
     * @return PaymentService&MockObject
     */
    private function createPaymentServiceMock(): PaymentService
    {
        $paymentService = $this->getMockBuilder(PaymentService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSessionPaymentId', 'doExecuteStandardRedirectPayment'])
            ->getMock();
        $paymentService->method('getSessionPaymentId')
            ->willReturn(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID);

        return $paymentService;
    }

    /**
     * @return ModuleSettings&MockObject
     */
    private function createModuleSettingsMock(bool $redirectFlow, bool $vaultingAllowed = false): ModuleSettings
    {
        $moduleSettings = $this->createMock(ModuleSettings::class);
        $moduleSettings->method('isPayPalStandardRedirectFlow')->willReturn($redirectFlow);
        $moduleSettings->method('isVaultingAllowedForPayment')->willReturn($vaultingAllowed);

        return $moduleSettings;
    }
}
