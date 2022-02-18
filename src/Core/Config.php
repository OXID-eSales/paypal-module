<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPalApi\Client;

/**
 * Class Config
 */
class Config
{
    use ServiceContainer;

    /**
     * Checks if module configurations are valid
     *
     * @throws StandardException
     */
    public function checkHealth(): void
    {
        if (!$this->getServiceFromContainer(ModuleSettings::class)->checkHealth()) {
            throw oxNew(
                StandardException::class
            );
        }
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        try {
            $this->checkHealth();
        } catch (StandardException $exception) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isSandbox(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->isSandbox();
    }

    /**
     * Get client id based on set active mode
     *
     * @return string
     */
    public function getClientId(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getClientId();
    }

    /**
     * Get client secret based on active mode
     *
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getClientSecret();
    }

    /**
     * @return string
     */
    public function getWebhookId()
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getWebhookId();
    }

    public function isAcdcEligibility(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->isAcdcEligibility();
    }

    public function getLiveClientId(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getLiveClientId();
    }

    public function getLiveClientSecret(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getLiveClientSecret();
    }

    public function getLiveWebhookId(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getLiveWebhookId();
    }

    public function getSandboxClientId(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getSandboxClientId();
    }

    public function getSandboxClientSecret(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getSandboxClientSecret();
    }

    public function getSandboxWebhookId(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getSandboxWebhookId();
    }

    public function showPayPalBasketButton(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->showPayPalBasketButton();
    }

    public function showPayPalCheckoutButton(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->showPayPalCheckoutButton();
    }

    public function loginWithPayPalEMail(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->loginWithPayPalEMail();
    }

    public function showPayPalProductDetailsButton(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->showPayPalProductDetailsButton();
    }

    public function getAutoBillOutstanding(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getAutoBillOutstanding();
    }

    public function getSetupFeeFailureAction(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getSetupFeeFailureAction();
    }

    public function getPaymentFailureThreshold(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getPaymentFailureThreshold();
    }

    public function showAllPayPalBanners(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->showAllPayPalBanners();
    }

    public function showBannersOnStartPage(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->showBannersOnStartPage();
    }

    public function getStartPageBannerSelector(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getStartPageBannerSelector();
    }

    public function showBannersOnCategoryPage(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->showBannersOnCategoryPage();
    }

    public function getCategoryPageBannerSelector(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getCategoryPageBannerSelector();
    }

    public function showBannersOnSearchPage(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->showBannersOnSearchPage();
    }

    public function getSearchPageBannerSelector(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getSearchPageBannerSelector();
    }

    public function showBannersOnProductDetailsPage(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->showBannersOnProductDetailsPage();
    }

    public function getProductDetailsPageBannerSelector(): string
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getProductDetailsPageBannerSelector();
    }

    public function showBannersOnCheckoutPage(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->showBannersOnCheckoutPage();
    }

    /**
     * TODO: use Service\ModuleSettings
     * Config value getter
     * @Todo PSPAYPAL-491 Work in progress, add tests
     * @Todo Ensure we fetch this setting from the active subshop.
     * @param mixed oxconfig.OXVARNAME
     * @return string|boolean value
     */
    public function getPayPalModuleConfigurationValue($varname)
    {
        if ($varname == '') {
            return (bool) false;
        }

        //TODO: try catch invalid settings
        #return $this->getServiceFromContainer(ModuleSettings::class)->getRawValue($varname);

        return (string) Registry::getConfig()->getConfigParam($varname);
    }

    /**
     * This ClientId is public. The only function is to create
     * a basiclly AccessToken,  Which one is needed to generate
     * the request for the merchant ClientId.
     * For this purpose, this ClientId is unencrypted, here as part
     * of this open Source Module
     * this method is private see getTechnicalClientId which respects the sandbox mode for you
     * @return string
     */
    public function getLiveOxidClientId(): string
    {
        return "AQPFC4NJr-nIiumTXvyfHFJLK-RlWAcv9D0zAc4OWiKiQXyXnJZ7Lw1E2h6O2mtceJf5kWflplieijnK";
    }

    /**
     * This ClientId is public. The only function is to create
     * a basiclly AccessToken,  Which one is needed to generate
     * the request for the merchant ClientId and Secret.
     * For this purpose, this ClientId is unencrypted, here as part
     * of this open Source Module
     *
     * @return string
     */
    public function getSandboxOxidClientId(): string
    {
        return "AS-lHBWs8cudxxonSeQ1eRbdn1Nr-7baqAURRNJnIuP-PPQFzFF1XkjDYV3NG3M6O75st2D98DOil4Vd";
    }

    /**
     * This PartnerId is public. The only function is to create
     * a basiclly AccessToken,  Which one is needed to generate
     * the request for the merchant ClientId and Secret.
     * For this purpose, this ClientId is unencrypted, here as part
     * of this open Source Module
     * this method is private see getTechnicalPartnerId which respects the sandbox mode for you
     * @return string
     */
    public function getLiveOxidPartnerId(): string
    {
        return "FULA6AY33UTA4";
    }

    /**
     * This PartnerId is public. The only function is to create
     * a basiclly AccessToken,  Which one is needed to generate
     * the request for the merchant ClientId and Secret.
     * For this purpose, this PartnerId is unencrypted, here as part
     * of this open Source Module
     *
     * @return string
     */
    public function getSandboxOxidPartnerId(): string
    {
        return "LRCHTG6NYPSXN";
    }

    /**
     * This Secret is public. The only function is to create
     * a basiclly AccessToken,  Which one is needed to generate
     * the request for the merchant ClientId and Secret.
     * For this purpose, this ClientId is unencrypted, here as part
     * of this open Source Module
     *
     * @return string
     */
    public function getLiveOxidSecret(): string
    {
        return "ELcHsbqzqmC8wVbndnDxokTnQboMn-HfcJ2tGfWbxJUIAIys0HMqfzbHrev5R--RPd6B2xNWJrddtO9z";
    }

    /**
     * This Secret is public. The only function is to create
     * a basiclly AccessToken,  Which one is needed to generate
     * the request for the merchant ClientId and Secret.
     * For this purpose, this PartnerId is unencrypted, here as part
     * of this open Source Module
     *
     * @return string
     */
    public function getSandboxOxidSecret(): string
    {
        return "EANkP__pSQ25b1cXuO4CrC_KeDc78rKtgUpeEDthejOVjkJV9sv0mfjxM_A4qXyMqbdCIeib0tDfQY_6";
    }

    /**
     * @return string
     */
    public function getTechnicalClientId()
    {
        return $this->isSandbox() ?
            $this->getSandboxOxidClientId()
            : $this->getLiveOxidClientId();
    }

    /**
     * @return string
     */
    public function getTechnicalPartnerId()
    {
        return $this->isSandbox() ?
            $this->getSandboxOxidPartnerId()
            : $this->getLiveOxidPartnerId();
    }

    /**
     * @return string
     */
    public function getTechnicalClientSecret()
    {
        return $this->isSandbox() ?
            $this->getSandboxOxidSecret()
            : $this->getLiveOxidSecret();
    }

    /**
     * Get webhook controller url
     *
     * @return string
     */
    public function getWebhookControllerUrl(): string
    {
        return html_entity_decode(
            Registry::getConfig()->getCurrentShopUrl(false) . 'index.php?cl=oscpaypalwebhook'
        );
    }

    public function getClientUrl(): string
    {
        return $this->isSandbox() ? $this->getClientSandboxUrl() : $this->getClientLiveUrl();
    }

    public function getClientLiveUrl(): string
    {
        return Client::PRODUCTION_URL;
    }

    public function getClientSandboxUrl(): string
    {
        return Client::SANDBOX_URL;
    }
}
