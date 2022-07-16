<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidEsales\Eshop\Application\Model\UserPayment as EshopModelUserPayment;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Capture;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalOrder;
use OxidSolutionCatalysts\PayPalApi\Service\Orders;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;

/**
 * PayPal Eshop model order class
 *
 * @mixin \OxidEsales\Eshop\Application\Model\Order
 */
class Order extends Order_parent
{
    use ServiceContainer;

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
     * Error during payment execution
     *
     * @var int
     */
    public const ORDER_STATE_PAYMENTERROR = 2;

    /**
     * PayPal order information
     *
     * @var PayPalOrder
     */
    protected $payPalOrder;

    /**
     * PayPal order Id
     *
     * @var string
     */
    protected $payPalOrderId;

    /**
     * PayPalPlus order Id
     *
     * @var string
     */
    protected $payPalPlusOrderId;

    /**
     * PayPalPlus order Id
     *
     * @var string
     */
    protected $payPalSoapOrderId;

    /**
     * PayPal Billing Agreement Id;
     *
     * @var string
     */
    protected $payPalBillingAgreementId;

    /**
     * PayPal Product Id
     *
     * @var string
     */
    protected $payPalProductId;

    public function savePuiInvoiceNr(string $invoiceNr): void
    {
        $this->assign(
            ['oxinvoicenr' => $invoiceNr]
        );
        $this->save();
    }

    public function finalizeOrderAfterExternalPayment(string $payPalOrderId): void
    {
        if (!$this->isLoaded()) {
            throw PayPalException::cannotFinalizeOrderAfterExternalPaymentSuccess($payPalOrderId);
        }

        if (!$this->oxorder__oxordernr->value) {
            $this->_setNumber();
        } else {
            oxNew(\OxidEsales\Eshop\Core\Counter::class)->update(
                $this->_getCounterIdent(),
                $this->oxorder__oxordernr->value
            );
        }

        $this->_updateOrderDate();

        $basket = Registry::getSession()->getBasket();
        $user = Registry::getSession()->getUser();
        $paymentsId = $this->getFieldData('oxpaymenttype');
        $this->afterOrderCleanUp($basket, $user);

        $this->setTransId($payPalOrderId);

        $isPayPalUAPM = PayPalDefinitions::isUAPMPayment($paymentsId);
        $isPayPalACDC = $paymentsId === PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID;
        $isPayPalStandard = $paymentsId === PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID;
        $isPayPalPayLater = $paymentsId === PayPalDefinitions::PAYLATER_PAYPAL_PAYMENT_ID;

        if ($isPayPalUAPM || $isPayPalStandard || $isPayPalPayLater) {
            if (
                $isPayPalStandard &&
                $this->getServiceFromContainer(ModuleSettings::class)
                    ->getPayPalStandardCaptureStrategy() !== 'directly'
            ) {
                $this->_setOrderStatus('NOT_FINISHED');
                $paymentService = $this->getServiceFromContainer(PaymentService::class);
                $paymentService->trackPayPalOrder(
                    $this->getId(),
                    $payPalOrderId,
                    $paymentsId,
                    PayPalOrder::STATUS_APPROVED
                );
            } else {
                $this->doExecutePayPalPayment($payPalOrderId);
            }
        } elseif ($isPayPalACDC) {
            $this->markOrderPaid();
        }

        $userPayment = oxNew(EshopModelUserPayment::class);
        $userPayment->load($this->getFieldData('oxpaymentid'));

        $session = Registry::getSession();
        $session->setVariable('blDontCheckProductStockForPayPalMails', true);
        $this->_sendOrderByEmail($user, $basket, $userPayment);
        $session->deleteVariable('blDontCheckProductStockForPayPalMails');
    }

    //TODO: this place should be refactored in shop core
    protected function afterOrderCleanUp(EshopModelBasket $basket, EshopModelUser $user): void
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
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket      basket object
     * @param object                                     $oUserpayment user payment object
     *
     * @return  integer 2 or an error code
     * @deprecated underscore prefix violates PSR12, will be renamed to "executePayment" in next major
     */
    protected function _executePayment($basket, $userpayment) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $sessionPaymentId = (string) $paymentService->getSessionPaymentId();

        $isPayPalUAPM = PayPalDefinitions::isUAPMPayment($sessionPaymentId);
        $isPayPalACDC = $sessionPaymentId === PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID;
        $isPayPalStandard = $sessionPaymentId === PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID;
        $isPayPalPayLater = $sessionPaymentId === PayPalDefinitions::PAYLATER_PAYPAL_PAYMENT_ID;

        //catch UAPM, Standard and Pay Later PayPal payments here
        if ($isPayPalUAPM || $isPayPalStandard || $isPayPalPayLater) {
            try {
                if ($isPayPalUAPM) {
                    $redirectLink = $paymentService->doExecuteUAPMPayment($this, $basket);
                } else {
                    $intent = $this->getServiceFromContainer(ModuleSettings::class)
                        ->getPayPalStandardCaptureStrategy() === 'directly' ?
                        Constants::PAYPAL_ORDER_INTENT_CAPTURE :
                        Constants::PAYPAL_ORDER_INTENT_AUTHORIZE;

                    $redirectLink = $paymentService->doExecuteStandardPayment($this, $basket, $intent);
                    if ($isPayPalPayLater) {
                        $redirectLink .= '&fundingSource=paylater';
                    }
                }
                PayPalSession::setSessionRedirectLink($redirectLink);

                return self::ORDER_STATE_SESSIONPAYMENT_INPROGRESS;
            } catch (\Exception $exception) {
                $this->delete();
                Registry::getLogger()->error($exception->getMessage(), [$exception]);
            }
            return self::ORDER_STATE_PAYMENTERROR;
        } elseif ($isPayPalACDC) {
            return self::ORDER_STATE_ACDCINPROGRESS;
        } else {
            return parent::_executePayment($basket, $userpayment);
        }
    }

    /**
     * Get PayPal order object for the current active order object
     * Result is cached and returned on subsequent calls
     *
     * @return PayPalOrder
     * @throws ApiException
     */
    public function getPayPalCheckoutOrder(): PayPalOrder
    {
        if (!$this->payPalOrder) {
            /** @var Orders $orderService */
            $orderService = Registry::get(ServiceFactory::class)->getOrderService();
            $this->payPalOrder = $orderService->showOrderDetails($this->getPayPalOrderIdForOxOrderId(), '');
        }

        return $this->payPalOrder;
    }

    protected function doExecutePayPalPayment($payPalOrderId): bool
    {
        /** @var PaymentService $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $sessionPaymentId = (string) $paymentService->getSessionPaymentId();
        $success = false;

        // Capture Order
        try {
            $paymentService->doCapturePayPalOrder($this, $payPalOrderId, $sessionPaymentId);
            $success = true;
        } catch (\Exception $exception) {
            Registry::getLogger()->error("Error on order capture call.", [$exception]);
        }

        $this->markOrderPaid();

        // destroy PayPal-Session
        PayPalSession::storePayPalOrderId('');

        return $success;
    }

    /**
     * Update order oxpaid to current time.
     */
    public function markOrderPaid()
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
    public function setTransId($sTransId)
    {
        $db = DatabaseProvider::getDb();

        $query = 'update oxorder set oxtransid=? where oxid=?';
        $db->execute($query, [$sTransId, $this->getId()]);

        //updating order object
        $this->oxorder__oxtransid = new Field($sTransId);
    }

    public function markOrderPaymentFailed()
    {
        $this->_setOrderStatus('ERROR');
    }

    /**
     * Returns PayPal order id.
     *
     * @param string|null $oxId
     *
     * @return string
     */
    public function getPayPalOrderIdForOxOrderId(string $oxId = null)
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
    public function getPayPalPlusOrderIdForOxOrderId(string $oxId = null)
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
    public function getPayPalSoapOrderIdForOxOrderId(string $oxId = null)
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
        $order = oxNew(PayPalPlusOrder::class);
        return $order->tableExists();
    }

    /**
     * Checks if PayPalSoap-tables exists anymore
     *
     * @return bool
     */
    public function tableExitsForPayPalSoap(): bool
    {
        $order = oxNew(PayPalSoapOrder::class);
        return $order->tableExists();
    }

    /**
     * Get order payment capture or null if not captured
     *
     * @return Capture|null
     * @throws ApiException
     */
    public function getOrderPaymentCapture(): ?Capture
    {
        return $this->getPayPalCheckoutOrder()->purchase_units[0]->payments->captures[0] ?? null;
    }
}
