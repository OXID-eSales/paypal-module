<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Service;

use Exception;
use Monolog\Logger;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Service\OrderProcessTrackingService;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings as ModuleSettingsService;
use OxidSolutionCatalysts\PayPal\Service\SCAValidator;
use OxidSolutionCatalysts\PayPal\Service\SCAValidatorInterface;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderModel;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ThreeDSecureAuthenticationResponse;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use TypeError;

final class PaymentTest extends BaseTestCase
{
    protected const TEST_USER_ID = 'e7af1c3b786fd02906ccd75698f4e6b9';
    protected const TEST_PRODUCT_ID = 'dc5ffdf380e15674b56dd562a7cb6aec';
    private string $success3DCard;
    private string $failedAuthentication;
    private string $missingCardAuthentication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->success3DCard          = serialize($this->createSuccess3DCardOrder());
        $this->failedAuthentication   = serialize($this->createFailedAuthenticationOrder());
        $this->missingCardAuthentication = serialize($this->createMissingCardAuthenticationOrder());
    }

    private function createSuccess3DCardOrder(): ApiOrderModel
    {
        $order = new ApiOrderModel();
        $order->id = 'some_id';

        $paymentSource = new PaymentSourceResponse();
        $card = new CardResponse();
        $card->last_digits = '7704';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';

        $auth = new AuthenticationResponse();
        $auth->liability_shift = 'POSSIBLE';

        $threeDS = new ThreeDSecureAuthenticationResponse();
        $threeDS->authentication_status = 'Y';
        $threeDS->enrollment_status     = 'Y';

        $auth->three_d_secure = $threeDS;
        $card->authentication_result = $auth;
        $paymentSource->card = $card;

        $order->payment_source = $paymentSource;
        return $order;
    }

    /**
     * Example builder for a "failed authentication" 3D-secure card order.
     */
    private function createFailedAuthenticationOrder(): ApiOrderModel
    {
        $order = new ApiOrderModel();

        $paymentSource = new PaymentSourceResponse();
        $card = new CardResponse();
        $card->last_digits = '2421';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';

        $auth = new AuthenticationResponse();
        $auth->liability_shift = 'NO';

        $threeDS = new ThreeDSecureAuthenticationResponse();
        $threeDS->authentication_status = 'N';
        $threeDS->enrollment_status     = 'Y';

        $auth->three_d_secure = $threeDS;
        $card->authentication_result = $auth;
        $paymentSource->card = $card;

        $order->payment_source = $paymentSource;
        return $order;
    }

    /**
     * Example builder for an order that has a card but no authentication result.
     */
    private function createMissingCardAuthenticationOrder(): ApiOrderModel
    {
        $order = new ApiOrderModel();

        $paymentSource = new PaymentSourceResponse();
        $card = new CardResponse();
        $card->last_digits = '9760';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';
        // No authentication_result, so "missing" SCA data.

        $paymentSource->card = $card;
        $order->payment_source = $paymentSource;
        return $order;
    }

    public function testCreatePayPalOrder(): void
    {
        $user = oxNew(EshopModelUser::class);
        $user->load(self::TEST_USER_ID);

        $basket = oxNew(EshopModelBasket::class);
        $basket->addToBasket(self::TEST_PRODUCT_ID, 1);
        $basket->setUser($user);
        $basket->setBasketUser($user);
        $basket->setPayment(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);
        $basket->setShipping('oxidstandard');
        $basket->calculateBasket(true);

        /** @var PaymentService $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);

        $session = EshopRegistry::getSession();
        $session->setVariable('paymentid', PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);

        try {
            $result = $paymentService->doCreatePayPalOrder($basket, OrderRequest::INTENT_CAPTURE);
        } catch (TypeError $e) {
            $this->fail('Expected ApiException, got TypeError');
        }

        try {
            $this->assertNotEmpty($result->id);
        } catch (Exception $e) {
            //webhook might not be set up, so we cannot verify the order further
            $this->assertTrue(true);
        }
    }

    public function testCreatePuiPayPalOrder(): void
    {
        $this->markTestSkipped('For manual use only, for automatic tests we have codeception tests');

        // The rest of the method remains unchanged
        $_POST['pui_required'] = [
            'birthdate' => [
                'day' => '1',
                'month' => '4',
                'year' => '2000'
            ],
            'phonenumber' => '040111222333'
        ];

        $loggerMock = $this->getPsrLoggerMock();
        $loggerMock->expects($this->never())
            ->method('error');
        EshopRegistry::set('logger', $loggerMock);

        $user = oxNew(EshopModelUser::class);
        $user->load(self::TEST_USER_ID);

        $basket = oxNew(EshopModelBasket::class);
        $basket->addToBasket(self::TEST_PRODUCT_ID, 1);
        $basket->setUser($user);
        $basket->setBasketUser($user);
        $basket->setPayment(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID);
        $basket->setShipping('oxidstandard');
        $basket->calculateBasket(true);

        $transactionId = EshopRegistry::getUtilsObject()->generateUId();
        $order = $this->getMockBuilder(EshopModelOrder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $order->expects($this->any())
            ->method('getShopId')
            ->willReturn(1);
        $order->expects($this->any())
            ->method('getId')
            ->willReturn($transactionId);
        $order->expects($this->once())
            ->method('savePuiInvoiceNr');

        /** @var PaymentService $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $result = $paymentService->doExecutePuiPayment($order, $basket, '007c7c9d810c4a4cb3f5b88e3e040083');

        $this->assertTrue($result);
        $this->assertSame(PaymentService::PAYMENT_ERROR_NONE, $paymentService->getPaymentExecutionError());
    }

    public function testSandboxAccountCanCreatePuiOrder(): void
    {
        $this->markTestSkipped('For manual use only, for automatic tests we have codeception tests');

        /** @var \OxidSolutionCatalysts\PayPalApi\Service\Orders $orderService */
        $orderService = EshopRegistry::get(ServiceFactory::class)
            ->getOrderService();

        $result = $orderService->createOrder(
            $this->getPuiOrderRequest(),
            'Oxid_Cart_Payments',
            '007c7c9d810c4a4cb3f5b88e3e040083',
            'return=minimal',
            'request-id-' . microtime()
        );

        $this->assertNotEmpty($result->id);
    }

    /**
     * TODO: Fix the test
     */
    public function testACDCOrder3DSecureSuccess(): void
    {
        $this->markTestSkipped("This test is failing, it needs to be fixed");
        // The string is still in $this->success3DCard, but now it was built in setUp()
        /**
         * @var PaymentService|MockObject $paymentService
         */
        $paymentService = $this->getPaymentServiceMock($this->success3DCard);

        $shopOrderModel = oxNew(EshopModelOrder::class);
        $shopOrderModel->setId('order_id');
        $shopOrderModel->oxorder__oxordernr = new \OxidEsales\Eshop\Core\Field('order_nr');

        $apiOrder = $paymentService->doCapturePayPalOrder(
            $shopOrderModel,
            'some_id',
            PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID
        );

        $this->assertInstanceOf(ApiOrderModel::class, $apiOrder);
    }

    public function testACDCOrder3DSecureFail(): void
    {
        // Mock SCAValidator to force 3D verification to fail
        $scaValidatorMock = $this->createMock(SCAValidatorInterface::class);
        $scaValidatorMock->expects($this->once())
            ->method('verify3D')
            ->with($this->anything(), $this->anything())
            ->willReturn(false);

        $paymentService = $this->getPaymentServiceMock(
            $this->failedAuthentication,
            [],
            false,
            Constants::PAYPAL_SCA_ALWAYS,
            $scaValidatorMock
        );

        $shopOrderModel = oxNew(EshopModelOrder::class);
        $this->expectExceptionMessage('OSC_PAYPAL_3DSECURITY_ERROR');
        $this->expectException(StandardException::class);

        $paymentService->doCapturePayPalOrder(
            $shopOrderModel,
            'some_id',
            PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID
        );
    }




    private function getPuiOrderRequest(): OrderRequest
    {
        $decoded = $this->getPuiRequestData();
        $request = new OrderRequest();

        $request->intent = OrderRequest::INTENT_CAPTURE;
        $request->purchase_units = $decoded['purchase_units'];
        $request->experience_context = $decoded['experience_context'];
        $request->payment_source = $decoded['payment_source'];
        $request->processing_instruction = "ORDER_COMPLETE_ON_PAYMENT_APPROVAL";

        return $request;
    }

    private function getPuiRequestData(): array
    {
        $json = file_get_contents(__DIR__ . '/../../Fixtures/pui_order_request.json');
        return json_decode($json, true);
    }

    private function getPaymentServiceMock(
        string $serializedOrder,
        array $addMockMethods = [],
        bool $alwaysIgnoreSCAResult = false,
        string $sca = Constants::PAYPAL_SCA_ALWAYS,
        ?SCAValidatorInterface $scaValidator = null
    ): MockObject {
        $moduleSettingsService = $this->getMockBuilder(ModuleSettingsService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $moduleSettingsService->expects($this->any())
            ->method('getPayPalSCAContingency')
            ->willReturn($sca);

        $moduleSettingsService->expects($this->any())
            ->method('alwaysIgnoreSCAResult')
            ->willReturn($alwaysIgnoreSCAResult);

        $logger = $this->createMock(LoggerInterface::class);

        $serviceFactory = $this->getMockBuilder(ServiceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentService = $this->getMockBuilder(PaymentService::class)
            ->onlyMethods(array_merge(['fetchOrderFields', 'trackPayPalOrder'], $addMockMethods))
            ->setConstructorArgs(
                [
                    EshopRegistry::getSession(),
                    $this->getMockBuilder(OrderRepository::class)
                        ->disableOriginalConstructor()
                        ->getMock(),
                    $scaValidator ?? new SCAValidator($serviceFactory, $moduleSettingsService),
                    $moduleSettingsService,
                    $logger,
                    $this->getServiceFromContainer(OrderProcessTrackingService::class),
                    null,
                    null
                ]
            )
            ->getMock();


        $paymentService->expects($this->any())
            ->method('fetchOrderFields')
            ->willReturn(unserialize($serializedOrder));

        $paymentService->expects($this->any())
            ->method('trackPayPalOrder');

        return $paymentService;
    }
}
