<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

/**
 * Sends the confirmation mails for refunds and order cancellations that were
 * triggered in the backend.
 *
 * This class and Core\Email are the only places holding the mail logic, so that
 * the same feature can be moved to the central payment base module later without
 * touching the trigger points. Everything the module-specific side has to do is
 * to call sendRefundMail() at the point where the refund was confirmed by the
 * payment provider, and sendCancelMail() when an order was cancelled.
 */
class RefundMailService
{
    /**
     * Confirmation of a refund. Does nothing when the merchant did not choose a
     * recipient for it, and nothing for the cancellation flow, which sends its
     * own mail covering the cancellation and the refunded amount together.
     *
     * @param Order $order
     * @param float $refundedAmount amount the PayPal confirmed
     * @param string $currency currency code of the refunded amount
     * @param string $context one of the Constants::REFUND_CONTEXT_* values
     * @return void
     */
    public function sendRefundMail(
        Order $order,
        float $refundedAmount,
        string $currency,
        string $context = Constants::REFUND_CONTEXT_REFUND
    ): void {
        if ($context === Constants::REFUND_CONTEXT_CANCEL) {
            return;
        }

        $config = oxNew(Config::class);
        $recipients = $this->resolveRecipients($config->getRefundMailRecipient());

        foreach ($recipients as $recipient) {
            $this->deliver(
                $order,
                $recipient,
                'refund',
                static function (Email $mailer) use ($order, $refundedAmount, $currency, $recipient): bool {
                    return $recipient === Constants::MAIL_RECIPIENT_OWNER
                        ? $mailer->sendPayPalRefundMailToOwner($order, $refundedAmount, $currency)
                        : $mailer->sendPayPalRefundMailToCustomer($order, $refundedAmount, $currency);
                }
            );
        }
    }

    /**
     * Confirmation of an order cancellation. The refunded amount is part of this
     * mail when the cancellation triggered a refund the payment provider
     * confirmed, and omitted when no money was moved.
     *
     * @param Order $order
     * @param float|null $refundedAmount null if the cancellation refunded nothing
     * @param string $currency currency code of the refunded amount
     * @return void
     */
    public function sendCancelMail(Order $order, ?float $refundedAmount, string $currency): void
    {
        $config = oxNew(Config::class);
        $recipients = $this->resolveRecipients($config->getCancelMailRecipient());

        foreach ($recipients as $recipient) {
            $this->deliver(
                $order,
                $recipient,
                'cancel',
                static function (Email $mailer) use ($order, $refundedAmount, $currency, $recipient): bool {
                    return $recipient === Constants::MAIL_RECIPIENT_OWNER
                        ? $mailer->sendPayPalCancelMailToOwner($order, $refundedAmount, $currency)
                        : $mailer->sendPayPalCancelMailToCustomer($order, $refundedAmount, $currency);
                }
            );
        }
    }

    /**
     * The single recipients a configured mode expands to, in sending order.
     *
     * @param string $mode one of the Constants::MAIL_RECIPIENT_* modes
     * @return string[] MAIL_RECIPIENT_CUSTOMER / MAIL_RECIPIENT_OWNER, empty when
     *                  no mail should be sent
     */
    protected function resolveRecipients(string $mode): array
    {
        if ($mode === Constants::MAIL_RECIPIENT_CUSTOMER) {
            return [Constants::MAIL_RECIPIENT_CUSTOMER];
        }

        if ($mode === Constants::MAIL_RECIPIENT_OWNER) {
            return [Constants::MAIL_RECIPIENT_OWNER];
        }

        if ($mode === Constants::MAIL_RECIPIENT_BOTH) {
            return [Constants::MAIL_RECIPIENT_CUSTOMER, Constants::MAIL_RECIPIENT_OWNER];
        }

        return [];
    }

    /**
     * Sends one mail with a fresh mailer instance and logs the result. A failing
     * mailer must never abort the backend action that triggered it: the refund or
     * cancellation has already happened at this point, and letting a mail problem
     * bubble up would leave the merchant with an error page for an action that
     * actually succeeded.
     *
     * @param Order $order
     * @param string $recipient one of the Constants::MAIL_RECIPIENT_* recipients
     * @param string $type refund|cancel, for the log entry
     * @param callable $send receives the mailer, returns the send result
     * @return void
     */
    protected function deliver(Order $order, string $recipient, string $type, callable $send): void
    {
        $recipientName = $recipient === Constants::MAIL_RECIPIENT_OWNER ? 'shop owner' : 'customer';

        try {
            /** @var Email $mailer */
            $mailer = oxNew(Email::class);
            $sent = $send($mailer);

            $this->log(
                $sent ? LogLevel::INFO : LogLevel::WARNING,
                sprintf(
                    'PayPal %s confirmation mail to %s for order %s: %s',
                    $type,
                    $recipientName,
                    $this->orderNumber($order),
                    $sent ? 'sent' : 'not sent'
                ),
                $order
            );
        } catch (Throwable $throwable) {
            $this->log(
                LogLevel::ERROR,
                sprintf(
                    'PayPal %s confirmation mail to %s for order %s failed: %s',
                    $type,
                    $recipientName,
                    $this->orderNumber($order),
                    $throwable->getMessage()
                ),
                $order
            );
        }
    }

    /**
     * Order number for log messages. getFieldData() is untyped, so anything that
     * is not a plain value is reported as an empty number instead of being cast.
     *
     * @param Order $order
     * @return string
     */
    protected function orderNumber(Order $order): string
    {
        $orderNr = $order->getFieldData('oxordernr');

        return is_scalar($orderNr) ? (string)$orderNr : '';
    }

    /**
     * @param string $level
     * @param string $message
     * @param Order $order
     * @return void
     */
    protected function log(string $level, string $message, Order $order): void
    {
        try {
            /** @var LoggerInterface $logger */
            $logger = ContainerFactory::getInstance()
                ->getContainer()
                ->get('OxidSolutionCatalysts\\PayPal\\Logger');
            $logger->log(
                $level,
                $message,
                [
                    'orderId' => $order->getId(),
                    'requestType' => 'paypal-refund-mail',
                ]
            );
        } catch (Throwable $throwable) {
            // logging must not break the backend action either
        }
    }
}
