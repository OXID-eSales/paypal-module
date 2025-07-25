<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Service\GenericService;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPal\Core\RequestReader;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventVerificationException;

/**
 * Class EventVerifier
 *
 * @see https://developer.paypal.com/docs/api-basics/notifications/webhooks/notification-messages/#event-headers
 * @see https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature
 */
class EventVerifier implements EventVerifierInterface
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
     * Verify a webhook event
     *
     * @param RequestReader $requestReader The request reader containing webhook data
     * @return bool True if the webhook is verified, false otherwise
     * @throws \Exception If verification fails due to an error
     */
    public function verify(RequestReader $requestReader): bool
    {
        return $this->verifyLegacy($requestReader->getHeaders(), $requestReader->getRawPost());
    }

    /**
     * Legacy verify method for backward compatibility
     *
     * @param array $headers Event request headers
     * @param string $body Event request body
     *
     * @throws ApiException|WebhookEventVerificationException
     */
    public function verifyLegacy(array $headers, string $body): bool
    {
        $config = new Config();

        $normalizedHeaders = array_change_key_case($headers, CASE_UPPER);
        if (array_diff(self::VERIFICATION_EVENT_HEADERS, array_keys($normalizedHeaders))) {
            throw new WebhookEventVerificationException('Missing required verification headers');
        }

        // body must be encoded so that it is not double-encoded later
        $normalizedBody = json_decode($body);

        $payload = [
            'auth_algo' => $normalizedHeaders['PAYPAL-AUTH-ALGO'],
            'cert_url' => $normalizedHeaders['PAYPAL-CERT-URL'],
            'transmission_id' => $normalizedHeaders['PAYPAL-TRANSMISSION-ID'],
            'transmission_sig' => $normalizedHeaders['PAYPAL-TRANSMISSION-SIG'],
            'transmission_time' => $normalizedHeaders['PAYPAL-TRANSMISSION-TIME'],
            'webhook_id' => $config->getWebhookId(),
            'webhook_event' => $normalizedBody
        ];

        $headers = [];
        $headers['PayPal-Partner-Attribution-Id'] = Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP;

        /** @var GenericService $notificationService */
        $notificationService = Registry::get(ServiceFactory::class)->getNotificationService();
        $response = $notificationService->request('POST', $payload, [], $headers);

        if (
            !isset($response['verification_status']) || (
            $response['verification_status'] !== self::VERIFICATION_STATUS_SUCCESS)
        ) {
            throw new WebhookEventVerificationException('Event verification failed');
        }

        return true;
    }
}
