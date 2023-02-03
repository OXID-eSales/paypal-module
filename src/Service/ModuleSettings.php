<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\EshopCommunity\Core\Registry;
use OxidEsales\Eshop\Core\Config as EshopCoreConfig;
use OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\User;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidEsales\Eshop\Application\Model\Country;

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

    /** @var Database */
    private $db;

    /** @var EshopCoreConfig */
    private $config;

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

    public function __construct(
        EshopCoreConfig $config,
        Database $db
    ) {
        $this->config = $config;
        $this->db = $db;
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

    public function getActivePayments(): array
    {
        /** @var array|null $activePayments */
        $activePayments = $this->getSettingValue('oscPayPalActivePayments');
        return $activePayments ?: [];
    }

    public function save(string $name, $value, string $type = '')
    {
        $this->config->setConfigParam($name, $value);
        $shopId = (string)$this->config->getShopId();
        $module = 'module:osc_paypal';

        if (!$type) {
            $query = "select oxvartype
                from oxconfig
                where oxshopid = :shopId
                and oxmodule = :module
                and oxvarname = :value";
            $type = $this->db->getOne($query, [
                ':shopId' => $shopId,
                ':module' => $module,
                ':value' => $value
            ]);
        }

        $this->config->saveShopConfVar(
            $type,
            $name,
            $value,
            $shopId,
            $module
        );
    }

    public function saveSandboxMode(bool $mode)
    {
        $this->save('oscPayPalSandboxMode', $mode, 'bool');
    }

    public function saveClientId(string $clientId)
    {
        if ($this->isSandbox()) {
            $this->save('oscPayPalSandboxClientId', $clientId, 'str');
        } else {
            $this->save('oscPayPalClientId', $clientId, 'str');
        }
    }

    public function saveClientSecret(string $clientSecret)
    {
        if ($this->isSandbox()) {
            $this->save('oscPayPalSandboxClientSecret', $clientSecret, 'str');
        } else {
            $this->save('oscPayPalClientSecret', $clientSecret, 'str');
        }
    }

    public function saveAcdcEligibility(bool $eligibility)
    {
        if ($this->isSandbox()) {
            $this->save('oscPayPalSandboxAcdcEligibility', $eligibility, 'bool');
        } else {
            $this->save('oscPayPalAcdcEligibility', $eligibility, 'bool');
        }
    }

    public function savePuiEligibility(bool $eligibility)
    {
        if ($this->isSandbox()) {
            $this->save('oscPayPalSandboxPuiEligibility', $eligibility, 'bool');
        } else {
            $this->save('oscPayPalPuiEligibility', $eligibility, 'bool');
        }
    }

    public function saveWebhookId(string $webhookId)
    {
        if ($this->isSandbox()) {
            $this->save('oscPayPalSandboxWebhookId', $webhookId, 'str');
        } else {
            $this->save('oscPayPalWebhookId', $webhookId, 'str');
        }
    }

    public function saveActivePayments(array $activePayments)
    {
        $this->save('oscPayPalActivePayments', $activePayments);
    }

    /**
     * add details controller to requireSession
     */
    public function addRequireSession()
    {
        $cfg = $this->config->getConfigParam('aRequireSessionWithParams');
        $cfg = is_array($cfg) ? $cfg : [];
        $cfg = array_merge_recursive($cfg, $this->requireSessionWithParams);
        $this->config->saveShopConfVar('arr', 'aRequireSessionWithParams', $cfg, (string)$this->config->getShopId());
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
        $result = (bool) Registry::getConfig()->getConfigParam('blShowNetPrice');
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
        $value = (string) $this->getSettingValue('oscPayPalSCAContingency');
        return $value === Constants::PAYPAL_SCA_ALWAYS ? $value : Constants::PAYPAL_SCA_WHEN_REQUIRED;
    }

    public function alwaysIgnoreSCAResult(): bool
    {
        $value = (string) $this->getSettingValue('oscPayPalSCAContingency');
        return $value === Constants::PAYPAL_SCA_DISABLED;
    }

    /**
     * @return mixed
     */
    private function getSettingValue(string $key)
    {
        return $this->config->getConfigParam($key);
    }
}
