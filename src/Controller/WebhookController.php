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

        $requestReader = new RequestReader();
        $verificationService = Registry::get(EventVerifier::class);
        $dispatcher = Registry::get(EventDispatcher::class);

        $webhookRequestHandler = new WebhookRequestHandler($requestReader, $verificationService, $dispatcher);
        $webhookRequestHandler->process();

        Registry::getUtils()->showMessageAndExit('');
    }
}
