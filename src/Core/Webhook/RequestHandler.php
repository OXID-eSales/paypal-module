<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Webhook;

use OxidSolutionCatalysts\PayPal\Core\RequestReader;
use OxidSolutionCatalysts\PayPal\Core\Utils\PayPalLogger;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifier as VerificationService;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher as WebhookDispatcher;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;

final class RequestHandler
{
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

    public function process(): bool
    {
        $result = false;
        $logger = new PayPalLogger();

        try {
            $requestBody = $this->requestReader->getRawPost();
            $headers = $this->requestReader->getHeaders();

            $this->verificationService->verify($headers, $requestBody);

            $this->processEvent($requestBody);

            $result = true;
        } catch (WebhookEventException $exception) {
            //we could not handle the call and don't want to receive it again, log and be done
            $logger->error($exception->getMessage(), [$exception]);
        } catch (ApiException $exception) {
            //we could not handle the call but want to retry, so log and rethrow
            $logger->error($exception->getMessage(), [$exception]);
            throw $exception;
        }

        return $result;
    }

    private function processEvent(string $data)
    {
        $data = json_decode($data, true);
        if (
            is_array($data) &&
            isset($data['event_type'])
        ) {
            $this->webhookDispatcher->dispatch(new Event($data, $data['event_type']));
        } else {
            throw new WebhookEventException(json_last_error_msg());
        }
    }
}
