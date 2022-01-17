<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidSolutionCatalysts\PayPal\Module;

class ModuleSettings
{
    /** @var ModuleSettingBridgeInterface */
    private $moduleSettingBridge;

    //TODO: we need service for fetching module settings from db (this one)
    //another class for moduleconfiguration (database values/edefaults)
    //and the view configuration should go into some separate class
    //also add shopcontext to get shop settings

    public function __construct(
        ModuleSettingBridgeInterface $moduleSettingBridge
    )
    {
        $this->moduleSettingBridge = $moduleSettingBridge;
    }

    public function showAllPayPalBanners(): bool
    {
        return (bool) $this->getSettingValue('oePayPalBannersShowAll');
    }

    public function isSandbox(): bool
    {
        return (bool) $this->getSettingValue('blPayPalSandboxMode');
    }

    public function getLiveClientId(): string
    {
        return (string) $this->getSettingValue('sPayPalClientId');
    }

    public function getLiveClientSecret(): string
    {
        return (string) $this->getSettingValue('sPayPalClientSecret');
    }

    public function getLiveWebhookId(): string
    {
        return (string) $this->getSettingValue('sPayPalWebhookId');
    }

    public function getSandboxClientId(): string
    {
        return (string) $this->getSettingValue('sPayPalSandboxClientId');
    }

    public function getSandboxClientSecret(): string
    {
        return (string) $this->getSettingValue('sPayPalSandboxClientSecret');
    }

    public function getSandboxWebhookId(): string
    {
        return (string) $this->getSettingValue('sPayPalSandboxWebhookId');
    }

    public function showPayPalBasketButton(): bool
    {
        return (bool) $this->getSettingValue('blPayPalShowBasketButton');
    }

    public function showPayPalCheckoutButton(): bool
    {
        //TODO: doublecheck why setting was missing in metadata
        return false;
       # return (bool) $this->getSettingValue('blPayPalShowCheckoutButton', false);
    }

    public function showPayPalProductDetailsButton(): bool
    {
        return (bool) $this->getSettingValue('blPayPalShowProductDetailsButton');
    }

    public function getAutoBillOutstanding(): bool
    {
        return (bool) $this->getSettingValue('blPayPalAutoBillOutstanding');
    }

    public function getSetupFeeFailureAction(): string
    {
        $value = (string) $this->getSettingValue('sPayPalSetupFeeFailureAction');
        return !empty($value) ? $value : 'CONTINUE';
    }

    public function getPaymentFailureThreshold(): string
    {
        $value = $this->getSettingValue('sPayPalPaymentFailureThreshold');
        return !empty($value) ? $value : '1';
    }

    public function showBannersOnStartPage(): bool
    {
        return (bool) $this->getSettingValue('oePayPalBannersStartPage');
    }

    public function getStartPageBannerSelector(): string
    {
        return (string) $this->getSettingValue('oePayPalBannersStartPageSelector');
    }

    public function showBannersOnCategoryPage(): bool
    {
        return (bool) $this->getSettingValue('oePayPalBannersCategoryPage');
    }

    public function getCategoryPageBannerSelector(): string
    {
        return (string) $this->getSettingValue('oePayPalBannersCategoryPageSelector');
    }

    public function showBannersOnSearchPage(): bool
    {
        return (bool) $this->getSettingValue('oePayPalBannersSearchResultsPage');
    }

    public function getSearchPageBannerSelector(): string
    {
        return (string) $this->getSettingValue('oePayPalBannersSearchResultsPageSelector');
    }

    public function showBannersOnProductDetailsPage(): bool
    {
        return (bool) $this->getSettingValue('oePayPalBannersProductDetailsPage');
    }

    public function getProductDetailsPageBannerSelector(): string
    {
        return (string) $this->getSettingValue('oePayPalBannersProductDetailsPageSelector');
    }

    public function showBannersOnCheckoutPage(): bool
    {
        return (bool) $this->getSettingValue('oePayPalBannersCheckoutPage');
    }

    public function getPayPalBannerCartPageSelector(): string
    {
        return (string) $this->getSettingValue('oePayPalBannersCartPageSelector');
    }

    public function getPayPalBannerPaymentPageSelector(): string
    {
        return (string) $this->getSettingValue('oePayPalBannersPaymentPageSelector');
    }

    public function getPayPalBannersColorScheme(): string
    {
        return (string) $this->getSettingValue('oePayPalBannersColorScheme');
    }

    public function save(string $name, $value): void
    {
        $this->moduleSettingBridge->save($name, $value, Module::MODULE_ID);
    }

    /**
     * @return mixed
     */
    private function getSettingValue(string $key)
    {
        return $this->moduleSettingBridge->get($key, Module::MODULE_ID);
    }
}