<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;

/**
 * @mixin \OxidEsales\Eshop\Core\ViewConfig
 */
class ViewConfig extends ViewConfig_parent
{
    use ServiceContainer;

    /**
     * @return bool
     */
    public function isPayPalActive(): bool
    {
        return $this->getPayPalConfig()->isActive();
    }

    /**
     * @return bool
     */
    public function isPayPalSessionActive(): bool
    {
        return PayPalSession::isPayPalOrderActive();
    }

    /**
     * @return Config
     */
    public function getPayPalConfig(): Config
    {
        return oxNew(Config::class);
    }

    /**
     * @return Bool
     */
    public function showOverlay(): bool
    {
        return PayPalSession::isSubscriptionProcessing();
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
    public function getcheckoutOrderId(): ?string
    {
        return PayPalSession::getcheckoutOrderId();
    }

    /**
     * get CancelPayPalPayment-Url
     *
     * @return string
     */
    public function getCancelPayPalPaymentUrl(): string
    {
        return $this->getSelfLink() . 'cl=PayPalProxyController&fnc=cancelPayPalPayment';
    }

    /**
     * Gets PayPal JS SDK url
     *
     * @param bool $subscribe is it a PayPal Subscription
     *
     * @return string
     */
    public function getPayPalJsSdkUrl($subscribe = false): string
    {
        $payPalConfig = $this->getPayPalConfig();
        $config = Registry::getConfig();

        $params = [];

        $params['client-id'] = $payPalConfig->getClientId();

        if ($subscribe) {
            $params['vault'] = 'true';
            $params['intent'] = 'subscription';
            $params['locale'] = 'de_DE';
        } else {
            $params['integration-date'] = Constants::PAYPAL_INTEGRATION_DATE;
            $params['intent'] = strtolower(Constants::PAYPAL_ORDER_INTENT_CAPTURE);
            $params['commit'] = 'false';
        }

        if ($currency = $config->getActShopCurrencyObject()) {
            $params['currency'] = strtoupper($currency->name);
        }

        // Available components: enable messages+buttons for PDP
        if ($this->getActiveClassName('details')) {
            $params['components'] = 'messages,buttons';
        }

        return Constants::PAYPAL_JS_SDK_URL . '?' . http_build_query($params);
    }

    /**
     * PSPAYPAL-491 -->
     * Returns whether PayPal banners should be shown on the start page
     */
    public function enablePayPalBanners(): bool
    {
        //TODO: refactor all similar settings fetching places
        return $this->getServiceFromContainer(ModuleSettings::class)->showAllPayPalBanners();
    }

    /**
     * Client ID getter for use with the installment banner feature.
     * @return string
     */
    public function getPayPalClientId(): string
    {
        return $this->getPayPalConfig()->getClientId();
    }

    /**
     * API URL getter for use with the installment banner feature
     * @return string
     */
    public function getPayPalApiBannerUrl(): string
    {
        $params['client-id'] = $this->getPayPalClientId();

        $components = 'messages';
        // enable buttons for PDP
        if ($this->getActiveClassName('details')) {
            $components .= ',buttons';
        }

        $params['components'] = $components;

        return Constants::PAYPAL_JS_SDK_URL . '?' . http_build_query($params);
    }

    /**
     * Returns whether PayPal banners should be shown on the start page
     *
     * @return bool
     */
    public function showPayPalBannerOnStartPage()
    { return true; //hier weiter
        $config = Registry::getConfig();
        return (
            $config->getConfigParam('oePayPalBannersShowAll') #&&
       #     $config->getConfigParam('oePayPalBannersStartPage') &&
       #     $config->getConfigParam('oePayPalBannersStartPageSelector') &&
       #     $config->getConfigParam('bl_perfLoadPrice')
        );
    }

    /**
     * Returns PayPal banners selector for the start page
     *
     * @return string
     */
    public function getPayPalBannerStartPageSelector()
    {
        $config = Registry::getConfig();
        return $config->getConfigParam('oePayPalBannersStartPageSelector');
    }

    /**
     * Returns whether PayPal banners should be shown on the category page
     *
     * @return bool
     */
    public function showPayPalBannerOnCategoryPage()
    {
        $config = Registry::getConfig();
        return (
            $config->getConfigParam('oePayPalBannersShowAll') &&
            $config->getConfigParam('oePayPalBannersCategoryPage') &&
            $config->getConfigParam('oePayPalBannersCategoryPageSelector') &&
            $config->getConfigParam('bl_perfLoadPrice')
        );
    }

    /**
     * Returns PayPal banners selector for the category page
     *
     * @return string
     */
    public function getPayPalBannerCategoryPageSelector()
    {
        $config = Registry::getConfig();
        return $config->getConfigParam('oePayPalBannersCategoryPageSelector');
    }

    /**
     * Returns whether PayPal banners should be shown on the search results page
     *
     * @return bool
     */
    public function showPayPalBannerOnSearchResultsPage()
    {
        $config = Registry::getConfig();

        return (
            $config->getConfigParam('oePayPalBannersShowAll') &&
            $config->getConfigParam('oePayPalBannersSearchResultsPage') &&
            $config->getConfigParam('oePayPalBannersSearchResultsPageSelector') &&
            $config->getConfigParam('bl_perfLoadPrice')
        );
    }

    /**
     * Returns PayPal banners selector for the search page
     *
     * @return string
     */
    public function getPayPalBannerSearchPageSelector()
    {
        $config = Registry::getConfig();
        return $config->getConfigParam('oePayPalBannersSearchResultsPageSelector');
    }

    /**
     * Returns whether PayPal banners should be shown on the product details page
     *
     * @return bool
     */
    public function showPayPalBannerOnProductDetailsPage()
    {
        $config = Registry::getConfig();

        return (
            $config->getConfigParam('oePayPalBannersShowAll') &&
            $config->getConfigParam('oePayPalBannersProductDetailsPage') &&
            $config->getConfigParam('oePayPalBannersProductDetailsPageSelector') &&
            $config->getConfigParam('bl_perfLoadPrice')
        );
    }

    /**
     * Returns PayPal banners selector for the product detail page
     *
     * @return string
     */
    public function getPayPalBannerProductDetailsPageSelector()
    {
        $config = Registry::getConfig();
        return $config->getConfigParam('oePayPalBannersProductDetailsPageSelector');
    }

    /**
     * Returns whether PayPal banners should be shown on the checkout page
     *
     * @return bool
     */
    public function showPayPalBannerOnCheckoutPage()
    {
        $showBanner = false;
        $actionClassName = $this->getActionClassName();
        $config = Registry::getConfig();
        if ($actionClassName === 'basket') {
            $showBanner = (
                $config->getConfigParam('oePayPalBannersShowAll') &&
                $config->getConfigParam('oePayPalBannersCheckoutPage') &&
                $config->getConfigParam('oePayPalBannersCartPageSelector') &&
                $config->getConfigParam('bl_perfLoadPrice')
            );
        } elseif ($actionClassName === 'payment') {
            $showBanner = (
                $config->getConfigParam('oePayPalBannersShowAll') &&
                $config->getConfigParam('oePayPalBannersCheckoutPage') &&
                $config->getConfigParam('oePayPalBannersPaymentPageSelector') &&
                $config->getConfigParam('bl_perfLoadPrice')
            );
        }

        return $showBanner;
    }

    /**
     * Returns PayPal banners selector for the cart page
     *
     * @return string
     */
    public function getPayPalBannerCartPageSelector()
    {
        $config = Registry::getConfig();
        return $config->getConfigParam('oePayPalBannersCartPageSelector');
    }

    /**
     * Returns PayPal banners selector for the payment page
     *
     * @return string
     */
    public function getPayPalBannerPaymentPageSelector()
    {
        $config = Registry::getConfig();
        return $config->getConfigParam('oePayPalBannersPaymentPageSelector');
    }

    /**
     * Returns the PayPal banners colour scheme
     *
     * @return string
     */
    public function getPayPalBannersColorScheme()
    {
        return Registry::getConfig()->getConfigParam('oePayPalBannersColorScheme');
    }

    // <-- PSPAYPAL-491
}
