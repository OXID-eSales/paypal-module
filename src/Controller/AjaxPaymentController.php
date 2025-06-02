<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use Exception;
use JsonException;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\JsonTrait;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;

class AjaxPaymentController extends ProxyController
{
    use JsonTrait;
    use ServiceContainer;

    private Logger $logger;

    public function __construct()
    {
        parent::__construct();

        $this->logger = $this->getServiceFromContainer(Logger::class);
    }

    public function captureOrder(): void
    {
        $data = $this->getRequestParameters();
        $payPalOrderId = $data['orderId'];

        $this->logger->log('debug', sprintf('Order with id %s capture', $payPalOrderId));

        $orderService = Registry::get(ServiceFactory::class)->getOrderService();
        $request = new OrderCaptureRequest();
        try {
            $orderService->capturePaymentForOrder(
                '',
                $payPalOrderId,
                $request,
                '',
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
            );
        } catch (ApiException $exception) {
            $issue = $exception->getErrorIssue();
            $languageObject = Registry::getLang();
            $translatedErrorMessage = $languageObject->translateString(
                'OSC_PAYPAL_' . $issue,
                (int)$languageObject->getBaseLanguage(),
                false
            );
            $this->logger->log('error', $exception->getMessage(), [$exception]);

            $this->outputJson([
                'status' => 'error',
                'error' => $translatedErrorMessage
            ]);
        }

        $shopOrderId = Registry::getSession()->getVariable('sess_challenge');
        /** @var Order $oOrder */
        $oOrder = oxNew(Order::class);
        $oOrder->load($shopOrderId);
        $oOrder->markOrderPaid();

        PayPalSession::unsetPayPalSession();

        $this->outputJson([
            'status' => 'success'
        ]);
    }

    public function cancelPayPalSession(): void
    {
        PayPalSession::unsetPayPalSession();
    }

    /**
     * @psalm-suppress InternalMethod
     * @throws \JsonException
     */
    public function createAcdcOrder(): void
    {
        $user = oxNew(User::class);
        if (!$user->loadActiveUser()) {
            $this->permissionsCheck();
        }

        $data = $this->getRequestParameters();
        $_POST['sDeliveryAddressMD5'] = $data['deliveryAddressId'];
        $_POST['vaultPayment'] = $data['vaultPayment'] ? "true" : "false";
        $_POST['oscPayPalPaymentTypeForVaulting'] = PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID;
        /** @var PaymentService $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        /** @var Logger $logger */
        $logger = $this->getServiceFromContainer(Logger::class);
        /** @var Order $order */
        $order = oxNew(Order::class);
        /** @var \OxidEsales\Eshop\Core\Session $session */
        $session = Registry::getSession();
        /** @var \OxidSolutionCatalysts\PayPal\Model\Basket $basket */
        $basket = $session->getBasket();

        $session->setVariable('paymentid', PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID);
        $session->setVariable('sess_challenge', Registry::getUtilsObject()->generateUID());

        try {
            //finalizing an ordering process (validating, storing order into DB, executing payment, setting status ...)
            $iSuccess = $order->finalizePayPalOrder($basket, $user);

            // performing special actions after user finishes order (assignment to special user groups)
            $user->onOrderExecute($basket, $iSuccess);
        } catch (Exception $exception) {
            $logger->log('error', $exception->getMessage(), [$exception]);
            $this->outputJson(['error' => 'failed to execute shop order']);
            return;
        }

        $response = $paymentService->doCreatePatchedOrder($basket);

        if (!($paypalOrderId = $response['id'])) {
            $this->outputJson(['error' => 'cannot create paypal order']);
            return;
        }

        $sessionOrderId = (string)$session->getVariable('sess_challenge');
        $payPalOrder = $paymentService->getPayPalCheckoutOrder($sessionOrderId, $paypalOrderId);
        $payPalOrder->setStatus($response['status']);
        $payPalOrder->save();

        PayPalSession::storePayPalOrderId($paypalOrderId);

        $this->outputJson([
            'status' => 'success',
            'shopOrder' => [
                'shopOrderId' => $order->oxorder__oxid->value,
                'customId' => $paymentService->getCustomIdParameter($order)
            ],
            'payPalOrder' => $response,
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

        $this->logger->log('debug', sprintf(
            'Order with id %s error: %s',
            $shopOrderId,
            $errorMessage
        ));

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

        if (null == $shopOrderId) {
            $this->logger->log('error', sprintf($message));
            $this->outputJson([
                'status' => 'error'
            ]);
            return;
        }

        /** @var PayPalOrder $order */
        $order = oxNew(Order::class);
        $order->load($shopOrderId);


        if ($order->oxorder__oxuserid->value !== $user->getId()) {
            $this->logger->log('error', sprintf($message));
            $this->outputJson([
                'status' => 'error'
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
            $this->logger->log('error', __CLASS__ . '::' . __FUNCTION__ . '(): Shop order id is empty');
        }

        $this->permissionsCheck(
            $shopOrderId,
            'Current user do not have permission to cancel referenced order'
        );

        /** @var PayPalOrder $order */
        $order = oxNew(Order::class);
        $order->load($shopOrderId);

        $orderNumberPart = !$order->hasOrderNumber() ? 'without Order number and' : '';
        $this->logger->log('debug', sprintf(
            'Temporary order %s with id %s was canceled',
            $shopOrderId,
            $orderNumberPart
        ));

        $order->cancelOrder();
        $order->markOrderPaymentFailed();
        $order->save();

        Registry::getSession()->deleteVariable('sess_challenge'); //session cleanup
        PayPalSession::unsetPayPalSession();

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

        /** @var PayPalOrder $oOrder */
        $oOrder = oxNew(Order::class);
        $oOrder->load($shopOrderId);

        if ($cancelSession) {
            $this->outputJson([
                'status' => 'error',
                'message' => 'Order id mismatch error.', //@TODO improve errors messages
            ]);
        }
        $paymentsId = (string) $oOrder->getFieldData('oxpaymenttype');
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

        if($vaultPayment) {
            //assuming that if there is no error during the request and vaulted was requested it went fine
            Registry::getSession()->setVariable("vaultSuccess", true);
        }

        $this->outputJson([
            'status' => 'success',
            'oxid' => $oOrder->oxorder__oxid->value,
            'paypalOrderDetails' => $payPalOrder
        ]);
    }

    public function createShopOrder(): void
    {
        /** @var \OxidSolutionCatalysts\PayPal\Service\Payment $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $data = $this->getRequestParameters();
        $_POST['sDeliveryAddressMD5'] = $data['deliveryAddressId'];

        $user = oxNew(User::class);
        if (! $user->loadActiveUser()) {
            $this->permissionsCheck();
        }

        $basket = Registry::getSession()->getBasket();
        $order = oxNew(Order::class);
        Registry::getSession()->deleteVariable('sess_challenge');

        //finalizing an ordering process (validating, storing order into DB, setting status)
        $success = $order->finalizePayPalOrder($basket, $user, false);

        Registry::getSession()->setVariable('sess_challenge', $basket->getOrderId());

        // performing special actions after user finishes order (assignment to special user groups)
        $user->onOrderExecute($basket, $success);

        $this->outputJson([
            'status' => 'success',
            'shopOrderId' => $order->oxorder__oxid->value,
            'customId' => $paymentService->getCustomIdParameter($order)
        ]);
    }

    /**
     * @return array|mixed
     * @throws \JsonException
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
    public function updateOxUserWithPayPalCustomerId(): void
    {
        $data = $this->getRequestParameters();
        $user = $this->getUser();

        if (! $user->loadActiveUser()) {
            $this->permissionsCheck();
        }

        $user->oxuser__oscpaypalcustomerid = new Field($data['payPalCustomerId']);

        $user->save();

        $this->outputJson([
            'status' => 'success'
        ]);
    }
}
