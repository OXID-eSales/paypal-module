<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\RefundMailService;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\Refund;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * OrderList class
 *
 * @mixin \OxidEsales\Eshop\Application\Controller\Admin\OrderList
 */
class OrderList extends OrderList_parent
{
    use ServiceContainer;

    /**
     * Cancels an order in the backend and, when the merchant asked for it, refunds what the
     * PayPal payment still holds. The confirmation mail goes out afterwards and states the
     * refunded amount if one was refunded, so the customer gets one mail for the whole event.
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

        $refundedAmount = $this->refundOnCancel($order);

        $mailService = oxNew(RefundMailService::class);
        $mailService->sendCancelMail($order, $refundedAmount, $this->orderCurrency($order));
    }

    /**
     * Refunds what the PayPal payment still holds, if the merchant switched that on
     * (module setting oscPayPalAutomatedRefundOnCancel, off by default).
     *
     * Only the remaining amount is refunded - captured minus already refunded: a cancellation
     * cancels the whole order, and an amount that went back to the customer before must not be
     * sent a second time. Nothing left to refund means nothing happens, which is also the case
     * for an order that was never captured.
     *
     * A refund that does not work out must never undo the cancellation: the order is cancelled
     * at this point, so the problem is logged, reported to the merchant and left at that - the
     * refund form in the PayPal tab of the order is still there to do it by hand. A refund PayPal
     * only accepted as PENDING is not reported as refunded either, because no money has moved
     * yet; the customer then gets the cancellation mail that points at a separate refund
     * confirmation.
     *
     * @param Order $order
     * @return float|null amount PayPal confirmed as refunded, null when nothing was refunded
     */
    protected function refundOnCancel(Order $order): ?float
    {
        /** @var ModuleSettings $moduleSettings */
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        if (!$moduleSettings->automatedRefundOnCancel()) {
            return null;
        }

        try {
            // No PayPal order behind this one - a checkout that never got that far - means there is
            // nothing to refund and no reason to ask PayPal about it: the lookup would go out with
            // an empty order id and come back 404. Same check the order view uses before it talks
            // to PayPal at all.
            if (!$order->paidWithPayPal()) {
                return null;
            }

            $remainingRefundAmount = $order->getPayPalRemainingRefundAmount();
            if ($remainingRefundAmount <= 0.0) {
                return null;
            }

            $refund = $order->refundPayPalCapture(
                $remainingRefundAmount,
                false,
                $this->refundNoteToPayer($order)
            );

            if (!$refund instanceof Refund || (string)$refund->status !== Constants::PAYPAL_STATUS_COMPLETED) {
                $this->logRefundOnCancel(
                    'warning',
                    sprintf(
                        'automated refund on cancellation of order %s was not completed by PayPal '
                        . '(status %s), the refund has to be done by hand',
                        $this->orderFieldAsString($order, 'oxordernr'),
                        $refund instanceof Refund ? (string)$refund->status : 'none'
                    )
                );

                return null;
            }

            return $remainingRefundAmount;
        } catch (Throwable $throwable) {
            $this->logRefundOnCancel(
                'error',
                sprintf(
                    'automated refund on cancellation of order %s failed: %s',
                    $this->orderFieldAsString($order, 'oxordernr'),
                    $throwable->getMessage()
                )
            );
            Registry::getUtilsView()->addErrorToDisplay('OSC_PAYPAL_CANCEL_REFUND_FAILED');

            return null;
        }
    }

    /**
     * Note PayPal shows the customer for an automated refund, in the language the order was
     * placed in - the merchant does not get to type one here, and a refund without a reason
     * leaves the customer guessing.
     *
     * The ident lives in the frontend language files, which a backend request has not loaded:
     * asked from here as it is, translateString() answers with the ident itself and PayPal would
     * show the customer "OSC_PAYPAL_CANCEL_REFUND_NOTE_TO_PAYER". So this opens the same window
     * Core\Email opens for the mails - admin mode off, order language on, both restored
     * afterwards, because the backend page is rendered after this and would otherwise lose its
     * own templates and translations. Should the ident still not resolve, the note is left out
     * entirely rather than sending the customer something unreadable.
     *
     * @param Order $order
     * @return string empty when the note cannot be translated
     */
    protected function refundNoteToPayer(Order $order): string
    {
        $orderNr = $this->orderFieldAsString($order, 'oxordernr');
        $languageId = (int)$this->orderFieldAsString($order, 'oxlang');

        $config = Registry::getConfig();
        $lang = Registry::getLang();
        $wasAdmin = $config->isAdmin();
        $previousTplLanguage = $lang->getTplLanguage();
        $previousBaseLanguage = $lang->getBaseLanguage();

        $config->setAdminMode(false);
        $lang->setTplLanguage($languageId);
        $lang->setBaseLanguage($languageId);

        try {
            /** @var string $note */
            $note = $lang->translateString('OSC_PAYPAL_CANCEL_REFUND_NOTE_TO_PAYER', $languageId, false);
            $translated = $lang->isTranslated();
        } finally {
            $config->setAdminMode($wasAdmin);
            $lang->setTplLanguage($previousTplLanguage);
            $lang->setBaseLanguage($previousBaseLanguage);
        }

        return $translated ? sprintf($note, $orderNr) : '';
    }

    /**
     * @param string $level
     * @param string $message
     * @return void
     */
    protected function logRefundOnCancel(string $level, string $message): void
    {
        try {
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log($level, 'PayPal ' . $message);
        } catch (Throwable $throwable) {
            // logging must not break the cancellation either
        }
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
