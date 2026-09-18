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
     * @param string $context Optional description of the failing operation
     */
    protected function logError(Exception $exception, string $context = ''): void
    {
        $message = ('' === $context) ? $exception->getMessage() : $context . ': ' . $exception->getMessage();

        try {
            $this->getLogger()->log('error', $message, [$exception]);
        } catch (Exception $e) {
            // Fallback if logger is not available
            error_log('PayPal Webhook Error: ' . $message);
        }
    }

    /**
     * Helper method to trace the onboarding steps consistently. The module logger is configured
     * with log level debug (see services.yaml), so these entries always reach the PayPal log.
     *
     * @param string $message
     */
    protected function logDebug(string $message): void
    {
        try {
            $this->getLogger()->log('debug', $message);
        } catch (Exception $exception) {
            // a missing logger must never abort the onboarding, and a trace is not worth error_log()
        }
    }

    protected function getLogger(): LoggerInterface
    {
        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');

        return $logger;
    }

    protected function getModuleSettings(): ModuleSettings
    {
        /** @var ModuleSettings $moduleSettings */
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);

        return $moduleSettings;
    }

    /**
     * @throws OnboardingException if the endpoint is not https, or the webhook could not be
     *                             removed or created
     */
    public function ensureWebhook(): string
    {
        $endpoint = $this->getWebhookEndpoint();

        $this->logDebug(sprintf('Onboarding: ensuring the PayPal webhook for endpoint %s', $endpoint));

        if (false === strpos($endpoint, "https:")) {
            throw OnboardingException::nonsslUrl();
        }

        $hook = $this->getHookForUrl($endpoint);
        $webhookId = $hook['id'] ?? '';
        $availableEvents = $this->getAvailableEventNames();
        $missingEvents = array_diff(
            array_column($availableEvents, "name"),
            array_column($this->getEnabledEvents($hook), "name")
        );

        if ($missingEvents) {
            $this->logDebug(sprintf(
                '' === $webhookId
                    ? 'Onboarding: no webhook exists for this endpoint, creating one for the event types %2$s'
                    : 'Onboarding: webhook %1$s does not have the event types %2$s enabled, registering it anew',
                $webhookId,
                implode(', ', $missingEvents)
            ));

            try {
                $this->removeWebhook($webhookId);
            } catch (Exception $exception) {
                // without this, a failing DELETE reaches the caller as "registration failed", while
                // the creation was never even attempted - and it would fail too, because PayPal
                // refuses a second webhook for an url it already knows
                throw OnboardingException::webhookRemovalFailed($webhookId, $exception->getMessage(), $exception);
            }

            $webhookId = $this->registerWebhooks();

            $this->logDebug(sprintf('Onboarding: registered the new webhook %s', $webhookId));
        } else {
            $this->logDebug(sprintf(
                'Onboarding: webhook %s already has all %d event types enabled, keeping it',
                $webhookId,
                count($availableEvents)
            ));
        }

        $storedWebhookId = $this->getModuleSettings()->getWebhookId();
        if ($webhookId === $storedWebhookId) {
            $this->logDebug(sprintf('Onboarding: webhook id %s is already stored, nothing to save', $webhookId));
        } else {
            $this->saveWebhookId($webhookId);

            $this->logDebug(sprintf(
                'Onboarding: stored webhook id %s for %s, replacing the stored %s',
                $webhookId,
                $endpoint,
                '' === $storedWebhookId ? '(none)' : $storedWebhookId
            ));
        }

        return $webhookId;
    }

    public function getHookForUrl(string $url): array
    {
        $foreignHookCount = 0;
        foreach ($this->getAllRegisteredWebhooks() as $hook) {
            if ($url === ($hook['url'] ?? '')) {
                $this->logDebug(sprintf(
                    'Onboarding: PayPal has webhook %s registered for %s',
                    $hook['id'] ?? '',
                    $url
                ));

                return $hook;
            }
            $foreignHookCount++;
        }

        // no webhook registered for this url, never fall back to a foreign one
        $this->logDebug(sprintf(
            'Onboarding: PayPal has no webhook registered for %s (%d webhook(s) on this account'
            . ' point to other urls)',
            $url,
            $foreignHookCount
        ));

        return [];
    }

    /**
     * @throws OnboardingException if the webhook could not be created
     */
    protected function registerWebhooks(): string
    {
        try {
            $payload = [
                'url' => $this->getWebhookEndpoint(),
                'event_types' => $this->getAvailableEventNames(),
            ];

            /** @var GenericService $webhookService */
            $webhookService = Registry::get(ServiceFactory::class)->getWebhookService();
            $webHookResponse = $webhookService->request('POST', $payload);
        } catch (Exception $exception) {
            throw OnboardingException::webhookRegistrationFailed($exception->getMessage(), $exception);
        }

        $webhookId = $webHookResponse['id'] ?? '';
        if ('' === $webhookId) {
            throw OnboardingException::webhookRegistrationFailed('response contained no webhook id');
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

        $this->logDebug(sprintf('Onboarding: removed the webhook %s', $webhookId));
    }

    public function getWebhookEndpoint(): string
    {
        //TODO: PayPal wants a https url, so we could validate and warn the customer if url does not fit
        return oxNew(PayPalConfig::class)->getWebhookControllerUrl();
    }

    public function saveWebhookId(string $webhookId): void
    {
        $this->getModuleSettings()->saveWebhookId($webhookId);
    }

    public function getAllRegisteredWebhooks(): array
    {
        /** @var GenericService $webhookService */
        $webhookService = Registry::get(ServiceFactory::class)->getWebhookService();
        try {
            $result = $webhookService->request('GET');
        } catch (ApiException $exception) {
            $this->logError($exception, 'Registered PayPal webhooks could not be fetched');
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
