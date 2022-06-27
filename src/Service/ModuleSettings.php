<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\EshopCommunity\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Module;
use OxidEsales\Eshop\Application\Model\Payment;

class ModuleSettings
{
    /**
     * Force session start for details-controller, so PayPal-Express-Buttons works everytime
     *
     * @var array
     */
    protected $requireSessionWithParams = [
        'cl' => [
            'details' => true
        ]
    ];

    protected $payPalCheckoutExpressPaymentEnabled = null;

    /** @var ModuleSettingBridgeInterface */
    private $moduleSettingBridge;

    /** @var ContextInterface */
    private $context;

    //TODO: we need service for fetching module settings from db (this one)
    //another class for moduleconfiguration (database values/edefaults)
    //and the view configuration should go into some separate class
    //also add shopcontext to get shop settings

    public function __construct(
        ModuleSettingBridgeInterface $moduleSettingBridge,
        ContextInterface $context
    ) {
        $this->moduleSettingBridge = $moduleSettingBridge;
        $this->context = $context;
    }

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
        return ($this->getSettingValue('oscPayPalShowBasketButton') &&
            $this->isPayPalCheckoutExpressPaymentEnabled());
    }

    public function showPayPalMiniBasketButton(): bool
    {
        return ($this->getSettingValue('oscPayPalShowMiniBasketButton') &&
            $this->isPayPalCheckoutExpressPaymentEnabled());
    }

    public function showPayPalPayLaterButton(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalShowPayLaterButton');
    }

    public function showPayPalProductDetailsButton(): bool
    {
        return ($this->getSettingValue('oscPayPalShowProductDetailsButton') &&
            $this->isPayPalCheckoutExpressPaymentEnabled());
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

    public function getPayPalStandardCaptureStrategy(): string
    {
        return (string) $this->getSettingValue('oscPayPalStandardCaptureStrategy');
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
        $this->moduleSettingBridge->save($name, $value, Module::MODULE_ID);
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
     * add details controller to requireSession
     */
    public function addRequireSession(): void
    {
        $config = Registry::getConfig();
        $cfg = $config->getConfigParam('aRequireSessionWithParams');
        $cfg = is_array($cfg) ? $cfg : [];
        $cfg = array_merge_recursive($cfg, $this->requireSessionWithParams);
        $config->saveShopConfVar('arr', 'aRequireSessionWithParams', $cfg, (string)$this->context->getCurrentShopId());
    }

    /**
     * This setting indicates whether settings from the legacy modules have been transferred.
     * @return bool
     */
    public function getLegacySettingsTransferStatus(): bool
    {
        return (bool) $this->getSettingValue('oscPayPalLegacySettingsTransferred');
    }

    /**
     * @return mixed
     */
    private function getSettingValue(string $key)
    {
        return $this->moduleSettingBridge->get($key, Module::MODULE_ID);
    }

    /**
     * @return boolean
     */
    private function isPayPalCheckoutExpressPaymentEnabled(): bool
    {
        if ($this->payPalCheckoutExpressPaymentEnabled === null) {
            $payment = oxNew(Payment::class);
            $payment->load(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);
            $this->payPalCheckoutExpressPaymentEnabled = (bool)$payment->oxpayments__oxactive->value;
        }
        return $this->payPalCheckoutExpressPaymentEnabled;
    }
}
