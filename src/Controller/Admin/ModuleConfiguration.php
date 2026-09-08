<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use Exception;
use GuzzleHttp\Exception\ClientException;
use JsonException;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPal\Core\Constants as PayPalConstants;
use OxidSolutionCatalysts\PayPal\Core\LegacyOeppModuleDetails;
use OxidSolutionCatalysts\PayPal\Core\Onboarding\Onboarding;
use OxidSolutionCatalysts\PayPal\Core\Onboarding\Webhook;
use OxidSolutionCatalysts\PayPal\Core\PartnerConfig;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\RequestReader;
use OxidSolutionCatalysts\PayPal\Exception\OnboardingException;
use OxidSolutionCatalysts\PayPal\Module;
use OxidSolutionCatalysts\PayPal\Service\LanguageLocaleMapper;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Controller for admin > PayPal/Configuration page
 */
class ModuleConfiguration extends ModuleConfiguration_parent
{
    use ServiceContainer;

    /**
     * @var string Current class template name.
     */
    protected $_sThisTemplate = '@osc_paypal/admin/oscpaypalconfig'; // phpcs:ignore PSR2.Classes.PropertyDeclaration

    /**
     * @return string
     */
    public function render()
    {
        $thisTemplate = parent::render();

        if ($this->_sModuleId === 'osc_paypal') {
            $config = new Config();
            $this->addTplParam('config', $config);
            $thisTemplate = $this->_sThisTemplate;
            try {
                $config->checkHealth();
            } catch (StandardException $e) {
                Registry::getUtilsView()->addErrorToDisplay($e, false, true, 'paypal_error');
            }
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
            PayPalConstants::PAYPAL_ONBOARDING_LIVE_URL,
            false
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
            PayPalConstants::PAYPAL_ONBOARDING_SANDBOX_URL,
            true
        );
    }

    /**
     * Maps arguments and constants to request parameters, generates a sign up url
     *
     * @param string $partnerId
     *
     * @return string
     */
    private function buildSignUpLink(
        string $partnerClientId,
        string $partnerId,
        string $url,
        bool $isSandbox = false
    ): string {
        $lang = Registry::getLang();
        $config = new Config();

        // the onboarding country decides which products and capabilities the merchant account is
        // offered, so it has to be the country the shop itself sits in. It used to be the uppercased
        // backend language ("de" -> "DE"), which sent a swiss merchant through a german onboarding
        $countryCode = $config->getShopCountryIso();
        $localeCode = $this->getServiceFromContainer(LanguageLocaleMapper::class)
            ->mapLanguageToBcp47Locale((string)$lang->getLanguageAbbr(), $countryCode);

        $partnerLogoUrl = Registry::getConfig()->getOutUrl(null, true)
            . 'modules/' . Module::MODULE_ID . '/img/oxid_logo.png';

        $params = [
            'partnerClientId' => $partnerClientId,
            'partnerId' => $partnerId,
            'partnerLogoUrl' => $partnerLogoUrl,
            'product' => 'PPCP',
            'secondaryProducts' => 'advanced_vaulting,payment_methods',
            'capabilities' => 'GOOGLE_PAY,APPLE_PAY,PAY_UPON_INVOICE,PAYPAL_WALLET_VAULTING_ADVANCED',
            'integrationType' => 'FO',
            'features' =>
                'PAYMENT,REFUND,ACCESS_MERCHANT_INFORMATION,ADVANCED_TRANSACTIONS_SEARCH,VAULT,BILLING_AGREEMENT',
            'sellerNonce' => $this->createNonce(),
            'displayMode' => 'minibrowser'
        ];

        // both are omitted rather than filled with a guess when they cannot be resolved, so PayPal
        // asks the merchant instead of starting the onboarding in the wrong country or language
        if ($countryCode !== '') {
            $params['country.x'] = $countryCode;
        }
        if ($localeCode !== '') {
            $params['locale.x'] = $localeCode;
        }

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
     * @throws \OxidSolutionCatalysts\PayPal\Exception\OnboardingException
     * @throws \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleSettingNotFountException
     */
    public function save()
    {
        $confArr = Registry::getRequest()->getRequestEscapedParameter('conf');
        if (is_array($confArr)) {
            $confArr = $this->handleSpecialFields($confArr);
            $this->saveConfig($confArr);
            $this->checkEligibility($confArr);
            $this->registerWebhooks();
        }

        parent::save();
    }

    /**
     * Saves configuration values
     *
     * @param array $conf
     */
    protected function saveConfig(array $conf): void
    {
        foreach ($conf as $confName => $value) {
            try {
                $this->getServiceFromContainer(ModuleSettings::class)->save($confName, $value);
            } catch (ModuleSettingNotFountException $exception) {
                /** @var LoggerInterface $logger */
                $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
                $logger->log('warning', 'Skipped saving unknown module setting', [$exception]);
            }
        }
    }

    /**
     * check Eligibility if config would be changed
     *
     * @param $confArr array
     */
    protected function checkEligibility(array $confArr): void
    {
        $config = new Config();
        /** skip check if no client ID provided */
        if (
            $config->getClientId() === '' ||
            $config->getMerchantId() === '' ||
            $config->getWebhookId() === ''
        ) {
            return;
        }

        try {
            $handler = oxNew(Onboarding::class);
            $onBoardingClient = $handler->getOnboardingClient($config->isSandbox(), true);
            $merchantInformations = $onBoardingClient->getMerchantInformations();
            $handler->saveEligibility($merchantInformations);
            if (isset($confArr['oscPayPalSetVaulting'])) {
                $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
                $isEligible = $moduleSettings->isSandbox()
                    ? $moduleSettings->isSandboxVaultingEligibility()
                    : $moduleSettings->isLiveVaultingEligibility();

                if (!$isEligible) {
                    $moduleSettings->save('oscPayPalSetVaulting', false);
                }
            }
        } catch (OnboardingException | ClientException | ModuleSettingNotFountException | ApiException $exception) {

            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log('warning', 'PayPal eligibility refresh failed during config save', [$exception]);
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
        $conf['oscPayPalSandboxMode'] = $conf['oscPayPalSandboxMode'] === 'sandbox';

        if (!isset($conf['oscPayPalAutomatedRefundOnCancel'])) {
            $conf['oscPayPalAutomatedRefundOnCancel'] = false;
        }
        if (!isset($conf['oscPayPalShowProductDetailsButton'])) {
            $conf['oscPayPalShowProductDetailsButton'] = false;
        }
        if (!isset($conf['oscPayPalShowBasketButton'])) {
            $conf['oscPayPalShowBasketButton'] = false;
        }
        if (!isset($conf['oscPayPalShowMiniBasketButton'])) {
            $conf['oscPayPalShowMiniBasketButton'] = false;
        }
        if (!isset($conf['oscPayPalShowPayLaterButton'])) {
            $conf['oscPayPalShowPayLaterButton'] = false;
        }
        if (!isset($conf['oscPayPalBannersShowAll'])) {
            $conf['oscPayPalBannersShowAll'] = false;
        }
        if (!isset($conf['oscPayPalBannersStartPage'])) {
            $conf['oscPayPalBannersStartPage'] = false;
        }
        if (!isset($conf['oscPayPalBannersCategoryPage'])) {
            $conf['oscPayPalBannersCategoryPage'] = false;
        }
        if (!isset($conf['oscPayPalBannersSearchResultsPage'])) {
            $conf['oscPayPalBannersSearchResultsPage'] = false;
        }
        if (!isset($conf['oscPayPalBannersProductDetailsPage'])) {
            $conf['oscPayPalBannersProductDetailsPage'] = false;
        }
        if (!isset($conf['oscPayPalBannersCheckoutPage'])) {
            $conf['oscPayPalBannersCheckoutPage'] = false;
        }
        if (!isset($conf['oscPayPalLoginWithPayPalEMail'])) {
            $conf['oscPayPalLoginWithPayPalEMail'] = false;
        }
        if (!isset($conf['oscPayPalCleanUpNotFinishedOrdersAutomaticlly'])) {
            $conf['oscPayPalCleanUpNotFinishedOrdersAutomaticlly'] = false;
        }
        if (!isset($conf['oscPayPalSetVaulting'])) {
            $conf['oscPayPalSetVaulting'] = false;
        }
        if (!isset($conf['oscPayPalUseGooglePayAddress'])) {
            $conf['oscPayPalUseGooglePayAddress'] = false;
        }
        if (!isset($conf['oscPayPalDefaultShippingPriceExpress'])) {
            $conf['oscPayPalDefaultShippingPriceExpress'] = false;
        } else {
            $dAmount = (string) str_replace(',', '.', $conf['oscPayPalDefaultShippingPriceExpress']);
            $conf['oscPayPalDefaultShippingPriceExpress'] = $dAmount;
        }
        if (!isset($conf['oscPayPalUseStructuralCustomIdSchema'])) {
            $conf['oscPayPalUseStructuralCustomIdSchema'] = false;
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
        try {
            $LegacyOeppModuleDetails = Registry::get(LegacyOeppModuleDetails::class);

            if ($LegacyOeppModuleDetails->isLegacyModulePresent()) {
                return !$this->getServiceFromContainer(ModuleSettings::class)->getLegacySettingsTransferStatus();
            }
        } catch (Throwable $exc) {
            // If not existing, an exception will be thrown -> do nothing and return false
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
            if ($configKeyName === 'oePayPalBannersHideAll') {
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
     * @throws JsonException
     */
    public function autoConfigurationFromCallback()
    {
        try {
            $requestReader = oxNew(RequestReader::class);
            $payload = $requestReader->getRawPost();
            PayPalSession::storeOnboardingPayload($payload);
        } catch (Exception $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log('error', 'Onboarding callback payload could not be stored: ' . $exception->getMessage(), [$exception]);
        }

        $this->autoConfiguration();
        $this->registerWebhooks();

        $result = [];
        header('Content-Type: application/json; charset=UTF-8');
        Registry::getUtils()->showMessageAndExit(json_encode($result, JSON_THROW_ON_ERROR));
    }

    /**
     * Get ClientID, ClientSecret
     */
    protected function autoConfiguration(): array
    {
        $credentials = [];

        try {
            $handler = oxNew(Onboarding::class);
            $credentials = $handler->autoConfigurationFromCallback();
        } catch (Exception $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log('error', 'Onboarding credential auto-fetch failed: ' . $exception->getMessage(), [$exception]);
        }
        return $credentials;
    }

    /**
     * webhook registration
     */
    public function registerWebhooks(): void
    {
        (oxNew(Webhook::class))->registerWebhooksWithErrorHandling();
    }
}
