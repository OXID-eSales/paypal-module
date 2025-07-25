<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Application\Component\Widget\WidgetController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Core\RequestReader;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifier;
use OxidSolutionCatalysts\PayPal\Core\Webhook\RequestHandler as WebhookRequestHandler;

/**
 * Class WebhookController
 * @package OxidSolutionCatalysts\PayPal\Controller
 */
class WebhookController extends WidgetController
{
    private ?Logger $logger = null;
    private ?EventVerifier $eventVerifier = null;
    private ?EventDispatcher $eventDispatcher = null;

    public function __construct(
        ?Logger $logger = null,
        ?EventVerifier $eventVerifier = null,
        ?EventDispatcher $eventDispatcher = null
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

        // Get services either from injection or fallback to Registry
        $logger = $this->getLogger();
        $eventVerifier = $this->getEventVerifier();
        $eventDispatcher = $this->getEventDispatcher();

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

    private function getLogger(): Logger
    {
        if ($this->logger !== null) {
            return $this->logger;
        }
        
        // Fallback to Registry if not injected
        return Registry::get(Logger::class);
    }

    private function getEventVerifier(): EventVerifier
    {
        if ($this->eventVerifier !== null) {
            return $this->eventVerifier;
        }
        
        // Fallback to Registry if not injected
        return Registry::get(EventVerifier::class);
    }

    private function getEventDispatcher(): EventDispatcher
    {
        if ($this->eventDispatcher !== null) {
            return $this->eventDispatcher;
        }
        
        // Fallback to Registry if not injected
        return Registry::get(EventDispatcher::class);
    }

    private function sendErrorResponse(): void
    {
        header('Content-Type: text/html', true, 500);
        exit;
    }
}
