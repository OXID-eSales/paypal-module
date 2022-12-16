<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Exception;

class WebhookEventVerificationException extends WebhookEventException
{
    const MISSING_HEADERS_MESSAGE = 'Missing required verification headers';

    const VERIFICATION_FAILED_MESSAGE = 'Event verification failed';

    public static function missingHeaders(): self
    {
        return new self(self::MISSING_HEADERS_MESSAGE);
    }

    public static function verificationFailed(): self
    {
        return new self(self::VERIFICATION_FAILED_MESSAGE);
    }
}
