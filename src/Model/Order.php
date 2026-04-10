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

    /**
     * @var null|OrderProcessTrackingService $orderProcessTrackingService
     */
    private $orderProcessTrackingService;
    /**
     * @var null|ModuleSettings $moduleSettings
     */
    private $moduleSettings;
    /**
     * @var null|PaymentService $paymentService
     */
    private $paymentService;

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

    public function __construct()
    {
        parent::__construct();
        $this->orderProcessTrackingService = $this->getServiceFromContainer(OrderProcessTrackingService::class);
        $this->moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $this->paymentService = $this->getServiceFromContainer(PaymentService::class);
    }

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

        // If the order was already completed by the AJAX captureOrder flow
        // (markOrderPaid + setTransId in PayPalOrderCompletedSubscriber),
        // skip redundant processing. Without this guard, finalizeacdc would
        // attempt to capture/authorize an already-captured order, fail with
        // an API error, and incorrectly cancel the paid order.
        if ($this->isOrderPaid() && !empty($this->getFieldData('oxtransid'))) {
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log('debug', 'finalizeOrderAfterExternalPayment: order already paid, skipping', [
                'shopOrderId' => $this->getId(),
                'payPalOrderId' => $payPalOrderId,
                'transId' => $this->getFieldData('oxtransid')
            ]);
            return;
        }

        $payPalApiOrder = $this->paymentService->fetchOrderFields($payPalOrderId);
        $basket = Registry::getSession()->getBasket();
        $user = Registry::getSession()->getUser();
        $this->afterOrderCleanUp($basket, $user);

        $isPayPalACDC = $paymentsId === PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID;
        $isPaypalGooglePay = $paymentsId === PayPalDefinitions::GOOGLEPAY_PAYPAL_PAYMENT_ID;
        $isPayPalStandard = $paymentsId === PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID;
        $isPaypalApplePay = $paymentsId === PayPalDefinitions::APPLEPAY_PAYPAL_PAYMENT_ID;
        $isUAPM = PayPalDefinitions::isUAPMPayment($paymentsId);

        $transactionId = null;
        $payPalPaymentSuccess = true;

        if (
            ($isPayPalACDC && $forceFetchDetails) ||
            $isPaypalGooglePay ||
            $isPaypalApplePay ||
            $isUAPM
        ) {
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
            }
            elseif ($isUAPM && $this->isPayPalOrderApproved($payPalApiOrder)) {
                $this->markOrderPaymentNotFinished();
                $transactionId = $this->extractTransactionId($payPalApiOrder);
                //prepare capture tracking
                $this->paymentService->trackPayPalOrder(
                    $this->getId(),
                    $payPalOrderId,
                    $paymentsId,
                    PayPalApiOrder::STATUS_APPROVED,
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
            ($isPayPalStandard || $isPayPalACDC) &&
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
                    $this->markOrderPaymentFailed();
                    $logger->log('error', 'Error on order authorization call.', [$result]);
                    throw PayPalException::cannotFinalizeOrderAfterExternalPayment($payPalOrderId, $paymentsId);
                }
            } catch (Exception $exception) {
                $this->markOrderPaymentFailed();
                throw PayPalException::cannotFinalizeOrderAfterExternalPayment($payPalOrderId, $paymentsId);
            }

            $transactionId = '';

            $this->markOrderPaymentNotFinished();
            $this->paymentService->trackPayPalOrder(
                $this->getId(),
                $payPalOrderId,
                $paymentsId,
                PayPalApiOrder::STATUS_APPROVED
            );
        } elseif (
            $isPayPalStandard &&
            $this->moduleSettings
                ->getPayPalStandardCaptureStrategy() === 'directly'
        ) {
            // PayPal Standard directly, PayPal Paylater
            $payPalPaymentSuccess = $this->doExecutePayPalPayment($payPalOrderId);
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
                    $this->markOrderPaymentFailed();
                    throw PayPalException::cannotFinalizeOrderAfterExternalPayment($payPalOrderId, $paymentsId);
                }
            }

            $this->setTransId($capture->id);
        }

        $this->sendPayPalOrderByEmail($user, $basket);
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

        $this->_markVouchers($oBasket, $oUser);

        return parent::_sendOrderByEmail($oUser, $oBasket, $oPayment);
    }

    /**
     * @inheritDoc
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket basket object
     * @param \OxidEsales\Eshop\Application\Model\User   $oUser   user object
     * @deprecated underscore prefix violates PSR12, will be renamed to "markVouchers" in next major
     */
    protected function _markVouchers($oBasket, $oUser) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sessionPaymentId = (string) $this->paymentService->getSessionPaymentId();

        // Skip markVoucher if finalizeOrder is called in the proxyController.
        if (PayPalDefinitions::isProxyControllerPayment($sessionPaymentId)) {
            return null;
        }

        return parent::_markVouchers($oBasket, $oUser);
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
     * Override to use SELECT ... FOR UPDATE for stock validation.
     * This prevents race conditions where two parallel requests both read
     * the same stock level and both proceed to place an order.
     * Requires an active DB transaction (started in finalizeOrder) to hold the lock.
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket basket object
     */
    public function validateStock($oBasket)
    {
        foreach ($oBasket->getContents() as $key => $oContent) {
            try {
                $oProd = $oContent->getArticle(true, null, true);
            } catch (\OxidEsales\Eshop\Core\Exception\NoArticleException $oEx) {
                $oBasket->removeItem($key);
                throw $oEx;
            } catch (\OxidEsales\Eshop\Core\Exception\ArticleInputException $oEx) {
                $oBasket->removeItem($key);
                throw $oEx;
            }

            $dArtStockAmount = $oBasket->getArtStockInBasket($oProd->getId(), $key);
            // 3rd parameter: selectForUpdate = true
            $iOnStock = $oProd->checkForStock($oContent->getAmount(), $dArtStockAmount, true);
            if ($iOnStock !== true) {
                /** @var \OxidEsales\Eshop\Core\Exception\OutOfStockException $oEx */
                $oEx = oxNew(\OxidEsales\Eshop\Core\Exception\OutOfStockException::class);
                $oEx->setMessage('ERROR_MESSAGE_OUTOFSTOCK_OUTOFSTOCK');
                $oEx->setArticleNr($oProd->oxarticles__oxartnum->value);
                $oEx->setProductId($oProd->getId());
                $oEx->setBasketIndex($key);

                if (!is_numeric($iOnStock)) {
                    $iOnStock = 0;
                }
                $oEx->setRemainingAmount($iOnStock);
                throw $oEx;
            }
        }
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
            if (!Registry::getSession()->getVariable('isPayPalPaymentCheckout')) {
                return self::ORDER_STATE_PAYMENTERROR;
            }
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
                $this->cancelPayPalOrder();
                /** @var LoggerInterface $logger */
                $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
                $logger->log('error', $exception->getMessage(), [$exception]);
            }
            return self::ORDER_STATE_PAYMENTERROR;
        }

        // Handle Express/Button payments with proper storno on failure
        if (PayPalDefinitions::isButtonPayment($sessionPaymentId)) {
            $oPayTransaction = $this->_getGateway();
            $oPayTransaction->setPaymentParams($userpayment);

            if (!$oPayTransaction->executePayment($basket->getPrice()->getBruttoPrice(), $this)) {
                $this->cancelPayPalOrder();

                if (method_exists($oPayTransaction, 'getLastError')) {
                    if (($sLastError = $oPayTransaction->getLastError())) {
                        return $sLastError;
                    }
                }
                if (method_exists($oPayTransaction, 'getLastErrorNo')) {
                    if (($iLastErrorNo = $oPayTransaction->getLastErrorNo())) {
                        return $iLastErrorNo;
                    }
                }

                return self::ORDER_STATE_PAYMENTERROR;
            }
            return true;
        }

        // for all other PayPal-Payments ignore the _executePayment, because it is handle before
        if (Registry::getSession()->getVariable('isPayPalPaymentCheckout')) {
            return true;
        }

        // Gateway payments (e.g. PUI) are processed by OXID's core PaymentGateway,
        // so they must fall through to parent::_executePayment().
        if (PayPalDefinitions::isGatewayPayment($sessionPaymentId)) {
            return parent::_executePayment($basket, $userpayment);
        }

        // Reject any PayPal payment that was not handled by the flows above
        if (PayPalDefinitions::isPayPalPayment($sessionPaymentId)) {
            return self::ORDER_STATE_PAYMENTERROR;
        }

        return parent::_executePayment($basket, $userpayment);
    }

    /**
     * Properly cancels a failed PayPal order: storno with stock release,
     * mark as failed, clean up PayPal session, and only hard-delete if
     * no order number was assigned.
     *
     * @return bool true if order was deleted, false if kept as storno
     */
    public function cancelPayPalOrder(): bool
    {
        // Safety guard: never cancel an order that has been successfully captured
        if ($this->isOrderSuccessfullyPaid() || !empty($this->getFieldData('oxtransid'))) {
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log('debug', sprintf(
                'PayPal order with id %s (nr: %s) cancel skipped - payment already processed (transid: %s)',
                $this->getId(),
                $this->getFieldData('oxordernr'),
                $this->getFieldData('oxtransid')
            ));
            return false;
        }

        // Safety guard: check PayPal API status before canceling.
        // Covers the race condition where the customer clicked "Pay" in the
        // PayPal popup but closed it before the onApprove callback fired.
        // In that case PayPal may have already approved/captured the payment
        // even though the frontend triggered a cancel.
        if ($this->isPayPalOrderApprovedOrCaptured()) {
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log('warning', sprintf(
                'PayPal order with id %s (nr: %s) cancel blocked - PayPal order already approved/captured',
                $this->getId(),
                $this->getFieldData('oxordernr')
            ));
            return false;
        }

        $this->cancelOrder();
        $this->markOrderPaymentFailed();
        $this->save();

        PayPalSession::unsetPayPalOrderId();

        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');

        if (!$this->hasOrderNumber()) {
            $this->delete();
            $logger->log('debug', sprintf(
                'PayPal order with id %s was canceled and deleted (no order number)',
                $this->getId()
            ));
            return true;
        }

        $logger->log('debug', sprintf(
            'PayPal order with id %s (nr: %s) was canceled and kept as storno',
            $this->getId(),
            $this->getFieldData('oxordernr')
        ));

        return false;
    }

    /**
     * Checks the PayPal API to see if the order has already been
     * approved or captured. This prevents canceling an order where
     * the customer clicked "Pay" but closed the popup before the
     * onApprove JS callback could fire.
     */
    protected function isPayPalOrderApprovedOrCaptured(): bool
    {
        $payPalOrderId = $this->getPayPalOrderIdForOxOrderId();
        if (!$payPalOrderId) {
            return false;
        }

        try {
            $orderService = Registry::get(ServiceFactory::class)->getOrderService();
            $apiOrder = $orderService->showOrderDetails(
                $payPalOrderId,
                '',
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
            );

            $status = $apiOrder->status ?? '';

            return in_array($status, ['APPROVED', 'COMPLETED'], true);
        } catch (ApiException $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log('debug', sprintf(
                'PayPal API check failed for order %s during cancel guard: %s',
                $this->getId(),
                $exception->getMessage()
            ));
            // If the API call fails, allow the cancel to proceed
            return false;
        }
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
            $this->paymentService->doCapturePayPalOrder($this, $payPalOrderId, $sessionPaymentId);
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

    public function markOrderPaymentFailed(): void
    {
        $this->_setOrderStatus('ERROR');
    }

    public function markOrderPaymentNotFinished(): void
    {
        $this->_setOrderStatus('NOT_FINISHED');
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

    public function isOrderSuccessfullyPaid(): bool
    {
        return $this->paidWithPayPal() &&
            $this->isOrderFinished() &&
            $this->isOrderPaid();
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
        }
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
        $oOrderId = $oSession->getVariable('sess_challenge');

        //we might have the case that the order is already stored but we are waiting for webhook events
        if (
            $this->paymentService->isPayPalPayment()
        ) {
            //order payment is being processed
            $isLoaded = $this->load($oOrderId);
            if ($isLoaded) {
                // If the order was already completed by the AJAX capture flow
                // (markOrderPaid + setTransId in PayPalOrderCompletedSubscriber),
                // do not create a duplicate order. The PayPal session is already
                // cleaned up at this point, so isOrderExecutionInProgress() returns
                // false and the webhook-wait guard below would not catch this case.
                // This prevents a race condition with stock-1 articles where the
                // browser redirect triggers a second finalizeOrder() call.
                if ($this->isOrderPaid() && !empty($this->getFieldData('oxtransid'))) {
                    $logger->log('debug', 'finalizeOrder: order already paid, skipping duplicate finalization', [
                        'shopOrderId' => $oOrderId,
                        'transId' => $this->getFieldData('oxtransid')
                    ]);
                    return 1;
                }

                if (
                    $this->paymentService->isOrderExecutionInProgress() &&
                    !$this->isOrderFinished() &&
                    !$this->isOrderPaid() &&
                    !$this->isWaitForWebhookTimeoutReached()
                ) {
                    return self::ORDER_STATE_WAIT_FOR_WEBHOOK_EVENTS;
                }
            }
        }

        // ! paranoia-check !
        // It can happen that a customer starts a PayPal session, then goes through the checkout again in a second tab,
        // selects a different payment method, and tries to finalize.
        $bIsPayPalPayment = $this->paymentService->isPayPalPayment();
        $payPalOrderRepository = $this->getServiceFromContainer(OrderRepository::class);
        $payPalOrderId = $payPalOrderRepository->getPayPalOrderIdByShopOrderId(
            $oOrderId
        );

        if (!$bIsPayPalPayment && $payPalOrderId)
        {
            $this->paymentService->removeTemporaryOrder();
            $oSession->setVariable('sess_challenge', Registry::getUtilsObject()->generateUId());
        }

        // Wrap in DB transaction so that validateStock (SELECT ... FOR UPDATE)
        // holds an exclusive lock until stock reduction in save() completes.
        // This prevents two parallel requests from reading the same stock level.
        if (!$recalculatingOrder) {
            $db = DatabaseProvider::getDb();
            $db->startTransaction();
            try {
                $result = parent::finalizeOrder($basket, $user, $recalculatingOrder);
                $db->commitTransaction();
            } catch (\Exception $e) {
                $db->rollbackTransaction();
                throw $e;
            }
        } else {
            $result = parent::finalizeOrder($basket, $user, $recalculatingOrder);
        }

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

    public function isPayPalOrderApproved(PayPalApiOrder $apiOrder): bool
    {
        return (
            isset(
                $apiOrder->status
            ) &&
            $apiOrder->status === PayPalApiOrder::STATUS_APPROVED
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
