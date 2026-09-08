<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Core\Onboarding;

use OxidSolutionCatalysts\PayPal\Core\Onboarding\Onboarding;
use PHPUnit\Framework\TestCase;

/**
 * Testing how the country of the PayPal account is read out of the merchant integration data,
 * \OxidSolutionCatalysts\PayPal\Core\Onboarding\Onboarding::extractMerchantCountry().
 */
class MerchantCountryTest extends TestCase
{
    public function testCountryIsTakenFromTheMerchantIntegrationResponse(): void
    {
        // Shortened response of GET /v1/customer/partners/{partnerId}/merchant-integrations/{sellerId}
        $merchantInformations = [
            'merchant_id' => 'L47KBKPNMRMNY',
            'products' => [['name' => 'PPCP_CUSTOM', 'vetting_status' => 'SUBSCRIBED']],
            'capabilities' => [['name' => 'CUSTOM_CARD_PROCESSING', 'status' => 'ACTIVE']],
            'payments_receivable' => true,
            'primary_email_confirmed' => true,
            'country' => 'CH',
        ];

        $this->assertSame('CH', Onboarding::extractMerchantCountry($merchantInformations));
    }

    public function testCountryIsNormalised(): void
    {
        $this->assertSame('DE', Onboarding::extractMerchantCountry(['country' => ' de ']));
    }

    public function testMissingCountryIsReportedAsUnknown(): void
    {
        // An empty result means "keep what is stored and decide by the shop country" - it must
        // never be stored as the country of the account.
        $this->assertSame('', Onboarding::extractMerchantCountry([]));
        $this->assertSame('', Onboarding::extractMerchantCountry(['country' => '']));
    }

    public function testUnusableCountryIsReportedAsUnknown(): void
    {
        // Anything that is not a plain ISO 3166-1 alpha-2 code is not understood, and a country
        // decides whether a payment method is offered at all - so it stays unknown.
        $this->assertSame('', Onboarding::extractMerchantCountry(['country' => 'CHE']));
        $this->assertSame('', Onboarding::extractMerchantCountry(['country' => 'C']));
        $this->assertSame('', Onboarding::extractMerchantCountry(['country' => 'C1']));
        $this->assertSame('', Onboarding::extractMerchantCountry(['country' => ['code' => 'CH']]));
        $this->assertSame('', Onboarding::extractMerchantCountry(['country' => null]));
    }
}
