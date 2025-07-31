<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
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
    protected $_sPuiTplHtml = "@osc_paypal/frontend/shared/email/html/pui_paymentinfo.tpl";

    /**
     * PUI Payment Information - Plain
     *
     * @var string
     */
    protected $_sPuiTplPlain = "@osc_paypal/frontend/shared/email/plain/pui_paymentinfo.tpl";

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
        $renderer = $this->getRenderer();

        // Process view data array through oxOutput processor
        $this->_processViewArray();

        $this->setBody($renderer->renderTemplate($this->_sPuiTplHtml, $this->getViewData()));
        $this->setAltBody($renderer->renderTemplate($this->_sPuiTplPlain, $this->getViewData()));

        //Sets subject to email
        $lang = Registry::getLang();
        $this->setSubject($lang->translateString("OSC_PAYPAL_PAYMENT_PUI_HEADING"));

        $fullName = $order->oxorder__oxbillfname->getRawValue() . " " . $order->oxorder__oxbilllname->getRawValue();

        $this->setRecipient($order->oxorder__oxbillemail->value, $fullName);
        $this->setReplyTo($shop->oxshops__oxorderemail->value, $shop->oxshops__oxname->getRawValue());

        return $this->send();
    }
}
