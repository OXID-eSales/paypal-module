<?php

/**
 * This file is part of OXID eSales PayPal module.
 *
 * OXID eSales PayPal module is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eSales PayPal module is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eSales PayPal module.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2020
 */

namespace OxidProfessionalServices\PayPal\Controller;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Registry;
use OxidProfessionalServices\PayPal\Api\Exception\ApiException;
use OxidProfessionalServices\PayPal\Core\Request;
use OxidProfessionalServices\PayPal\Core\Webhook\Event;
use OxidProfessionalServices\PayPal\Core\Webhook\EventDispatcher;
use OxidProfessionalServices\PayPal\Core\Webhook\EventVerifier;
use OxidProfessionalServices\PayPal\Core\Webhook\Exception\EventException;

/**
 * Class WebhookController
 * @package OxidProfessionalServices\PayPal\Controller
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
            if ($data !== null) {
                $dispatcher = Registry::get(EventDispatcher::class);
                $dispatcher->dispatch(new Event($data));
            } else {
                throw new EventException(json_last_error_msg());
            }
        } catch (EventException | ApiException $exception) {
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
            Registry::getUtils()->redirect(Registry::getConfig()->getShopUrl());
        }

        Registry::getUtils()->showMessageAndExit('');
    }
}
