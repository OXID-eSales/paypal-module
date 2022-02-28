<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use Exception;
use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Subscriptions\TransactionsList;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;

class SubscriptionTransactionController extends AdminController
{
    /**
     * @inheritDoc
     */
    protected $filters = null;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplateName('oscpaypalsubscriptiontransactions.tpl');
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        try {
            $this->addTplParam('subscriptionId', $this->getSubscriptionId());
            $this->addTplParam('filters', $this->getFilters());
            $this->addTplParam('transactions', $this->getTransactions());
        } catch (ApiException $exception) {
            if ($exception->shouldDisplay()) {
                $this->addTplParam('error', $exception->getErrorDescription());
            }
            Registry::getLogger()->error($exception);
        }

        return parent::render();
    }

    /**
     * Get transaction list subscription
     *
     * @return TransactionsList
     * @throws ApiException
     * @throws Exception
     */
    private function getTransactions(): TransactionsList
    {
        $filters = $this->getFilters();
        $subscriptionId = $this->getSubscriptionId();

        if (!$subscriptionId || empty($filters['startTime']) || empty($filters['endTime'])) {
            return new TransactionsList();
        }

        /**
         * @var ServiceFactory $serviceFactory
         */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $subscriptionService = $serviceFactory->getSubscriptionService();

        $filters['startTime'] = strtotime($filters['startTime']);
        $filters['endTime'] = strtotime($filters['endTime']);

        return $subscriptionService->listTransactionsForSubscription(
            $subscriptionId,
            date('Y-m-d\TH:i:s\.v\Z', $filters['startTime']),
            date('Y-m-d\TH:i:s\.v\Z', $filters['endTime'])
        );
    }

    /**
     * Get used filter values
     *
     * @return array
     */
    private function getFilters(): array
    {
        if (is_null($this->filters)) {
            $filters = Registry::getRequest()->getRequestEscapedParameter('filters', []);
            if (!isset($filters['endTime']) && !isset($filters['startTime'])) {
                $filters['endTime'] = date('Y-m-d', time());
                $filters['startTime'] = date('Y-m-d', time() - (60 * 60 * 24 * 30));
            }
            $this->filters = $filters;
        }
        return (array) $this->filters;
    }

    /**
     * Get subscription ID
     *
     * @return string
     */
    private function getSubscriptionId(): string
    {
        return Registry::getRequest()->getRequestEscapedParameter('subscriptionId', '');
    }
}
