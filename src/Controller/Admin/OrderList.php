<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use OxidEsales\Eshop\Application\Model\Order;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\RefundMailService;

/**
 * OrderList class
 *
 * @mixin \OxidEsales\Eshop\Application\Controller\Admin\OrderList
 */
class OrderList extends OrderList_parent
{
    /**
     * Sends the cancellation confirmation mail after an order was cancelled in
     * the backend. Unlike a refund, a cancellation moves no money in the PayPal
     * module, so the mail confirms the cancellation only; a refund is triggered
     * separately and confirmed separately.
     *
     * Orders of other payment methods and orders that cannot be loaded are passed
     * straight through to the parent implementation.
     *
     * @return void
     */
    public function cancelOrder()
    {
        $orderId = $this->getEditObjectId();
        if (!$orderId) {
            parent::cancelOrder();

            return;
        }

        $order = oxNew(Order::class);
        if (!$order->load($orderId) || !$this->isPayPalOrder($order)) {
            parent::cancelOrder();

            return;
        }

        parent::cancelOrder();

        $mailService = oxNew(RefundMailService::class);
        $mailService->sendCancelMail($order, null, $this->orderCurrency($order));
    }

    /**
     * @param Order $order
     * @return bool
     */
    protected function isPayPalOrder(Order $order): bool
    {
        return PayPalDefinitions::isPayPalPayment($this->orderFieldAsString($order, 'oxpaymenttype'));
    }

    /**
     * @param Order $order
     * @return string
     */
    protected function orderCurrency(Order $order): string
    {
        return $this->orderFieldAsString($order, 'oxcurrency');
    }

    /**
     * getFieldData() is untyped, so anything that is not a plain value yields an
     * empty string instead of being cast.
     *
     * @param Order $order
     * @param string $field
     * @return string
     */
    private function orderFieldAsString(Order $order, string $field): string
    {
        $value = $order->getFieldData($field);

        return is_scalar($value) ? (string)$value : '';
    }
}
