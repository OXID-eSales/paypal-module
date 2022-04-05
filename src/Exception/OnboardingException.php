<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Exception;

use Exception;

class OnboardingException extends Exception
{
    public static function mandatoryDataNotFound(): self
    {
        return new self('Required data not found in request');
    }

    public static function merchantInformationsNotFound(): self
    {
        return new self('merchantInformations not found in request');
    }

    public static function nonsslUrl(): self
    {
        return new self('Webhook can only be registered on ssl endpoint');
    }

    public static function autoConfiguration(string $message): self
    {
        return new self('Autoconfiguration failed: ' . $message);
    }
}
