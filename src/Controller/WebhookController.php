<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPal\Core\Request;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifier;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Exception\EventException;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Exception\EventTypeException;

/**
 * Class WebhookController
 * @package OxidSolutionCatalysts\PayPal\Controller
 */
class WebhookController extends FrontendController
{
    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        try {
            /** @var Request $request */
            $request = Registry::get(Request::class);
            $data = $request->getRawPost();

            $verifier = oxNew(EventVerifier::class);
            $verifier->verify($request->getHeaders(), $data);

            $data = json_decode($data, true);
            if (is_array($data) &&
                isset($data['event_type'])
            ) {
                $dispatcher = Registry::get(EventDispatcher::class);
                $dispatcher->dispatch(new Event($data, $data['event_type']));
            } else {
                throw new EventException(json_last_error_msg());
            }
        } catch(EventTypeException $exception) {
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
        } catch (EventException | ApiException $exception) {
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
            //TODO
            Registry::getUtils()->redirect(Registry::getConfig()->getShopUrl());
        }

        Registry::getUtils()->showMessageAndExit('');
    }
}
