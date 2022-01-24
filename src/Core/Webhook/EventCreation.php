<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventHandlerMapping;

/**
 * Class EventCreation
 *
 * @see https://developer.paypal.com/docs/api/webhooks/v1/#webhooks_post
 */
class EventCreation
{
    /**
     *
     * @throws ApiException
     */
    public function create()
    {
        $config = new Config();

        $eventHandler = [];
        foreach (EventHandlerMapping::MAPPING as $key => $value) {
            $eventHandler[] = [
                'name' => $key
            ];
        }

        $paypload = [
            'url' => $config->getWebhookControllerUrl(),
            'event_types' => $eventHandler
        ];

        /** @var GenericService $notificationService */
        $webhookService = Registry::get(ServiceFactory::class)->geWebhookService();

        return $webhookService->request('post', $paypload);
    }
}
