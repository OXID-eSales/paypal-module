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
use OxidProfessionalServices\PayPal\Api\Exception\ApiException;
use OxidProfessionalServices\PayPal\Api\Service\GenericService;
use OxidProfessionalServices\PayPal\Core\Config;
use OxidProfessionalServices\PayPal\Core\ServiceFactory;
use OxidProfessionalServices\PayPal\Core\Webhook\Exception\EventVerificationException;

/**
 * Class EventVerifier
 *
 * @see https://developer.paypal.com/docs/api-basics/notifications/webhooks/notification-messages/#event-headers
 * @see https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature
 */
class EventVerifier
{
    private const VERIFICATION_STATUS_SUCCESS = 'SUCCESS';

    private const VERIFICATION_EVENT_HEADERS = [
        'PAYPAL-AUTH-ALGO',
        'PAYPAL-CERT-URL',
        'PAYPAL-TRANSMISSION-ID',
        'PAYPAL-TRANSMISSION-SIG',
        'PAYPAL-TRANSMISSION-TIME'
    ];

    /**
     * @param array $headers Event request headers
     * @param string $body Event request body
     *
     * @throws ApiException|EventVerificationException
     */
    public function verify(array $headers, string $body)
    {
        $config = new Config();

        if (array_diff(self::VERIFICATION_EVENT_HEADERS, array_keys($headers))) {
            throw new EventVerificationException('Missing required verification headers');
        }

        $payload = [
            'auth_algo' => $headers['PAYPAL-AUTH-ALGO'],
            'cert_url' => $headers['PAYPAL-CERT-URL'],
            'transmission_id' => $headers['PAYPAL-TRANSMISSION-ID'],
            'transmission_sig' => $headers['PAYPAL-TRANSMISSION-SIG'],
            'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'],
            'webhook_id' => $config->getWebhookId(),
            'webhook_event' => $body
        ];

        /** @var GenericService $notificationService */
        $notificationService = Registry::get(ServiceFactory::class)->getNotificationService();
        $response = $notificationService->request('post', $payload);

        if ($response['verification_status'] !== self::VERIFICATION_STATUS_SUCCESS) {
            throw new EventVerificationException('Event verification failed');
        }
    }
}
