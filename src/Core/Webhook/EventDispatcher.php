<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook;

use OxidSolutionCatalysts\PayPal\Exception\WebhookEventTypeException;

/**
 * Delivers events to appropriate handlers
 */
class EventDispatcher
{
    /**
     * @param Event $event
     * @throws \OxidSolutionCatalysts\PayPal\Exception\WebhookEventTypeException
     */
    public function dispatch(Event $event): void
    {
        $handlers = EventHandlerMapping::MAPPING;
        $eventType = $event->getEventType();

        if (isset($handlers[$eventType])) {
            $handler = $this->oxNew($handlers[$eventType]);
            $handler->handle($event);
        } else {
            throw WebhookEventTypeException::handlerNotFound($eventType);
        }
    }

    /**
     * We need this method for proper mocking in tests
     * @param string $class
     * @return string
     */
    protected function oxNew(string $class): string
    {
        return oxNew($class);
    }
}
