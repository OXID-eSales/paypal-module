<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;

/**
 * Controller for admin > PayPal/Configuration page
 */
class PayPalConfigController extends AdminController
{
    use ServiceContainer;

    public const SIGN_UP_HOST = 'https://www.sandbox.paypal.com/bizsignup/partner/entry'; //TODO: use env

    /**
     * @var string Current class template name.
     */
    protected $_sThisTemplate = 'pspaypalconfig.tpl';

    /**
     * Get webhook controller url
     *
     * @return string
     */
    public function getWebhookControllerUrl(): string
    {
        return Registry::getUtilsUrl()->getActiveShopHost() . '/index.php?cl=PayPalWebhookController';
    }

    /**
     * @return string
     */
    public function render()
    {
        $thisTemplate = parent::render();

        $config = new Config();
        $this->addTplParam('config', $config);

        try {
            $config->checkHealth();
        } catch (StandardException $e) {
            Registry::getUtilsView()->addErrorToDisplay($e, false, true, 'paypal_error');
        }

        return $thisTemplate;
    }

    /**
     * Template Getter: Get a Link for SignUp the Live Merchant Integration
     * see getSignUpMerchantIntegrationLink
     * @return string
     */
    public function getLiveSignUpMerchantIntegrationLink(): string
    {
        $config = new Config();

        return $this->buildSignUpLink(
            $config->getLiveOxidPartnerId(),
            $config->getLiveOxidClientId()
        );
    }

    /**
     * Template Getter: Get a Link for SignUp the Live Merchant Integration
     * see getSignUpMerchantIntegrationLink
     * @return string
     */
    public function getSandboxSignUpMerchantIntegrationLink(): string
    {
        $config = new Config();

        return $this->buildSignUpLink(
            $config->getSandboxOxidPartnerId(),
            $config->getSandboxOxidClientId()
        );
    }

    /**
     * Maps arguments and constants to request parameters, generates a sign up url
     *
     * @param string $partnerId
     * @param string $clientId
     *
     * @return string
     */
    private function buildSignUpLink(string $partnerId, string $clientId): string
    {
        $params = [
            'sellerNonce' => $this->createNonce(),
            'partnerId' => $partnerId,
            'product' => 'EXPRESS_CHECKOUT',
            'integrationType' => 'FO',
            'partnerClientId' => $clientId,
            //'partnerLogoUrl' => '',
            'displayMode' => 'minibrowser',
            'features' => 'PAYMENT,REFUND,ADVANCED_TRANSACTIONS_SEARCH'
        ];

        return self::SIGN_UP_HOST . '?' . http_build_query($params);
    }

    /**
     * create a unique Seller Nonce to check your own transactions
     *
     * @return string
     */
    public function createNonce(): string
    {
        if (!empty(Registry::getSession()->getVariable('PAYPAL_MODULE_NONCE'))) {
            return Registry::getSession()->getVariable('PAYPAL_MODULE_NONCE');
        }

        try {
            // throws Exception if it was not possible to gather sufficient entropy.
            $nonce = bin2hex(random_bytes(42));
        } catch (\Exception $e) {
            $nonce = md5(uniqid('', true) . '|' . microtime()) . substr(md5(mt_rand()), 0, 24);
        }

        Registry::getSession()->setVariable('PAYPAL_MODULE_NONCE', $nonce);

        return $nonce;
    }


    /**
     * Saves configuration values
     */
    public function save()
    {
        $confArr = Registry::getRequest()->getRequestEscapedParameter('conf');
        $shopId = Registry::getConfig()->getShopId();

        $confArr = $this->handleSpecialFields($confArr);
        $this->saveConfig($confArr, $shopId);

        parent::save();
    }

    /**
     * Saves configuration values
     *
     * @param array $conf
     * @param int   $shopId
     */
    protected function saveConfig(array $conf, int $shopId): void
    {
        foreach ($conf as $confName => $value) {
            $value = trim($value);
            $this->getServiceFromContainer(ModuleSettings::class)->save($confName, $value);
        }
    }

    /**
     * Handles checkboxes/dropdowns
     *
     * @param array $conf
     *
     * @return array
     */
    protected function handleSpecialFields(array $conf): array
    {
        if ($conf['blPayPalSandboxMode'] === 'sandbox') {
            $conf['blPayPalSandboxMode'] = 1;
        } else {
            $conf['blPayPalSandboxMode'] = 0;
        }

        if (!isset($conf['blPayPalShowProductDetailsButton'])) {
            $conf['blPayPalShowProductDetailsButton'] = 0;
        }

        if (!isset($conf['blPayPalShowBasketButton'])) {
            $conf['blPayPalShowBasketButton'] = 0;
        }

        if (!isset($conf['oePayPalBannersShowAll'])) {
            $conf['oePayPalBannersShowAll'] = 0;
        }
        if (!isset($conf['oePayPalBannersStartPage'])) {
            $conf['oePayPalBannersStartPage'] = 0;
        }
        if (!isset($conf['oePayPalBannersCategoryPage'])) {
            $conf['oePayPalBannersCategoryPage'] = 0;
        }
        if (!isset($conf['oePayPalBannersSearchResultsPage'])) {
            $conf['oePayPalBannersSearchResultsPage'] = 0;
        }
        if (!isset($conf['oePayPalBannersProductDetailsPage'])) {
            $conf['oePayPalBannersProductDetailsPage'] = 0;
        }
        if (!isset($conf['oePayPalBannersCheckoutPage'])) {
            $conf['oePayPalBannersCheckoutPage'] = 0;
        }

        return $conf;
    }

    /**
     * @return array
     */
    public function getTotalCycleDefaults()
    {
        $array = [];

        for ($i = 1; $i < 1000; $i++) {
            $array[] = $i;
        }

        return $array;
    }
}
