<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Application\Component\Widget\WidgetController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\RequestReader;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifier;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher;
use OxidSolutionCatalysts\PayPal\Core\Webhook\RequestHandler as WebhookRequestHandler;

/**
 * Class WebhookController
 * @package OxidSolutionCatalysts\PayPal\Controller
 */
class WebhookController extends WidgetController
{
    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        try {
            $requestReader = new RequestReader();
            $verificationService = Registry::get(EventVerifier::class);
            $dispatcher = Registry::get(EventDispatcher::class);

            Registry::getLogger()->debug('PayPal Webhook request ' . $requestReader->getRawPost());
            Registry::getLogger()->debug('PayPal Webhook headers' . serialize($requestReader->getHeaders()));

            $webhookRequestHandler = new WebhookRequestHandler($requestReader, $verificationService, $dispatcher);
            $success = $webhookRequestHandler->process();
        } catch (\Exception $exception) {
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
            $this->sendErrorResponse();
        }
        //We need to return a 200 if the call could be processed successfully, the otherwise webhook event
        //will be sent it again:
        //  "If your app responds with any other status code, PayPal tries to resend the notification
        //   message 25 times over the course of three days."
        Registry::getUtils()->showMessageAndExit('');
    }

    private function sendErrorResponse(): void
    {
        header('Content-Type: text/html', true, 500);
        exit;
    }
}
