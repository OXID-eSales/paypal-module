<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Traits;

use ReflectionMethod;
use Symfony\Contracts\EventDispatcher\Event;

trait NormalizedEventDispatcher
{
    use ServiceContainer;

    /**
     * Normalized dispatch method that accepts arguments in any order
     * and matches them by type to the parent dispatcher's signature
     *
     * @param mixed ...$args Variable arguments (Event object and optional event name string)
     * @return object The dispatched event
     * @throws \ReflectionException
     */
    public function dispatchNormalized(...$args): object
    {
        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = $this->getServiceFromContainer('event_dispatcher');

        // Match arguments by type
        $event = null;
        $eventName = null;

        foreach ($args as $arg) {
            if ($arg instanceof Event || is_object($arg)) {
                $event = $arg;
            } elseif (is_string($arg)) {
                $eventName = $arg;
            }
        }

        if ($event === null) {
            throw new \InvalidArgumentException('Event object is required');
        }

        // Symfony 4.3+ uses the event object class name as the event name if none is provided
        $eventName = $eventName ?? get_class($event);

        // Symfony 5+ uses dispatch($event, $eventName)
        // Symfony 3 & 4 use dispatch($eventName, $event)
        // Check the method signature to determine the correct order
        $reflection = new ReflectionMethod($dispatcher, 'dispatch');
        $parameters = $reflection->getParameters();

        // Version <5 signature
        if (count($parameters) > 0) {
            $firstParam = $parameters[0];
            if (empty($firstParam->getType())) {
                return $dispatcher->dispatch($eventName, $event);
            }
        }

        // Version 5+ signature
        return $dispatcher->dispatch($event, $eventName);
    }
}
