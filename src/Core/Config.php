<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidSolutionCatalysts\PayPal\Model\User;
use OxidSolutionCatalysts\PayPal\Module;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPalApi\Client;
use RuntimeException;
use Symfony\Component\Filesystem\Path;

/**
 * Class Config
 */
class Config
{
    use ServiceContainer;
    


    public function __construct(private ?ModuleSettings $moduleSettings = null)
    {
        $this->moduleSettings = $this->moduleSettings
            ?? $this->getServiceFromContainer(ModuleSettings::class);
    }
    
    /**
     * Checks if module configurations are valid
     *
     * @throws StandardException
     */
    public function checkHealth(): void
    {
        if (!$this->moduleSettings->checkHealth()) {
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
        return $this->moduleSettings->isSandbox();
    }

    /**
     * Get client id based on set active mode
     */
    public function getClientId(): string
    {
        return $this->moduleSettings->getClientId();
    }

    public function getLiveClientId(): string
    {
        return  $this->moduleSettings->getLiveClientId();
    }

    public function getSandboxClientId(): string
    {
        return  $this->moduleSettings->getSandboxClientId();
    }

    /**
     * Get client secret based on active mode
     */
    public function getClientSecret(): string
    {
        return  $this->moduleSettings->getClientSecret();
    }

    public function getLiveClientSecret(): string
    {
        return $this->moduleSettings->getLiveClientSecret();
    }

    public function getSandboxClientSecret(): string
    {
        return $this->moduleSettings->getSandboxClientSecret();
    }

    /**
     * Get merchantId based on active mode
     */
    public function getMerchantId(): string
    {
        return $this->moduleSettings->getMerchantId();
    }

    public function getLiveMerchantId(): string
    {
        return $this->moduleSettings->getLiveMerchantId();
    }

    public function getSandboxMerchantId(): string
    {
        return $this->moduleSettings->getSandboxMerchantId();
    }

    public function getWebhookId(): string
    {
        return $this->moduleSettings->getWebhookId();
    }

    public function getLiveWebhookId(): string
    {
        return $this->moduleSettings->getLiveWebhookId();
    }

    public function getSandboxWebhookId(): string
    {
        return $this->moduleSettings->getSandboxWebhookId();
    }

    /**
     * Get Eligibility
     */
    public function isAcdcEligibility(): bool
    {
        return $this->moduleSettings->isAcdcEligibility();
    }

    public function isLiveAcdcEligibility(): bool
    {
        return $this->moduleSettings->isLiveAcdcEligibility();
    }

    public function isSandboxAcdcEligibility(): bool
    {
        return $this->moduleSettings->isSandboxAcdcEligibility();
    }

    public function isPuiEligibility(): bool
    {
        return $this->moduleSettings->isPuiEligibility();
    }

    public function isLivePuiEligibility(): bool
    {
        return $this->moduleSettings->isLivePuiEligibility();
    }

    public function isSandboxPuiEligibility(): bool
    {
        return $this->moduleSettings->isSandboxPuiEligibility();
    }

    public function isVaultingEligibility(): bool
    {
        return $this->moduleSettings->isVaultingEligibility();
    }

    public function isLiveVaultingEligibility(): bool
    {
        return $this->moduleSettings->isLiveVaultingEligibility();
    }

    public function isSandboxVaultingEligibility(): bool
    {
        return $this->moduleSettings->isSandboxVaultingEligibility();
    }

    public function isLiveApplePayEligibility(): bool
    {
        return $this->moduleSettings->isLiveApplePayEligibility();
    }

    public function isSandboxApplePayEligibility(): bool
    {
        return $this->moduleSettings->isSandboxApplePayEligibility();
    }

    public function isLiveGooglePayEligibility(): bool
    {
        return $this->moduleSettings->isLiveGooglePayEligibility();
    }

    public function isSandboxGooglePayEligibility(): bool
    {
        return $this->moduleSettings->isSandboxGooglePayEligibility();
    }

    public function isLiveEpsEligibility(): bool
    {
        return $this->moduleSettings->isLiveEpsEligibility();
    }

    public function isSandboxEpsEligibility(): bool
    {
        return $this->moduleSettings->isSandboxEpsEligibility();
    }

    public function isLivePrzelewy24Eligibility(): bool
    {
        return $this->moduleSettings->isLivePrzelewy24Eligibility();
    }

    public function isSandboxPrzelewy24Eligibility(): bool
    {
        return $this->moduleSettings->isSandboxPrzelewy24Eligibility();
    }

    public function isLiveSepaEligibility(): bool
    {
        return $this->moduleSettings->isLiveSepaEligibility();
    }

    public function isSandboxSepaEligibility(): bool
    {
        return $this->moduleSettings->isSandboxSepaEligibility();
    }

    public function isLiveBlikEligibility(): bool
    {
        return $this->moduleSettings->isLiveBlikEligibility();
    }

    public function isSandboxBlikEligibility(): bool
    {
        return $this->moduleSettings->isSandboxBlikEligibility();
    }

    public function isLiveBanContactEligibility(): bool
    {
        return $this->moduleSettings->isLiveBanContactEligibility();
    }

    public function isSandboxBanContactEligibility(): bool
    {
        return $this->moduleSettings->isSandboxBanContactEligibility();
    }

    public function isLiveIDealEligibility(): bool
    {
        return $this->moduleSettings->isLiveIDealEligibility();
    }

    public function isSandboxIDealEligibility(): bool
    {
        return $this->moduleSettings->isSandboxIDealEligibility();
    }

    public function getSupportedLocales(): array
    {
        return $this->moduleSettings->getSupportedLocales();
    }

    public function getSupportedLocalesCommaSeparated(): string
    {
        return $this->moduleSettings->getSupportedLocalesCommaSeparated();
    }

    public function showPayPalBasketButton(): bool
    {
        return $this->moduleSettings->showPayPalBasketButton();
    }

    public function showPayPalMiniBasketButton(): bool
    {
        return $this->moduleSettings->showPayPalMiniBasketButton();
    }

    public function showPayPalPayLaterButton(): bool
    {
        return $this->moduleSettings->showPayPalPayLaterButton();
    }

    public function showPayPalProductDetailsButton(): bool
    {
        return $this->moduleSettings->showPayPalProductDetailsButton();
    }

    public function loginWithPayPalEMail(): bool
    {
        return $this->moduleSettings->loginWithPayPalEMail();
    }

    public function getAutoBillOutstanding(): bool
    {
        return $this->moduleSettings->getAutoBillOutstanding();
    }

    public function getSetupFeeFailureAction(): string
    {
        return $this->moduleSettings->getSetupFeeFailureAction();
    }

    public function getPaymentFailureThreshold(): string
    {
        return $this->moduleSettings->getPaymentFailureThreshold();
    }

    public function showAllPayPalBanners(): bool
    {
        return $this->moduleSettings->showAllPayPalBanners();
    }

    public function showBannersOnStartPage(): bool
    {
        return $this->moduleSettings->showBannersOnStartPage();
    }

    public function getStartPageBannerSelector(): string
    {
        return $this->moduleSettings->getStartPageBannerSelector();
    }
    public function getDefaultShippingPriceForExpress(): string
    {
        return $this->moduleSettings->getDefaultShippingPriceForExpress();
    }

    public function showBannersOnCategoryPage(): bool
    {
        return $this->moduleSettings->showBannersOnCategoryPage();
    }

    public function getCategoryPageBannerSelector(): string
    {
        return $this->moduleSettings->getCategoryPageBannerSelector();
    }

    public function showBannersOnSearchPage(): bool
    {
        return $this->moduleSettings->showBannersOnSearchPage();
    }

    public function getSearchPageBannerSelector(): string
    {
        return $this->moduleSettings->getSearchPageBannerSelector();
    }

    public function showBannersOnProductDetailsPage(): bool
    {
        return $this->moduleSettings->showBannersOnProductDetailsPage();
    }

    public function getProductDetailsPageBannerSelector(): string
    {
        return $this->moduleSettings->getProductDetailsPageBannerSelector();
    }

    public function showBannersOnCheckoutPage(): bool
    {
        return $this->moduleSettings->showBannersOnCheckoutPage();
    }

    public function getPayPalCheckoutBannerCartPageSelector(): string
    {
        return $this->moduleSettings->getPayPalCheckoutBannerCartPageSelector();
    }

    public function getPayPalCheckoutBannerPaymentPageSelector(): string
    {
        return $this->moduleSettings->getPayPalCheckoutBannerPaymentPageSelector();
    }

    public function getPayPalCheckoutBannerColorScheme(): string
    {
        return $this->moduleSettings->getPayPalCheckoutBannerColorScheme();
    }

    public function getPayPalButtonStyleLayout(): string
    {
        return $this->moduleSettings->getPayPalButtonStyleLayout();
    }

    public function getPayPalButtonStyleColor(): string
    {
        return $this->moduleSettings->getPayPalButtonStyleColor();
    }

    public function getPayPalButtonStyleShape(): string
    {
        return $this->moduleSettings->getPayPalButtonStyleShape();
    }

    public function getPayPalButtonStyleLabel(): string
    {
        return $this->moduleSettings->getPayPalButtonStyleLabel();
    }

    public function getPayPalStandardCaptureStrategy(): string
    {
        return $this->moduleSettings->getPayPalStandardCaptureStrategy();
    }

    public function getPayPalSCAContingency(): string
    {
        return $this->moduleSettings->getPayPalSCAContingency();
    }

    public function alwaysIgnoreSCAResult(): bool
    {
        return $this->moduleSettings->alwaysIgnoreSCAResult();
    }

    public function cleanUpNotFinishedOrdersAutomaticlly(): bool
    {
        return $this->moduleSettings->cleanUpNotFinishedOrdersAutomaticlly();
    }

    public function getStartTimeCleanUpOrders(): int
    {
        return $this->moduleSettings->getStartTimeCleanUpOrders();
    }

    public function isCustomIdSchemaStructural(): bool
    {
        return $this->moduleSettings->isCustomIdSchemaStructural();
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
        $dir = Path::join(getenv('OXID_BUILD_DIRECTORY'),  Module::MODULE_ID);
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

        return html_entity_decode(
            Registry::getConfig()->getCurrentShopUrl(false) . $webhookUrl
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
        return $this->moduleSettings->getIsVaultingActive();
    }

    public function getUserIdForVaulting(): string
    {
        // In case of Standard PayPal we use vaulting via API not, via Buttons
        if(PayPalSession::isPayPalStandardOrderActive()){
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

    public function getIsGooglePayDeliveryAdressActive(): bool
    {
        return $this->moduleSettings->getIsGooglePayDeliveryAddressActive();
    }
}
