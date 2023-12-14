<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleSettingNotFountException;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidSolutionCatalysts\PayPal\Core\Logger;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Module;
use Psr\Log\LoggerInterface;

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

    /**
     * Country Restriction for PayPal as comma seperated string
     *
     * @var bool
     */
    protected $payPalCheckoutExpressPaymentEnabled = null;

    /**
     * Country Restriction for PayPal as comma seperated string
     *
     * @var string
     */
    protected $countryRestrictionForPayPalExpress = null;

    /** @var ModuleSettingBridgeInterface */
    private $moduleSettingBridge;

    /** @var ModuleConfigurationDaoBridgeInterface */
    private $moduleConfigurationDaoBridgeInterface;

    /** @var ModuleConfiguration */
    private $moduleConfiguration = null;

    /** @var ContextInterface */
    private $context;

    //TODO: we need service for fetching module settings from db (this one)
    //another class for moduleconfiguration (database values/edefaults)
    //and the view configuration should go into some separate class
    //also add shopcontext to get shop settings

    public function __construct(
        ModuleSettingBridgeInterface $moduleSettingBridge,
        ContextInterface $context,
        ModuleConfigurationDaoBridgeInterface $moduleConfigurationDaoBridgeInterface
    ) {
        $this->moduleSettingBridge = $moduleSettingBridge;
        $this->context = $context;
        $this->moduleConfigurationDaoBridgeInterface = $moduleConfigurationDaoBridgeInterface;
    }

    public function showAllPayPalBanners(): bool
    {
        return (bool)$this->getSettingValue('oscPayPalBannersShowAll');
    }

    public function isSandbox(): bool
    {
        return (bool)$this->getSettingValue('oscPayPalSandboxMode');
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

    public function getMerchantId(): string
    {
        return $this->isSandbox() ?
            $this->getSandboxMerchantId() :
            $this->getLiveMerchantId();
    }

    public function getWebhookId(): string
    {
        return $this->isSandbox() ?
            $this->getSandboxWebhookId() :
            $this->getLiveWebhookId();
    }

    public function getSupportedLocales(): array
    {
        $commaSeparated = $this->getSupportedLocalesCommaSeparated();

        return explode(',', $commaSeparated);
    }

    public function getSupportedLocalesCommaSeparated(): string
    {
        return $this->getSettingValue('oscPayPalLocales');
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
        return (string)$this->getSettingValue('oscPayPalClientId');
    }

    public function getLiveClientSecret(): string
    {
        return (string)$this->getSettingValue('oscPayPalClientSecret');
    }

    public function getLiveMerchantId(): string
    {
        return (string)$this->getSettingValue('oscPayPalClientMerchantId');
    }

    public function getLiveWebhookId(): string
    {
        return (string)$this->getSettingValue('oscPayPalWebhookId');
    }

    public function getSandboxClientId(): string
    {
        return (string)$this->getSettingValue('oscPayPalSandboxClientId');
    }

    public function getSandboxClientSecret(): string
    {
        return (string)$this->getSettingValue('oscPayPalSandboxClientSecret');
    }

    public function getSandboxMerchantId(): string
    {
        return (string)$this->getSettingValue('oscPayPalSandboxClientMerchantId');
    }

    public function getSandboxWebhookId(): string
    {
        return (string)$this->getSettingValue('oscPayPalSandboxWebhookId');
    }

    public function showPayPalBasketButton(): bool
    {
        return $this->getSettingValue('oscPayPalShowBasketButton') &&
            ($this->isPayPalCheckoutExpressPaymentEnabled() || $this->isAdmin());
    }

    public function showPayPalMiniBasketButton(): bool
    {

        return
            $this->getSettingValue('oscPayPalShowMiniBasketButton') &&
            ($this->isPayPalCheckoutExpressPaymentEnabled() || $this->isAdmin());
    }

    public function showPayPalPayLaterButton(): bool
    {
        return (bool)$this->getSettingValue('oscPayPalShowPayLaterButton');
    }

    public function showPayPalProductDetailsButton(): bool
    {
        return $this->getSettingValue('oscPayPalShowProductDetailsButton') &&
            ($this->isPayPalCheckoutExpressPaymentEnabled() || $this->isAdmin());
    }

    public function getAutoBillOutstanding(): bool
    {
        return (bool)$this->getSettingValue('oscPayPalAutoBillOutstanding');
    }

    public function getSetupFeeFailureAction(): string
    {
        $value = (string)$this->getSettingValue('oscPayPalSetupFeeFailureAction');
        return !empty($value) ? $value : 'CONTINUE';
    }

    public function getPaymentFailureThreshold(): string
    {
        $value = $this->getSettingValue('oscPayPalPaymentFailureThreshold');
        return !empty($value) ? $value : '1';
    }

    public function showBannersOnStartPage(): bool
    {
        return (bool)$this->getSettingValue('oscPayPalBannersStartPage');
    }

    public function getStartPageBannerSelector(): string
    {
        return (string)$this->getSettingValue('oscPayPalBannersStartPageSelector');
    }

    public function showBannersOnCategoryPage(): bool
    {
        return (bool)$this->getSettingValue('oscPayPalBannersCategoryPage');
    }

    public function getCategoryPageBannerSelector(): string
    {
        return (string)$this->getSettingValue('oscPayPalBannersCategoryPageSelector');
    }

    public function showBannersOnSearchPage(): bool
    {
        return (bool)$this->getSettingValue('oscPayPalBannersSearchResultsPage');
    }

    public function getSearchPageBannerSelector(): string
    {
        return (string)$this->getSettingValue('oscPayPalBannersSearchResultsPageSelector');
    }

    public function showBannersOnProductDetailsPage(): bool
    {
        return (bool)$this->getSettingValue('oscPayPalBannersProductDetailsPage');
    }

    public function getProductDetailsPageBannerSelector(): string
    {
        return (string)$this->getSettingValue('oscPayPalBannersProductDetailsPageSelector');
    }

    public function showBannersOnCheckoutPage(): bool
    {
        return (bool)$this->getSettingValue('oscPayPalBannersCheckoutPage');
    }

    public function getPayPalCheckoutBannerCartPageSelector(): string
    {
        return (string)$this->getSettingValue('oscPayPalBannersCartPageSelector');
    }

    public function getPayPalCheckoutBannerPaymentPageSelector(): string
    {
        return (string)$this->getSettingValue('oscPayPalBannersPaymentPageSelector');
    }

    public function getPayPalCheckoutBannerColorScheme(): string
    {
        return (string)$this->getSettingValue('oscPayPalBannersColorScheme');
    }

    public function getPayPalStandardCaptureStrategy(): string
    {
        return (string)$this->getSettingValue('oscPayPalStandardCaptureStrategy');
    }

    public function getPayPalCheckoutBannersColorScheme(): string
    {
        return (string)$this->getSettingValue('oscPayPalBannersColorScheme');
    }

    public function loginWithPayPalEMail(): bool
    {
        return (bool)$this->getSettingValue('oscPayPalLoginWithPayPalEMail');
    }

    public function cleanUpNotFinishedOrdersAutomaticlly(): bool
    {
        return (bool)$this->getSettingValue('oscPayPalCleanUpNotFinishedOrdersAutomaticlly');
    }

    public function getStartTimeCleanUpOrders(): int
    {
        return (int)$this->getSettingValue('oscPayPalStartTimeCleanUpOrders');
    }


    public function isLiveAcdcEligibility(): bool
    {
        return (bool)$this->getSettingValue('oscPayPalAcdcEligibility');
    }

    public function isLivePuiEligibility(): bool
    {
        return (bool)$this->getSettingValue('oscPayPalPuiEligibility');
    }

    public function isSandboxAcdcEligibility(): bool
    {
        return (bool)$this->getSettingValue('oscPayPalSandboxAcdcEligibility');
    }

    public function isSandboxPuiEligibility(): bool
    {
        return (bool)$this->getSettingValue('oscPayPalSandboxPuiEligibility');
    }

    public function getActivePayments(): array
    {
        /** @var array|null $activePayments */
        $activePayments = $this->getSettingValue('oscPayPalActivePayments');
        return $activePayments ?: [];
    }

    /**
     * @throws ModuleSettingNotFountException
     */
    public function save(string $name, $value): void
    {
        if (is_null($this->moduleConfiguration)) {
            $this->moduleConfiguration = $this->moduleConfigurationDaoBridgeInterface->get(Module::MODULE_ID);
        }
        $moduleSetting = $this->moduleConfiguration->getModuleSetting($name);

        if ($moduleSetting->getType() === 'str') {
            $value = trim($value);
        }
        if ($moduleSetting->getType() === 'bool') {
            $value = (bool)$value;
        }

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

    /**
     * @throws ModuleSettingNotFountException
     */
    public function saveMerchantId(string $merchantId, ?bool $isSandbox = null): void
    {
        $isSandbox = !is_null($isSandbox) ? $isSandbox : $this->isSandbox();

        if ($isSandbox) {
            $this->save('oscPayPalSandboxClientMerchantId', $merchantId);
        }

        if (!$isSandbox) {
            $this->save('oscPayPalClientMerchantId', $merchantId);
        }

        /** @var Logger $logger */
        $logger = oxNew(Logger::class);
        $logger->log(
            'debug',
            sprintf(
                'Saving Live  Merchant ID %s from onboarding',
                $merchantId
            )
        );
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

    public function saveActivePayments(array $activePayments): void
    {
        $this->save('oscPayPalActivePayments', $activePayments);
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
        return (bool)$this->getSettingValue('oscPayPalLegacySettingsTransferred');
    }

    /**
     * @return boolean
     */
    public function isPayPalCheckoutExpressPaymentEnabled(): bool
    {
        if ($this->payPalCheckoutExpressPaymentEnabled === null) {
            $expressEnabled = false;
            $payment = oxNew(Payment::class);
            $payment->load(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);
            // check currency
            if ($expressEnabled = (bool)$payment->oxpayments__oxactive->value) {
                $actShopCurrency = Registry::getConfig()->getActShopCurrencyObject();
                $payPalDefinitions = PayPalDefinitions::getPayPalDefinitions();
                $expressEnabled = in_array(
                    $actShopCurrency->name,
                    $payPalDefinitions[PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID]['currencies']
                );
            }
            $this->payPalCheckoutExpressPaymentEnabled = $expressEnabled;
        }
        return $this->payPalCheckoutExpressPaymentEnabled;
    }

    /**
     * Checks and return true if price view mode is netto
     *
     * @return bool
     */
    public function isPriceViewModeNetto(): bool
    {
        $result = (bool)Registry::getConfig()->getConfigParam('blShowNetPrice');
        $user = oxNew(User::class);
        if ($user->loadActiveUser()) {
            $result = $user->isPriceViewModeNetto();
        }
        return $result;
    }

    /**
     * Returns comma seperated String with the Country Restriction for PayPal Express
     */
    public function getCountryRestrictionForPayPalExpress(): string
    {
        if (is_null($this->countryRestrictionForPayPalExpress)) {
            $this->countryRestrictionForPayPalExpress = '';
            $payment = oxNew(Payment::class);
            $payment->load(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);
            $countries = $payment->getCountries();
            $countriesIso = [];
            if (count($countries)) {
                $country = oxNew(Country::class);
                foreach ($countries as $countryId) {
                    $country->load($countryId);
                    $countriesIso[] = $country->getFieldData('oxisoalpha2');
                }
                $this->countryRestrictionForPayPalExpress = sprintf(
                    "'%s'",
                    implode("','", $countriesIso)
                );
            }
        }
        return $this->countryRestrictionForPayPalExpress;
    }

    public function getPayPalSCAContingency(): string
    {
        $value = (string)$this->getSettingValue('oscPayPalSCAContingency');
        return $value === Constants::PAYPAL_SCA_ALWAYS ? $value : Constants::PAYPAL_SCA_WHEN_REQUIRED;
    }

    public function alwaysIgnoreSCAResult(): bool
    {
        $value = (string)$this->getSettingValue('oscPayPalSCAContingency');
        return $value === Constants::PAYPAL_SCA_DISABLED;
    }

    /**
     * @return mixed
     */
    private function getSettingValue(string $key)
    {
        return $this->moduleSettingBridge->get($key, Module::MODULE_ID);
    }

    private function isAdmin(): bool
    {
        return isAdmin();
    }
}
