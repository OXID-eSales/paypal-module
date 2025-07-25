<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Application\Component\Widget\WidgetController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\LoggerInterface;
use OxidSolutionCatalysts\PayPal\Core\RequestReader;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifierInterface;
use OxidSolutionCatalysts\PayPal\Core\Webhook\RequestHandler as WebhookRequestHandler;

/**
 * Class WebhookController
 * @package OxidSolutionCatalysts\PayPal\Controller
 */
class WebhookController extends WidgetController
{
    private LoggerInterface $logger;
    private EventVerifierInterface $eventVerifier;
    private EventDispatcher $eventDispatcher;

    public function __construct(
        LoggerInterface $logger,
        EventVerifierInterface $eventVerifier,
        EventDispatcher $eventDispatcher
    ) {
        $this->logger = $logger;
        $this->eventVerifier = $eventVerifier;
        $this->eventDispatcher = $eventDispatcher;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        // Use injected services directly
        $logger = $this->logger;
        $eventVerifier = $this->eventVerifier;
        $eventDispatcher = $this->eventDispatcher;

        try {
            $requestReader = new RequestReader();

            $logger->log('debug', 'PayPal Webhook request ' . $requestReader->getRawPost());
            $logger->log('debug', 'PayPal Webhook headers ' . serialize($requestReader->getHeaders()));

            $webhookRequestHandler = new WebhookRequestHandler(
                $requestReader, 
                $eventVerifier, 
                $eventDispatcher
            );
            $webhookRequestHandler->process();
        } catch (\Exception $exception) {
            $logger->log('error', $exception->getMessage(), [$exception]);
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
