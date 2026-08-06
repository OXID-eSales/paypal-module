<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Domain\Admin\Event\AdminModeChangedEvent;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Pui;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
    protected $sPuiTplHtml = "@osc_paypal/frontend/email/html/pui_paymentinfo";

    /**
     * PUI Payment Information - Plain
     *
     * @var string
     */
    protected $sPuiTplPlain = "@osc_paypal/frontend/email/plain/pui_paymentinfo";

    /**
     * Refund confirmation - HTML
     *
     * @var string
     */
    protected $payPalRefundTplHtml = "@osc_paypal/frontend/email/html/refund";

    /**
     * Refund confirmation - Plain
     *
     * @var string
     */
    protected $payPalRefundTplPlain = "@osc_paypal/frontend/email/plain/refund";

    /**
     * Cancellation confirmation - HTML
     *
     * @var string
     */
    protected $payPalCancelTplHtml = "@osc_paypal/frontend/email/html/cancel";

    /**
     * Cancellation confirmation - Plain
     *
     * @var string
     */
    protected $payPalCancelTplPlain = "@osc_paypal/frontend/email/plain/cancel";

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
        $shop = $this->getShop();
        $this->setMailParams($shop);

        $this->setViewData("order", $order);
        $this->setViewData("puiPaymentDetails", $puiPaymentDetails);
        $this->setViewData("currency", $order->getOrderCurrency());

        // create messages
        $renderer = $this->getRenderer();

        // Process view data array through oxOutput processor
        $this->processViewArray();

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
        // The customer is written to in the language they ordered in. The shop owner
        // keeps the language the backend is running in, because that copy is read
        // next to the order there - so only the customer mail switches the language.
        // Core\Email::sendSendedNowMail() handles its backend triggered mail the
        // same way, including loading the shop in that language: the shop name and
        // the sender texts are translatable too.
        $mailLanguage = $toOwner ? null : $this->payPalOrderLanguage($order);

        $shop = $mailLanguage === null ? $this->getShop() : $this->getShop($mailLanguage);
        $this->setMailParams($shop);

        $this->setViewData('order', $order);
        $this->setViewData('currency', $order->getOrderCurrency());
        $this->setViewData('isPayPalOwnerMail', $toOwner);
        foreach ($viewData as $name => $value) {
            $this->setViewData($name, $value);
        }

        $renderer = $this->getRenderer();

        // Process view data array through oxOutput processor
        $this->processViewArray();

        $lang = Registry::getLang();
        $previousTplLanguage = (int)$lang->getTplLanguage();
        $previousBaseLanguage = (int)$lang->getBaseLanguage();
        if ($mailLanguage !== null) {
            $lang->setTplLanguage($mailLanguage);
            $lang->setBaseLanguage($mailLanguage);
        }

        // These mails are triggered from the backend, but they use frontend
        // templates and frontend language files. Rendering them in admin mode
        // leaves core idents unresolved ("ERROR: Translation for ORDER_NUMBER not
        // found!") and looks the frontend templates up below the admin theme, so
        // switch the admin mode off around the rendering and restore it after.
        $wasAdmin = $this->switchPayPalAdminMode(false);

        try {
            $this->setBody($renderer->renderTemplate($htmlTemplate, $this->getViewData()));
            $this->setAltBody($renderer->renderTemplate($plainTemplate, $this->getViewData()));

            // the subject ident lives in the frontend language files too, so it has
            // to be translated here and not after the mode was restored
            /** @var string $subject */
            $subject = $lang->translateString($subjectIdent);
            $this->setSubject(sprintf($subject, $this->payPalFieldAsString($order, 'oxordernr')));
        } finally {
            // A failing template must not leave the shop behind in frontend mode or
            // in the order language: the admin page that triggered the mail is
            // rendered after this and would lose its templates and translations.
            $this->switchPayPalAdminMode($wasAdmin);
            if ($mailLanguage !== null) {
                $lang->setTplLanguage($previousTplLanguage);
                $lang->setBaseLanguage($previousBaseLanguage);
            }
        }

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
     * Language the order was placed in. getFieldData() is untyped, so anything
     * that is not a number falls back to the shop default language.
     *
     * @param Order $order
     * @return int
     */
    protected function payPalOrderLanguage(Order $order): int
    {
        $language = $order->getFieldData('oxlang');

        return is_numeric($language) ? (int)$language : 0;
    }

    /**
     * Switches the admin mode and returns the mode that was active before.
     *
     * Setting the config flag alone is not enough: the template engine resolves
     * its namespaced template directories once and keeps them, so "@__main__"
     * would still point at the admin theme and a frontend template including
     * "email/html/header.html.twig" would not be found. The AdminModeChangedEvent
     * makes the engine reload those directories. The core switches the mode the
     * same way (Core\Email::switchToShopMode()), but its helpers are private,
     * hence the copy here.
     *
     * @param bool $isAdmin mode to switch to
     * @return bool mode that was active before
     */
    protected function switchPayPalAdminMode(bool $isAdmin): bool
    {
        $config = Registry::getConfig();
        $wasAdmin = (bool)$config->isAdmin();

        if ($wasAdmin === $isAdmin) {
            return $wasAdmin;
        }

        $config->setAdminMode($isAdmin);

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = ContainerFactory::getInstance()
            ->getContainer()
            ->get(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(new AdminModeChangedEvent());

        return $wasAdmin;
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
