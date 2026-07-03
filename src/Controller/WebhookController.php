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
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventRetryException;
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
        } catch (WebhookEventRetryException $retryException) {
            // Transient condition (e.g. the shop order is still inside the finalizeOrder()
            // transaction and not yet visible on this connection): ask PayPal to redeliver later
            // instead of acknowledging. PayPal retries on any non-2xx status (up to 25 times over
            // 3 days) until it receives a 2xx — so by a later retry the order has been committed.
            $retryAfter = $retryException->getRetryAfter() ?? 0;
            $logger->log(
                'info',
                'Webhook not processed yet, requesting PayPal retry (HTTP 503): ' . $retryException->getMessage()
            );
            http_response_code(503);
            header('Retry-After: ' . $retryAfter);
            exit('');
        } catch (\Exception $exception) {
            $logger->log('error', 'Webhook processing failed (responding 200 to avoid PayPal retry storm): ' . $exception->getMessage(), [$exception]);
        }
        // Respond 200 for handled, permanent or benign cases. A non-200 status causes PayPal to
        // retry the webhook 25 times over 3 days; that is only desired for the transient retry
        // case handled above. For permanent errors (bad signature, unknown event, code bug) a
        // retry would fail identically — so those are acknowledged with 200 and logged above.
        Registry::getUtils()->showMessageAndExit('');
    }
}
