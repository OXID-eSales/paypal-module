<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Service;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidSolutionCatalysts\PayPal\Service\PartnerRequestBuilder;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPal\Service\Partner as PartnerService;
use OxidSolutionCatalysts\PayPal\Core\PartnerConfig;
use OxidSolutionCatalysts\PayPalApi\Service\GenericService;

final class PartnerTest extends BaseTestCase
{
    public function testGetPartnerRequest(): void
    {
        /** @var PartnerService $service */
        $builder = $this->getServiceFromContainer(PartnerRequestBuilder::class);

        $result = $builder->getRequest('nonce', 'tracking_id');

        $this->assertEquals('tracking_id', $result->tracking_id);
    }

    public function testGetPartnerReferralLinks(): void
    {
        /** @var PartnerService $service */
        $service = $this->getServiceFromContainer(PartnerService::class);

        /** @var PartnerConfig $config */
        $partnerConfig = oxNew(PartnerConfig::class);

        $result = $service->getPartnerReferralLinks($partnerConfig->createNonce(), 'tracking_id', true);

        $this->assertCount(2, $result);
        $this->assertNotEmpty($result['self']);
        $this->assertNotEmpty($result['action_url']);
    }

    public function testSandboxAccountCanCreateReferralLinks(): void
    {
        /** @var PartnerService $service */
        $partnerService = $this->getServiceFromContainer(PartnerService::class);

        $apiService = oxNew(
            GenericService::class,
            $partnerService->getPartnerClient(true),
            '/v2/customer/partner-referrals/'
        );

        $result = $apiService->request('post', $this->getPartnerRequestData());

        $this->assertNotEmpty($result['links']);
    }

    private function getPartnerRequestData(): array
    {
        $json = file_get_contents(__DIR__ . '/../../Fixtures/partner_referral_request.json');

        return json_decode($json, true);
    }
}