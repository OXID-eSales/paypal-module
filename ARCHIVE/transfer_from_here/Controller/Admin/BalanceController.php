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

use OxidEsales\Eshop\Application\Controller\Admin\AdminListController;
use OxidEsales\Eshop\Core\Registry;
use OxidProfessionalServices\PayPal\Api\Exception\ApiException;
use OxidProfessionalServices\PayPal\Api\Model\TransactionSearch\BalancesResponse;
use OxidProfessionalServices\PayPal\Core\ServiceFactory;

class BalanceController extends AdminListController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'pspaypalbalances.tpl';

    /**
     * @return string
     */
    public function getAsOfTime()
    {
        if (!$asOfTime = (string) Registry::getRequest()->getRequestEscapedParameter('asOfTime')) {
            $asOfTime = date('Y-m-d', time());
        }
        return $asOfTime;
    }

    /**
     * @return string
     */
    public function getCurrencyCode()
    {
        return (string) Registry::getRequest()->getRequestEscapedParameter('currencyCode');
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        try {
            $this->addTplParam('balances', $this->getBalances());
        } catch (ApiException $exception) {
            if ($exception->shouldDisplay()) {
                $this->addTplParam('error', Registry::getLang()->translateString('OXPS_PAYPAL_ERROR_' .
                    $exception->getErrorIssue()));
            }
            Registry::getLogger()->error($exception);
        }

        return parent::render();
    }

    /**
     * Get balance information
     *
     * @return BalancesResponse
     * @throws ApiException
     */
    protected function getBalances(): BalancesResponse
    {
        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $transactionService = $serviceFactory->getTransactionSearchService();

        $asOfTime = strtotime($this->getAsOfTime());

        return $transactionService->listAllBalances(
            date('Y-m-d\TH:i:s\.v\Z', $asOfTime),
            $this->getCurrencyCode()
        );
    }
}
