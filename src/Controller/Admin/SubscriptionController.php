<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminListController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Repository\SubscriptionRepository;

class SubscriptionController extends AdminListController
{
    /**
     * @var subscriptionList
     */
    protected $subscriptionList = null;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        $this->setTemplateName('pspaypalsubscriptions.tpl');

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->addTplParam('subscriptions', $this->getSubscriptionList());
        $this->addTplParam('detailsLink', $this->getDetailsLink());

        return parent::render();
    }

    /**
     * Get link for subscription details page
     *
     * @return string
     */
    private function getDetailsLink(): string
    {
        $viewConfig = $this->getViewConfig();
        $request = Registry::getRequest();

        $params = [
            'cl' => 'PayPalSubscriptionDetailsController',
            'jumppage' => $request->getRequestEscapedParameter('jumppage'),
            'filters' => $this->getFilter() ? json_encode($this->getFilter()) : null,
        ];

        return $viewConfig->getSelfLink() . http_build_query(array_filter($params));
    }

    /**
     * Get list of subscriptions
     *
     * @return string
     */
    protected function getSubscriptionList()
    {
        if (is_null($this->subscriptionList)) {
            $this->subscriptionList = [];
            $from = (int)Registry::getRequest()->getRequestEscapedParameter('jumppage');

            $subscriptionRepo = new SubscriptionRepository();
            $subscriptionList = $subscriptionRepo->getSubscriptionOrders(
                $this->getFilter(),
                $from
            );
            if (count($subscriptionList)) {
                $this->subscriptionList = $subscriptionList;
            }
        }
        return $this->subscriptionList;
    }

    /**
     * @inheritDoc
     */
    public function getFilter()
    {
        return Registry::getRequest()->getRequestEscapedParameter('filters') ?? [];
    }
}
