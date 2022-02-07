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
use OxidSolutionCatalysts\PayPal\Core\RequestReader;
use OxidSolutionCatalysts\PayPal\Core\Onboarding\Onboarding;
use OxidSolutionCatalysts\PayPal\Core\Onboarding\Webhook;

class OnboardingController extends AdminController
{
    use ServiceContainer;

    /**
     * Get ClientID, ClientSecret, WebhookID
     */
    public function autoConfigurationFromCallback()
    {
        $credentials = [];
        $webhookId = '';

        //credentials
        try {
            $requestReader = oxNew(RequestReader::class);
            /** @var Onboarding $handler */
            $handler = oxNew(Onboarding::class, $requestReader);
            $credentials = $handler->autoConfigurationFromCallback();
        } catch (\Exception $exception) {
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
        }

        //webhook registration
        try {
            /** @var Webhook $handler */
            $handler = oxNew(Webhook::class);
            $webhookId = $handler->ensureWebhook();
        } catch (OnboardingException $exception) {
            Registry::getUtilsView()->addErrorToDisplay($exception);
        } catch (\Exception $exception) {
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
        }
        $result = array_merge($credentials, ['webhook_id' => $webhookId]);

        header('Content-Type: application/json; charset=UTF-8');
        Registry::getUtils()->showMessageAndExit(json_encode($result));
    }
}
