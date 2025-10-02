<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Model;

use DateTimeImmutable;
use Exception;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Application\Model\UserPayment;
use OxidEsales\Eshop\Core\Counter as EshopCoreCounter;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\OrderProcessTrackingService;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Tracker\Tracker;
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Capture;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPalApi\Service\Orders;
use Psr\Log\LoggerInterface;

/**
 * PayPal Eshop model order class
 *
 * @mixin \OxidEsales\Eshop\Application\Model\Order
 */
class Order extends Order_parent
{
    use ServiceContainer;

    private ?OrderProcessTrackingService $orderProcessTrackingService;
    private ?ModuleSettings $moduleSettings;

    private ?PaymentService $paymentService;

    public function __construct()
    {
        parent::__construct();
        $this->orderProcessTrackingService = $this->getServiceFromContainer(OrderProcessTrackingService::class);
        $this->moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $this->paymentService = $this->getServiceFromContainer(PaymentService::class);
    }

    /**
     * Uapm payment in progress
     *
     * @var int
     */
    public const ORDER_STATE_SESSIONPAYMENT_INPROGRESS = 500;

    /**
     * ACDC payment in progress
     *
     * @var int
     */
    public const ORDER_STATE_ACDCINPROGRESS = 700;

    /**
     * ACDC payment in progress
     *
     * @var int
     */
    public const ORDER_STATE_ACDCCOMPLETED = 750;

    /**
     * Error during payment execution
     *
     * @var int
     */
    public const ORDER_STATE_PAYMENTERROR = 2;

    /**
     * Order finalizations is waiting for webhook events
     *
     * @var int
     */
    public const ORDER_STATE_WAIT_FOR_WEBHOOK_EVENTS = 600;

    /**
     * Order finalizations waiting for webhook events timed out
     *
     * @var int
     */
    public const ORDER_STATE_TIMEOUT_FOR_WEBHOOK_EVENTS = 900;

    /**
     * ACDC payment completed but order needs call on OrderController::
     *
     * @var int
     */
    public const ORDER_STATE_NEED_CALL_ACDC_FINALIZE = 800;

    /**
     * PayPal order information
     * @var null|PayPalApiOrder $payPalApiOrder
     */
    protected $payPalApiOrder = null;

    /**
     * PayPal order Id
     * @var null|string
     */
    protected $payPalOrderId = null;

    /**
     * PayPal order Repo
     * @var PayPalOrder $payPalOrder
     */
    protected $payPalOrder;

    /**
     * PayPalPlus order Id
     * @var null|string
     */
    protected $payPalPlusOrderId = null;

    /**
     * PayPalPlus order Id
     * @var null|string
     */
    protected $payPalSoapOrderId = null;

    public function savePuiInvoiceNr(string $invoiceNr): void
    {
        $this->assign(
            ['oxinvoicenr' => $invoiceNr]
        );
        $this->save();
    }

    /**
     * @throws PayPalException
     * @throws ApiException
     * @throws \Exception
     */
    public function finalizeOrderAfterExternalPayment(string $payPalOrderId, bool $forceFetchDetails = false): void
    {
        if (!$this->isLoaded()) {
            throw PayPalException::cannotFinalizeOrderAfterExternalPaymentSuccess($payPalOrderId);
        }

        $paymentsId = (string) $this->getFieldData('oxpaymenttype');
        if (!$this->paymentService->isPayPalPayment($paymentsId)) {
            throw PayPalException::cannotFinalizeOrderAfterExternalPayment($payPalOrderId, $paymentsId);
        }

        $payPalApiOrder = $this->paymentService->fetchOrderFields($payPalOrderId);
        $basket = Registry::getSession()->getBasket();
        $user = Registry::getSession()->getUser();
        $this->afterOrderCleanUp($basket, $user);

        $isPayPalACDC = $paymentsId === PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID;
        $isPaypalGooglePay = $paymentsId === PayPalDefinitions::GOOGLEPAY_PAYPAL_PAYMENT_ID;
        $isPayPalStandard = $paymentsId === PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID;
        $isPaypalApplePay = $paymentsId === PayPalDefinitions::APPLEPAY_PAYPAL_PAYMENT_ID;

        $transactionId = null;
        $payPalPaymentSuccess = true;

        if (($isPayPalACDC && $forceFetchDetails) || $isPaypalGooglePay  || $isPaypalApplePay) {
            if ($this->isPayPalOrderCompleted($payPalApiOrder)) {
                $this->markOrderPaid();
                $transactionId = $this->extractTransactionId($payPalApiOrder);
                $this->setTransId($transactionId);
                $this->paymentService->trackPayPalOrder(
                    $this->getId(),
                    $payPalOrderId,
                    $paymentsId,
                    PayPalApiOrder::STATUS_COMPLETED,
                    $transactionId
                );
            } else {
                throw PayPalException::cannotFinalizeOrderAfterExternalPayment($payPalOrderId, $paymentsId);
            }
        }

        if ($isPaypalGooglePay || $isPaypalApplePay) {
            //webhook should kick in and handle order state and we should not call the api too often
            Registry::getSession()->deleteVariable(Constants::SESSION_ACDC_PAYPALORDER_STATUS);
            // remove PayPal order id from session
            PayPalSession::unsetPayPalOrderId();
        } elseif (
            ($isPayPalStandard || $isPayPalACDC ) &&
            $this->moduleSettings
                ->getPayPalStandardCaptureStrategy() !== 'directly'
        ) {
            $paymentId = (string) $this->paymentService->getSessionPaymentId();

            try {
                $result = $this->paymentService->doAuthorizePayment($payPalOrderId, $this->getId(), $paymentId);

                /** @var LoggerInterface $logger */
                $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
                if ($result['paymentStatus'] === 'success' && $result['status'] === 'success') {
                    PayPalSession::unsetPayPalSession();
                } else {
                    $this->_setOrderStatus('ERROR');
                    $logger->log('error', 'Error on order authorization call.', [$result]);
                    throw PayPalException::cannotFinalizeOrderAfterExternalPayment($payPalOrderId, $paymentsId);
                }
            } catch (Exception $exception) {
                $this->_setOrderStatus('ERROR');
                throw PayPalException::cannotFinalizeOrderAfterExternalPayment($payPalOrderId, $paymentsId);
            }

            $transactionId = '';

            $this->_setOrderStatus('NOT_FINISHED');
            $this->paymentService->trackPayPalOrder(
                $this->getId(),
                $payPalOrderId,
                $paymentsId,
                PayPalApiOrder::STATUS_APPROVED
            );
        } else {
            // uAPM, PayPal Standard directly, PayPal Paylater
            $payPalPaymentSuccess = $this->doExecutePayPalPayment($payPalOrderId);
            //TODO: maybe we can get transation id as return value if payment was completed
        }

        //TODO: reduce calls to api, see above
        if (is_null($transactionId) && $payPalApiOrder->intent === OrderRequest::INTENT_CAPTURE) {
            $capture = $this->getOrderPaymentCapture($payPalOrderId);
            $orderService = Registry::get(ServiceFactory::class)->getOrderService();
            if ($payPalPaymentSuccess) {
                $request = new OrderCaptureRequest();
                try {
                    $capture = $orderService->capturePaymentForOrder(
                        '',
                        $payPalOrderId,
                        $request,
                        '',
                        Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
                    );
                } catch (ApiException $exception) {
                    $this->_setOrderStatus('ERROR');
                    throw PayPalException::cannotFinalizeOrderAfterExternalPayment($payPalOrderId, $paymentsId);
                }
            }

            $this->setTransId($capture->id);
        }

        if (!$isPaypalGooglePay && !$isPaypalApplePay) {
            $this->sendPayPalOrderByEmail($user, $basket);
        }
    }

    /**
     * send Order By Email without Stock-Check
     * @param User $user
     * @param Basket $basket
     */
    public function sendPayPalOrderByEmail(User $user, Basket $basket): void
    {
        $userPayment = oxNew(UserPayment::class);
        $userPayment->load($this->getFieldData('oxpaymentid'));

        Registry::getSession()->setVariable('blDontCheckProductStockForPayPalMails', true);
        $this->_sendOrderByEmail($user, $basket, $userPayment);
        Registry::getSession()->deleteVariable('blDontCheckProductStockForPayPalMails');
    }

    /**
     * @inheritDoc
     *
     * @param User $oUser    order user
     * @param \OxidEsales\Eshop\Application\Model\Basket      $oBasket  current order basket
     * @param \OxidEsales\Eshop\Application\Model\UserPayment $oPayment order payment
     *
     * @return bool
     */
    protected function _sendOrderByEmail($oUser = null, $oBasket = null, $oPayment = null)
    {
        if (Registry::getSession()->getVariable('isPayPalPaymentCheckout')) {
            return self::ORDER_STATE_OK;
        }

        return parent::_sendOrderByEmail($oUser, $oBasket, $oPayment);
    }

    //TODO: this place should be refactored in shop core
    protected function afterOrderCleanUp(Basket $basket, User $user): void
    {
        // deleting remark info only when order is finished
        Registry::getSession()->deleteVariable('ordrem');

        // store orderid
        $basket->setOrderId($this->getId());

        // updating wish lists
        $this->_updateWishlist($basket->getContents(), $user);

        // updating users notice list
        $this->_updateNoticeList($basket->getContents(), $user);

        // marking vouchers as used and sets them to $this->_aVoucherList (will be used in order email)
        // skipping this action in case of order recalculation
        $this->_markVouchers($basket, $user);
    }

    /**
     * Executes payment. Additionally loads oxPaymentGateway object, initiates
     * it by adding payment parameters (oxPaymentGateway::setPaymentParams())
     * and finally executes it (oxPaymentGateway::executePayment()). On failure -
     * deletes order and returns * error code 2.
     *
     * @param Basket $basket      basket object
     * @param object $userpayment user payment object
     *
     * @return  integer 2 or an error code
     * @deprecated underscore prefix violates PSR12, will be renamed to "executePayment" in next major
     */
    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    protected function _executePayment(Basket $basket, $userpayment)
    {
        $sessionPaymentId = (string) $this->paymentService->getSessionPaymentId();

        if (PayPalDefinitions::isProxyControllerPayment($sessionPaymentId)) {
            return true;
        }

        $isPayPalUAPM = PayPalDefinitions::isUAPMPayment($sessionPaymentId);

        //catch UAPM
        if ($isPayPalUAPM) {
            try {
                //order number needs to be set before the payment is requested
                $this->setOrderNumber();

                $redirectLink = $this->paymentService->doExecuteUAPMPayment($this, $basket);

                PayPalSession::setSessionRedirectLink($redirectLink);

                return self::ORDER_STATE_SESSIONPAYMENT_INPROGRESS;
            } catch (Exception $exception) {
                $this->delete();
                /** @var LoggerInterface $logger */
                $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
                $logger->log('error', $exception->getMessage(), [$exception]);
            }
            return self::ORDER_STATE_PAYMENTERROR;
        }

        // for all other PayPal-Payments ignore the _executePayment, because it is handle before
        if (Registry::getSession()->getVariable('isPayPalPaymentCheckout')) {
            return true;
        }

        return parent::_executePayment($basket, $userpayment);
    }

    /**
     * Get PayPal order object for the current active order object
     * Result is cached and returned on subsequent calls
     *
     * @param string $payPalOrderId
     * @return PayPalApiOrder
     * @throws ApiException
     */
    public function getPayPalCheckoutOrder(string $payPalOrderId = ''): PayPalApiOrder
    {
        $payPalOrderId = $payPalOrderId ?: $this->getPayPalOrderIdForOxOrderId();
        if (!$this->payPalApiOrder) {
            /** @var Orders $orderService */
            $orderService = Registry::get(ServiceFactory::class)->getOrderService();
            $orderService->setTrackingId($this->orderProcessTrackingService->getTrackingId());

            $this->payPalApiOrder = $orderService->showOrderDetails(
                $payPalOrderId,
                '',
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
            );
        }

        return $this->payPalApiOrder;
    }

    protected function doExecutePayPalPayment(string $payPalOrderId): bool
    {
        $sessionPaymentId = (string) $this->paymentService->getSessionPaymentId();
        $success = false;

        // Capture Order
        try {
            // At this point we only trigger the capture. We find out that order was really captured via the
            // CHECKOUT.ORDER.COMPLETED webhook, where we mark the order as paid
            $order = $this->paymentService->doCapturePayPalOrder($this, $payPalOrderId, $sessionPaymentId);
            // success means at this point, that we triggered the capture without errors
            $success = true;
        } catch (Exception $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log('error', "Error on order capture call.", [$exception]);
        }

        // destroy PayPal-Session
        PayPalSession::unsetPayPalOrderId();

        return $success;
    }

    public function doProvidePayPalTrackingCarrier(
        string $transactionId = '',
        string $trackCarrier = '',
        string $trackCode = '',
        string $status = ''
    ): bool {
        $trackCode = $trackCode ?: $this->getPayPalTrackingCode();
        $trackCarrier = $trackCarrier ?: $this->getPayPalTrackingCarrier();
        $transactionId = $transactionId ?: $this->getPayPalTransactionId();

        if (!$trackCode || !$trackCarrier || !$transactionId) {
            return false;
        }
        return oxNew(Tracker::class)->sendtracking(
            $transactionId,
            $trackCode,
            $trackCarrier,
            $status
        );
    }

    /**
     * Update order oxpaid to current time.
     */
    public function markOrderPaid(): void
    {
        $this->_setOrderStatus('OK');

        $db = DatabaseProvider::getDb();
        $utilsDate = Registry::getUtilsDate();
        $date = date('Y-m-d H:i:s', $utilsDate->getTime());

        $query = 'update oxorder set oxpaid=? where oxid=?';
        $db->execute($query, [$date, $this->getId()]);

        //updating order object
        $this->oxorder__oxpaid = new Field($date);
    }

    /**
     * Update order oxtransid
     */
    public function setTransId($sTransId): void
    {
        $db = DatabaseProvider::getDb();

        $query = 'update oxorder set oxtransid=? where oxid=?';
        $db->execute($query, [$sTransId, $this->getId()]);

        //updating order object
        $this->oxorder__oxtransid = new Field($sTransId);
    }

    public function markOrderPaymentFailed(): void
    {
        $this->_setOrderStatus('ERROR');
    }

    /**
     * Returns PayPal order id.
     *
     * @param string|null $oxId
     */
    public function getPayPalOrderIdForOxOrderId(string $oxId = null): string
    {
        //TODO: model?
        if (is_null($this->payPalOrderId)) {
            $this->payPalOrderId = '';
            $oxId = is_null($oxId) ? $this->getId() : $oxId;
            $table = 'oscpaypal_order';
            $shopId = $this->getShopId();
            $params = [$table . '.oxorderid' => $oxId, $table . '.oxshopid' => $shopId];

            $paypalOrderObj = oxNew(BaseModel::class);
            $paypalOrderObj->init($table);
            $select = $paypalOrderObj->buildSelectString($params);

            if ($data = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getRow($select)) {
                $this->payPalOrderId = $data['oxpaypalorderid'];
            }
        }
        return $this->payPalOrderId;
    }

    /**
     * Returns PayPalPlus order id.
     *
     * @param string|null $oxId
     *
     * @return string
     */
    public function getPayPalPlusOrderIdForOxOrderId(string $oxId = null): string
    {
        if (is_null($this->payPalPlusOrderId)) {
            $this->payPalPlusOrderId = '';
            $oxId = is_null($oxId) ? $this->getId() : $oxId;
            $order = oxNew(PayPalPlusOrder::class);
            if ($order->tableExists() && $order->loadByOrderId($oxId)) {
                $this->payPalPlusOrderId = $order->getId();
            }
        }
        return $this->payPalPlusOrderId;
    }

    /**
     * Returns PayPalSoap order id
     *
     * @param string|null $oxId
     *
     * @return string
     */
    public function getPayPalSoapOrderIdForOxOrderId(string $oxId = null): string
    {
        if (is_null($this->payPalSoapOrderId)) {
            $this->payPalSoapOrderId = '';
            $oxId = is_null($oxId) ? $this->getId() : $oxId;
            $order = oxNew(PayPalSoapOrder::class);
            if ($order->tableExists() && $order->loadByOrderId($oxId)) {
                $this->payPalSoapOrderId = $order->getId();
            }
        }
        return $this->payPalSoapOrderId;
    }

    /**
     * Checks if the order was paid using PayPal
     *
     * @return bool
     */
    public function paidWithPayPal(): bool
    {
        return (bool)$this->getPayPalOrderIdForOxOrderId();
    }

    /**
     * Checks if the order was paid using PayPalPlus
     *
     * @return bool
     */
    public function paidWithPayPalPlus(): bool
    {
        return (bool)$this->getPayPalPlusOrderIdForOxOrderId();
    }

    /**
     * Checks if the order was paid using PayPalSoap
     *
     * @return bool
     */
    public function paidWithPayPalSoap(): bool
    {
        return (bool)$this->getPayPalSoapOrderIdForOxOrderId();
    }

    /**
     * Checks if PayPalPlus-tables exists anymore
     *
     * @return bool
     */
    public function tableExitsForPayPalPlus(): bool
    {
        return oxNew(PayPalPlusOrder::class)->tableExists();
    }

    /**
     * Checks if PayPalSoap-tables exists anymore
     *
     * @return bool
     */
    public function tableExitsForPayPalSoap(): bool
    {
        return oxNew(PayPalSoapOrder::class)->tableExists();
    }

    /**
     * Get order payment capture or null if not captured
     *
     * @return Capture|null
     * @throws ApiException
     */
    public function getOrderPaymentCapture($payPalOrderId = ''): ?Capture
    {
        return $this->getPayPalCheckoutOrder($payPalOrderId)->purchase_units[0]->payments->captures[0] ?? null;
    }

    public function setOrderNumber(): void
    {
        if (!$this->hasOrderNumber()) {
            $this->_setNumber();
        } else {
            oxNew(EshopCoreCounter::class)
                ->update($this->_getCounterIdent(), $this->getFieldData('oxordernr'));
        }
    }

    public function setOrderStatus($sStatus): void
    {
        $this->_setOrderStatus($sStatus);
    }

    public function isOrderFinished(): bool
    {
        return 'OK' === $this->getFieldData('oxtransstatus');
    }

    public function isOrderPaid(): bool
    {
        return false === strpos((string) $this->getFieldData('oxpaid'), '0000');
    }

    /**
     * @throws Exception
     */
    public function isWaitForWebhookTimeoutReached(): bool
    {
        $orderTime = new DateTimeImmutable((string) $this->getFieldData('oxorderdate'));

        return (new DateTimeImmutable('now'))->getTimestamp() >
            ($orderTime->getTimestamp() + Constants::PAYPAL_WAIT_FOR_WEBOOK_TIMEOUT_IN_SEC);
    }

    public function hasOrderNumber(): bool
    {
        return 0 < (int) $this->getFieldData('oxordernr');
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function finalizeOrder(Basket $basket, $user, $recalculatingOrder = false)
    {
        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
        $logger->log('debug', 'finalizeOrder');

        $oSession = Registry::getSession();

        //we might have the case that the order is already stored but we are waiting for webhook events
        if (
            $this->paymentService->isPayPalPayment()
        ) {
            //order payment is being processed
            $oOrderId = $oSession->getVariable('sess_challenge');
            $isLoaded = $this->load($oOrderId);
            if (
                $isLoaded &&
                $this->paymentService->isOrderExecutionInProgress() &&
                !$this->isOrderFinished() &&
                !$this->isOrderPaid() &&
                !$this->isWaitForWebhookTimeoutReached()
            ) {
                return self::ORDER_STATE_WAIT_FOR_WEBHOOK_EVENTS;
            }
        }

        $result = parent::finalizeOrder($basket, $user, $recalculatingOrder);

        if (
            $this->paymentService->isPayPalPayment() &&
            !$this->isOrderFinished() &&
            !$this->isOrderPaid() &&
            !$this->hasOrderNumber() &&
            $this->isWaitForWebhookTimeoutReached()
        ) {
            return self::ORDER_STATE_TIMEOUT_FOR_WEBHOOK_EVENTS;
        }

        return $result;
    }

    public function isPayPalOrderCompleted(PayPalApiOrder $apiOrder): bool
    {
        return (
            isset(
                $apiOrder->status,
                $apiOrder->purchase_units[0]->payments->captures[0]->status
            ) &&
            $apiOrder->status === PayPalApiOrder::STATUS_COMPLETED &&
            $apiOrder->purchase_units[0]->payments->captures[0]->status === Capture::STATUS_COMPLETED
        );
    }

    protected function extractTransactionId(PayPalApiOrder $apiOrder): string
    {
        return (string) $apiOrder->purchase_units[0]->payments->captures[0]->id;
    }

    public function setPayPalTracking(string $trackingCarrier, string $trackingCode): void
    {
        // for backwards compatibility
        $this->assign(
            [
                'oxtrackcode' => $trackingCode
            ]
        );
        $this->save();

        $payPalOrder = $this->getPayPalRepository();
        $payPalOrder->setTrackingCode($trackingCode);
        $payPalOrder->setTrackingCarrier($trackingCarrier);
        $payPalOrder->save();
    }

    public function getPayPalTrackingCarrier(): string
    {
        return $this->getPayPalRepository()->getTrackingCarrier();
    }

    public function getPayPalTrackingCode(): string
    {
        return $this->getPayPalRepository()->getTrackingCode();
    }

    public function getPayPalTransactionId(): string
    {
        return $this->getPayPalRepository()->getTransactionId();
    }

    protected function getPayPalRepository(): PayPalOrder
    {
        /** @var OrderRepository $payPalOrderRepository */
        $payPalOrderRepository = $this->getServiceFromContainer(OrderRepository::class);
        $this->payPalOrder = $payPalOrderRepository->paypalOrderByOrderId(
            $this->getId()
        );
        return $this->payPalOrder;
    }

    /**
     * @inerhitDoc
     *
     * @param string $sOxId Ordering ID (default null)
     *
     * @return bool
     */
    public function delete($sOxId = null)
    {
        $sOxId = $sOxId ?? $this->getId();

        // delete PayPalOrder too
        /** @var OrderRepository $payPalOrderRepository */
        $payPalOrderRepository = $this->getServiceFromContainer(OrderRepository::class);
        $payPalOrder = $payPalOrderRepository->paypalOrderByOrderId(
            $sOxId
        );
        if ($payPalOrder->isLoaded()) {
            $payPalOrder->delete();
        }

        return parent::delete($sOxId);
    }

    /**
     * @inheritdoc
     *
     * @param string $sStatus order transaction status
     */
    protected function _setOrderStatus($sStatus)
    {
        // The status "OK" is set in PayPalCheckout by the markOrderAsPaid method.
        // Therefore, it is intercepted here.
        if (
            $sStatus === 'OK' &&
            Registry::getSession()->getVariable('isPayPalPaymentCheckout')
        ) {
            return;
        }
        parent::_setOrderStatus($sStatus);
    }

    public function setOrderProcessTrackingService(OrderProcessTrackingService $orderProcessTrackingService): void
    {
        $this->orderProcessTrackingService = $orderProcessTrackingService;
    }

    public function setModuleSettings(ModuleSettings $moduleSettings): void
    {
        $this->moduleSettings = $moduleSettings;
    }

    public function setPaymentService(PaymentService $paymentService): void
    {
        $this->paymentService = $paymentService;
    }
}
