<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Service\GenericService;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Exception\EventVerificationException;

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
    public function verify(array $headers, string $body): bool
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

        if (!$response['verification_status'] || (
            $response['verification_status'] !== self::VERIFICATION_STATUS_SUCCESS)
        ) {
            throw new EventVerificationException('Event verification failed');
        }

        return true;
    }
}
