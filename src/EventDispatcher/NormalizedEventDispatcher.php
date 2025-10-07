<?php

namespace OxidSolutionCatalysts\PayPal\EventDispatcher;

use InvalidArgumentException;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

class NormalizedEventDispatcher extends EventDispatcher
{
    /**
     * Normalized dispatch method that accepts arguments in any order
     * and matches them by type to the parent dispatcher's signature
     *
     * @param mixed ...$args Variable arguments (Event object and optional event name string)
     * @return object The dispatched event
     */
    public function dispatchNormalized(...$args): object
    {
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
            throw new InvalidArgumentException('Event object is required');
        }

        // Symfony 4.3+ uses the event object class name as the event name if none is provided
        $eventName = $eventName ?? get_class($event);

        // Symfony 5+ uses dispatch($event, $eventName)
        // Symfony 3 & 4 use dispatch($eventName, $event)
        // Check the method signature to determine the correct order
        $reflection = new ReflectionMethod(parent::class, 'dispatch');
        $parameters = $reflection->getParameters();

        if (count($parameters) > 0) {
            $firstParam = $parameters[0];
            // If first parameter expects a string, we're in Symfony 3/4
            if ($firstParam->getType() && $firstParam->getType()->getName() === 'string') {
                return $this->dispatch($eventName, $event);
            }
        }

        // Symfony 5+ signature
        return $this->dispatch($event, $eventName);
    }
}