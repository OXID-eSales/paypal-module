<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Core;

use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use PHPUnit\Framework\TestCase;

/**
 * Testing the merchant country restriction of \OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions.
 */
class PayPalDefinitionsMerchantCountryTest extends TestCase
{
    public function testAcdcIsNotOfferedToSwissMerchants(): void
    {
        $this->assertFalse(
            PayPalDefinitions::isPaymentSupportedInMerchantCountry(
                PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                'CH'
            )
        );
    }

    public function testCountryIsMatchedCaseInsensitively(): void
    {
        $this->assertFalse(
            PayPalDefinitions::isPaymentSupportedInMerchantCountry(
                PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                'ch'
            )
        );
    }

    public function testAcdcStaysAvailableEverywhereElse(): void
    {
        foreach (['DE', 'AT', 'US', 'GB'] as $countryIso) {
            $this->assertTrue(
                PayPalDefinitions::isPaymentSupportedInMerchantCountry(
                    PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                    $countryIso
                ),
                sprintf('ACDC must stay available for a merchant in %s', $countryIso)
            );
        }
    }

    public function testUnresolvedShopCountryRestrictsNothing(): void
    {
        // A shop whose own country cannot be resolved must not lose a working payment method to a
        // guess - the restriction only applies to a country we actually know.
        $this->assertTrue(
            PayPalDefinitions::isPaymentSupportedInMerchantCountry(
                PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                ''
            )
        );
    }

    public function testPaymentsWithoutAMerchantCountryRestrictionAreNeverRestricted(): void
    {
        $this->assertTrue(
            PayPalDefinitions::isPaymentSupportedInMerchantCountry(
                PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID,
                'CH'
            )
        );
    }

    public function testUnknownPaymentIsNotRestricted(): void
    {
        $this->assertTrue(
            PayPalDefinitions::isPaymentSupportedInMerchantCountry('nosuchpayment', 'CH')
        );
    }
}
