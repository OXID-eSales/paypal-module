<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\modules\osc\paypal\tests\Unit\Core;

use OxidSolutionCatalysts\PayPal\Core\PayPalCancelReason;
use PHPUnit\Framework\TestCase;

class PayPalCancelReasonTest extends TestCase
{
    public function testBuyerCancelIsCustomerCausedAndNeedsNoAction(): void
    {
        $this->assertSame(
            PayPalCancelReason::CATEGORY_CUSTOMER,
            PayPalCancelReason::getCategory(PayPalCancelReason::BUYER_CANCELLED)
        );
        $this->assertSame('none', PayPalCancelReason::getAction(PayPalCancelReason::BUYER_CANCELLED));
    }

    public function testPaymentDeclineIsPspCausedAndNeedsNoAction(): void
    {
        $this->assertSame(
            PayPalCancelReason::CATEGORY_PSP,
            PayPalCancelReason::getCategory(PayPalCancelReason::PAYMENT_DECLINED)
        );
        $this->assertSame('none', PayPalCancelReason::getAction(PayPalCancelReason::PAYMENT_DECLINED));
    }

    public function testUnknownIsTheOnlyReasonThatAsksTheMerchantToReport(): void
    {
        $this->assertSame(
            PayPalCancelReason::CATEGORY_NEEDS_ATTENTION,
            PayPalCancelReason::getCategory(PayPalCancelReason::UNKNOWN)
        );
        $this->assertSame('report-to-support', PayPalCancelReason::getAction(PayPalCancelReason::UNKNOWN));
    }

    public function testUnmappedReasonDefaultsToNeedsAttention(): void
    {
        // A reason that is not in the map must surface, not be silently swallowed.
        $this->assertSame(
            PayPalCancelReason::CATEGORY_NEEDS_ATTENTION,
            PayPalCancelReason::getCategory('SOMETHING_NEW')
        );
        $this->assertSame('report-to-support', PayPalCancelReason::getAction('SOMETHING_NEW'));
    }

    public function testLogSuffixContainsReasonCategoryAndAction(): void
    {
        $suffix = PayPalCancelReason::formatLogSuffix(PayPalCancelReason::BUYER_CANCELLED);

        $this->assertSame('reason=BUYER_CANCELLED category=customer action=none', $suffix);
    }

    public function testLogSuffixAppendsPayPalIssueWhenGiven(): void
    {
        $suffix = PayPalCancelReason::formatLogSuffix(
            PayPalCancelReason::PAYMENT_DECLINED,
            'TRANSACTION_REFUSED'
        );

        $this->assertSame(
            'reason=PAYMENT_DECLINED category=psp action=none ppIssue=TRANSACTION_REFUSED',
            $suffix
        );
    }

    public function testLogSuffixOmitsEmptyPayPalIssue(): void
    {
        $suffix = PayPalCancelReason::formatLogSuffix(PayPalCancelReason::PAYMENT_DECLINED, '');

        $this->assertSame('reason=PAYMENT_DECLINED category=psp action=none', $suffix);
    }
}
