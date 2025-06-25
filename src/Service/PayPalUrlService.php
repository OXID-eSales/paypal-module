<?php

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Session;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;

class PayPalUrlService
{
    public function __construct(private Config $config, private Session $session)
    {
    }
    public function getCancelUrl(): string
    {
        return $this->config->getShopUrl() . 'index.php?cl=order&fnc=cancelpaypalsession';
    }
    public function getReturnUrl(): string
    {
        return $this->session->getVariable('paymentid') === PayPalDefinitions::GOOGLEPAY_PAYPAL_PAYMENT_ID ?
            $this->config->getShopUrl() . 'index.php?cl=order&fnc=finalizeGooglePay&stoken='
                . $this->session->getSessionChallengeToken() :
            $this->config->getShopUrl() . 'index.php?cl=order&fnc=finalizepaypalsession';
    }
}
