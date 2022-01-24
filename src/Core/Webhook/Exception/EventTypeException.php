<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Exception;

class EventTypeException extends EventException
{
    public static function handlerNotFound(string $type): self
    {
        return new self(sprintf("Event handler for '%s' not found.", $type));
    }
}
