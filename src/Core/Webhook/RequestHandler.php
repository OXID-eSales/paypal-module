<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Webhook;

use JsonException;
use OxidSolutionCatalysts\PayPal\Core\RequestReader;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher as WebhookDispatcher;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifier as VerificationService;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventRetryException;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventTypeException;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use Psr\Log\LoggerInterface;

final class RequestHandler
{
    use ServiceContainer;

    /** @var RequestReader */
    private $requestReader;

    /** @var VerificationService */
    private $verificationService;

    /** @var WebhookDispatcher */
    private $webhookDispatcher;

    public function __construct(
        RequestReader $requestReader,
        VerificationService $verificationService,
        WebhookDispatcher $webhookDispatcher
    ) {
        $this->requestReader = $requestReader;
        $this->verificationService = $verificationService;
        $this->webhookDispatcher = $webhookDispatcher;
    }

    /**
     * @throws ApiException
     * @throws JsonException
     */
    public function process(): bool
    {
        $result = false;
        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');

        try {
            $requestBody = $this->requestReader->getRawPost();
            $headers = $this->requestReader->getHeaders();

            $this->verificationService->verify($headers, $requestBody);
            $this->processEvent($requestBody);

            $result = true;
        } catch (WebhookEventException | WebhookEventTypeException $exception) {
            //we could not handle the call and don't want to receive it again, log and be done
            $logger->log('error', 'Webhook permanent failure (no retry): ' . $exception->getMessage(), [$exception]);
        } catch (WebhookEventRetryException $exception) {
            if ($exception->isRetryable()) {
                // Transient: e.g. the shop order is still inside the finalizeOrder() transaction
                // and not yet visible on this connection, or delivery is deliberately delayed.
                // Rethrow so WebhookController can ask PayPal to redeliver (HTTP 503 + Retry-After)
                // instead of acknowledging with 200 (PayPal only retries on a non-2xx status).
                $logger->log('info', 'Webhook retry requested: ' . $exception->getMessage());
                throw $exception;
            }
            // Benign, permanent condition rather than a failure: e.g. an abandoned PayPal Express
            // checkout where the buyer approved at PayPal but never returned to complete the order,
            // so no shop order exists (and the retry window has elapsed). Log once at info level
            // (without the stack trace) and do NOT rethrow — the WebhookController responds 200 so
            // PayPal stops redelivering.
            $logger->log(
                'info',
                'Webhook skipped (no matching shop order, e.g. abandoned express checkout): '
                . $exception->getMessage()
            );
        } catch (ApiException $exception) {
            //we could not handle the call but want to retry, so log and rethrow
            $logger->log('error', 'Webhook transient failure (retry expected): ' . $exception->getMessage(), [$exception]);
            throw $exception;
        }

        return $result;
    }

    /**
     * @throws WebhookEventTypeException
     * @throws WebhookEventException
     * @throws JsonException
     */
    private function processEvent(string $data): void
    {
        $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        if (
            isset($data['event_type'])
        ) {
            $this->webhookDispatcher->dispatch(new Event($data, $data['event_type']));
        } else {
            throw new WebhookEventException(json_last_error_msg());
        }
    }
}
