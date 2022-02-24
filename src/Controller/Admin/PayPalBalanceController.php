<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminListController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\TransactionSearch\BalancesResponse;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;

class PayPalBalanceController extends AdminListController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'oscpaypalbalances.tpl';

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
                $this->addTplParam('error', Registry::getLang()->translateString('OSC_PAYPAL_ERROR_' .
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
