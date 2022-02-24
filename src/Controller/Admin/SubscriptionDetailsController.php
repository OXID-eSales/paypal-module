<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Traits\AdminOrderFunctionTrait;

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
        $this->setTemplateName('oscpaypalsubscriptiondetails.tpl');
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
