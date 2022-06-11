<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Exception\OnboardingException;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPal\Core\RequestReader;
use OxidSolutionCatalysts\PayPal\Core\Onboarding\Onboarding;
use OxidSolutionCatalysts\PayPal\Core\Onboarding\Webhook;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;

class OnboardingController extends AdminController
{
    use ServiceContainer;

    /**
     * Get ClientID, ClientSecret, WebhookID
     */
    public function autoConfigurationFromCallback()
    {
        try {
            $requestReader = oxNew(RequestReader::class);
            PayPalSession::storeOnboardingPayload($requestReader->getRawPost());
        } catch (\Exception $exception) {
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
        }

        $result = [];
        header('Content-Type: application/json; charset=UTF-8');
        Registry::getUtils()->showMessageAndExit(json_encode($result));
    }

    public function returnFromSignup()
    {
        $config = new Config();
        if (
            ('true' === (string) Registry::getRequest()->getRequestParameter('permissionsGranted')) &&
            ('true' === (string) Registry::getRequest()->getRequestParameter('consentStatus'))
        ) {
            PayPalSession::storeMerchantIdInPayPal(Registry::getRequest()->getRequestParameter('merchantIdInPayPal'));
        }

        $this->autoConfiguration();
        $this->registerWebhooks();

        $url = $config->getAdminUrlForJSCalls() . 'cl=oscpaypalconfig&aoc=ready';

        Registry::getUtils()->redirect($url, false, 302);
    }

    /**
     * Get ClientID, ClientSecret
     */
    protected function autoConfiguration(): array
    {
        $credentials = [];

        try {
            /** @var Onboarding $handler */
            $handler = oxNew(Onboarding::class);
            $credentials = $handler->autoConfigurationFromCallback();
        } catch (\Exception $exception) {
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
        }
        return $credentials;
    }

    /**
     * webhook registration
     */
    protected function registerWebhooks(): string
    {
        $webhookId = '';

        try {
            /** @var Webhook $handler */
            $handler = oxNew(Webhook::class);
            $webhookId = $handler->ensureWebhook();
        } catch (OnboardingException $exception) {
            Registry::getUtilsView()->addErrorToDisplay($exception->getMessage());
        } catch (\Exception $exception) {
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
        }

        return $webhookId;
    }
}
