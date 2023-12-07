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
        return $this->getSslSelfLink() . 'cl=oscpaypalproxy&fnc=cancelPayPalPayment';
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

        $localeCode = $this->getServiceFromContainer(LanguageLocaleMapper::class)
            ->mapLanguageToLocale($lang->getLanguageAbbr());

        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $params = [];

        $params['client-id'] = $this->getPayPalClientId();
        $params['integration-date'] = Constants::PAYPAL_INTEGRATION_DATE;
        $params['intent'] = strtolower(Constants::PAYPAL_ORDER_INTENT_CAPTURE);
        $params['commit'] = 'false';

        if ($currency = $config->getActShopCurrencyObject()) {
            $params['currency'] = strtoupper($currency->name);
        }

        $params['components'] = 'buttons';
        // Available components: enable messages+buttons for PDP
        if ($this->isPayPalBannerActive()) {
            $params['components'] .= ',messages';
        }

        if ($moduleSettings->showPayPalPayLaterButton()) {
            $params['enable-funding'] = 'paylater';
        }

        $params['disable-funding'] = 'sepa,bancontact,blik,eps,giropay,ideal,mercadopago,mybank,p24,sofort,venmo';

        if ($moduleSettings->isAcdcEligibility()) {
            $params['disable-funding'] .= ',card';
        } else {
            if (isset($params['enable-funding'])) {
                $params['enable-funding'] .= ',card';
            } else {
                $params['enable-funding'] = 'card';
            }
        }
        $params['locale'] = $localeCode;

        return Constants::PAYPAL_JS_SDK_URL . '?' . http_build_query($params);
    }

    /**
     * Gets PayPal JS SDK url for ACDC
     *
     * @return string
     */
    public function getPayPalJsSdkUrlForACDC(): string
    {
        return $this->getBasePayPalJsSdkUrl('hosted-fields');
    }

    /**
     * Gets PayPal JS SDK url for Button Payments like SEPA and CreditCardFallback
     *
     * @return string
     */
    public function getPayPalJsSdkUrlForButtonPayments(): string
    {
        return $this->getBasePayPalJsSdkUrl('funding-eligibility', true);
    }

    protected function getBasePayPalJsSdkUrl($type = '', $continueFlow = false): string
    {
        $config = Registry::getConfig();
        $lang = Registry::getLang();

        $localeCode = $this->getServiceFromContainer(LanguageLocaleMapper::class)
            ->mapLanguageToLocale($lang->getLanguageAbbr());

        $params = [];

        $params['client-id'] = $this->getPayPalClientId();
        $params['integration-date'] = Constants::PAYPAL_INTEGRATION_DATE;

        if ($currency = $config->getActShopCurrencyObject()) {
            $params['currency'] = strtoupper($currency->name);
        }

        if ($continueFlow) {
            $params['intent'] = strtolower(Constants::PAYPAL_ORDER_INTENT_CAPTURE);
            $params['commit'] = 'false';
        }

        $params['components'] = 'buttons,' . $type;

        if ($this->isPayPalBannerActive()) {
            $params['components'] .= ',messages';
        }
        $params['locale'] = $localeCode;

        return Constants::PAYPAL_JS_SDK_URL . '?' . http_build_query($params);
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
     * API URL getter for use with the installment banner feature
     */
    public function getPayPalApiBannerUrl(): string
    {
        $params['client-id'] = $this->getPayPalClientId();

        $params['components'] = 'messages';

        return Constants::PAYPAL_JS_SDK_URL . '?' . http_build_query($params);
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
}
