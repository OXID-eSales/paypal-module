<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Exception;

class WebhookEventVerificationException extends WebhookEventException
{
    protected const MISSING_HEADERS_MESSAGE = 'Missing required verification headers';

    protected const VERIFICATION_FAILED_MESSAGE = 'Event verification failed';

    public static function missingHeaders(): self
    {
        return new self(self::MISSING_HEADERS_MESSAGE);
    }

    public static function verificationFailed(): self
    {
        return new self(self::VERIFICATION_FAILED_MESSAGE);
    }
}
