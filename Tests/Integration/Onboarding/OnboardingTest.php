<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Onboarding;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPalApi\Onboarding as ApiOnboardingClient;
use OxidSolutionCatalysts\PayPal\Controller\Admin\PayPalConfigController;
use OxidSolutionCatalysts\PayPal\Core\Onboarding\Onboarding;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\PartnerConfig;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;

final class OnboardingTest extends BaseTestCase
{
    public function testAutoConfigurationFromCallback(): void
    {
        $response = '{"authCode":"C21AAJlAxXbg1yhcvDvsQBkCkGBWcbdrFGDFA2vm4rjeXEpE-HsiV7ONaEPCyi-A3ebfRyK-hqbqld7ZBPAqwm8-MqiL1pCyw",' .
            '"sharedId":"AXOTtYBILwghOuYdORqSJACgbInEh2Kb4z9MDcU07vJtCxMWhESD0Ck1z59lRK9D-5vmI6AuHKd_ztM5","isSandBox":true}';

        $expected = json_decode($response, true);

        PayPalSession::storeOnboardingPayload($response);

        $apiClient = $this->getMockBuilder(ApiOnboardingClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $apiClient->expects($this->once())
            ->method('authAfterWebLogin')
            ->with($expected['authCode'], $expected['sharedId']);

        $credentials = [
            'client_id' => 'client_id',
            'client_secret' => 'client_secret'
        ];
        $apiClient->expects($this->once())
            ->method('getCredentials')
            ->willReturn($credentials);

        $service = $this->getMockBuilder(Onboarding::class)
            ->setMethods(['saveCredentials', 'getOnboardingClient'])
            ->getMock();
        $service->expects($this->once())
            ->method('getOnboardingClient')
            ->willReturn($apiClient);
        $service->expects($this->once())
           ->method('saveCredentials')
           ->with(['client_id' => 'client_id', 'client_secret' => 'client_secret']);

        $this->assertEquals(
            $credentials,
            $service->autoConfigurationFromCallback()
        );
    }
}
