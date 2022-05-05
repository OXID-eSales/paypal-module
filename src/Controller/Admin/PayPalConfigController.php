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
use OxidSolutionCatalysts\PayPal\Core\PartnerConfig;
use OxidSolutionCatalysts\PayPal\Core\Constants as PayPalConstants;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Core\LegacyOeppModuleDetails;

/**
 * Controller for admin > PayPal/Configuration page
 */
class PayPalConfigController extends AdminController
{
    use ServiceContainer;

    /**
     * @var string Current class template name.
     */
    protected $_sThisTemplate = 'oscpaypalconfig.tpl';

    /**
     * @return string
     */
    public function render()
    {
        $thisTemplate = parent::render();
        $config = new Config();
        $this->addTplParam('config', $config);

        // popUp Rendering onboarding
        if ($aoc = Registry::getConfig()->getRequestParameter("aoc")) {
            $this->_aViewData['isSandBox'] = ($aoc == 'live') ? false : true;
            $this->_aViewData['ready'] = ($aoc == 'ready') ? true : false;
            $this->_aViewData['bottom_buttons'] = '';
            return 'oscpaypalconfig_popup.tpl';
        }

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
        /** @var PartnerConfig $partnerConfig */
        $partnerConfig = oxNew(PartnerConfig::class);

        return $this->buildSignUpLink(
            $partnerConfig->getTechnicalClientId(),
            $partnerConfig->getTechnicalPartnerId(),
            PayPalConstants::PAYPAL_ONBOARDING_LIVE_URL
        );
    }

    /**
     * Template Getter: Get a Link for SignUp the Live Merchant Integration
     * see getSignUpMerchantIntegrationLink
     * @return string
     */
    public function getSandboxSignUpMerchantIntegrationLink(): string
    {
        /** @var PartnerConfig $partnerConfig */
        $partnerConfig = oxNew(PartnerConfig::class);

        return $this->buildSignUpLink(
            $partnerConfig->getTechnicalClientId(true),
            $partnerConfig->getTechnicalPartnerId(true),
            PayPalConstants::PAYPAL_ONBOARDING_SANDBOX_URL
        );
    }

    /**
     * Maps arguments and constants to request parameters, generates a sign up url
     *
     * @param string $partnerId
     *
     * @return string
     */
    private function buildSignUpLink(string $partnerClientId, string $partnerId, string $url): string
    {
        $lang = Registry::getLang();
        $config = Registry::getConfig();
        $session = Registry::getSession();

        $countryCode = strtoupper($lang->getLanguageAbbr());
        $localeCode = $lang->getLanguageAbbr() . '-' . $countryCode;

        $adminShopUrl = $config->getCurrentShopUrl(true);

        $partnerLogoUrl = $config->getOutUrl(null, true) . 'admin/img/loginlogo.png';
        $returnToPartnerUrl = $adminShopUrl .
            'index.php?cl=oscpaypalonboarding&fnc=returnFromSignup' .
            '&stoken=' . (string) $session->getSessionChallengeToken();

        $params = [
            'partnerClientId' => $partnerClientId,
            'partnerId' => $partnerId,
            'partnerLogoUrl' => $partnerLogoUrl,
            'returnToPartnerUrl' => $returnToPartnerUrl,
            'product' => 'ppcp',
            'secondaryProducts' => 'payment_methods',
            'capabilities' => 'PAY_UPON_INVOICE',
            'integrationType' => 'FO',
            'features' => 'PAYMENT,REFUND,ACCESS_MERCHANT_INFORMATION,ADVANCED_TRANSACTIONS_SEARCH',
            'country.x' => $countryCode,
            'locale.x' => $localeCode,
            'sellerNonce' => $this->createNonce(),
            'displayMode' => 'minibrowser'
        ];

        return $url . '?' . http_build_query($params);
    }

    /**
     * create a unique Seller Nonce to check your own transactions
     *
     * @return string
     */
    public function createNonce(): string
    {
        $session = Registry::getSession();

        if (!empty($session->getVariable('PAYPAL_MODULE_NONCE'))) {
            return $session->getVariable('PAYPAL_MODULE_NONCE');
        }

        /** @var PartnerConfig $partnerConfig */
        $partnerConfig = oxNew(PartnerConfig::class);
        $nonce = $partnerConfig->createNonce();

        $session->setVariable('PAYPAL_MODULE_NONCE', $nonce);

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
        if ($conf['oscPayPalSandboxMode'] === 'sandbox') {
            $conf['oscPayPalSandboxMode'] = 1;
        } else {
            $conf['oscPayPalSandboxMode'] = 0;
        }

        if (!isset($conf['oscPayPalShowProductDetailsButton'])) {
            $conf['oscPayPalShowProductDetailsButton'] = 0;
        }
        if (!isset($conf['oscPayPalShowBasketButton'])) {
            $conf['oscPayPalShowBasketButton'] = 0;
        }
        if (!isset($conf['oscPayPalShowPayLaterButton'])) {
            $conf['oscPayPalShowPayLaterButton'] = 0;
        }
        if (!isset($conf['oscPayPalBannersShowAll'])) {
            $conf['oscPayPalBannersShowAll'] = 0;
        }
        if (!isset($conf['oscPayPalBannersStartPage'])) {
            $conf['oscPayPalBannersStartPage'] = 0;
        }
        if (!isset($conf['oscPayPalBannersCategoryPage'])) {
            $conf['oscPayPalBannersCategoryPage'] = 0;
        }
        if (!isset($conf['oscPayPalBannersSearchResultsPage'])) {
            $conf['oscPayPalBannersSearchResultsPage'] = 0;
        }
        if (!isset($conf['oscPayPalBannersProductDetailsPage'])) {
            $conf['oscPayPalBannersProductDetailsPage'] = 0;
        }
        if (!isset($conf['oscPayPalBannersCheckoutPage'])) {
            $conf['oscPayPalBannersCheckoutPage'] = 0;
        }
        if (!isset($conf['oscPayPalLoginWithPayPalEMail'])) {
            $conf['oscPayPalLoginWithPayPalEMail'] = 0;
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

    /**
     * Template variable getter,  check whether legacy oepaypal module exists and show button when config value is false
     * @return bool
     */
    public function showTransferLegacySettingsButton(): bool
    {
        $LegacyOeppModuleDetails = Registry::get(LegacyOeppModuleDetails::class);

        if ($LegacyOeppModuleDetails->isLegacyModulePresent()) {
            $showButton = !$this->getServiceFromContainer(ModuleSettings::class)->getLegacySettingsTransferStatus();

            return $showButton;
        }

        return false;
    }

    /**
     * Transcribe banner settings from the classic PayPal Module (oepaypal)
     */
    public function transferBannerSettings()
    {
        $LegacyOeppModuleDetails = Registry::get(LegacyOeppModuleDetails::class);
        $transferrableSettings = $LegacyOeppModuleDetails->getTransferrableSettings();
        $oldConfigKeys = array_keys($transferrableSettings);
        $currentShopId = Registry::getConfig()->getActiveShop()->getId();

        foreach ($oldConfigKeys as $configKeyName)
        {
            // Read old values
            $legacyConfigValue = Registry::getConfig()->getShopConfVar(
                $configKeyName,
                $currentShopId,
                'module:'.LegacyOeppModuleDetails::LEGACY_MODULE_ID
            );

            // Invert "hide" option
            if ($configKeyName == 'oePayPalBannersHideAll')
            {
                $legacyConfigValue = !$legacyConfigValue;
            }

            // Write new config values
            $this->getServiceFromContainer(ModuleSettings::class)->save(
                $transferrableSettings[$configKeyName],
                $legacyConfigValue
            );
        }

        // Save legacy settings transfer status
        $this->getServiceFromContainer(ModuleSettings::class)->save('oscPayPalLegacySettingsTransferred', true);

        Registry::getUtilsView()->addErrorToDisplay(
            Registry::getLang()->translateString('OSC_PAYPAL_BANNER_TRANSFERREDOLDSETTINGS'),
            false,
            true
        );
    }

    /**
     * Transfer transaction data from the classic oepaypal extension
     * @return void
     */
    public function showTransferOeppTransactiondataButton()
    {
        return true;
    }
}
