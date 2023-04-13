<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use GuzzleHttp\Exception\ClientException;
use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPal\Core\Onboarding\Webhook;
use OxidSolutionCatalysts\PayPal\Core\PartnerConfig;
use OxidSolutionCatalysts\PayPal\Core\Constants as PayPalConstants;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\RequestReader;
use OxidSolutionCatalysts\PayPal\Exception\OnboardingException;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Core\Onboarding\Onboarding;
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
    protected $_sThisTemplate = 'oscpaypalconfig.tpl'; // phpcs:ignore PSR2.Classes.PropertyDeclaration

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
        $config = new Config();

        $countryCode = strtoupper($lang->getLanguageAbbr());
        $localeCode = $lang->getLanguageAbbr() . '-' . $countryCode;

        $partnerLogoUrl = Registry::getConfig()->getOutUrl(null, true) . 'admin/img/loginlogo.png';
        $returnToPartnerUrl = $config->getAdminUrlForJSCalls() . 'cl=oscpaypalconfig&fnc=returnFromSignup';

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
        $this->checkEligibility();
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
     * check Eligibility if config would be changed
     *
     * @param array $confArr

     */
    protected function checkEligibility(): void
    {
        $config = new Config();
        try {
            $handler = oxNew(Onboarding::class);
            $onBoardingClient = $handler->getOnboardingClient($config->isSandbox(), true);
            $merchantInformations = $onBoardingClient->getMerchantInformations();
            $handler->saveEligibility($merchantInformations);
        } catch (ClientException $exception) {
            Registry::getLogger()->error("Error on checkEligibility", [$exception]);
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
        if (!isset($conf['oscPayPalShowMiniBasketButton'])) {
            $conf['oscPayPalShowMiniBasketButton'] = 0;
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
        if (!isset($conf['oscPayPalCleanUpNotFinishedOrdersAutomaticlly'])) {
            $conf['oscPayPalCleanUpNotFinishedOrdersAutomaticlly'] = 0;
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

        foreach ($oldConfigKeys as $configKeyName) {
            // Read old values
            $legacyConfigValue = Registry::getConfig()->getShopConfVar(
                $configKeyName,
                $currentShopId,
                'module:' . LegacyOeppModuleDetails::LEGACY_MODULE_ID
            );

            // Invert "hide" option
            if ($configKeyName == 'oePayPalBannersHideAll') {
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
     * Get ClientID, ClientSecret, WebhookID
     */
    public function autoConfigurationFromCallback()
    {
        try {
            $requestReader = oxNew(RequestReader::class);
            PayPalSession::storeOnboardingPayload($requestReader->getRawPost());
        } catch (\Exception $exception) {
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
        }

        $result = [];
        header('Content-Type: application/json; charset=UTF-8');
        Registry::getUtils()->showMessageAndExit(json_encode($result));
    }

    public function returnFromSignup()
    {
        $config = new Config();
        $request = Registry::getRequest();
        if (
            ('true' === (string) $request->getRequestParameter('permissionsGranted')) &&
            ('true' === (string) $request->getRequestParameter('consentStatus'))
        ) {
            /** @var ModuleSettings $moduleSettings */
            $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
            $moduleSettings->saveMerchantId($request->getRequestParameter('merchantIdInPayPal'));
        }

        $this->autoConfiguration();
        $this->registerWebhooks();

        $url = $config->getAdminUrlForJSCalls() . 'cl=oscpaypalconfig&aoc=ready';

        Registry::getUtils()->redirect($url, false, 302);
    }

    /**
     * Get ClientID, ClientSecret
     */
    protected function autoConfiguration(): array
    {
        $credentials = [];

        try {
            /** @var Onboarding $handler */
            $handler = oxNew(Onboarding::class);
            $credentials = $handler->autoConfigurationFromCallback();
        } catch (\Exception $exception) {
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
        }
        return $credentials;
    }

    /**
     * webhook registration
     */
    protected function registerWebhooks(): string
    {
        $webhookId = '';

        try {
            /** @var Webhook $handler */
            $handler = oxNew(Webhook::class);
            $webhookId = $handler->ensureWebhook();
        } catch (OnboardingException $exception) {
            Registry::getUtilsView()->addErrorToDisplay($exception->getMessage());
        } catch (\Exception $exception) {
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
        }

        return $webhookId;
    }
}
