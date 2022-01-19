<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Exception\EventException;

/**
 * Interface HandlerInterface
 *
 * @package OxidSolutionCatalysts\PayPal\Core\Webhook
 */
interface HandlerInterface
{
    /**
     * @param Event $event
     *
     * @throws EventException
     */
    public function handle(Event $event): void;
}
