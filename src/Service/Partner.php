<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Core\registry as EshopRegistry;
use OxidSolutionCatalysts\PayPal\Core\PartnerConfig;
use OxidSolutionCatalysts\PayPalApi\Service\Partner as PayPalApiPartnerService;
use OxidSolutionCatalysts\PayPalApi\Client as PayPalApiClient;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\ReferralDataReferralData;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\ReferralDataIntegrationDetails;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\ReferralDataRestApiIntegrationFirstPartyDetails;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\ReferralDataCreateReferralDataResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\ReferralDataRestApiIntegration;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\ReferralDataOperation;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Psr\Log\LoggerInterface;

class Partner
{
    /** @var LoggerInterface  */
    private $logger;

    /** @var PartnerRequestBuilder */
    private $requestBuilder;

    public function __construct(
        LoggerInterface $logger,
        PartnerRequestBuilder $requestBuilder
    )
    {
        $this->logger = $logger;
        $this->requestBuilder = $requestBuilder;
    }

    public function getPartnerReferralLinks(string $nonce, string $trackingId): array
    {
        /** @var PayPalApiPartnerService $apiService */
        $apiService = $this->getPartnerApiService();

        /** @var  ReferralDataReferralData $request */
        $request = $this->requestBuilder->getRequest($nonce, $trackingId);

        /** @var ReferralDataCreateReferralDataResponse $result */
        $result = $apiService->createPartnerReferral($request);

        return is_array($result->links) ? $result->links : [];
    }

    public function getPartnerApiService(): PayPalApiPartnerService
    {
        return oxNew(PayPalApiPartnerService::class, $this->getPartnerClient());
    }

    public function getPartnerClient(): PayPalApiClient
    {
        /** @var PartnerConfig $config */
        $partnerConfig = oxNew(PartnerConfig::class);

        $client = new PayPalApiClient(
            $this->logger,
            $partnerConfig->isSandbox() ? PayPalApiClient::SANDBOX_URL : PayPalApiClient::PRODUCTION_URL,
            $partnerConfig->getTechnicalClientId(),
            $partnerConfig->getTechnicalClientSecret(),
            '',
            false
        );

        return $client;
    }
}