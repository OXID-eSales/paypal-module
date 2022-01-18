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

namespace OxidProfessionalServices\PayPal\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidProfessionalServices\PayPal\Api\Exception\ApiException;
use OxidProfessionalServices\PayPal\Core\ServiceFactory;
use OxidProfessionalServices\PayPal\Traits\AdminOrderFunctionTrait;

class SubscriptionDetailsController extends AdminController
{
    use AdminOrderFunctionTrait;

    /**
     * @var Subscription
     */
    private $subscription;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplateName('pspaypalsubscriptiondetails.tpl');
    }

    /**
     * @inheritDoc
     */
    public function executeFunction($functionName)
    {
        try {
            parent::executeFunction($functionName);
        } catch (ApiException $exception) {
            $this->addTplParam('error', $exception->getErrorDescription());
            Registry::getLogger()->error($exception);
        }
    }

    /**
     * @inheritDoc
     *
     * @throws StandardException
     */
    public function render()
    {
        try {
            $request = Registry::getRequest();
            $billingAgreementId = $request->getRequestEscapedParameter('billingagreementid');
            $paypalSubscription = $this->getPayPalSubscription($billingAgreementId);
            $product = $this->getSubscriptionProduct($paypalSubscription->id);
            $this->addTplParam('payPalSubscription', $paypalSubscription);
            $this->addTplParam('subscriptionProduct', $product);
        } catch (ApiException $exception) {
            if ($exception->shouldDisplay()) {
                $this->addTplParam('error', $exception->getErrorDescription());
            }
            Registry::getLogger()->error($exception);
        }

        return parent::render();
    }

    /**
     * @return string
     */
    public function getListLink()
    {
        $viewConfig = $this->getViewConfig();
        $request = Registry::getRequest();

        $params = [
            'cl' => 'PayPalSubscriptionController',
            'jumppage' => $request->getRequestEscapedParameter('jumppage'),
            'filters' => $request->getRequestEscapedParameter('filters') ?
                json_decode($request->getRequestEscapedParameter('filters')) : null,
        ];

        return $viewConfig->getSelfLink() . http_build_query(array_filter($params));
    }
}
