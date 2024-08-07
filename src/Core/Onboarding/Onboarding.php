<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Onboarding;

use JsonException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\Config as PayPalConfig;
use OxidSolutionCatalysts\PayPal\Core\PartnerConfig;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Exception\OnboardingException;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Onboarding as ApiOnboardingClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class Onboarding
{
    use ServiceContainer;

    public function autoConfigurationFromCallback(): array
    {
        $credentials = [];
        try {
            //fetch and save credentials
            $credentials = $this->fetchCredentials();
            $this->saveCredentials($credentials);

            // fetch and save Eligibility
            $merchantInformations = $this->fetchMerchantInformations();
            $this->saveEligibility($merchantInformations);

            $paypalConfig = oxNew(PayPalConfig::class);
            file_put_contents($paypalConfig->getOnboardingBlockCacheFileName(), "1");
        } catch (\Exception $exception) {
            throw OnboardingException::autoConfiguration($exception->getMessage());
        }

        return $credentials;
    }

    /**
     * @throws ApiException
     * @throws OnboardingException
     * @throws JsonException
     */
    public function fetchCredentials(): array
    {
        $onboardingResponse = $this->getOnboardingPayload();
        $this->saveSandboxMode($onboardingResponse['isSandBox']);

        $nonce = Registry::getSession()->getVariable('PAYPAL_MODULE_NONCE');
        Registry::getSession()->deleteVariable('PAYPAL_MODULE_NONCE');

        /** @var ApiOnboardingClient $apiClient */
        $apiClient = $this->getOnboardingClient($onboardingResponse['isSandBox']);
        $apiClient->authAfterWebLogin($onboardingResponse['authCode'], $onboardingResponse['sharedId'], $nonce);

        return $apiClient->getCredentials();
    }

    /**
     * @throws OnboardingException
     * @throws JsonException
     */
    public function getOnboardingPayload(): array
    {
        $response = json_decode(PayPalSession::getOnboardingPayload(), true, 512, JSON_THROW_ON_ERROR);

        if (
            !isset($response['authCode'], $response['sharedId'], $response['isSandBox'])
        ) {
            throw OnboardingException::mandatoryDataNotFound();
        }

        return $response;
    }

    public function saveSandboxMode(bool $isSandbox): void
    {
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $moduleSettings->saveSandboxMode($isSandbox);
    }

    /**
     * @throws OnboardingException
     */
    public function saveCredentials(array $credentials): array
    {
        if (
            !isset($credentials['client_id'], $credentials['client_secret'])
        ) {
            throw OnboardingException::mandatoryDataNotFound();
        }

        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $moduleSettings->saveClientId($credentials['client_id']);
        $moduleSettings->saveClientSecret($credentials['client_secret']);

        return [
            'client_id' => $moduleSettings->getClientId(),
            'client_secret' => $moduleSettings->getClientSecret()
        ];
    }

    /**
     * @return void
     */
    private function downloadAndSaveApplePayCertificate()
    {
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $isSandbox = $moduleSettings->isSandbox();
        $filesystem = oxNew(Filesystem::class);
        $this->ensureDirectoryExists($filesystem);
        $this->updateCertificateIfChanged($filesystem, $isSandbox);
    }

    /**
     * @param Filesystem $filesystem
     * @return void
     */
    private function ensureDirectoryExists(Filesystem $filesystem): void
    {
        $directory = getShopBasePath() . '.well-known/';
        try {
            $filesystem->mkdir($directory);
        } catch (IOException $e) {
            throw new IOException($e->getMessage());
        }
    }

    /**
     * @param Filesystem $filesystem
     * @param string $environment
     * @param array $config
     * @return void
     */
    private function updateCertificateIfChanged(Filesystem $filesystem, bool $isSandbox): void
    {
        $certificateUrl = $isSandbox ?
            Constants::PAYPAL_APPLEPAYCERT_SANDBOX_URL :
            Constants::PAYPAL_APPLEPAYCERT_LIVE_URL;
        $filename = basename(parse_url($certificateUrl, PHP_URL_PATH));
        $savePath = getShopBasePath() . '.well-known/' . $filename;

        $currentContent = $filesystem->exists($savePath) ? file_get_contents($savePath) : null;

        $newContent = file_get_contents($certificateUrl);

        if ($newContent !== false && $newContent !== $currentContent) {
            try {
                $filesystem->dumpFile($savePath, $newContent);
            } catch (IOException $e) {
                throw new IOException($e->getMessage());
            }
        }
    }
    public function getOnboardingClient(bool $isSandbox, bool $withCredentials = false): ApiOnboardingClient
    {
        $paypalConfig = oxNew(PayPalConfig::class);
        $partnerConfig = oxNew(PartnerConfig::class);
        $session = Registry::getSession();
        $sessionId = $session->getId();
        $actionHash = md5($sessionId);

        $clientId = '';
        $clientSecret = '';
        $merchantId = '';
        if ($withCredentials) {
            $clientId = $paypalConfig->getClientId();
            $clientSecret = $paypalConfig->getClientSecret();
            $merchantId = $paypalConfig->getMerchantId();
        }

        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');

        return new ApiOnboardingClient(
            $logger,
            $isSandbox ? $paypalConfig->getClientSandboxUrl() : $paypalConfig->getClientLiveUrl(),
            $clientId,
            $clientSecret,
            $partnerConfig->getTechnicalPartnerId($isSandbox),
            $merchantId,
            $paypalConfig->getTokenCacheFileName(),
            $actionHash
        );
    }

    /**
     * @return array
     * @throws ApiException
     * @throws JsonException
     * @throws OnboardingException
     */
    public function fetchMerchantInformations(): array
    {
        $onboardingResponse = $this->getOnboardingPayload();
        return $this->getOnboardingClient($onboardingResponse['isSandBox'], true)->getMerchantInformations();
    }

    public function saveEligibility(array $merchantInformations): array
    {
        if (!isset($merchantInformations['products'])) {
            throw OnboardingException::merchantInformationsNotFound();
        }

        $isPuiEligibility = false;
        $isAcdcEligibility = false;
        $isVaultingEligibility = false;
        $isVaultingCapability = false;
        $isGooglePayCapability = false;
        $isApplePayEligibility = false;

        foreach ($merchantInformations['capabilities'] as $capability) {
            if (
                $capability['name'] === 'PAYPAL_WALLET_VAULTING_ADVANCED' &&
                $capability['status'] === 'ACTIVE'
            ) {
                $isVaultingCapability = true;
            }
            if (
                $capability['name'] === 'APPLE_PAY' &&
                $capability['status'] === 'ACTIVE'
            ) {
                $isApplePayEligibility = true;
            }
            if (
                $capability['name'] === 'GOOGLE_PAY' &&
                $capability['status'] === 'ACTIVE'
            ) {
                $isGooglePayCapability = true;
            }
        }

        foreach ($merchantInformations['products'] as $product) {
            if (
                $product['name'] === 'PAYMENT_METHODS' &&
                in_array('PAY_UPON_INVOICE', $product['capabilities'], true)
            ) {
                $isPuiEligibility = true;
            } elseif (
                $product['name'] === 'PPCP_CUSTOM' &&
                in_array('CUSTOM_CARD_PROCESSING', $product['capabilities'], true)
            ) {
                $isAcdcEligibility = true;
            }

            if (
                $isVaultingCapability &&
                $product['name'] === 'PPCP_CUSTOM' &&
                in_array('PAYPAL_WALLET_VAULTING_ADVANCED', $product['capabilities'], true)
            ) {
                $isVaultingEligibility = true;
            }
        }
        if ($isApplePayEligibility) {
            $this->downloadAndSaveApplePayCertificate();
        }
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $moduleSettings->savePuiEligibility($isPuiEligibility);
        $moduleSettings->saveAcdcEligibility($isAcdcEligibility);
        $moduleSettings->saveVaultingEligibility($isVaultingEligibility);
        $moduleSettings->saveGooglePayEligibility($isGooglePayCapability);
        $moduleSettings->saveApplePayEligibility($isApplePayEligibility);

        return [
            'acdc' => $isAcdcEligibility,
            'pui' => $isPuiEligibility,
            'vaulting' => $isVaultingEligibility,
            'googlepay' => $isGooglePayCapability,
            'applepay' => $isVaultingEligibility,
        ];
    }
}
