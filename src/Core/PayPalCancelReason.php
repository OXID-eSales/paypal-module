<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

/**
 * Classification for why a PayPal order was canceled / kept as storno.
 *
 * The goal is to let the merchant tell apart stornos that are expected
 * (customer aborted the popup, PayPal refused the payment) from stornos
 * that actually point to a process/module problem and warrant a support
 * ticket. Every terminal cancel log line carries a reason, a category and
 * a recommended merchant action, so the log can be filtered on category.
 */
class PayPalCancelReason
{
    /** Customer closed/aborted the PayPal popup before approving. */
    public const BUYER_CANCELLED = 'BUYER_CANCELLED';

    /** PayPal refused the capture (e.g. TRANSACTION_REFUSED, INSTRUMENT_DECLINED). */
    public const PAYMENT_DECLINED = 'PAYMENT_DECLINED';

    /** ACDC card payment where the 3-D-Secure challenge was not completed. */
    public const SCA_3DS_NOT_COMPLETED = 'SCA_3DS_NOT_COMPLETED';

    /** No classifiable cause — this is the only category that should be reported. */
    public const UNKNOWN = 'UNKNOWN';

    /** Caused by the shopper — no merchant action needed. */
    public const CATEGORY_CUSTOMER = 'customer';

    /** Caused by PayPal/the payment provider — no merchant action needed. */
    public const CATEGORY_PSP = 'psp';

    /** Unexplained — the merchant should report these to OXID support. */
    public const CATEGORY_NEEDS_ATTENTION = 'needs-attention';

    private const CATEGORY_MAP = [
        self::BUYER_CANCELLED => self::CATEGORY_CUSTOMER,
        self::PAYMENT_DECLINED => self::CATEGORY_PSP,
        self::SCA_3DS_NOT_COMPLETED => self::CATEGORY_CUSTOMER,
        self::UNKNOWN => self::CATEGORY_NEEDS_ATTENTION,
    ];

    /**
     * Returns the category for a reason, defaulting to needs-attention so an
     * unmapped/unexpected reason is surfaced rather than silently swallowed.
     */
    public static function getCategory(string $reason): string
    {
        return self::CATEGORY_MAP[$reason] ?? self::CATEGORY_NEEDS_ATTENTION;
    }

    /**
     * Recommended merchant action: only the needs-attention category asks the
     * merchant to report; everything else is an expected, self-explaining case.
     */
    public static function getAction(string $reason): string
    {
        return self::getCategory($reason) === self::CATEGORY_NEEDS_ATTENTION
            ? 'report-to-support'
            : 'none';
    }

    /**
     * Builds the greppable suffix appended to cancel/storno log lines, e.g.
     * "reason=PAYMENT_DECLINED category=psp action=none ppIssue=TRANSACTION_REFUSED".
     *
     * @param string      $reason  one of the reason constants
     * @param string|null $ppIssue optional PayPal error issue for PAYMENT_DECLINED
     */
    public static function formatLogSuffix(string $reason, ?string $ppIssue = null): string
    {
        $suffix = sprintf(
            'reason=%s category=%s action=%s',
            $reason,
            self::getCategory($reason),
            self::getAction($reason)
        );

        if ($ppIssue !== null && $ppIssue !== '') {
            $suffix .= ' ppIssue=' . $ppIssue;
        }

        return $suffix;
    }
}
