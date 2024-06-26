<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Onboarding;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Config as PayPalConfig;
use OxidSolutionCatalysts\PayPal\Core\PartnerConfig;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Exception\OnboardingException;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Onboarding as ApiOnboardingClient;
use Psr\Log\LoggerInterface;

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

    public function fetchCredentials(): array
    {
        $credentials = [];

        $onboardingResponse = $this->getOnboardingPayload();
        $this->saveSandboxMode($onboardingResponse['isSandBox']);

        $nonce = Registry::getSession()->getVariable('PAYPAL_MODULE_NONCE');
        Registry::getSession()->deleteVariable('PAYPAL_MODULE_NONCE');

        /** @var ApiOnboardingClient $apiClient */
        $apiClient = $this->getOnboardingClient($onboardingResponse['isSandBox']);
        $apiClient->authAfterWebLogin($onboardingResponse['authCode'], $onboardingResponse['sharedId'], $nonce);

        $credentials = $apiClient->getCredentials();

        return $credentials;
    }

    public function getOnboardingPayload(): array
    {
        $response = json_decode(PayPalSession::getOnboardingPayload(), true);

        if (
            !is_array($response) ||
            !isset($response['authCode']) ||
            !isset($response['sharedId']) ||
            !isset($response['isSandBox'])
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

    public function saveCredentials(array $credentials): array
    {
        if (
            !isset($credentials['client_id']) ||
            !isset($credentials['client_secret'])
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

    public function getOnboardingClient(bool $isSandbox, bool $withCredentials = false): ApiOnboardingClient
    {
        $paypalConfig = oxNew(PayPalConfig::class);
        $partnerConfig = oxNew(PartnerConfig::class);

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
            $paypalConfig->getTokenCacheFileName()
        );
    }

    public function fetchMerchantInformations()
    {
        $onboardingResponse = $this->getOnboardingPayload();
        /** @var ApiOnboardingClient $apiClient */
        $apiClient = $this->getOnboardingClient($onboardingResponse['isSandBox'], true);
        return $apiClient->getMerchantInformations();
    }


    public function haveCapability(string $name, array $caps): bool
    {
        return in_array($name, $caps);
    }

    public function saveEligibility(array $merchantInformations): array
    {
        if (!isset($merchantInformations['products'])) {
            throw OnboardingException::merchantInformationsNotFound();
        }

        $isPuiEligibility = false;
        $isAcdcEligibility = false;
        $isGooglePayCapability = false;

        foreach ($merchantInformations['products'] as $product) {
            if($product['name'] === 'PAYMENT_METHODS'){
                $isPuiEligibility = $this->haveCapability('GOOGLE_PAY', $product['capabilities']);
                $isGooglePayCapability = $this->haveCapability('GOOGLE_PAY', $product['capabilities']);
            }

            if ($product['name'] === 'PPCP_CUSTOM') {
                $isAcdcEligibility = $this->haveCapability('CUSTOM_CARD_PROCESSING', $product['capabilities']);
            }
        }

        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $moduleSettings->savePuiEligibility($isPuiEligibility);
        $moduleSettings->saveAcdcEligibility($isAcdcEligibility);
        $moduleSettings->saveGooglePayEligibility($isGooglePayCapability);

        return [
            'acdc' => $isAcdcEligibility,
            'pui' => $isPuiEligibility,
            'googlepay' => $isGooglePayCapability,
        ];
    }
}
