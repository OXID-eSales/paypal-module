<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Application\Component\Widget\WidgetController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\RequestReader;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifier;
use OxidSolutionCatalysts\PayPal\Core\Webhook\RequestHandler as WebhookRequestHandler;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use Psr\Log\LoggerInterface;

/**
 * Class WebhookController
 * @package OxidSolutionCatalysts\PayPal\Controller
 */
class WebhookController extends WidgetController
{
    use ServiceContainer;

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');

        try {
            $requestReader = new RequestReader();
            $verificationService = Registry::get(EventVerifier::class);
            $dispatcher = Registry::get(EventDispatcher::class);

            $logger->log('debug', 'PayPal Webhook request ' . $requestReader->getRawPost());
            $logger->log('debug', 'PayPal Webhook headers ' . serialize($requestReader->getHeaders()));

            $webhookRequestHandler = new WebhookRequestHandler($requestReader, $verificationService, $dispatcher);
            $webhookRequestHandler->process();
        } catch (\Exception $exception) {
            $logger->log('error', 'Webhook processing failed (responding 200 to avoid PayPal retry storm): ' . $exception->getMessage(), [$exception]);
        }
        // Always respond with 200, even on processing errors. A non-200 status causes PayPal
        // to retry the webhook 25 times over 3 days. If the error is permanent (bad signature,
        // unknown event, code bug), every retry will fail the same way — generating unnecessary
        // load and log spam. The error is already logged above for investigation.
        Registry::getUtils()->showMessageAndExit('');
    }
}
