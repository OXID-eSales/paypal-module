<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Service;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\Eshop\Core\Session as EshopSession;
use OxidSolutionCatalysts\PayPal\Core\ConfirmOrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings as ModuleSettingsService;
use OxidSolutionCatalysts\PayPal\Service\SCAValidatorInterface;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPalApi\Service\Orders as ApiOrderService;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderModel;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPalApi\Service\Orders as PayPalApiOrders;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutOrderApprovedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderResponse;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Service\SCAValidator;

final class PaymentTest extends BaseTestCase
{
    protected const TEST_USER_ID = 'e7af1c3b786fd02906ccd75698f4e6b9';

    protected const TEST_PRODUCT_ID = 'dc5ffdf380e15674b56dd562a7cb6aec';

    private $success3DCard = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";s:7:"some_id";s:14:' .
    '"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";' .
    'O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_add' .
    'ress";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"7704";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";' .
    's:6:"issuer";N;s:3:"bin";N;s:21:"authentication_result";O:67:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Aut' .
    'henticationResponse":2:{s:15:"liability_shift";s:8:"POSSIBLE";s:14:"three_d_secure";O:79:"OxidSolutionCata' .
    'lysts\PayPalApi\Model\Orders\ThreeDSecureAuthenticationResponse":2:{s:21:"authentication_status";s:1:"Y";' .
    's:17:"enrollment_status";s:1:"Y";}}s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipa' .
    'y";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"m' .
    'ultibanco";N;s:6:"mybank";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;' .
    's:8:"satispay";N;s:6:"sofort";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}' .
    's:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;' .
    's:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_cont' .
    'ext";N;s:11:"create_time";N;s:11:"update_time";N;}';

    private $failedAuthentication = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:' .
    '"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";O:57:' .
    '"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_address";' .
    'N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"2421";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";s:6:"iss' .
    'uer";N;s:3:"bin";N;s:21:"authentication_result";O:67:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Authenticati' .
    'onResponse":2:{s:15:"liability_shift";s:2:"NO";s:14:"three_d_secure";O:79:"OxidSolutionCatalysts\PayPalApi\Mod' .
    'el\Orders\ThreeDSecureAuthenticationResponse":2:{s:21:"authentication_status";s:1:"N";s:17:"enrollment_status";' .
    's:1:"Y";}}s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;' .
    's:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"multibanco";N;s:6:"mybank";' .
    'N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:6:"sofort' .
    '";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_i' .
    'nstruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status' .
    '";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"upda' .
    'te_time";N;}';

    private $missingCardAuthentication = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;' .
    's:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";' .
    'O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_addr' .
    'ess";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"9760";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";s:6:"i' .
    'ssuer";N;s:3:"bin";N;s:21:"authentication_result";N;s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"ban' .
    'k";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"id' .
    'eal";N;s:10:"multibanco";N;s:6:"mybank";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"s' .
    'afetypay";N;s:8:"satispay";N;s:6:"sofort";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"appl' .
    'e_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_ti' .
    'me";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"applicat' .
    'ion_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    public function _testCreatePayPalOrder(): void
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

        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $result = $paymentService->doCreatePayPalOrder($basket, OrderRequest::INTENT_CAPTURE);

        $this->assertNotEmpty($result->id);
    }

    public function _testCreatePuiPayPalOrder(): void
    {
        $this->setRequestParameter('pui_required_birthdate_day', 1);
        $this->setRequestParameter('pui_required_birthdate_month', 4);
        $this->setRequestParameter('pui_required_birthdate_year', 2000);
        $this->setRequestParameter('pui_required_phonenumber', '040 111222333');

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

        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $result = $paymentService->doExecutePuiPayment($order, $basket);

        $this->assertTrue($result);
    }

    public function _testSandboxAccountCanCreatePuiOrder(): void
    {
        /** @var ApiOrderService $orderService */
        $orderService = EshopRegistry::get(ServiceFactory::class)
            ->getOrderService();

        $result = $orderService->createOrder(
            $this->getPuiOrderRequest(),
            '',
            'test-' . microtime(),
            'return=minimal',
            'request-id-' . microtime()
        );

        $this->assertNotEmpty($result->id);
    }

    public function testACDCOrder3DSecureSuccess(): void
    {
        $paymentService = $this->getPaymentServiceMock($this->success3DCard, ['verify3D']);

        $paymentService->expects($this->once())
            ->method('verify3D')
            ->willReturn(true);

        $shopOrderModel = oxNew(EshopModelOrder::class);
        $shopOrderModel->setId('order_id');

        $apiOrder = $paymentService->doCapturePayPalOrder(
            $shopOrderModel,
            'some_id',
            PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID
        );

        $this->assertInstanceOf(ApiOrderModel::class, $apiOrder);
    }

    public function testACDCOrder3DSecureFail(): void
    {
        $paymentService = $this->getPaymentServiceMock($this->failedAuthentication, ['verify3D']);

        $paymentService->expects($this->once())
            ->method('verify3D')
            ->willReturn(false);

        $shopOrderModel = oxNew(EshopModelOrder::class);
        $shopOrderModel->setId('order_id');

        $this->expectExceptionMessage('OXPS_PAYPAL_ORDEREXECUTION_ERROR');

        $paymentService->doCapturePayPalOrder(
            $shopOrderModel,
            'some_id',
            PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID
        );
    }

    public function dataProviderverify3D(): array
    {
        return [
            'success' => [
                'paymentId' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                'paypalOrder' => $this->success3DCard,
                'alwaysIgnoreSCAResult' => false,
                'assert' => 'assertTrue',
                'sca' => Constants::PAYPAL_SCA_ALWAYS
            ],
            'fail' => [
                'paymentId' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                'paypalOrder' => $this->failedAuthentication,
                'alwaysIgnoreSCAResult' => false,
                'assert' => 'assertFalse',
                'sca' => Constants::PAYPAL_SCA_ALWAYS
            ],
            'other_payment' => [
                'paymentId' => PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID,
                'paypalOrder' => $this->failedAuthentication,
                'alwaysIgnoreSCAResult' => false,
                'assert' => 'assertTrue',
                'sca' => Constants::PAYPAL_SCA_ALWAYS
            ],
            'ignore_sca' => [
                'paymentId' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                'paypalOrder' => $this->failedAuthentication,
                'alwaysIgnoreSCAResult' => true,
                'assert' => 'assertTrue',
                'sca' => Constants::PAYPAL_SCA_WHEN_REQUIRED
            ],
            'sca_automatic_empty_result' => [
                'paymentId' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                'paypalOrder' => $this->missingCardAuthentication,
                'alwaysIgnoreSCAResult' => false,
                'assert' => 'assertTrue',
                'sca' => Constants::PAYPAL_SCA_WHEN_REQUIRED
            ]
        ];
    }

    /**
     * @dataProvider dataProviderverify3D
     *
     */
    public function testVerify3D(
        string $paymentId,
        string $paypalOrder,
        bool $alwaysIgnoreSCAResult,
        string $assert,
        string $sca
    ): void
    {
        $paymentService = $this->getPaymentServiceMock($paypalOrder, [], $alwaysIgnoreSCAResult, $sca);

        $this->$assert(
            $paymentService->verify3D(
                $paymentId,
                unserialize($paypalOrder)
            )
        );
    }

    private function getPuiOrderRequest(): OrderRequest
    {
        $decoded = $this->getPuiRequestData();
        $request = new OrderRequest();

        $request->intent = OrderRequest::INTENT_CAPTURE;
        $request->purchase_units = $decoded['purchase_units'];
        $request->application_context = $decoded['application_context'];
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
        string $sca = Constants::PAYPAL_SCA_ALWAYS
    ): PaymentService
    {
        $moduleSettingsService = $this->getMockBuilder(ModuleSettingsService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $moduleSettingsService->expects($this->any())
            ->method('getPayPalSCAContingency')
            ->willReturn($sca);

        $moduleSettingsService->expects($this->any())
            ->method('alwaysIgnoreSCAResult')
            ->willReturn($alwaysIgnoreSCAResult);

        $serviceFactoryMock = $this->getMockBuilder( ServiceFactory::class)
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
                    new SCAValidator(),
                    $moduleSettingsService,
                    $serviceFactoryMock,
                    $this->getMockBuilder(PatchRequestFactory::class)
                        ->disableOriginalConstructor()
                        ->getMock(),
                    $this->getMockBuilder(OrderRequestFactory::class)
                        ->disableOriginalConstructor()
                        ->getMock(),
                    $this->getMockBuilder(ConfirmOrderRequestFactory::class)
                        ->disableOriginalConstructor()
                        ->getMock()
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
