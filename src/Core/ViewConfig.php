<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\LanguageLocaleMapper;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;

/**
 * @mixin \OxidEsales\Eshop\Core\ViewConfig
 */
class ViewConfig extends ViewConfig_parent
{
    use ServiceContainer;

    /**
     * is this a "Flow"-Theme Compatible Theme?
     * @param boolean
     */
    protected $isFlowCompatibleTheme = null;

    /**
     * is this a "Wave"-Theme Compatible Theme?
     * @param boolean
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

    /**
     * @return bool
     */
    public function isPayPalSandbox(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->isSandbox();
    }

    /**
     * @return bool
     */
    public function isPayPalExpressSessionActive(): bool
    {
        return PayPalSession::isPayPalExpressOrderActive();
    }

    /**
     * @return bool
     */
    public function isPayPalACDCSessionActive(): bool
    {
        return PayPalSession::isPayPalACDCOrderActive();
    }

    /**
     * @return bool
     */
    public function isPayPalExpressPaymentEnabled(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->isPayPalCheckoutExpressPaymentEnabled();
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
        return $this->getSslSelfLink() . 'cl=oscpaypalproxy&fnc=cancelPayPalPayment&redirect=1';
    }

    /**
     * Gets PayPal JS SDK url
     *
     * @return string
     */
    public function getPayPalJsSdkUrl(): string
    {
        $config = Registry::getConfig();
        $lang = Registry::getLang();
        $params = [];
        $enableFunding = [];
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

        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);

        $params['client-id'] = $this->getPayPalClientId();
        $params['integration-date'] = Constants::PAYPAL_INTEGRATION_DATE;
        $params['intent'] = strtolower(Constants::PAYPAL_ORDER_INTENT_CAPTURE);
        $params['commit'] = 'false';

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

        if ($moduleSettings->isAcdcEligibility()) {
            $components[] = 'hosted-fields';
        } else {
            $enableFunding[] = 'card';
        }

        if ($this->getIsVaultingActive()) {
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
        if (
            $className !== 'payment' &&
            $ppActive &&
            $configShowMiniBasketButton &&
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

    public function getUserIdForVaulting(): string
    {
        if (!$this->getUser()) {
            return "";
        }

        $payPalCustomerId = $this->getUser()->getFieldData("oscpaypalcustomerid");

        if (!$payPalCustomerId) {
            return "";
        }

        $vaultingService = Registry::get(ServiceFactory::class)->getVaultingService();
        $response = $vaultingService->generateUserIdToken($payPalCustomerId);

        return $response["id_token"];
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
     * @return string|null
     */
    public function getVaultPaymentTokens()
    {
        if ($this->getIsVaultingActive() && $customerId = $this->getUser()->getFieldData("oscpaypalcustomerid")) {
            $vaultingService = Registry::get(ServiceFactory::class)->getVaultingService();

            return $vaultingService->getVaultPaymentTokens($customerId)["payment_tokens"] ?? null;
        }

        return null;
    }

    public function getDataClientToken(): string
    {

        /** @var \OxidSolutionCatalysts\PayPal\Core\Api\IdentityService $identityService */
        $identityService = Registry::get(ServiceFactory::class)->getIdentityService();

        $response = $identityService->requestClientToken();

        return $response['client_token'] ?? '';
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
            $theme = oxNew(\OxidEsales\Eshop\Core\Theme::class);
            $theme->load($theme->getActiveThemeId());
            // check active theme or parent theme
            if (
                $theme->getActiveThemeId() == $themeId ||
                $theme->getInfo('parentTheme') == $themeId
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

        $url = html_entity_decode($this->getConfig()->getShopHomeUrl());

        return $url . $params;
    }

    public function getGeneratePaymentTokenLink()
    {
        $config = oxNew(Config::class);
        $params = 'cl=osctokencontroller&fnc=generatepaymenttoken';
        if ($config->isSandbox()) {
            $params .= '&XDEBUG_SESSION_START=1';
        }

        $url = html_entity_decode($this->getConfig()->getShopHomeUrl());

        return $url . $params . '&token=';
    }

    public function isAcdcEligibility(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->isAcdcEligibility();
    }
}
