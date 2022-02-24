<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidSolutionCatalysts\PayPal\Core\PartnerConfig;
use OxidSolutionCatalysts\PayPalApi\Service\Partner as PayPalApiPartnerService;
use OxidSolutionCatalysts\PayPalApi\Client as PayPalApiClient;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\ReferralDataReferralData;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\ReferralDataCreateReferralDataResponse;
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

    public function getPartnerReferralLinks(string $nonce, string $trackingId, bool $isSandbox = false): array
    {
        try {
            /** @var PayPalApiPartnerService $apiService */
            $apiService = $this->getPartnerApiService($isSandbox);

            /** @var  ReferralDataReferralData $request */
            $request = $this->requestBuilder->getRequest($nonce, $trackingId);

            /** @var ReferralDataCreateReferralDataResponse $result */
            $result = $apiService->createPartnerReferral($request);
        } catch (ApiException $exception) {
            $this->logger->error($exception->getMessage(), [$exception]);
        }

        $links = is_array($result->links) ? $result->links : [];
        $return = [];
        foreach ($links as $sub) {
            $return[$sub['rel']] = $sub['href'];
        }

        return $return;
    }

    public function getPartnerApiService(bool $isSandbox): PayPalApiPartnerService
    {
        return oxNew(PayPalApiPartnerService::class, $this->getPartnerClient($isSandbox));
    }

    public function getPartnerClient( bool $isSandbox): PayPalApiClient
    {
        /** @var PartnerConfig $config */
        $partnerConfig = oxNew(PartnerConfig::class);

        $client = new PayPalApiClient(
            $this->logger,
            $isSandbox ? PayPalApiClient::SANDBOX_URL : PayPalApiClient::PRODUCTION_URL,
            $partnerConfig->getTechnicalClientId($isSandbox),
            $partnerConfig->getTechnicalClientSecret($isSandbox),
            '',
            false
        );

        return $client;
    }
}