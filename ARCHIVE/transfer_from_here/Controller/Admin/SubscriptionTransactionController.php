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

use Exception;
use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Core\Registry;
use OxidProfessionalServices\PayPal\Api\Exception\ApiException;
use OxidProfessionalServices\PayPal\Api\Model\Subscriptions\TransactionsList;
use OxidProfessionalServices\PayPal\Core\ServiceFactory;

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
        $this->setTemplateName('pspaypalsubscriptiontransactions.tpl');
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
