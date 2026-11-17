<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Service;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Service\Factory\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\OrderProcessTrackingService;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Service\SCAValidatorInterface;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPalApi\Service\Orders;
use OxidSolutionCatalysts\PayPalApi\Service\Payments;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * The thank-you page tells the customer whether the PayPal account was vaulted
 * (session variable vaultSuccess). It has to report what really happened: the
 * capture used to report a failure even after the PayPal customer id was saved.
 */
final class PaymentVaultingResultTest extends BaseTestCase
{
    private const PAYPAL_ORDER_ID = '5O190127TN364715T';
    private const PAYPAL_CUSTOMER_ID = 'wxj1234567890';

    private ?ServiceFactory $originalServiceFactory = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalServiceFactory = Registry::get(ServiceFactory::class);
        Registry::getSession()->deleteVariable('vaultSuccess');
    }

    protected function tearDown(): void
    {
        Registry::set(ServiceFactory::class, $this->originalServiceFactory);
        Registry::getSession()->deleteVariable('vaultSuccess');

        parent::tearDown();
    }

    public function testSaveCustomerIdToUserReportsTheSavedId(): void
    {
        $user = $this->createMock(EshopModelUser::class);
        $user->expects($this->once())->method('save')->willReturn('userid');

        $this->assertTrue(
            $this->createPaymentService($this->createMock(Orders::class))
                ->saveCustomerIdToUser(self::PAYPAL_CUSTOMER_ID, $user)
        );
    }

    public function testSaveCustomerIdToUserReportsAFailedSave(): void
    {
        $user = $this->createMock(EshopModelUser::class);
        $user->method('save')->willReturn(false);

        $this->assertFalse(
            $this->createPaymentService($this->createMock(Orders::class))
                ->saveCustomerIdToUser(self::PAYPAL_CUSTOMER_ID, $user)
        );
    }

    /**
     * @dataProvider vaultResultProvider
     */
    public function testCaptureReportsTheVaultingResult(bool $customerIdSaved): void
    {
        $orderService = $this->createMock(Orders::class);
        $orderService->expects($this->once())
            ->method('capturePaymentForOrder')
            ->willReturn($this->createCapturedApiOrder());

        $paymentService = $this->createPaymentService($orderService, ['trackPayPalOrder', 'saveCustomerIdToUser']);
        $paymentService->expects($this->once())
            ->method('saveCustomerIdToUser')
            ->with(self::PAYPAL_CUSTOMER_ID)
            ->willReturn($customerIdSaved);

        $order = $this->createOrderMock();
        $order->expects($this->once())->method('markOrderPaid');

        $paymentService->doCapturePayPalOrder(
            $order,
            self::PAYPAL_ORDER_ID,
            PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID,
            $this->createApprovedApiOrder()
        );

        $this->assertSame($customerIdSaved, Registry::getSession()->getVariable('vaultSuccess'));
    }

    public static function vaultResultProvider(): array
    {
        return [
            'customer id saved' => [true],
            'customer id not saved' => [false],
        ];
    }

    private function createApprovedApiOrder(): PayPalApiOrder
    {
        return new PayPalApiOrder([
            'id' => self::PAYPAL_ORDER_ID,
            'intent' => 'CAPTURE',
            'status' => 'APPROVED',
            'payment_source' => ['paypal' => ['email_address' => 'buyer@example.com']],
        ]);
    }

    private function createCapturedApiOrder(): PayPalApiOrder
    {
        return new PayPalApiOrder([
            'id' => self::PAYPAL_ORDER_ID,
            'intent' => 'CAPTURE',
            'status' => 'COMPLETED',
            'payment_source' => [
                'paypal' => [
                    'email_address' => 'buyer@example.com',
                    'attributes' => [
                        'vault' => [
                            'id' => 'token-id',
                            'status' => 'VAULTED',
                            'customer' => ['id' => self::PAYPAL_CUSTOMER_ID],
                        ],
                    ],
                ],
            ],
            'purchase_units' => [
                ['payments' => ['captures' => [['id' => 'CAPTURE-ID', 'status' => 'COMPLETED']]]],
            ],
        ]);
    }

    /**
     * @return EshopModelOrder&MockObject
     */
    private function createOrderMock()
    {
        // the class oxNew() builds is the top of the module chain, it has the PayPal order methods
        $order = $this->getMockBuilder(get_class(oxNew(EshopModelOrder::class)))
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'isPayPalOrderCompleted', 'markOrderPaid', 'setTransId'])
            ->getMock();
        $order->method('getId')->willReturn('_testvaultorder');
        $order->method('isPayPalOrderCompleted')->willReturn(true);

        return $order;
    }

    /**
     * @return PaymentService&MockObject
     */
    private function createPaymentService(Orders $orderService, array $mockedMethods = ['trackPayPalOrder'])
    {
        $serviceFactory = $this->createMock(ServiceFactory::class);
        $serviceFactory->method('getOrderService')->willReturn($orderService);
        $serviceFactory->method('getPaymentService')->willReturn($this->createMock(Payments::class));
        // doCapturePayPalOrder() fetches the API payment service from the registry
        Registry::set(ServiceFactory::class, $serviceFactory);

        $scaValidator = $this->createMock(SCAValidatorInterface::class);
        $scaValidator->method('verify3D')->willReturn(true);

        return $this->getMockBuilder(PaymentService::class)
            ->setConstructorArgs([
                Registry::getSession(),
                $this->createMock(OrderRepository::class),
                $scaValidator,
                $this->createMock(ModuleSettings::class),
                $this->createMock(LoggerInterface::class),
                $this->createMock(OrderProcessTrackingService::class),
                $serviceFactory,
                Registry::get(PatchRequestFactory::class),
                $this->createMock(OrderRequestFactory::class)
            ])
            ->onlyMethods($mockedMethods)
            ->getMock();
    }
}
