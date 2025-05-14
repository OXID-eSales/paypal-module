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
use OxidSolutionCatalysts\PayPal\Service\Logger;
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
        try {
            $paypalConfig = oxNew(PayPalConfig::class);

            //fetch and save credentials
            $credentials = $this->fetchCredentials();
            $this->saveCredentials($credentials);

            // remove the old token from onboarding, because this was generated with community-credentials
            // now we use the new credentials and a new token is necessary
            unlink($paypalConfig->getTokenCacheFileName());

            // fetch and save Eligibility
            $merchantInformations = $this->fetchMerchantInformations();
            $this->saveEligibility($merchantInformations);
        } catch (\Exception $exception) {
            throw OnboardingException::autoConfiguration($exception->getMessage());
        }

        return $credentials;
    }

    /**
     * @throws OnboardingException
     * @throws JsonException
     */
    public function fetchCredentials(): array
    {
        $onboardingResponse = $this->getOnboardingPayload();
        $this->saveSandboxMode($onboardingResponse['isSandBox']);

        $nonce = Registry::getSession()->getVariable('PAYPAL_MODULE_NONCE');
        Registry::getSession()->deleteVariable('PAYPAL_MODULE_NONCE');

        $credentials = [];
        try {
            $apiClient = $this->getOnboardingClient($onboardingResponse['isSandBox']);
            $apiClient->authAfterWebLogin($onboardingResponse['authCode'], $onboardingResponse['sharedId'], $nonce);

            $credentials = $apiClient->getCredentials();
        } catch (ApiException $exception) {
            /**
             * @var Logger $logger
            */
            $logger = $this->getServiceFromContainer(Logger::class);
            $logger->log('error', $exception->getMessage(), [$exception]);
        }
        return $credentials;
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
    public function saveCredentials(array $credentials): void
    {
        if (
            !isset($credentials['client_id'], $credentials['client_secret'], $credentials['payer_id'])
        ) {
            throw OnboardingException::mandatoryDataNotFound();
        }

        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $moduleSettings->saveClientId($credentials['client_id']);
        $moduleSettings->saveClientSecret($credentials['client_secret']);
        $moduleSettings->saveMerchantId($credentials['payer_id']);
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

        $isApplePayCapability = false;
        $isPuiCapability = false;
        $isAcdcCapability = false;
        $isVaultingCapability = false;
        $isGooglePayCapability = false;
        $isEpsCapability = false;
        $isPrzelewy24Capability = false;
        $isSepaCapability = false;
        $isBlikCapability = false;
        $isBanContactCapability = false;
        $isIDealCapability = false;

        foreach ($merchantInformations['capabilities'] as $capability) {
            $isVaultingCapability = $this->checkCapability($capability, 'PAYPAL_WALLET_VAULTING_ADVANCED') ?
                true :
                $isVaultingCapability;
            $isApplePayCapability = $this->checkCapability($capability, 'APPLE_PAY') ?
                true :
                $isApplePayCapability;
            $isGooglePayCapability = $this->checkCapability($capability, 'GOOGLE_PAY') ?
                true :
                $isGooglePayCapability;
            $isPuiCapability = $this->checkCapability($capability, 'PAY_UPON_INVOICE') ?
                true :
                $isPuiCapability;
            $isAcdcCapability = $this->checkCapability($capability, 'CUSTOM_CARD_PROCESSING') ?
                true :
                $isAcdcCapability;
            $isEpsCapability = $this->checkCapability($capability, 'EPS') ?
                true :
                $isEpsCapability;
            $isPrzelewy24Capability = $this->checkCapability($capability, 'PRZELEWY24') ?
                true :
                $isPrzelewy24Capability;
            $isSepaCapability = $this->checkCapability($capability, 'SEPA') ?
                true :
                $isSepaCapability;
            $isBlikCapability = $this->checkCapability($capability, 'BLIK') ?
                true :
                $isBlikCapability;
            $isBanContactCapability = $this->checkCapability($capability, 'BANCONTACT') ?
                true :
                $isBanContactCapability;
            $isIDealCapability = $this->checkCapability($capability, 'IDEAL') ?
                true :
                $isIDealCapability;
        }

        if ($isApplePayCapability) {
            $this->downloadAndSaveApplePayCertificate();
        }

        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $moduleSettings->savePuiEligibility($isPuiCapability);
        $moduleSettings->saveAcdcEligibility($isAcdcCapability);
        $moduleSettings->saveVaultingEligibility($isVaultingCapability);
        $moduleSettings->saveGooglePayEligibility($isGooglePayCapability);

        $moduleSettings->saveApplePayEligibility($isApplePayCapability);
        $moduleSettings->saveEpsEligibility($isEpsCapability);
        $moduleSettings->savePrzelewy24Eligibility($isPrzelewy24Capability);
        $moduleSettings->saveSepaEligibility($isSepaCapability);
        $moduleSettings->saveBlikEligibility($isBlikCapability);
        $moduleSettings->saveBanContactEligibility($isBanContactCapability);
        $moduleSettings->saveIDealEligibility($isIDealCapability);

        return [
            'acdc'        => $isAcdcCapability,
            'pui'         => $isPuiCapability,
            'vaulting'    => $isVaultingCapability,
            'googlepay'   => $isGooglePayCapability,
            'applepay'    => $isApplePayCapability,
            'eps'         => $isEpsCapability,
            'przelewy24'  => $isPrzelewy24Capability,
            'sepa'        => $isSepaCapability,
            'blik'        => $isBlikCapability,
            'bancontact'  => $isBanContactCapability,
            'ideal'       => $isIDealCapability
        ];
    }

    private function checkCapability(array $capability, string $name): bool
    {
        return $capability['name'] === $name &&
            $capability['status'] === 'ACTIVE';
    }
}
