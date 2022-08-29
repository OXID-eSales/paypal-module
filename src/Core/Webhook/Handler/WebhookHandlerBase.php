<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Service\WebhookService;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Traits\WebhookHandlerTrait;

class WebhookHandlerBase implements HandlerInterface
{
    use WebhookHandlerTrait;

    /**
     * @param Event $event
     *
     * @throws WebhookEventException
     */
    public function handle(Event $event): void
    {
        $this->afterHandleEvent();
    }

    public function afterHandleEvent(): void
    {
        //Webhook is used to trigger unfinished order cleanup at the end of each webhook handle.
        //TODO: check if webhook handler really is the place place for this
        $this->cleanUpNotFinishedOrders();
    }
}
