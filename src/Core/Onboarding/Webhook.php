<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Onboarding;

use Exception;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Core\Config as PayPalConfig;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventHandlerMapping;
use OxidSolutionCatalysts\PayPal\Exception\OnboardingException;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Service\GenericService;

class Webhook
{
    use ServiceContainer;

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

    public function registerWebhooks(): string
    {
        $webhookId = '';
        try {
            $paypload = [
                'url' => $this->getWebhookEndpoint(),
                'event_types' => $this->getAvailableEventNames(),
            ];

            /** @var GenericService $notificationService */
            $webhookService = Registry::get(ServiceFactory::class)->getWebhookService();
            $webHookResponse = $webhookService->request('post', $paypload);

            $webhookId = $webHookResponse['id'] ?? '';
        } catch (Exception $exception) {
            /** @var Logger $logger */
            $logger = $this->getServiceFromContainer(Logger::class);
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

        /** @var GenericService $notificationService */
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
        /** @var GenericService $notificationService */
        $webhookService = Registry::get(ServiceFactory::class)->getWebhookService();
        $result = $webhookService->request('get');

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
