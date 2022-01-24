<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Exception;

class WebhookEventTypeException extends WebhookEventException
{
    public static function handlerNotFound(string $type): self
    {
        return new self(sprintf("Event handler for '%s' not found.", $type));
    }
}
