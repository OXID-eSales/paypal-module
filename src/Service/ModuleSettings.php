<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidSolutionCatalysts\PayPal\Module;

class ModuleSettings
{
    public function showAllPayPalBanners(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalBannersShowAll');
    }

    public function isSandbox(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalSandboxMode');
    }

    /**
     * Checks if module configurations are valid
     */
    public function checkHealth(): bool
    {
        return (
            $this->getClientId() &&
            $this->getClientSecret() &&
            $this->getWebhookId()
        );
    }

    public function getClientId(): string
    {
        return $this->isSandbox() ?
            $this->getSandboxClientId() :
            $this->getLiveClientId();
    }

    public function getClientSecret(): string
    {
        return $this->isSandbox() ?
            $this->getSandboxClientSecret() :
            $this->getLiveClientSecret();
    }

    public function getWebhookId(): string
    {
        return $this->isSandbox() ?
            $this->getSandboxWebhookId() :
            $this->getLiveWebhookId();
    }

    public function isAcdcEligibility(): bool
    {
        return $this->isSandbox() ?
            $this->isSandboxAcdcEligibility() :
            $this->isLiveAcdcEligibility();
    }


    public function isPuiEligibility(): bool
    {
        return $this->isSandbox() ?
            $this->isSandboxPuiEligibility() :
            $this->isLivePuiEligibility();
    }

    public function getLiveClientId(): string
    {
        return (string) $this->getSettingValue('oscPayPalClientId');
    }

    public function getLiveClientSecret(): string
    {
        return (string) $this->getSettingValue('oscPayPalClientSecret');
    }

    public function getLiveWebhookId(): string
    {
        return (string) $this->getSettingValue('oscPayPalWebhookId');
    }

    public function getSandboxClientId(): string
    {
        return (string) $this->getSettingValue('oscPayPalSandboxClientId');
    }

    public function getSandboxClientSecret(): string
    {
        return (string) $this->getSettingValue('oscPayPalSandboxClientSecret');
    }

    public function getSandboxWebhookId(): string
    {
        return (string) $this->getSettingValue('oscPayPalSandboxWebhookId');
    }

    public function showPayPalBasketButton(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalShowBasketButton');
    }

    public function showPayPalPayLaterButton(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalShowPayLaterButton');
    }

    public function showPayPalProductDetailsButton(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalShowProductDetailsButton');
    }

    public function getAutoBillOutstanding(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalAutoBillOutstanding');
    }

    public function getSetupFeeFailureAction(): string
    {
        $value = (string) $this->getSettingValue('oscPayPalSetupFeeFailureAction');
        return !empty($value) ? $value : 'CONTINUE';
    }

    public function getPaymentFailureThreshold(): string
    {
        $value = $this->getSettingValue('oscPayPalPaymentFailureThreshold');
        return !empty($value) ? $value : '1';
    }

    public function showBannersOnStartPage(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalBannersStartPage');
    }

    public function getStartPageBannerSelector(): string
    {
        return (string) $this->getSettingValue('oscPayPalBannersStartPageSelector');
    }

    public function showBannersOnCategoryPage(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalBannersCategoryPage');
    }

    public function getCategoryPageBannerSelector(): string
    {
        return (string) $this->getSettingValue('oscPayPalBannersCategoryPageSelector');
    }

    public function showBannersOnSearchPage(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalBannersSearchResultsPage');
    }

    public function getSearchPageBannerSelector(): string
    {
        return (string) $this->getSettingValue('oscPayPalBannersSearchResultsPageSelector');
    }

    public function showBannersOnProductDetailsPage(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalBannersProductDetailsPage');
    }

    public function getProductDetailsPageBannerSelector(): string
    {
        return (string) $this->getSettingValue('oscPayPalBannersProductDetailsPageSelector');
    }

    public function showBannersOnCheckoutPage(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalBannersCheckoutPage');
    }

    public function getPayPalCheckoutBannerCartPageSelector(): string
    {
        return (string) $this->getSettingValue('oscPayPalBannersCartPageSelector');
    }

    public function getPayPalCheckoutBannerPaymentPageSelector(): string
    {
        return (string) $this->getSettingValue('oscPayPalBannersPaymentPageSelector');
    }

    public function getPayPalCheckoutBannerColorScheme(): string
    {
        return (string) $this->getSettingValue('oscPayPalBannersColorScheme');
    }

    public function getPayPalCheckoutBannersColorScheme(): string
    {
        return (string) $this->getSettingValue('oscPayPalBannersColorScheme');
    }

    public function loginWithPayPalEMail(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalLoginWithPayPalEMail');
    }

    public function isLiveAcdcEligibility(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalAcdcEligibility');
    }

    public function isLivePuiEligibility(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalPuiEligibility');
    }

    public function isSandboxAcdcEligibility(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalSandboxAcdcEligibility');
    }

    public function isSandboxPuiEligibility(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalSandboxPuiEligibility');
    }

    public function save(string $name, $value): void
    {
        Registry::getConfig()->setConfigParam($$name, $value);
    }

    public function saveSandboxMode(bool $mode): void
    {
        $this->save('oscPayPalSandboxMode', $mode);
    }

    public function saveClientId(string $clientId): void
    {
        if ($this->isSandbox()) {
            $this->save('oscPayPalSandboxClientId', $clientId);
        } else {
            $this->save('oscPayPalClientId', $clientId);
        }
    }

    public function saveClientSecret(string $clientSecret): void
    {
        if ($this->isSandbox()) {
            $this->save('oscPayPalSandboxClientSecret', $clientSecret);
        } else {
            $this->save('oscPayPalClientSecret', $clientSecret);
        }
    }

    public function saveAcdcEligibility(bool $eligibility): void
    {
        if ($this->isSandbox()) {
            $this->save('oscPayPalSandboxAcdcEligibility', $eligibility);
        } else {
            $this->save('oscPayPalAcdcEligibility', $eligibility);
        }
    }

    public function savePuiEligibility(bool $eligibility): void
    {
        if ($this->isSandbox()) {
            $this->save('oscPayPalSandboxPuiEligibility', $eligibility);
        } else {
            $this->save('oscPayPalPuiEligibility', $eligibility);
        }
    }

    public function saveWebhookId(string $webhookId): void
    {
        if ($this->isSandbox()) {
            $this->save('oscPayPalSandboxWebhookId', $webhookId);
        } else {
            $this->save('oscPayPalWebhookId', $webhookId);
        }
    }

    /**
     * @return mixed
     */
    private function getSettingValue(string $key)
    {
        return Registry::getConfig()->getConfigParam($key);
    }

    /**
     * This setting indicates whether settings from the legacy modules have been transferred.
     * @return bool
     */
    public function getLegacySettingsTransferStatus(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalLegacySettingsTransferred');
    }
}
