<?php

/**
 * This file is part of OXID eSales PayPal module.
 *
 * OXID eSales PayPal module is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eSales PayPal module is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eSales PayPal module.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2020
 */

namespace OxidProfessionalServices\PayPal\Core\Webhook;

use OxidEsales\Eshop\Core\Registry;
use OxidProfessionalServices\PayPal\Core\Config;
use OxidProfessionalServices\PayPal\Core\ServiceFactory;
use OxidProfessionalServices\PayPal\Core\Webhook\EventHandlerMapping;

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
