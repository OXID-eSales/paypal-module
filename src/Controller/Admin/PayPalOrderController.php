<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use Exception;
use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalModelPayPalOrder;
use OxidSolutionCatalysts\PayPal\Model\PayPalPlusOrder;
use OxidSolutionCatalysts\PayPal\Model\PayPalSoapOrder;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\AdminOrderTrait;
use OxidSolutionCatalysts\PayPal\Traits\RequestDataGetter;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Capture;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderModel;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentCollection;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\Refund;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\RefundRequest;
use OxidSolutionCatalysts\PayPalApi\Service\Payments;

/**
 * Order class wrapper for PayPal module
 */
class PayPalOrderController extends AdminDetailsController
{
    use AdminOrderTrait;
    use RequestDataGetter;

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
    public function executeFunction($functionName): void
    {
        try {
            parent::executeFunction($functionName);
        } catch (Exception $exception) {
            $this->addTplParam(
                'error',
                method_exists($exception, 'getErrorDescription')
                ? $exception->getErrorDescription() : $exception->getMessage()
            );
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


        /** @var \OxidSolutionCatalysts\PayPal\Model\Order $order */
        $order = $this->getOrder();
        $orderId = $this->getEditObjectId();
        $this->addTplParam('oxid', $orderId);
        $this->addTplParam('order', $order);
        $this->addTplParam('payPalOrder', null);

        if ($order->paidWithPayPal()) {
            // normal PayPal order
            try {
                /** @var PayPalOrder $paypalOrder */
                $paypalOrder = $this->getPayPalCheckoutOrder();
                $this->addTplParam('payPalOrder', $paypalOrder);

                /** @var ?Capture $capture */
                $capture = $order->getOrderPaymentCapture();
                $this->addTplParam('capture', $capture);
                $transactionId = $capture ? (string)$capture->id : '';

                /** @var \OxidSolutionCatalysts\PayPal\Model\PayPalOrder $paypalOrderModel */
                $paypalOrderModel = $this->getServiceFromContainer(OrderRepository::class)
                    ->paypalOrderByOrderIdAndPayPalId($orderId, (string)$paypalOrder->id, $transactionId);
                $this->addTplParam('payPalOrderDetails', $paypalOrderModel);

                //TODO: refactor, this is workaround if webhook failed to update information
                if (
                    $capture &&
                    (
                        (
                            ApiOrderModel::STATUS_SAVED === $paypalOrderModel->getStatus() &&
                            Capture::STATUS_COMPLETED === $capture->status
                        ) ||
                        (
                            Capture::STATUS_COMPLETED === $paypalOrderModel->getStatus() &&
                            (
                                Capture::STATUS_REFUNDED === $capture->status ||
                                Capture::STATUS_PARTIALLY_REFUNDED === $capture->status
                            )
                        )
                    )
                ) {
                    $paypalOrderModel->setStatus($capture->status);
                    $paypalOrderModel->save();
                }
            } catch (ApiException $exception) {
                $this->addTplParam('error', $lang->translateString('OSC_PAYPAL_ERROR_' . $exception->getErrorIssue()));
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
     * Refund payment action
     *
     * @throws ApiException
     * @throws StandardException
     */
    public function refund(): void
    {
        $refundAmount = self::getRequestStringParameter('refundAmount', true);
        $refundAmount = str_replace(",", ".", $refundAmount);
        $refundAmount = preg_replace("/[\,\.](\d{3})/", "$1", $refundAmount);
        $invoiceId = self::getRequestStringParameter('invoiceId', true);
        $refundAll = self::getRequestStringParameter('refundAll', true);
        $noteToPayer = self::getRequestStringParameter('noteToPayer', true);

        /** @var \OxidSolutionCatalysts\PayPal\Model\Order $order */
        $order = $this->getOrder();
        $capture = $order->getOrderPaymentCapture();
        if ($capture instanceof Capture) {
            $request = new RefundRequest();
            $request->note_to_payer = $noteToPayer;
            $request->invoice_id = !empty($invoiceId) ? $invoiceId : null;
            if (!$refundAll) {
                $request->initAmount();
                $amount = $request->amount;
                if ($amount) {
                    $currency_code = $amount->currency_code;
                    $amount->currency_code = $currency_code;
                    $amount->value = is_string($refundAmount) ? (string)$refundAmount : '';
                }
            }

            /** @var Payments $apiPaymentService */
            $apiPaymentService = Registry::get(ServiceFactory::class)->getPaymentService();

            /** @var OrderRepository $orderRepository */
            $orderRepository = $this->getServiceFromContainer(OrderRepository::class);
            /** @var PayPalModelPayPalOrder $payPalOrder */
            $payPalOrder = $orderRepository->paypalOrderByOrderIdAndPayPalId(
                $order->getId(),
                '',
                $order->getPaypalStringData('oxtransid')
            );

            /** @var Refund $refund */
            $refund = $apiPaymentService->refundCapturedPayment(
                $capture->id,
                $request,
                '',
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
            );

            /** @var PaymentService $paymentService */
            $paymentService = $this->getServiceFromContainer(PaymentService::class);
            $paymentService->trackPayPalOrder(
                $order->getId(),
                $payPalOrder->getPayPalOrderId(),
                $order->getPaypalStringData('oxpaymenttype'),
                (string) $refund->status,
                (string) $refund->id,
                Constants::PAYPAL_TRANSACTION_TYPE_REFUND
            );
        }
        // reset the order to get new informations about successful refund
        $this->refreshOrder();
    }

    /**
     * @return PayPalPlusOrder
     * @throws StandardException
     */
    protected function getPayPalPlusOrder(): PayPalPlusOrder
    {
        if (! $this->payPalPlusOrder instanceof PayPalPlusOrder) {
            $order = oxNew(PayPalPlusOrder::class);
            $orderId = $this->getEditObjectId();
            if ($orderId == null || !$order->loadByOrderId($orderId)) {
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
        if ($this->payPalSoapOrder instanceof PayPalSoapOrder) {
            $order = oxNew(PayPalSoapOrder::class);
            $orderId = $this->getEditObjectId();
            if ($orderId == null || !$order->loadByOrderId($orderId)) {
                throw new StandardException('PayPalSoapOrder not found');
            }
            $this->payPalSoapOrder = $order;
        }
        return $this->payPalSoapOrder;
    }

    /**
     * Template getter getPayPalPaymentStatus
     */
    public function getPayPalPaymentStatus(): ?string
    {
        return $this->getPayPalCheckoutOrder()->status;
    }

    /**
     * Template getter getPayPalTotalOrderSum
     */
    public function getPayPalTotalOrderSum(): float
    {
        $amountValue = 0.0;
        try {
            $amount = $this->getPayPalCheckoutOrder()->purchase_units[0]->amount;
            $amountValue = $amount ? (float)$amount->value : $amountValue;
        } catch (StandardException $e) {
        } catch (ApiException $e) {
        }

        return $amountValue;
    }

    /**
     * Template getter getPayPalCapturedAmount
     */
    public function getPayPalCapturedAmount(): float
    {
        $captureAmount = 0.0;
        $order = $this->getPayPalCheckoutOrder();
        $paymentCollection = $order->purchase_units[0]->payments;
        $captures = $paymentCollection ? $paymentCollection->captures : [];

        if (!empty($captures)) {
            foreach ($captures as $capture) {
                $money = $capture->amount;
                if ($money) {
                    $captureAmount += (float)$money->value;
                }
            }
        }
        return $captureAmount;
    }

    /**
     * Template getter getPayPalCapturedAmount
     */
    public function getPayPalAuthorizationAmount(): float
    {
        $authorizationAmount = 0.0;
        $order = $this->getPayPalCheckoutOrder();
        /** @var PaymentCollection $paymentCollection */
        $paymentCollection = $order->purchase_units[0]->payments;
        $authorizations = (array) $paymentCollection->authorizations;

        foreach ($authorizations as $authorization) {
            $amount = $authorization->amount;
            if (isset($amount->value)) {
                $authorizationAmount += (float)$amount->value;
            }
        }
        return $authorizationAmount;
    }

    /**
     * Template getter getPayPalRefundedAmount
     */
    public function getPayPalRefundedAmount(): float
    {
        $refundAmount = 0.0;
        /** @var PaymentCollection $paymentCollection */
        $paymentCollection = $this->getPayPalCheckoutOrder()->purchase_units[0]->payments;
        $refunds = (array) $paymentCollection->refunds;

        foreach ($refunds as $refund) {
            $amount = $refund->amount;
            if (isset($amount->value)) {
                $refundAmount += (float)$amount->value;
            }
        }
        return $refundAmount;
    }

    /**
     * Template getter getPayPalRemainingRefundAmount
     */
    public function getPayPalRemainingRefundAmount(): float
    {
        return $this->getPayPalCapturedAmount() - $this->getPayPalRefundedAmount();
    }

    /**
     * Template getter getPayPalRemainingRefundAmount
     */
    public function getPayPalResultedAmount(): float
    {
        return $this->getPayPalTotalOrderSum() - $this->getPayPalCapturedAmount();
    }

    /**
     * Template getter getPayPalCurrency
     */
    public function getPayPalCurrency(): string
    {
        $amountWithBreakdown = $this->getPayPalCheckoutOrder()->purchase_units[0]->amount;
        if ($amountWithBreakdown instanceof AmountWithBreakdown) {
            $amountBreakdown = $amountWithBreakdown->breakdown;
            $money = $amountBreakdown->item_total ?? null;
            return $money && !empty($money->currency_code) ? $money->currency_code : '';
        }

        return '';
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
    public function formatDate(string $date, bool $forSort = false): string
    {
        $timestamp = (int)strtotime($date);
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
        $purchaseUnitData = [];
        if (!$this->payPalOrderHistory) {
            $this->payPalOrderHistory = [];

            $payPalOrder = $this->getPayPalCheckoutOrder();
            $paymentCollection = $payPalOrder->purchase_units[0]->payments;
            if ($paymentCollection) {
                $purchaseUnitData = [
                    'captures' => is_array($paymentCollection->captures) ?
                        $paymentCollection->captures :
                        [],
                    'refunds' => is_array($paymentCollection->refunds) ?
                        $paymentCollection->refunds :
                        [],
                    'authorizations' => is_array($paymentCollection->authorizations) ?
                        $paymentCollection->authorizations :
                        [],
                ];
            }
            if (isset($purchaseUnitData['captures'])) {
                foreach ($purchaseUnitData['captures'] as $capture) {
                    $amount = $capture->amount;
                    $this->payPalOrderHistory[$this->formatDate((string)$capture->create_time, true)] = [
                        'action'        => 'CAPTURED',
                        'amount'        => $amount ? $amount->value : "0",
                        'date'          => $this->formatDate((string)$capture->create_time),
                        'status'        => $capture->status,
                        'transactionid' => $capture->id,
                        'comment'       => '',
                        'invoiceid'     => $capture->invoice_id
                    ];
                }
            }
            if (isset($purchaseUnitData['refunds'])) {
                foreach ($purchaseUnitData['refunds'] as $refund) {
                    $amount = $refund->amount;
                    $this->payPalOrderHistory[$this->formatDate((string)$refund->create_time, true)] = [
                        'action'        => 'REFUNDED',
                        'amount'        => $amount ? $amount->value : "0",
                        'date'          => $this->formatDate((string)$refund->create_time),
                        'status'        => $refund->status,
                        'transactionid' => $refund->id,
                        'comment'       => $refund->note_to_payer,
                        'invoiceid'     => $refund->invoice_id
                    ];
                }
            }
            if (isset($purchaseUnitData['authorizations'])) {
                foreach ($purchaseUnitData['authorizations'] as $authorization) {
                    $amount = $authorization->amount;
                    $this->payPalOrderHistory[$this->formatDate((string)$authorization->create_time, true)] = [
                        'action'        => 'AUTHORIZATION',
                        'amount'        => $amount ? $amount->value : "0",
                        'date'          => $this->formatDate((string)$authorization->create_time),
                        'status'        => $authorization->status,
                        'transactionid' => $authorization->id,
                        'comment'       => '',
                        'invoiceid'     => $authorization->invoice_id
                    ];
                }
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
