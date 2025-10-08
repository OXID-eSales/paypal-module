<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use Exception;
use JsonException;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidSolutionCatalysts\PayPal\Traits\NormalizedEventDispatcher;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Model\Order as ShopOrder;
use OxidSolutionCatalysts\PayPal\Service\Factory\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\OrderManager;
use OxidSolutionCatalysts\PayPal\Service\OrderProcessTrackingService;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\JsonTrait;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use Psr\Log\LoggerInterface;
use OxidSolutionCatalysts\PayPal\Event\PayPalOrderCompletedEvent;

class AjaxPaymentController extends ProxyController
{
    use JsonTrait;
    use NormalizedEventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderProcessTrackingService
     */
    private $orderProcessTrackingService;

    /**
     * @var \OxidSolutionCatalysts\PayPal\Service\Payment
     */
    private $paymentService;

    /** @var \OxidSolutionCatalysts\PayPal\Service\OrderRepository  */
    private $orderRepository;

    /** @var \OxidSolutionCatalysts\PayPal\Service\OrderManager */
    private $orderManager;

    public function __construct()
    {
        parent::__construct();

        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
        $this->logger = $logger;
        $this->orderProcessTrackingService = $this->getServiceFromContainer(
            OrderProcessTrackingService::class
        );
        $this->paymentService = $this->getServiceFromContainer(PaymentService::class);
        $this->orderRepository = $this->getServiceFromContainer(OrderRepository::class);
        $this->orderManager = $this->getServiceFromContainer(OrderManager::class);
    }

    /**
     * @throws \JsonException
     * @throws \ReflectionException
     */
    public function captureOrder(): void
    {
        $data = $this->getRequestParameters();
        $vaultPayment = filter_var($data['vaultPayment'], FILTER_VALIDATE_BOOLEAN);
        $payPalOrderId = $data['orderId'];
        $paymentId = $data['paymentId'] ?? Registry::getSession()->getVariable('paymentid');
        $orderService = Registry::get(ServiceFactory::class)->getOrderService();
        /** @var PaymentService $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $orderService->setTrackingId($this->orderProcessTrackingService->getTrackingId());
        $language = Registry::getLang();
        $request = new OrderCaptureRequest();
        $capturePaymentForOrder = null;
        $response = [
            'status' => 'error',
            'paymentStatus' => 'error'
        ];

        try {
            $payPalOrder = new \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order(PayPalSession::getCheckoutOrder());

            //Verify 3D result if acdc payment
            if (!$paymentService->verify3D($paymentId, $payPalOrder)) {
                $this->outputJson([
                    'status' => 'error',
                    'message' => $language->translateString('OSC_PAYPAL_3DSECURITY_ERROR')
                ]);
            }

            $capturePaymentForOrder = $orderService->capturePaymentForOrder(
                '',
                $payPalOrderId,
                $request,
                '',
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
            );
            $capturePaymentForOrder->intent = OrderRequest::INTENT_CAPTURE;
        } catch (ApiException $exception) {
            $issue = $exception->getErrorIssue();
            $translatedErrorMessage = $language->translateString(
                'OSC_PAYPAL_' . $issue,
                (int)$language->getBaseLanguage(),
                false
            );

            $this->outputJson([
                'status' => 'error',
                'message' => $translatedErrorMessage
            ]);
        }
        $shopOrderId = $this->orderRepository->fetchCurrentShopOrderId();
        $order = $this->orderRepository->fetchCurrentShopOrder();
        $basket = Registry::getSession()->getBasket();
        $user = $basket->getUser();

        $payPalCustomerId = null;
        if ($vaultPayment) {
            if (isset($capturePaymentForOrder->payment_source->paypal->attributes->vault->customer["id"])) {
                $payPalCustomerId = $capturePaymentForOrder->payment_source->paypal->attributes->vault->customer["id"];
            }

            if (isset($capturePaymentForOrder->payment_source->card->attributes->vault->customer["id"])) {
                $payPalCustomerId = $capturePaymentForOrder->payment_source->card->attributes->vault->customer["id"];
            }
        }

        if (
            $order instanceof ShopOrder
            && $order->isPayPalOrderCompleted($capturePaymentForOrder)
        ) {
            $paymentsId = (string)$order->getFieldData('oxpaymenttype');
            $transactionId = (string)$payPalOrder->purchase_units[0]->payments->captures[0]->id;
            $response['status'] = 'success';

            // Dispatch event for order completion actions
            $event = new PayPalOrderCompletedEvent(
                $order,
                $basket,
                $user,
                $shopOrderId,
                $payPalOrderId,
                $paymentsId,
                $transactionId,
                $payPalCustomerId
            );
            $this->dispatchNormalized($event, PayPalOrderCompletedEvent::NAME);

            if ($capturePaymentForOrder) {
                $response['paymentStatus'] = $capturePaymentForOrder->getCapturePaymentStatus() ? 'success' : 'error';
            }
        }

        if ($response['paymentStatus'] === 'error') {
            $response['message'] = $language->translateString('OSC_PAYPAL_CAPTURE_DENIED_ERROR');
            $response['status'] = 'error';
        }

        $this->outputJson($response);
    }

    /**
     * CompleteOrder with no capture.
     * Use it for vaulted payments or when capture is handled by PP with order creation.
     *
     * @return void
     * @throws \JsonException
     */
    public function completeOrder(bool $outputJson = true): ?array
    {
        $data = $this->getRequestParameters();
        $payPalOrderId = $data['orderId'];

        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        if ($moduleSettings->getPayPalDebugLevel() === 'debug') {
            $this->logger->log('debug', sprintf('Order with id %s capture', $payPalOrderId));
        }

        $order = $this->orderRepository->fetchCurrentShopOrder();
        $basket = Registry::getSession()->getBasket();
        $user = $basket->getUser();

        $this->sendPayPalOrderMail($order, $basket, $user);

        PayPalSession::unsetPayPalSession();

        $response = [
            'status' => 'success'
        ];

        if ($outputJson) {
            $this->outputJson($response);
        }

        return $response;
    }

    public function cancelPayPalSession(): void
    {
        PayPalSession::unsetPayPalSession();
    }

    /**
     * @throws \JsonException
     */
    public function createPayPalOrder(): void
    {
        $data = $this->getRequestParameters();
        $_POST['sDeliveryAddressMD5'] = $data['deliveryAddressId'];
        $_POST['vaultPayment'] = $data['vaultPayment'] ? "true" : "false";
        $_POST['oscPayPalPaymentTypeForVaulting'] = PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID;
        $_POST['useVaultedPayment'] = $data['useVaultedPayment'];
        $this->orderProcessTrackingService->setTrackingId($data['trackingId']);
        $this->addToBasket();

        $this->setPayPalPaymentMethod(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID);
        $session = Registry::getSession();
        $basket = $session->getBasket();

        if ($basket->getItemsCount() === 0) {
            $this->outputJson(['ERROR' => 'No Article in the Basket']);
        }

        /** @var ModuleSettings $moduleSettings */
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        /** @var PaymentService $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $captureStrategy = $moduleSettings->getPayPalStandardCaptureStrategy();
        $config = Registry::getConfig();
        $returnUrl = $config->getSslShopUrl() . 'index.php?cl=order&fnc=finalizepaypalsession';
        $cancelUrl = $config->getSslShopUrl() . 'index.php?cl=order&fnc=cancelpaypalsession';
        $paymentId = Registry::getSession()->getVariable('paymentid');
        $intent = $captureStrategy === 'directly' ? OrderRequest::INTENT_CAPTURE : OrderRequest::INTENT_AUTHORIZE;
        $userAction = $paymentId === PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID ?
            OrderRequestFactory::USER_ACTION_CONTINUE : OrderRequestFactory::USER_ACTION_PAY_NOW;

        $response = $paymentService->doCreatePayPalOrder(
            $basket,
            $intent,
            $userAction,
            null,
            '',
            '',
            Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP,
            $returnUrl,
            $cancelUrl,
            false
        );

        if ($response->id) {
            $sessionOrderId = $this->orderRepository->fetchCurrentShopOrderId();
            $order = $this->orderRepository->fetchCurrentShopOrder();

            PayPalSession::unsetPayPalSession();

            $this->outputJson([
                'status' => 'success',
                'shopOrder' => [
                    'shopOrderId' => $sessionOrderId,
                    'customId' => $paymentService->getCustomIdParameter($order)
                ],
                'payPalOrder' => $response,
            ]);
        }

        $this->outputJson([
            'status' => 'error',
            'message' => 'error'
        ]);
    }

    /**
     * @psalm-suppress InternalMethod
     */
    public function createAcdcOrder(): void
    {
        $data = $this->getRequestParameters();
        $_POST['sDeliveryAddressMD5'] = $data['deliveryAddressId'];
        $_POST['vaultPayment'] = $data['vaultPayment'] ? "true" : "false";
        $_POST['oscPayPalPaymentTypeForVaulting'] = PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID;
        $session = Registry::getSession();
        $paymentId = $data['paymentId'] ?? $session->getVariable('paymentid');
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $order = oxNew(Order::class);
        $user = oxNew(User::class);
        /** @var Basket $basket */
        $basket = $session->getBasket();
        if (null === $basket->getPaymentId()) {
            $basket->setPayment($paymentId);
            $session->setBasket($basket);
            $session->setVariable('paymentid', $paymentId);
        }
        if (!$user->loadActiveUser()) {
            $this->permissionsCheck();
        }

        $session->setVariable('sess_challenge', Registry::getUtilsObject()->generateUID());
        try {
            //finalizing ordering process (validating, storing order into DB, executing payment, setting status ...)
            $session->setVariable('isPayPalPaymentCheckout', true);
            $iSuccess = $order->finalizeOrder($basket, $user);
            $session->deleteVariable('isPayPalPaymentCheckout');

            // performing special actions after user finishes order (assignment to special user groups)
            $user->onOrderExecute($basket, $iSuccess);
        } catch (Exception $exception) {
            $this->logger->log('error', $exception->getMessage(), [$exception]);
            $this->outputJson(['error' => 'failed to execute shop order']);
            return;
        }

        $paypalOrder = $paymentService->doCreatePatchedOrder(
            $session->getBasket()
        );

        if (!($paypalOrderId = $paypalOrder['id'])) {
            $this->outputJson(['error' => 'cannot create paypal order']);
            return;
        }

        $sessionOrderId = (string)$session->getVariable('sess_challenge');
        $payPalOrder = $paymentService->getPayPalCheckoutOrder($sessionOrderId, $paypalOrderId);
        $payPalOrder->setStatus($paypalOrder['status']);
        $payPalOrder->save();

        $this->outputJson([
            'status' => 'success',
            'shopOrder' => [
                'shopOrderId' => $sessionOrderId,
                'customId' => $paymentService->getCustomIdParameter($order)
            ],
            'payPalOrder' => $paypalOrder,
        ]);
    }

    /**
     *
     * TODO implement error reporting from front to log file
     * @throws JsonException
     */
    public function logError(): void
    {
        $data = $this->getRequestParameters();

        $shopOrderId = $data['shopOrderId'];
        $errorMessage = $data['errorMessage'];

        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        if ($moduleSettings->getPayPalDebugLevel() === 'debug') {
            $this->logger->log('debug', sprintf(
                'Order with id %s error: %s',
                $shopOrderId,
                $errorMessage
            ));
        }

        $this->outputJson([
            'status' => 'success'
        ]);
    }

    public function permissionsCheck(
        ?string $shopOrderId = null,
        ?string $message = 'Operation not permitted'
    ): void {
        $user = oxNew(User::class);
        $user->loadActiveUser();

        if (is_null($shopOrderId)) {
            $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
            if (
                $moduleSettings->getPayPalDebugLevel() === 'debug'
                || $moduleSettings->getPayPalDebugLevel() === 'error'
            ) {
                $this->logger->log('error', sprintf($message));
            }
            $this->outputJson([
                'status' => 'error'
            ]);
            return;
        }

        /** @var PayPalOrder $order */
        $order = oxNew(Order::class);
        $order->load($shopOrderId);


        if ($order->oxorder__oxuserid->value !== $user->getId()) {
            $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
            if (
                $moduleSettings->getPayPalDebugLevel() === 'debug'
                || $moduleSettings->getPayPalDebugLevel() === 'error'
            ) {
                $this->logger->log('error', sprintf($message));
            }
            $this->outputJson([
                'status' => 'error',
                'message' => $message
            ]);
        }
    }

    /**
     * @throws JsonException
     * @throws \Exception
     */
    public function cancelShopOrder(): void
    {
        $data = $this->getRequestParameters();

        $shopOrderId = $data['shopOrderId'];
        if (empty($shopOrderId)) {
            $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
            if (
                $moduleSettings->getPayPalDebugLevel() === 'debug'
                || $moduleSettings->getPayPalDebugLevel() === 'error'
            ) {
                $this->logger->log(
                    'error',
                    __CLASS__ . '::' . __FUNCTION__ . '(): Shop order id is empty'
                );
            }
        }

        $this->permissionsCheck(
            $shopOrderId,
            'Current user do not have permission to cancel referenced order'
        );

        /** @var PayPalOrder $order */
        $order = oxNew(Order::class);
        $order->load($shopOrderId);

        $orderNumberPart = !$order->hasOrderNumber() ? 'without Order number and' : '';
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        if ($moduleSettings->getPayPalDebugLevel() === 'debug') {
            $this->logger->log('debug', sprintf(
                'Temporary order %s with id %s was canceled',
                $shopOrderId,
                $orderNumberPart
            ));
        }

        $order->cancelOrder();
        $order->markOrderPaymentFailed();
        $order->save();

        Registry::getSession()->deleteVariable('sess_challenge'); //session cleanup
        PayPalSession::unsetPayPalOrderId();

        $this->outputJson([
            'status' => 'success'
        ]);
    }

    public function patchShopOrder(): void
    {
        $data = $this->getRequestParameters();
        $vaultPayment = filter_var($data['vaultPayment'], FILTER_VALIDATE_BOOLEAN);
        $shopOrderId = $data['shopOrderId'];
        $this->permissionsCheck($shopOrderId);

        $sessionShopOrderId = Registry::getSession()->getVariable('sess_challenge');
        $payPalOrderId = $data['payPalOrderId'];
        $cancelSession = !$sessionShopOrderId || $shopOrderId !== $sessionShopOrderId;

        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);

        /** @var ShopOrder $oOrder */
        $oOrder = oxNew(Order::class);
        $oOrder->load($shopOrderId);
        $basket = Registry::getSession()->getBasket();

        if ($cancelSession) {
            $this->outputJson([
                'status' => 'error',
                'message' => 'Order id mismatch error.', //@TODO improve errors messages
            ]);
        }
        $paymentsId = (string)$oOrder->getFieldData('oxpaymenttype');
        /** @var PayPalApiOrder $payPalOrder */
        $payPalOrder = $paymentService->fetchOrderFields($payPalOrderId, '');
        $captureStrategy = $moduleSettings->getPayPalStandardCaptureStrategy();

        try {
            if ($captureStrategy === 'directly') {
                if ($oOrder->isPayPalOrderCompleted($payPalOrder)) {
                    $oOrder->markOrderPaid();
                    $transactionId = (string)$payPalOrder->purchase_units[0]->payments->captures[0]->id;
                    $oOrder->setTransId($transactionId);
                    $paymentService->trackPayPalOrder(
                        $shopOrderId,
                        $payPalOrderId,
                        $paymentsId,
                        PayPalApiOrder::STATUS_COMPLETED,
                        $transactionId
                    );
                }
            }

            //capture after shipment or manual
            if ($captureStrategy !== 'directly') {
                $oOrder->setOrderStatus('NOT_FINISHED');
                $oOrder->save();
                //prepare capture tracking
                $paymentService->trackPayPalOrder(
                    $oOrder->getId(),
                    $payPalOrderId,
                    $paymentsId,
                    PayPalApiOrder::STATUS_APPROVED
                );
            }
        } catch (\Exception $e) {
            $this->outputJson([
                'status' => 'error',
                'message' => 'Order completion error.', //@TODO improve errors messages
            ]);
        }

        if ($vaultPayment) {
            //assuming that if there is no error during the request and vaulted was requested it went fine
            Registry::getSession()->setVariable("vaultSuccess", true);
        }

        $this->outputJson([
            'status' => 'success',
            'oxid' => $oOrder->getId(),
            'paypalOrderDetails' => $payPalOrder
        ]);
    }

    public function createShopOrder(): void
    {
        $data = $this->getRequestParameters();
        $_POST['sDeliveryAddressMD5'] = $data['deliveryAddressId'] ?? null;
        $paymentId = $data['paymentId'] ?? null;
        $order = $this->orderManager->createShopOrder($paymentId);

        if (null === $order) {
            return;
        }

        $this->outputJson(array_merge(['status' => 'success'], $order));
    }

    /**
     * @return array|mixed
     * @throws JsonException
     */
    public function getRequestParameters(): array
    {
        $body = file_get_contents('php://input');
        $data = [];

        if (!empty($body)) {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }

        return $data;
    }

    /**
     * @throws JsonException
     */
    public function updateOxUserWithPayPalCustomerId(?array $data = []): void
    {
        $data = $data ? $data : $this->getRequestParameters();
        $user = $this->getUser();

        if (!$user->loadActiveUser()) {
            $this->permissionsCheck();
        }

        $user->oxuser__oscpaypalcustomerid = new Field($data['payPalCustomerId']);

        $user->save();

        $this->outputJson([
            'status' => 'success'
        ]);
    }

    protected function sendPayPalOrderMail(Order $order, ?Basket $basket, ?User $user): void
    {
        if (!$basket || !$user) {
            return;
        }

        /** @var ShopOrder $oOrder */
        $order->sendPayPalOrderByEmail(
            $user,
            $basket
        );
    }

    /**
     * Authorize a PayPal payment
     *
     * @return void
     * @throws JsonException
     */
    public function authorizePayment(): void
    {
        $data = $this->getRequestParameters();
        $checkoutOrderId = $data['orderId'];
        $shopOrderId = $data['shopOrderId'] ?? null;
        $paymentId = $data['paymentId'] ?? Registry::getSession()->getVariable('paymentid');

        /** @var PaymentService $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);

        /** @var ModuleSettings $moduleSettings */
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);

        if ($moduleSettings->getPayPalDebugLevel() === 'debug') {
            $this->logger->log('debug', sprintf('Authorizing order with id %s', $checkoutOrderId));
        }

        try {
            $authorizePaymentResult = $paymentService->doAuthorizePayment($checkoutOrderId, $shopOrderId, $paymentId);

            if (
                $authorizePaymentResult["status"] === 'success'
                && $authorizePaymentResult["paymentStatus"] === 'success'
            ) {
                $completeOrderResult = $this->completeOrder(false);

                if ($completeOrderResult["status"] === 'success') {
                    $this->outputJson([
                        'status' => 'success',
                        'paymentStatus' => $authorizePaymentResult["paymentStatus"]
                    ]);
                }
            }

            $this->outputJson([
                'status' => 'error',
                'message' => 'OSC_PAYPAL_ORDEREXECUTION_ERROR'
            ]);
        } catch (Exception $exception) {
            if ($moduleSettings->getPayPalDebugLevel() === 'debug') {
                $this->logger->log(
                    'debug',
                    'Error during payment authorization.',
                    [$exception->getMessage()]
                );
            }

            $this->outputJson([
                'status' => 'error',
                'message' => 'OSC_PAYPAL_ORDEREXECUTION_ERROR'
            ]);
        }
    }
}
