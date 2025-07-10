<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Application\Component\Widget\WidgetController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\modules\osc\paypal\src\Traits\OrderProcessTrackingTrait;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Core\RequestReader;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifier;
use OxidSolutionCatalysts\PayPal\Core\Webhook\RequestHandler as WebhookRequestHandler;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use Psr\Log\LoggerInterface;

/**
 * Class WebhookController
 * @package OxidSolutionCatalysts\PayPal\Controller
 */
class WebhookController extends WidgetController
{
    use ServiceContainer;
    use OrderProcessTrackingTrait;

    private LoggerInterface $logger;

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        $this->setTrackingId($this->getTrackingId());
        /** @var Logger $logger */
        $this->logger = $this->getServiceFromContainer(Logger::class);

        try {
            $requestReader = new RequestReader();
            $verificationService = Registry::get(EventVerifier::class);
            $dispatcher = Registry::get(EventDispatcher::class);

            $this->log('debug', 'PayPal Webhook request ' . $requestReader->getRawPost());
            $this->log('debug', 'PayPal Webhook headers ' . serialize($requestReader->getHeaders()));

            $webhookRequestHandler = new WebhookRequestHandler($requestReader, $verificationService, $dispatcher);
            $webhookRequestHandler->process();
        } catch (\Exception $exception) {
            $this->log('error', $exception->getMessage(), [$exception]);
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

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
