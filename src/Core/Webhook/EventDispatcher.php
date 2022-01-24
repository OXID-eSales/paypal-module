<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook;

use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;

/**
 * Delivers events to appropriate handlers
 */
class EventDispatcher
{
    /**
     * @param Event $event
     */
    public function dispatch(Event $event)
    {
        $handlers = EventHandlerMapping::MAPPING;
        $eventType = $event->getEventType();

        if ($handlerClass = $handlers[$eventType]) {
            $handler = oxNew($handlerClass);
            $handler->handle($event);
        } else {
            $exception = new StandardException(sprintf('Event handler for %s not found.', [$eventType]));
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
        }
    }
}
