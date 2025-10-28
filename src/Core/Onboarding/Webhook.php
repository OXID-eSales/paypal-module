<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Onboarding;

use Exception;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Config as PayPalConfig;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventHandlerMapping;
use OxidSolutionCatalysts\PayPal\Exception\OnboardingException;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Service\GenericService;
use Psr\Log\LoggerInterface;

class Webhook
{
    use ServiceContainer;

    /**
     * Register webhooks with integrated error handling
     * This method can be called from any context (controller, static service, etc.)
     *
     * @return bool Returns true if successful, false otherwise
     */
    public function registerWebhooksWithErrorHandling(): bool
    {
        try {
            $this->ensureWebhook();
            return true;
        } catch (OnboardingException $exception) {
            // Show error to user if possible
            if (class_exists('\OxidEsales\Eshop\Core\Registry')) {
                Registry::getUtilsView()->addErrorToDisplay($exception->getMessage());
            }
            $this->logError($exception);
            return false;
        } catch (Exception $exception) {
            $this->logError($exception);
            return false;
        }
    }

    /**
     * Helper method to log errors consistently
     *
     * @param Exception $exception
     */
    protected function logError(Exception $exception): void
    {
        try {
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log('error', $exception->getMessage(), [$exception]);
        } catch (Exception $e) {
            // Fallback if logger is not available
            error_log('PayPal Webhook Error: ' . $exception->getMessage());
        }
    }

    public function ensureWebhook(): string
    {
        $endpoint = $this->getWebhookEndpoint();

        if (false === strpos($endpoint, "https:")) {
            throw OnboardingException::nonsslUrl();
        }

        $hook = $this->getHookForUrl($endpoint);
        $webhookId = $hook['id'] ?? '';
        $registeredEvents = $this->getEnabledEvents($hook);
        if (
            array_diff(
                array_column($this->getAvailableEventNames(), "name"),
                array_column($registeredEvents, "name")
            )
        ) {
            $this->removeWebhook($webhookId);
            $webhookId = $this->registerWebhooks();
        }

        $this->saveWebhookId($webhookId);

        return $webhookId;
    }

    public function getHookForUrl(string $url): array
    {
        $allClientHooks = $this->getAllRegisteredWebhooks();
        $hook = [];
        foreach ($allClientHooks as $hook) {
            if ($url === $hook['url']) {
                return $hook;
            }
        }
        return $hook;
    }

    protected function registerWebhooks(): string
    {
        $webhookId = '';
        try {
            $payload = [
                'url' => $this->getWebhookEndpoint(),
                'event_types' => $this->getAvailableEventNames(),
            ];

            /** @var GenericService $webhookService */
            $webhookService = Registry::get(ServiceFactory::class)->getWebhookService();
            $webHookResponse = $webhookService->request('POST', $payload);

            $webhookId = $webHookResponse['id'] ?? '';
        } catch (Exception $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log(
                'error',
                'PayPal Webhook creation failed: ' . $exception->getMessage(),
                [$exception]
            );
        }

        return $webhookId;
    }

    public function removeWebhook(string $webhookId): void
    {
        if (empty($webhookId)) {
            //no webhook exists yet, nothing to be deleted
            return;
        }

        /** @var GenericService $webhookService */
        $webhookService = Registry::get(ServiceFactory::class)->getWebhookService('/' . $webhookId);

        $headers = [];
        $headers['Content-Type'] = 'application/json';

        $webhookService->request('DELETE', null, [], $headers);
    }

    public function getWebhookEndpoint(): string
    {
        //TODO: PayPal wants a https url, so we could validate and warn the customer if url does not fit
        return oxNew(PayPalConfig::class)->getWebhookControllerUrl();
    }

    public function saveWebhookId(string $webhookId): void
    {
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $moduleSettings->saveWebhookId($webhookId);
    }

    public function getAllRegisteredWebhooks(): array
    {
        /** @var GenericService $webhookService */
        $webhookService = Registry::get(ServiceFactory::class)->getWebhookService();
        try {
            $result = $webhookService->request('GET');
        } catch (ApiException $e) {
            $result = [];
        }

        return $result['webhooks'] ?? [];
    }

    public function getAvailableEventNames(): array
    {
        $eventNames = [];
        foreach (EventHandlerMapping::MAPPING as $key => $value) {
            $eventNames[] = [
                'name' => $key
            ];
        }
        return $eventNames;
    }

    public function getEnabledEvents(array $hook): array
    {
        $types = $hook['event_types'] ?? [];
        $events = [];
        foreach ($types as $type) {
            if ('ENABLED' === $type['status']) {
                $events[] = [
                    'name' => $type['name']
                ];
            }
        }

        return $events;
    }
}
