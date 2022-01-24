<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook;

final class Event
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $eventType;

    /**
     * Event constructor.
     *
     * @param array $data
     */
    public function __construct(array $data, string $eventType)
    {
        $this->data = $data;
        $this->eventType = $eventType;
    }

    /**
     * Get event data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getEventType(): string
    {
        return $this->eventType;
    }
}
