<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Capture;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\RefundRequest;
use OxidSolutionCatalysts\PayPalApi\Service\Payments;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Model\PayPalPlusOrder;
use OxidSolutionCatalysts\PayPal\Model\PayPalSoapOrder;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;

/**
 * Order class wrapper for PayPal module
 */
class PayPalOrderController extends AdminDetailsController
{
    use ServiceContainer;

    /**
     * @var PayPalOrder
     */
    protected $order = null;

    /**
     * @var PayPalPlusOrder
     */
    protected $payPalPlusOrder = null;

    /**
     * @var PayPalSoapOrder
     */
    protected $payPalSoapOrder = null;

    /**
     * Default oxorder PaymentType for PayPalPlus
     *
     * @var string
     */
    protected $payPalPlusPaymentType = 'payppaypalplus';

    /**
     * Default oxorder PaymentType for PayPalSoap
     *
     * @var string
     */
    protected $payPalSoapPaymentType = 'oxidpaypal';

    /**
     * @var array
     */
    protected $payPalOrderHistory;

    /**
     * An amount still possible to refund for current order payment.
     *
     * @var null|double
     */
    protected $remainingPayPalPlusRefundAmount = null;

    /**
     * A number of remaining, possible refunds to make for current order payment.
     *
     * @var null|int
     */
    protected $remainingPayPalPlusRefunds = null;

    /**
     * Maximum number of refunds allowed per payment.
     *
     * @var int
     */
    protected $maxPayPalPlusRefunds = 10;

    /**
     * @inheritDoc
     */
    public function executeFunction($functionName)
    {
        try {
            parent::executeFunction($functionName);
        } catch (ApiException $exception) {
            $this->addTplParam('error', $exception->getErrorDescription());
            Registry::getLogger()->error($exception->getMessage());
        }
    }

    /**
     * @return string
     * @throws StandardException
     */
    public function render()
    {
        parent::render();

        $lang = Registry::getLang();

        $result = "oscpaypalorder.tpl";

        $order = $this->getOrder();
        $orderId = $this->getEditObjectId();
        $this->addTplParam('oxid', $orderId);
        $this->addTplParam('order', $order);
        $this->addTplParam('payPalOrder', null);

        if ($order->paidWithPayPal()) {
            // normal paypal order
            try {
                $this->addTplParam('payPalOrder', $this->getPayPalCheckoutOrder());
                $this->addTplParam('capture', $order->getOrderPaymentCapture());
            } catch (ApiException $exception) {
                $this->addTplParam('error', $lang->translateString('OSC_PAYPAL_ERROR_' . $exception->getErrorIssue()));
                Registry::getLogger()->error($exception->getMessage());
            }
        } elseif (
            $order->getFieldData('oxpaymenttype') == $this->payPalPlusPaymentType &&
            !$order->tableExitsForPayPalPlus()
        ) {
            $this->addTplParam('error', $lang->translateString('OSC_PAYPAL_PAYPALPLUS_TABLE_DOES_NOT_EXISTS'));
        } elseif ($order->paidWithPayPalPlus()) {
            // old paypalplus order
            $this->addTplParam('payPalOrder', $this->getPayPalPlusOrder());
            $result = "oscpaypalorder_ppplus.tpl";
        } elseif (
            $order->getFieldData('oxpaymenttype') == $this->payPalSoapPaymentType &&
            !$order->tableExitsForPayPalSoap()
        ) {
            $this->addTplParam('error', $lang->translateString('OSC_PAYPAL_PAYPALSOAP_TABLE_DOES_NOT_EXISTS'));
        } elseif ($order->paidWithPayPalSoap()) {
            // old paypalsoap order
            $this->addTplParam('payPalOrder', $this->getPayPalSoapOrder());
            $result = "oscpaypalorder_pp.tpl";
        } else {
            $this->addTplParam('error', $lang->translateString('OSC_PAYPAL_ERROR_NOT_PAID_WITH_PAYPAL'));
        }
        return $result;
    }

    /**
     * Capture payment action
     *
     * @throws ApiException
     * @throws StandardException
     */
    public function capture(): void
    {
        $order = $this->getOrder();
        $paypalOrder = $this->getPayPalCheckoutOrder();
        $orderId = $paypalOrder->id;

        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $service = $serviceFactory->getOrderService();
        $request = new OrderCaptureRequest();
        $response = $service->capturePaymentForOrder('', $orderId, $request, '');

        if (
            $response->status == PayPalOrder::STATUS_COMPLETED &&
            $response->purchase_units[0]->payments->captures[0]->status == Capture::STATUS_COMPLETED
        ) {
            $order->markOrderPaid();

            /** @var \OxidSolutionCatalysts\PayPal\Model\PayPalOrder $paypalOrderModel */
            $paypalOrderModel = $this->getServiceFromContainer(OrderRepository::class)
                ->paypalOrderByOrderIdAndPayPalId($order->getId(), $orderId);
            $paypalOrderModel->setStatus($response->status);
            $paypalOrderModel->save();
        }
    }

    /**
     * Refund payment action
     *
     * @throws ApiException
     * @throws StandardException
     */
    public function refund(): void
    {
        $request = Registry::getRequest();
        $refundAmount = $request->getRequestEscapedParameter('refundAmount');
        $refundAmount = str_replace(',', '.', $refundAmount);
        $invoiceId = $request->getRequestEscapedParameter('invoiceId');
        $refundAll = $request->getRequestEscapedParameter('refundAll');
        $noteToPayer = $request->getRequestParameter('noteToPayer');

        $capture = $this->getOrder()->getOrderPaymentCapture();
        if ($capture instanceof Capture) {
            $request = new RefundRequest();
            $request->note_to_payer = $noteToPayer;
            $request->invoice_id = !empty($invoiceId) ? $invoiceId : null;
            if (!$refundAll) {
                $request->initAmount();
                $request->amount->currency_code = $capture->amount->currency_code;
                $request->amount->value = $refundAmount;
            }

            /** @var Payments $paymentService */
            $paymentService = Registry::get(ServiceFactory::class)->getPaymentService();
            $paymentService->refundCapturedPayment($capture->id, $request, '');
        }
    }

    /**
     * @return PayPalOrder
     * @throws StandardException
     * @throws ApiException
     */
    protected function getPayPalCheckoutOrder(): PayPalOrder
    {
        $order = $this->getOrder();
        return $order->getPayPalCheckoutOrder();
    }

    /**
     * @return PayPalPlusOrder
     * @throws StandardException
     */
    protected function getPayPalPlusOrder(): PayPalPlusOrder
    {
        if (is_null($this->payPalPlusOrder)) {
            $order = oxNew(PayPalPlusOrder::class);
            $orderId = $this->getEditObjectId();
            if ($orderId === null || !$order->loadByOrderId($orderId)) {
                throw new StandardException('PayPalPlusOrder not found');
            }
            $this->payPalPlusOrder = $order;
        }
        return $this->payPalPlusOrder;
    }

    /**
     * @return PayPalSoapOrder
     * @throws StandardException
     */
    protected function getPayPalSoapOrder(): PayPalSoapOrder
    {
        if (is_null($this->payPalSoapOrder)) {
            $order = oxNew(PayPalSoapOrder::class);
            $orderId = $this->getEditObjectId();
            if ($orderId === null || !$order->loadByOrderId($orderId)) {
                throw new StandardException('PayPalSoapOrder not found');
            }
            $this->payPalSoapOrder = $order;
        }
        return $this->payPalSoapOrder;
    }

    /**
     * Get active order
     *
     * @return Order
     * @throws StandardException
     */
    protected function getOrder(): Order
    {
        if (is_null($this->order)) {
            $order = oxNew(Order::class);
            $orderId = $this->getEditObjectId();
            if ($orderId === null || !$order->load($orderId)) {
                throw new StandardException('PayPalCheckout-Order not found');
            }
            $this->order = $order;
        }
        return $this->order;
    }

    /**
     * Get order payment capture id
     *
     * @return Capture|null
     * @throws StandardException|ApiException
     */
    protected function getOrderPaymentCapture(): ?Capture
    {
        return $this->getPayPalCheckoutOrder()->purchase_units[0]->payments->captures[0];
    }

    /**
     * Template getter getPayPalPaymentStatus
     */
    public function getPayPalPaymentStatus()
    {
        return $this->getPayPalCheckoutOrder()->status;
    }

    /**
     * Template getter getPayPalTotalOrderSum
     */
    public function getPayPalTotalOrderSum()
    {
        return $this->getPayPalCheckoutOrder()->purchase_units[0]->amount->value;
    }

    /**
     * Template getter getPayPalCapturedAmount
     */
    public function getPayPalCapturedAmount()
    {
        return $this->getPayPalCheckoutOrder()->purchase_units[0]->payments->captures[0]->amount->value;
    }

    /**
     * Template getter getPayPalRefundedAmount
     */
    public function getPayPalRefundedAmount()
    {
        return $this->getPayPalCheckoutOrder()->purchase_units[0]->payments->refunds[0]->amount->value;
    }

    /**
     * Template getter getPayPalRemainingRefundAmount
     */
    public function getPayPalRemainingRefundAmount()
    {
        return $this->getPayPalCapturedAmount() - $this->getPayPalRefundedAmount();
    }

    /**
     * Template getter getPayPalAuthorizationId
     */
    public function getPayPalAuthorizationId()
    {
        return $this->getPayPalCheckoutOrder()->purchase_units[0]->payments->authorizations[0]->id;
    }

    /**
     * Template getter getPayPalCurrency
     */
    public function getPayPalCurrency()
    {
        return $this->getPayPalCheckoutOrder()->purchase_units[0]->amount->breakdown->item_total->currency_code;
    }

    /**
     * Template getter getPayPalPaymentList
     */
    public function getPayPalPaymentList()
    {
        return null;
    }

    /**
     * Template getter for price formatting
     *
     * @param double $price price
     *
     * @return string
     */
    public function formatPrice($price)
    {
        return Registry::getLang()->formatCurrency($price);
    }

    /**
     * Returns formatted date
     *
     * @return string
     */
    public function formatDate($date, $forSort = false)
    {
        $timestamp = strtotime($date);
        return date(
            $forSort ? 'YmdHis' : 'd.m.Y H:i:s',
            $timestamp
        );
    }

    /**
     * Template getter for order History
     *
     * @return array
     * @throws StandardException|ApiException
     */
    public function getPayPalHistory()
    {
        if (!$this->payPalOrderHistory) {
            $this->payPalOrderHistory = [];

            $payPalOrder = $this->getPayPalCheckoutOrder();
            $purchaseUnitPayments =
                $payPalOrder->purchase_units[0] &&
                $payPalOrder->purchase_units[0]->payments ?
                $payPalOrder->purchase_units[0]->payments : null;
            $purchaseUnitData = [
                'captures' => is_array($purchaseUnitPayments->captures) ?
                    $purchaseUnitPayments->captures :
                    [],
                'refunds' => is_array($purchaseUnitPayments->refunds) ?
                    $purchaseUnitPayments->refunds :
                    [],
                'authorizations' => is_array($purchaseUnitPayments->authorizations) ?
                    $purchaseUnitPayments->authorizations :
                    [],
            ];

            foreach ($purchaseUnitData['captures'] as $capture) {
                $this->payPalOrderHistory[$this->formatDate($capture->create_time, true)] = [
                    'action'        => 'CAPTURED',
                    'amount'        => $capture->amount->value,
                    'date'          => $this->formatDate($capture->create_time),
                    'status'        => $capture->status,
                    'transactionid' => $capture->id,
                    'comment'       => '',
                    'invoiceid'     => $capture->invoice_id
                ];
            }
            foreach ($purchaseUnitData['refunds'] as $refund) {
                $this->payPalOrderHistory[$this->formatDate($refund->create_time, true)] = [
                    'action'        => 'REFUNDED',
                    'amount'        => $refund->amount->value,
                    'date'          => $this->formatDate($refund->create_time),
                    'status'        => $refund->status,
                    'transactionid' => $refund->id,
                    'comment'       => $refund->note_to_payer,
                    'invoiceid'     => $refund->invoice_id
                ];
            }
            foreach ($purchaseUnitData['authorizations'] as $authorization) {
                $this->payPalOrderHistory[$this->formatDate($authorization->create_time, true)] = [
                    'action'        => 'AUTHORIZATION',
                    'amount'        => $authorization->amount->value,
                    'date'          => $this->formatDate($authorization->create_time),
                    'status'        => $authorization->status,
                    'transactionid' => $authorization->id,
                    'comment'       => '',
                    'invoiceid'     => $authorization->invoice_id
                ];
            }
            ksort($this->payPalOrderHistory);
        }
        return $this->payPalOrderHistory;
    }

    /**
     * Get maximum possible, remaining payment amount to refund.
     *
     * @return double
     */
    public function getPayPalPlusRemainingRefundAmount(): float
    {
        if (is_null($this->remainingPayPalPlusRefundAmount)) {
            $payPalPlusOrder = $this->getPayPalPlusOrder();

            $dRemainingRefundAmount = $payPalPlusOrder->getTotal() -
                                      $payPalPlusOrder->getTotalAmountRefunded();

            if ($dRemainingRefundAmount < 0.0) {
                $dRemainingRefundAmount = 0.0;
            }

            $this->remainingPayPalPlusRefundAmount = round($dRemainingRefundAmount, 2);
        }

        return (float)$this->remainingPayPalPlusRefundAmount;
    }

    /**
     * Get remaining refunds count for current payment.
     *
     * @return int
     */
    public function getPayPalPlusRemainingRefundsCount()
    {
        if (is_null($this->remainingPayPalPlusRefunds)) {
            $iMaxRefunds = $this->maxPayPalPlusRefunds;
            $iRefundsAvailable = $iMaxRefunds;
            $payPalPlusOrder = $this->getPayPalPlusOrder();
            $iRefundsMade = 0;
            if ($refundsList = $payPalPlusOrder->getRefundsList()) {
                $iRefundsMade = $refundsList->count();
            }

            if ($iRefundsMade >= $iMaxRefunds) {
                $iRefundsAvailable = 0;
            } elseif ($iRefundsMade > 0) {
                $iRefundsAvailable = $iMaxRefunds - $iRefundsMade;
            }

            $this->remainingPayPalPlusRefunds = $iRefundsAvailable;
        }

        return $this->remainingPayPalPlusRefunds;
    }
}
