<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererBridgeInterface;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Pui;

/**
 * Mailing manager.
 * Collects mailing configuration, other parameters, performs mailing functions
 * (newsletters, ordering, registration emails, etc.).
 */
class Email extends Email_parent
{
    /**
     * PUI Payment Information - HTML
     *
     * @var string
     */
    protected $sPuiTplHtml = "modules/osc/paypal/email/html/pui_paymentinfo.tpl";

    /**
     * PUI Payment Information - Plain
     *
     * @var string
     */
    protected $sPuiTplPlain = "modules/osc/paypal/plain/html/pui_paymentinfo.tpl";

    /**
     * Refund confirmation - HTML
     *
     * @var string
     */
    protected $payPalRefundTplHtml = "modules/osc/paypal/email/html/refund.tpl";

    /**
     * Refund confirmation - Plain
     *
     * @var string
     */
    protected $payPalRefundTplPlain = "modules/osc/paypal/email/plain/refund.tpl";

    /**
     * Cancellation confirmation - HTML
     *
     * @var string
     */
    protected $payPalCancelTplHtml = "modules/osc/paypal/email/html/cancel.tpl";

    /**
     * Cancellation confirmation - Plain
     *
     * @var string
     */
    protected $payPalCancelTplPlain = "modules/osc/paypal/email/plain/cancel.tpl";

    /**
     * Sets mailer additional settings and sends pui info mail to user.
     * Returns true on success.
     *
     * @param Order $order Order object
     * @param Pui $puiPaymentDetails
     * @return bool
     */
    public function sendPuiInfo(Order $order, Pui $puiPaymentDetails)
    {
        $shop = $this->_getShop();
        $this->_setMailParams($shop);

        $this->setViewData("order", $order);
        $this->setViewData("puiPaymentDetails", $puiPaymentDetails);
        $this->setViewData("currency", $order->getOrderCurrency());

        // create messages
        $renderer = $this->getContainer()->get(TemplateRendererBridgeInterface::class)->getTemplateRenderer();

        // Process view data array through oxOutput processor
        $this->_processViewArray();

        $this->setBody($renderer->renderTemplate($this->sPuiTplHtml, $this->getViewData()));
        $this->setAltBody($renderer->renderTemplate($this->sPuiTplPlain, $this->getViewData()));

        //Sets subject to email
        $lang = Registry::getLang();
        $this->setSubject($lang->translateString("OSC_PAYPAL_PAYMENT_PUI_HEADING"));

        $fullName = $order->oxorder__oxbillfname->getRawValue() . " " . $order->oxorder__oxbilllname->getRawValue();

        $this->setRecipient($order->oxorder__oxbillemail->value, $fullName);
        $this->setReplyTo($shop->oxshops__oxorderemail->value, $shop->oxshops__oxname->getRawValue());

        return $this->send();
    }

    /**
     * @param Order $order
     * @param float $refundedAmount amount PayPal confirmed as refunded
     * @param string $currency currency code of the refunded amount
     * @return bool
     */
    public function sendPayPalRefundMailToCustomer(
        Order $order,
        float $refundedAmount,
        string $currency
    ): bool {
        return $this->sendPayPalRefundMail($order, $refundedAmount, $currency, false);
    }

    /**
     * @param Order $order
     * @param float $refundedAmount amount PayPal confirmed as refunded
     * @param string $currency currency code of the refunded amount
     * @return bool
     */
    public function sendPayPalRefundMailToOwner(
        Order $order,
        float $refundedAmount,
        string $currency
    ): bool {
        return $this->sendPayPalRefundMail($order, $refundedAmount, $currency, true);
    }

    /**
     * @param Order $order
     * @param float|null $refundedAmount amount refunded along with the
     *                                   cancellation, null if no refund was made
     * @param string $currency currency code of the refunded amount
     * @return bool
     */
    public function sendPayPalCancelMailToCustomer(
        Order $order,
        ?float $refundedAmount,
        string $currency
    ): bool {
        return $this->sendPayPalCancelMail($order, $refundedAmount, $currency, false);
    }

    /**
     * @param Order $order
     * @param float|null $refundedAmount amount refunded along with the
     *                                   cancellation, null if no refund was made
     * @param string $currency currency code of the refunded amount
     * @return bool
     */
    public function sendPayPalCancelMailToOwner(
        Order $order,
        ?float $refundedAmount,
        string $currency
    ): bool {
        return $this->sendPayPalCancelMail($order, $refundedAmount, $currency, true);
    }

    /**
     * @param Order $order
     * @param float $refundedAmount
     * @param string $currency
     * @param bool $toOwner send to the shop owner instead of the customer
     * @return bool
     */
    protected function sendPayPalRefundMail(
        Order $order,
        float $refundedAmount,
        string $currency,
        bool $toOwner
    ): bool {
        return $this->sendPayPalOrderMail(
            $order,
            $toOwner,
            $this->payPalRefundTplHtml,
            $this->payPalRefundTplPlain,
            $toOwner ? 'OSC_PAYPAL_REFUND_MAIL_SUBJECT_OWNER' : 'OSC_PAYPAL_REFUND_MAIL_SUBJECT',
            [
                'payPalRefundedAmount' => $refundedAmount,
                'payPalCurrencyCode' => $currency,
            ]
        );
    }

    /**
     * @param Order $order
     * @param float|null $refundedAmount
     * @param string $currency
     * @param bool $toOwner send to the shop owner instead of the customer
     * @return bool
     */
    protected function sendPayPalCancelMail(
        Order $order,
        ?float $refundedAmount,
        string $currency,
        bool $toOwner
    ): bool {
        return $this->sendPayPalOrderMail(
            $order,
            $toOwner,
            $this->payPalCancelTplHtml,
            $this->payPalCancelTplPlain,
            $toOwner ? 'OSC_PAYPAL_CANCEL_MAIL_SUBJECT_OWNER' : 'OSC_PAYPAL_CANCEL_MAIL_SUBJECT',
            [
                'payPalRefundedAmount' => $refundedAmount,
                'payPalCurrencyCode' => $currency,
            ]
        );
    }

    /**
     * @param Order $order
     * @param bool $toOwner
     * @param string $htmlTemplate
     * @param string $plainTemplate
     * @param string $subjectIdent language ident, receives the order number
     * @param array<string, mixed> $viewData additional template variables
     * @return bool
     */
    protected function sendPayPalOrderMail(
        Order $order,
        bool $toOwner,
        string $htmlTemplate,
        string $plainTemplate,
        string $subjectIdent,
        array $viewData
    ): bool {
        $shop = $this->_getShop();
        $this->_setMailParams($shop);

        $this->setViewData('order', $order);
        $this->setViewData('currency', $order->getOrderCurrency());
        $this->setViewData('isPayPalOwnerMail', $toOwner);
        foreach ($viewData as $name => $value) {
            $this->setViewData($name, $value);
        }

        $renderer = $this->getContainer()->get(TemplateRendererBridgeInterface::class)->getTemplateRenderer();

        // Process view data array through oxOutput processor
        $this->_processViewArray();

        $this->setBody($renderer->renderTemplate($htmlTemplate, $this->getViewData()));
        $this->setAltBody($renderer->renderTemplate($plainTemplate, $this->getViewData()));

        /** @var string $subject */
        $subject = Registry::getLang()->translateString($subjectIdent);
        $this->setSubject(sprintf($subject, $this->payPalFieldAsString($order, 'oxordernr')));

        if ($toOwner) {
            $this->setRecipient(
                $this->payPalFieldAsString($shop, 'oxowneremail'),
                $shop->oxshops__oxname->getRawValue()
            );

            return $this->send();
        }

        $fullName = $order->oxorder__oxbillfname->getRawValue()
            . ' ' . $order->oxorder__oxbilllname->getRawValue();

        $this->setRecipient($this->payPalFieldAsString($order, 'oxbillemail'), $fullName);
        $this->setReplyTo(
            $this->payPalFieldAsString($shop, 'oxorderemail'),
            $shop->oxshops__oxname->getRawValue()
        );

        return $this->send();
    }

    /**
     * getFieldData() is untyped, so anything that is not a plain value yields an
     * empty string instead of being cast.
     *
     * @param \OxidEsales\Eshop\Core\Model\BaseModel $model
     * @param string $field
     * @return string
     */
    protected function payPalFieldAsString($model, string $field): string
    {
        $value = $model->getFieldData($field);

        return is_scalar($value) ? (string)$value : '';
    }
}
