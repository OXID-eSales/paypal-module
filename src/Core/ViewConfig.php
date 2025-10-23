<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Theme;
use OxidSolutionCatalysts\PayPal\Core\Api\IdentityService;
use OxidSolutionCatalysts\PayPal\Service\LanguageLocaleMapper;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use Psr\Log\LoggerInterface;

/**
 * @mixin \OxidEsales\Eshop\Core\ViewConfig
 */
class ViewConfig extends ViewConfig_parent
{
    use ServiceContainer;

    /**
     * is this a "Flow"-Theme Compatible Theme?
     * @var boolean
     */
    protected $isFlowCompatibleTheme = null;

    /**
     * is this a "Wave"-Theme Compatible Theme?
     * @var boolean
     */
    protected $isWaveCompatibleTheme = null;

    /**
     * @return bool
     */
    public function isPayPalCheckoutActive(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->checkHealth();
    }

    /**
     * @return bool
     */
    public function isPayPalBannerActive(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->showAllPayPalBanners();
    }

    public function showPayPalBasketButton(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->showPayPalBasketButton();
    }

    public function showPayPalMiniBasketButton(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->showPayPalMiniBasketButton();
    }

    public function showPayPalProductDetailsButton(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->showPayPalProductDetailsButton();
    }

    public function isPayPalSandbox(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->isSandbox();
    }

    public function isPayPalExpressSessionActive(): bool
    {
        return PayPalSession::isPayPalExpressOrderActive();
    }

    public function isPayPalACDCSessionActive(): bool
    {
        return PayPalSession::isPayPalACDCOrderActive();
    }

    public function getIsVaultingActive(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getIsVaultingActive();
    }

    public function isVaultingEligibility(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->isVaultingEligibility();
    }

    /**
     * @return Config
     */
    public function getPayPalCheckoutConfig(): Config
    {
        return oxNew(Config::class);
    }

    /**
     * @return array
     */
    public function getPayPalCurrencyCodes(): array
    {
        return Currency::getCurrencyCodes();
    }

    /**
     * @return null or string
     */
    public function getCheckoutOrderId(): ?string
    {
        return PayPalSession::getCheckoutOrderId();
    }

    /**
     * @return string
     */
    public function getPayPalPuiFNParams(): string
    {
        return Constants::PAYPAL_PUI_FNPARAMS;
    }

    /**
     * @return string
     */
    public function getPayPalPuiFlowId(): string
    {
        return Constants::PAYPAL_PUI_FLOWID;
    }

    /**
     * get CancelPayPalPayment-Url
     *
     * @return string
     */
    public function getCancelPayPalPaymentUrl(): string
    {
        $link = $this->getSslSelfLink() . 'cl=oscpaypalproxy&fnc=cancelPayPalPayment&redirect=1';
        if ($this->isPayPalSandbox()) {
            $link .= "&XDEBUG_SESSION_START=1";
        }
        return $link;
    }

    /**
     * Gets PayPal JS SDK url
     *
     * @param bool $bCommitFlow
     * @return string
     */
    public function getPayPalJsSdkUrl(bool $bCommitFlow = false): string
    {
        $config = Registry::getConfig();
        $lang = Registry::getLang();
        $params = [];
        $enableFunding = ['card'];
        $disableFunding = [
            'bancontact',
            'blik',
            'eps',
            'giropay',
            'ideal',
            'mercadopago',
            'p24',
            'venmo',
        ];
        $components = [
            'buttons',
            'googlepay',
            'applepay',
        ];

        if ($this->getTopActiveClassName() !== 'payment') {
            $disableFunding[] = 'sepa';
        }

        $localeCode = $this->getServiceFromContainer(LanguageLocaleMapper::class)
            ->mapLanguageToLocale($lang->getLanguageAbbr());

        /** @var ModuleSettings $moduleSettings */
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $captureStrategy = $moduleSettings->getPayPalStandardCaptureStrategy();

        $params['client-id'] = $this->getPayPalClientId();
        $params['integration-date'] = Constants::PAYPAL_INTEGRATION_DATE;
        $params['intent'] = strtolower(Constants::PAYPAL_ORDER_INTENT_AUTHORIZE);
        if ('directly' === $captureStrategy) {
            $params['intent'] = strtolower(Constants::PAYPAL_ORDER_INTENT_CAPTURE);
        }
        $params['commit'] = $bCommitFlow ? 'true' : 'false';

        if ($currency = $config->getActShopCurrencyObject()) {
            $params['currency'] = strtoupper($currency->name);
        }

        $params['merchant-id'] = $moduleSettings->getMerchantId();

        if ($this->isPayPalBannerActive()) {
            $components[] = 'messages';
        }

        if ($moduleSettings->showPayPalPayLaterButton()) {
            $enableFunding[] = 'paylater';
        }

        if ($moduleSettings->isAcdcEligibility() || $this->getIsVaultingActive()) {
            $components[] = 'card-fields';
        }

        if ($components) {
            $params['components'] = implode(',', $components);
        }
        if ($enableFunding) {
            $params['enable-funding'] = implode(',', $enableFunding);
        }
        if ($disableFunding) {
            $params['disable-funding'] = implode(',', $disableFunding);
        }

        $params['locale'] = $localeCode;

        // Add parameters to the sandbox to test geoblocking features like PUI from anywhere
        if ($moduleSettings->isSandbox()) {
            $params['buyer-country'] = 'DE';
        }

        return Constants::PAYPAL_JS_SDK_URL . '?' . http_build_query($params);
    }

    public function showPayPalExpressInMiniBasket(): bool
    {
        $className = $this->getTopActiveClassName();
        $showButton = false;
        $payPalConfig = $this->getPayPalCheckoutConfig();
        $ppActive = $payPalConfig->isActive();
        $configShowMiniBasketButton = $payPalConfig->showPayPalMiniBasketButton();
        $ppExpressSessionActive = $this->isPayPalExpressSessionActive();
        $acdcSessionActive = $this->isPayPalACDCSessionActive();
        $isBasketTotalSumGreaterZero = $this->isBasketTotalSumGreaterZero();
        if (
            $className !== 'payment' &&
            $ppActive &&
            $configShowMiniBasketButton &&
            $isBasketTotalSumGreaterZero &&
            !$ppExpressSessionActive &&
            (
                (
                    $className === 'order' &&
                    !$acdcSessionActive
                ) ||
                $className !== 'order'
            )
        ) {
            $showButton = true;
        }
        return $showButton;
    }

    public function showPayPalExpressInBasket(): bool
    {
        $showButton = false;
        $payPalConfig = $this->getPayPalCheckoutConfig();
        $ppActive = $payPalConfig->isActive();
        $configShowPayPalBasketButton = $payPalConfig->showPayPalBasketButton();
        $ppExpressSessionActive = $this->isPayPalExpressSessionActive();
        $isBasketTotalSumGreaterZero = $this->isBasketTotalSumGreaterZero();
        if (
            $ppActive &&
            $configShowPayPalBasketButton &&
            $isBasketTotalSumGreaterZero &&
            !$ppExpressSessionActive
        ) {
            $showButton = true;
        }
        return $showButton;
    }

    public function getUserIdForVaulting(): string
    {
        return oxNew(Config::class)->getUserIdForVaulting();
    }

    public function isVaultingAllowedForPayPal(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->isVaultingAllowedForPayPal();
    }

    public function isVaultingAllowedForACDC(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->isVaultingAllowedForACDC();
    }

    /**
     * get Session Vault Success
     *
     * @return bool|null
     */
    public function getSessionVaultSuccess()
    {
        $session = Registry::getSession();
        $vaultSuccess = $session->getVariable("vaultSuccess");
        $session->deleteVariable("vaultSuccess");

        return $vaultSuccess;
    }

    /**
     * get Vault Token
     *
     * @return array|null
     */
    public function getVaultPaymentTokens()
    {
        if ($this->getIsVaultingActive() && $customerId = $this->getUser()->getFieldData("oscpaypalcustomerid")) {
            $vaultingService = Registry::get(ServiceFactory::class)->getVaultingService();

            $vaultPaymentTokens = $vaultingService->getVaultPaymentTokens($customerId)["payment_tokens"] ?? null;

            return $this->filterVaultPaymentTokensByController($vaultPaymentTokens);
        }

        return null;
    }

    public function getDataClientToken(): string
    {
        try {
            /** @var IdentityService $identityService */
            $identityService = Registry::get(ServiceFactory::class)->getIdentityService();

            $result = $identityService->requestClientToken();
        } catch (GuzzleException | Exception $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log('error', $exception->getMessage(), [$exception]);
            $result = '';
        }

        return $result;
    }

    /**
     * PSPAYPAL-491 -->
     * Returns whether PayPal banners should be shown on the start page
     */
    public function enablePayPalBanners(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->showAllPayPalBanners();
    }

    /**
     * Client ID getter for use with the installment banner feature.
     */
    public function getPayPalClientId(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getClientId();
    }

    /**
     * API ID getter for use with the installment banner feature
     */
    public function getPayPalPartnerAttributionIdForBanner(): string
    {
        return Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP;
    }

    /**
     * Returns whether PayPal banners should be shown on the start page
     */
    public function showPayPalCheckoutBannerOnStartPage(): bool
    {
        $config = Registry::getConfig();
        $settings = $this->getServiceFromContainer(ModuleSettings::class);

        return (
            $settings->showAllPayPalBanners() &&
            $settings->showBannersOnStartPage() &&
            $settings->getStartPageBannerSelector() &&
            $config->getConfigParam('bl_perfLoadPrice')
        );
    }

    /**
     * Returns PayPal banners selector for the start page
     */
    public function getPayPalCheckoutBannerStartPageSelector(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getStartPageBannerSelector();
    }

    /**
     * Returns whether PayPal banners should be shown on the category page
     */
    public function showPayPalCheckoutBannerOnCategoryPage(): bool
    {
        $config = Registry::getConfig();
        $settings = $this->getServiceFromContainer(ModuleSettings::class);

        return (
            $settings->showAllPayPalBanners() &&
            $settings->showBannersOnCategoryPage() &&
            $settings->getCategoryPageBannerSelector() &&
            $config->getConfigParam('bl_perfLoadPrice')
        );
    }

    /**
     * Returns PayPal banners selector for the category page
     */
    public function getPayPalCheckoutBannerCategoryPageSelector(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getCategoryPageBannerSelector();
    }

    /**
     * Returns whether PayPal banners should be shown on the search results page
     */
    public function showPayPalCheckoutBannerOnSearchResultsPage(): bool
    {
        $config = Registry::getConfig();
        $settings = $this->getServiceFromContainer(ModuleSettings::class);

        return (
            $settings->showAllPayPalBanners() &&
            $settings->showBannersOnSearchPage() &&
            $settings->getSearchPageBannerSelector() &&
            $config->getConfigParam('bl_perfLoadPrice')
        );
    }

    /**
     * Returns PayPal banners selector for the search page
     */
    public function getPayPalCheckoutBannerSearchPageSelector(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getSearchPageBannerSelector();
    }

    /**
     * Returns whether PayPal banners should be shown on the product details page
     */
    public function showPayPalCheckoutBannerOnProductDetailsPage(): bool
    {
        $config = Registry::getConfig();
        $settings = $this->getServiceFromContainer(ModuleSettings::class);

        return (
            $settings->showAllPayPalBanners() &&
            $settings->showBannersOnProductDetailsPage() &&
            $settings->getProductDetailsPageBannerSelector() &&
            $config->getConfigParam('bl_perfLoadPrice')
        );
    }

    /**
     * Returns PayPal banners selector for the product detail page
     */
    public function getPayPalCheckoutBannerProductDetailsPageSelector(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getProductDetailsPageBannerSelector();
    }

    /**
     * Returns whether PayPal banners should be shown on the checkout page
     */
    public function showPayPalCheckoutBannerOnCheckoutPage(): bool
    {
        $showBanner = false;
        $actionClassName = $this->getActionClassName();
        $config = Registry::getConfig();
        $settings = $this->getServiceFromContainer(ModuleSettings::class);

        if ($actionClassName === 'basket') {
            $showBanner = (
                $settings->showAllPayPalBanners() &&
                $settings->showBannersOnCheckoutPage() &&
                $settings->getPayPalCheckoutBannerCartPageSelector() &&
                $config->getConfigParam('bl_perfLoadPrice')
            );
        } elseif ($actionClassName === 'payment') {
            $showBanner = (
                $settings->showAllPayPalBanners() &&
                $settings->showBannersOnCheckoutPage() &&
                $settings->getPayPalCheckoutBannerPaymentPageSelector() &&
                $config->getConfigParam('bl_perfLoadPrice')
            );
        }

        return $showBanner;
    }

    /**
     * Returns PayPal banners selector for the cart page
     */
    public function getPayPalCheckoutBannerCartPageSelector(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getPayPalCheckoutBannerCartPageSelector();
    }

    /**
     * Returns PayPal banners selector for the payment page
     */
    public function getPayPalCheckoutBannerPaymentPageSelector(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getPayPalCheckoutBannerPaymentPageSelector();
    }

    /**
     * Returns the PayPal banners colour scheme
     */
    public function getPayPalCheckoutBannersColorScheme(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getPayPalCheckoutBannersColorScheme();
    }

    /**
     * Returns comma seperated String with the Country Restriction for PayPal Express
     */
    public function getCountryRestrictionForPayPalExpress(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getCountryRestrictionForPayPalExpress();
    }

    /**
     * Template variable getter. Check if is a Flow Theme Compatible Theme
     *
     * @return boolean
     */
    public function isFlowCompatibleTheme()
    {
        if (is_null($this->isFlowCompatibleTheme)) {
            $this->isFlowCompatibleTheme = $this->isCompatibleTheme('flow');
        }
        return $this->isFlowCompatibleTheme;
    }

    /**
     * Template variable getter. Check if is a Wave Theme Compatible Theme
     *
     * @return boolean
     */
    public function isWaveCompatibleTheme()
    {
        if (is_null($this->isWaveCompatibleTheme)) {
            $this->isWaveCompatibleTheme = $this->isCompatibleTheme('wave');
        }
        return $this->isWaveCompatibleTheme;
    }

    /**
     * Template variable getter. Check if is a ??? Theme Compatible Theme
     *
     * @return boolean
     * @psalm-suppress InternalMethod
     */
    public function isCompatibleTheme($themeId = null)
    {
        $result = false;
        if ($themeId) {
            $theme = oxNew(Theme::class);
            $theme->load($theme->getActiveThemeId());
            // check active theme or parent theme
            if (
                $theme->getActiveThemeId() === $themeId ||
                $theme->getInfo('parentTheme') === $themeId
            ) {
                $result = true;
            }
        }
        return $result;
    }

    //TODO: remove duplicated config getters
    public function getPayPalSCAContingency(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getPayPalSCAContingency();
    }

    public function getGenerateSetupTokenLink($card = false)
    {
        $config = oxNew(Config::class);
        $params = 'cl=osctokencontroller&fnc=generatesetuptoken';
        if ($config->isSandbox()) {
            $params .= '&XDEBUG_SESSION_START=1';
        }

        if ($card) {
            $params .= '&card=true';
        }

        $url = html_entity_decode(Registry::getConfig()->getShopHomeUrl());

        return $url . $params;
    }

    public function getGeneratePaymentTokenLink()
    {
        $config = oxNew(Config::class);
        $params = 'cl=osctokencontroller&fnc=generatepaymenttoken';
        if ($config->isSandbox()) {
            $params .= '&XDEBUG_SESSION_START=1';
        }

        $url = html_entity_decode(Registry::getConfig()->getShopHomeUrl());

        return $url . $params . '&token=';
    }

    public function isAcdcEligibility(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->isAcdcEligibility();
    }

    private function filterVaultPaymentTokensByController($vaultPaymentTokens)
    {
        if (is_null($vaultPaymentTokens)) {
            return null;
        }

        if ($this->isAccountVaultController()) {
            return array_filter(
                $vaultPaymentTokens,
                function ($token) {
                    return array_key_exists('payment_source', $token)
                        && !array_key_exists(PayPalDefinitions::PAYMENT_SOURCE_CARD, $token['payment_source']);
                }
            );
        }

        if ($this->isAccountVaultCartController()) {
            return array_filter(
                $vaultPaymentTokens,
                function ($token) {
                    return array_key_exists('payment_source', $token)
                        && array_key_exists(PayPalDefinitions::PAYMENT_SOURCE_CARD, $token['payment_source']);
                }
            );
        }

        return $vaultPaymentTokens;
    }

    private function isAccountVaultController(): bool
    {
        return Registry::getRequest()->getRequestEscapedParameter("cl") === 'oscaccountvault';
    }

    private function isAccountVaultCartController(): bool
    {
        return Registry::getRequest()->getRequestEscapedParameter("cl") === 'oscaccountvaultcard';
    }

    private function isBasketTotalSumGreaterZero(): bool
    {
        $basket = Registry::getSession()->getBasket();
        if (!$basket) {
            return false;
        }
        return ($basket->isPriceViewModeNetto && $basket->getNettoSum() > 0) || $basket->getBruttoSum() > 0;
    }
}
