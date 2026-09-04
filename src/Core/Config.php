<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidSolutionCatalysts\PayPal\Module;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPalApi\Client;
use RuntimeException;

/**
 * Class Config
 * Optimized version with service caching
 */
class Config
{
    use ServiceContainer;

    /**
     * Cached ModuleSettings service instance
     * @var ModuleSettings|null
     */
    private $moduleSettings = null;

    /**
     * Get ModuleSettings service (cached to avoid repeated container lookups)
     *
     * @return ModuleSettings
     */
    private function getModuleSettings(): ModuleSettings
    {
        if ($this->moduleSettings === null) {
            $this->moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        }
        return $this->moduleSettings;
    }

    /**
     * Checks if module configurations are valid
     *
     * @throws StandardException
     */
    public function checkHealth(): void
    {
        if (!$this->getModuleSettings()->checkHealth()) {
            throw oxNew(StandardException::class);
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
        return $this->getModuleSettings()->isSandbox();
    }

    /**
     * Get client id based on set active mode
     */
    public function getClientId(): string
    {
        return $this->getModuleSettings()->getClientId();
    }

    public function getLiveClientId(): string
    {
        return $this->getModuleSettings()->getLiveClientId();
    }

    public function getSandboxClientId(): string
    {
        return $this->getModuleSettings()->getSandboxClientId();
    }

    /**
     * Get client secret based on active mode
     */
    public function getClientSecret(): string
    {
        return $this->getModuleSettings()->getClientSecret();
    }

    public function getLiveClientSecret(): string
    {
        return $this->getModuleSettings()->getLiveClientSecret();
    }

    public function getSandboxClientSecret(): string
    {
        return $this->getModuleSettings()->getSandboxClientSecret();
    }

    /**
     * Get merchantId based on active mode
     */
    public function getMerchantId(): string
    {
        return $this->getModuleSettings()->getMerchantId();
    }

    public function getLiveMerchantId(): string
    {
        return $this->getModuleSettings()->getLiveMerchantId();
    }

    public function getSandboxMerchantId(): string
    {
        return $this->getModuleSettings()->getSandboxMerchantId();
    }

    public function getWebhookId(): string
    {
        return $this->getModuleSettings()->getWebhookId();
    }

    public function getLiveWebhookId(): string
    {
        return $this->getModuleSettings()->getLiveWebhookId();
    }

    public function getSandboxWebhookId(): string
    {
        return $this->getModuleSettings()->getSandboxWebhookId();
    }

    /**
     * Get Eligibility
     */
    public function isAcdcEligibility(): bool
    {
        return $this->getModuleSettings()->isAcdcEligibility();
    }

    public function isLiveAcdcEligibility(): bool
    {
        return $this->getModuleSettings()->isLiveAcdcEligibility();
    }

    public function isSandboxAcdcEligibility(): bool
    {
        return $this->getModuleSettings()->isSandboxAcdcEligibility();
    }

    /**
     * Whether PayPal offers ACDC to a merchant in the shop's own country at all. Needed by the
     * module configuration template, which reports the raw eligibility PayPal answered and would
     * otherwise promise a payment method the checkout deliberately hides.
     */
    public function isAcdcSupportedInShopCountry(): bool
    {
        return $this->getModuleSettings()->isAcdcSupportedInShopCountry();
    }

    public function isPuiEligibility(): bool
    {
        return $this->getModuleSettings()->isPuiEligibility();
    }

    public function isLivePuiEligibility(): bool
    {
        return $this->getModuleSettings()->isLivePuiEligibility();
    }

    public function isSandboxPuiEligibility(): bool
    {
        return $this->getModuleSettings()->isSandboxPuiEligibility();
    }

    public function isVaultingEligibility(): bool
    {
        return $this->getModuleSettings()->isVaultingEligibility();
    }

    public function isLiveVaultingEligibility(): bool
    {
        return $this->getModuleSettings()->isLiveVaultingEligibility();
    }

    public function isSandboxVaultingEligibility(): bool
    {
        return $this->getModuleSettings()->isSandboxVaultingEligibility();
    }

    public function isLiveApplePayEligibility(): bool
    {
        return $this->getModuleSettings()->isLiveApplePayEligibility();
    }

    public function isSandboxApplePayEligibility(): bool
    {
        return $this->getModuleSettings()->isSandboxApplePayEligibility();
    }

    public function isLiveGooglePayEligibility(): bool
    {
        return $this->getModuleSettings()->isLiveGooglePayEligibility();
    }

    public function isSandboxGooglePayEligibility(): bool
    {
        return $this->getModuleSettings()->isSandboxGooglePayEligibility();
    }

    public function isLiveEpsEligibility(): bool
    {
        return $this->getModuleSettings()->isLiveEpsEligibility();
    }

    public function isSandboxEpsEligibility(): bool
    {
        return $this->getModuleSettings()->isSandboxEpsEligibility();
    }

    public function isLivePrzelewy24Eligibility(): bool
    {
        return $this->getModuleSettings()->isLivePrzelewy24Eligibility();
    }

    public function isSandboxPrzelewy24Eligibility(): bool
    {
        return $this->getModuleSettings()->isSandboxPrzelewy24Eligibility();
    }

    public function isLiveSepaEligibility(): bool
    {
        return $this->getModuleSettings()->isLiveSepaEligibility();
    }

    public function isSandboxSepaEligibility(): bool
    {
        return $this->getModuleSettings()->isSandboxSepaEligibility();
    }

    public function isLiveBlikEligibility(): bool
    {
        return $this->getModuleSettings()->isLiveBlikEligibility();
    }

    public function isSandboxBlikEligibility(): bool
    {
        return $this->getModuleSettings()->isSandboxBlikEligibility();
    }

    public function isLiveBanContactEligibility(): bool
    {
        return $this->getModuleSettings()->isLiveBanContactEligibility();
    }

    public function isSandboxBanContactEligibility(): bool
    {
        return $this->getModuleSettings()->isSandboxBanContactEligibility();
    }

    public function isLiveIDealEligibility(): bool
    {
        return $this->getModuleSettings()->isLiveIDealEligibility();
    }

    public function isSandboxIDealEligibility(): bool
    {
        return $this->getModuleSettings()->isSandboxIDealEligibility();
    }

    public function getSupportedLocales(): array
    {
        return $this->getModuleSettings()->getSupportedLocales();
    }

    public function getSupportedLocalesCommaSeparated(): string
    {
        return $this->getModuleSettings()->getSupportedLocalesCommaSeparated();
    }

    public function showPayPalBasketButton(): bool
    {
        return $this->getModuleSettings()->showPayPalBasketButton();
    }

    public function showPayPalMiniBasketButton(): bool
    {
        return $this->getModuleSettings()->showPayPalMiniBasketButton();
    }

    public function showPayPalPayLaterButton(): bool
    {
        return $this->getModuleSettings()->showPayPalPayLaterButton();
    }

    public function showPayPalProductDetailsButton(): bool
    {
        return $this->getModuleSettings()->showPayPalProductDetailsButton();
    }

    public function loginWithPayPalEMail(): bool
    {
        return $this->getModuleSettings()->loginWithPayPalEMail();
    }

    public function getRefundMailRecipient(): string
    {
        return $this->getModuleSettings()->getRefundMailRecipient();
    }

    public function getCancelMailRecipient(): string
    {
        return $this->getModuleSettings()->getCancelMailRecipient();
    }

    public function getAutoBillOutstanding(): bool
    {
        return $this->getModuleSettings()->getAutoBillOutstanding();
    }

    public function getSetupFeeFailureAction(): string
    {
        return $this->getModuleSettings()->getSetupFeeFailureAction();
    }

    public function getPaymentFailureThreshold(): string
    {
        return $this->getModuleSettings()->getPaymentFailureThreshold();
    }

    public function showAllPayPalBanners(): bool
    {
        return $this->getModuleSettings()->showAllPayPalBanners();
    }

    public function showBannersOnStartPage(): bool
    {
        return $this->getModuleSettings()->showBannersOnStartPage();
    }

    public function getStartPageBannerSelector(): string
    {
        return $this->getModuleSettings()->getStartPageBannerSelector();
    }

    public function getDefaultShippingPriceForExpress(): string
    {
        return $this->getModuleSettings()->getDefaultShippingPriceForExpress();
    }

    public function getWebhookRetryDelay(): int
    {
        return $this->getModuleSettings()->getWebhookRetryDelay();
    }

    public function showBannersOnCategoryPage(): bool
    {
        return $this->getModuleSettings()->showBannersOnCategoryPage();
    }

    public function getCategoryPageBannerSelector(): string
    {
        return $this->getModuleSettings()->getCategoryPageBannerSelector();
    }

    public function showBannersOnSearchPage(): bool
    {
        return $this->getModuleSettings()->showBannersOnSearchPage();
    }

    public function getSearchPageBannerSelector(): string
    {
        return $this->getModuleSettings()->getSearchPageBannerSelector();
    }

    public function showBannersOnProductDetailsPage(): bool
    {
        return $this->getModuleSettings()->showBannersOnProductDetailsPage();
    }

    public function getProductDetailsPageBannerSelector(): string
    {
        return $this->getModuleSettings()->getProductDetailsPageBannerSelector();
    }

    public function showBannersOnCheckoutPage(): bool
    {
        return $this->getModuleSettings()->showBannersOnCheckoutPage();
    }

    public function getPayPalCheckoutBannerCartPageSelector(): string
    {
        return $this->getModuleSettings()->getPayPalCheckoutBannerCartPageSelector();
    }

    public function getPayPalCheckoutBannerPaymentPageSelector(): string
    {
        return $this->getModuleSettings()->getPayPalCheckoutBannerPaymentPageSelector();
    }

    public function getPayPalCheckoutBannerColorScheme(): string
    {
        return $this->getModuleSettings()->getPayPalCheckoutBannerColorScheme();
    }

    public function getPayPalButtonStyleLayout(): string
    {
        return $this->getModuleSettings()->getPayPalButtonStyleLayout();
    }

    public function getPayPalButtonStyleColor(): string
    {
        return $this->getModuleSettings()->getPayPalButtonStyleColor();
    }

    public function getPayPalButtonStyleShape(): string
    {
        return $this->getModuleSettings()->getPayPalButtonStyleShape();
    }

    public function getPayPalButtonStyleLabel(): string
    {
        return $this->getModuleSettings()->getPayPalButtonStyleLabel();
    }

    public function getPayPalStandardCaptureStrategy(): string
    {
        return $this->getModuleSettings()->getPayPalStandardCaptureStrategy();
    }

    public function getPayPalSCAContingency(): string
    {
        return $this->getModuleSettings()->getPayPalSCAContingency();
    }

    public function alwaysIgnoreSCAResult(): bool
    {
        return $this->getModuleSettings()->alwaysIgnoreSCAResult();
    }

    public function cleanUpNotFinishedOrdersAutomaticlly(): bool
    {
        return $this->getModuleSettings()->cleanUpNotFinishedOrdersAutomaticlly();
    }

    public function getStartTimeCleanUpOrders(): int
    {
        return $this->getModuleSettings()->getStartTimeCleanUpOrders();
    }

    public function isCustomIdSchemaStructural(): bool
    {
        return $this->getModuleSettings()->isCustomIdSchemaStructural();
    }

    /**
     * The country the shop itself sits in as ISO 3166-1 alpha-2, taken from the shop setting
     * "aHomeCountry". Needed wherever PayPal asks about the shop and not about the customer - the
     * merchant onboarding for instance - because that country must not be guessed from the backend
     * language. Returns an empty string when the setting is unset or the country cannot be loaded,
     * so callers can omit the value instead of sending an invented one.
     */
    public function getShopCountryIso(): string
    {
        $homeCountry = Registry::getConfig()->getConfigParam('aHomeCountry');
        $countryId = is_array($homeCountry) ? (string)current($homeCountry) : (string)$homeCountry;
        if ($countryId === '') {
            return '';
        }

        $country = oxNew(Country::class);
        if (!$country->load($countryId)) {
            return '';
        }

        $isoAlpha2 = strtoupper((string)$country->getFieldData('oxisoalpha2'));

        return preg_match('/^[A-Z]{2}$/', $isoAlpha2) ? $isoAlpha2 : '';
    }

    public function getTransactionUrl(): string
    {
        return $this->isSandbox() ? Constants::PAYPAL_TRANSACTION_SANDBOX_URL : Constants::PAYPAL_TRANSACTION_LIVE_URL;
    }

    public function tableExists(string $tableName = ''): bool
    {
        $exists = false;
        if ($tableName) {
            $exists = DatabaseProvider::getDb()->getOne(
                "SELECT
                    IF( EXISTS
                        (SELECT * FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = :database
                            AND TABLE_NAME = :tablename
                            LIMIT 1),
                    1, 0)
                    AS if_exists",
                [
                    ':database' => Registry::getConfig()->getConfigParam("dbName"),
                    ':tablename' => $tableName
                ]
            );
        }
        return (bool)$exists;
    }

    /**
     * Return path to cache dir
     * Write to a extra folder in the tmp folder to not be deleted when module config changes
     * (tmp dir is cleared when module config changes in oxid 7)
     *
     * @return string
     */
    public function getCacheDir(): string
    {
        $dir = Registry::getConfig()->getConfigParam('sCompileDir')
            . DIRECTORY_SEPARATOR . Module::MODULE_ID . DIRECTORY_SEPARATOR;
        if ((file_exists($dir) === false) && !mkdir($dir) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
        return $dir;
    }

    /**
     * get the full File Name of the Token Cache
     *
     * @return string
     */
    public function getTokenCacheFileName(): string
    {
        return $this->getCacheDir() . 'paypaltoken_' . Registry::getConfig()->getActiveShop()->getId() . '.txt';
    }

    /**
     * get the full File Name of the Data-Client-Token Cache
     *
     * @return string
     */
    public function getDataClientTokenCacheFileName(): string
    {
        return $this->getCacheDir() . 'dataclienttoken_' . Registry::getConfig()->getActiveShop()->getId() . '.txt';
    }

    /**
     * Get a Admin URL with all necessary Admin-params
     *
     * @return string
     */
    public function getAdminUrlForJSCalls(): string
    {
        $config = Registry::getConfig();
        $url = $config->getConfigParam('sAdminSSLURL') ?:
            $config->getConfigParam('sShopURL') . $config->getConfigParam('sAdminDir') . "/";
        $url = Registry::getSession()->processUrl($url . 'index.php?');
        $url = str_replace("&amp;", "&", $url);
        return $url;
    }

    /**
     * Get webhook controller url
     *
     * @return string
     */
    public function getWebhookControllerUrl(): string
    {
        $webhookUrl = 'index.php?cl=oscpaypalwebhook';

        if ($this->isSandbox()) {
            $webhookUrl .= '&XDEBUG_SESSION_START=1';
        }

        $config = Registry::getConfig();
        $frontendUrl = $config->isSsl() ? $config->getSslShopUrl() : $config->getShopUrl(null, false);

        return html_entity_decode(
            $frontendUrl . $webhookUrl
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

    public function getIsVaultingActive(): bool
    {
        return $this->getModuleSettings()->getIsVaultingActive();
    }

    public function getUserIdForVaulting(): string
    {
        // In case of Standard PayPal we use vaulting via API not, via Buttons
        if (PayPalSession::isPayPalStandardOrderActive()) {
            return '';
        }

        $user = Registry::getConfig()->getUser();
        $payPalCustomerId = $user ? $user->getFieldData("oscpaypalcustomerid") : '';

        if (!$payPalCustomerId) {
            return "";
        }

        $vaultingService = Registry::get(ServiceFactory::class)->getVaultingService();
        $response = $vaultingService->generateUserIdToken($payPalCustomerId);

        return $response["id_token"] ?? "";
    }

    public function getIsGooglePayDeliveryAddressActive(): bool
    {
        return $this->getModuleSettings()->getIsGooglePayDeliveryAddressActive();
    }

    public function getPayPalDebugLevel(): string
    {
        return $this->getModuleSettings()->getPayPalDebugLevel();
    }
}
