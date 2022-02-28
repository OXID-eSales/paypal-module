<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use DateInterval;
use DateTime;
use Exception;
use OxidEsales\Eshop\Application\Controller\Admin\AdminListController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\TransactionSearch\SearchResponse;
use OxidSolutionCatalysts\PayPalApi\Model\TransactionSearch\TransactionDetail;
use OxidSolutionCatalysts\PayPal\Core\Currency;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\TransactionEventCodes;

class PayPalTransactionController extends AdminListController
{
    private const DEFAULT_LIST_SIZE = 15;

    /**
     * @var SearchResponse|null
     */
    protected $response;

    /**
     * @var string Current class template name.
     */
    protected $_sThisTemplate = 'oscpaypaltransactions.tpl';

    /**
     * @inheritDoc
     */
    public function render()
    {
        try {
            if (Registry::getRequest()->getRequestParameter('where')) {
                $this->requestTransactions();
            }
            $this->addTplParam('eventCodes', TransactionEventCodes::EVENT_CODES);
        } catch (ApiException $exception) {  Registry::getLogger()->error($exception->getMessage());
            if ($exception->shouldDisplay()) {
                $this->addTplParam('error', Registry::getLang()->translateString('OSC_PAYPAL_ERROR_' .
                    $exception->getErrorIssue()));
            }
            Registry::getLogger()->error($exception);
        }

        return parent::render();
    }

    /**
     * Fetches filtered transaction data
     */
    protected function requestTransactions()
    {
        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $transactionService = $serviceFactory->getTransactionSearchService();
        $filters = $this->buildPayPalFilterParameters();

        $this->response = $transactionService->listTransactions(
            $filters['transactionId'],
            $filters['transactionType'],
            $filters['transactionStatus'],
            $filters['transactionAmount'],
            $filters['transactionCurrency'],
            $filters['transactionDate'],
            $filters['startDate'],
            $filters['endDate'],
            $filters['paymentInstrumentType'],
            $filters['storeId'],
            $filters['terminalId'],
            $this->getActivePage(),
            $this->getViewListSize(),
            $filters['balanceAffectingRecordsOnly']
        );
    }

    /**
     * Builds PayPal filter values
     *
     * @return array
     */
    protected function buildPayPalFilterParameters(): array
    {
        $filters = $this->getFilterValues();

        if (
            !empty($filters['fromPrice'])
            && is_numeric($filters['fromPrice'])
            && !empty($filters['toPrice'])
            && is_numeric($filters['toPrice'])
        ) {
            $fromPrice = $filters['fromPrice'];
            $toPrice = $filters['toPrice'];
            $currency = $filters['transactionCurrency'];

            $filters['$transactionAmount'] =
                sprintf(
                    '%s TO %s',
                    Currency::formatAmountInLowestDenominator($fromPrice, $currency),
                    Currency::formatAmountInLowestDenominator($toPrice, $currency)
                );
        } else {
            unset($filters['fromPrice']);
            unset($filters['toPrice']);
        }

        return $filters;
    }

    /**
     * Gets filter parameters
     *
     * @return array
     */
    public function getFilterValues(): array
    {
        $filters = $this->getListFilter();

        //Text input filters
        $textFilterKeys = [
            'transactionId',
            'transactionType',
            'transactionStatus',
            'transactionCurrency',
            'paymentInstrumentType',
            'balanceAffectingRecordsOnly',
            'terminalId',
            'storeId',
            'fromPrice',
            'toPrice'
        ];

        $textFilterValues = array_map(
            function ($filterKey) use ($filters) {
                if ($filterValue = (string)$filters['transactions'][$filterKey]) {
                    return trim($filterValue);
                }
                return null;
            },
            $textFilterKeys
        );

        $textFilters = array_combine($textFilterKeys, $textFilterValues);

        //Date input filters
        $dateFilterKeys = [
            'transactionDate',
            'startDate',
            'endDate',
        ];

        $dateFilterValues = array_map(
            function ($filterKey) use ($filters) {
                if ($filterValue = (string)$filters['transactions'][$filterKey]) {
                    try {
                        $date = new DateTime($filterValue);
                        return $date->format(DATE_ISO8601);
                    } catch (Exception $exception) {
                        return null;
                    }
                }
                return null;
            },
            $dateFilterKeys
        );

        return $this->setDefaultFilterValues(
            array_merge(
                $textFilters,
                array_combine($dateFilterKeys, $dateFilterValues)
            )
        );
    }

    /**
     * Sets default values for required parameters
     *
     * @param array $filters
     *
     * @return array
     */
    protected function setDefaultFilterValues(array $filters): array
    {
        //Setting default date values on initial page load
        if (empty($filters['startDate']) && empty($filters['endDate'])) {
            $utilsDate = Registry::getUtilsDate();
            //Maximum period of one month can be requested
            $today = new DateTime();
            $today->setTimestamp($utilsDate->getTime());
            $previousMonth = clone $today;
            $previousMonth->sub(new DateInterval('P1M'));

            $filters['startDate'] = $previousMonth->format(DateTime::ISO8601);
            $filters['endDate'] = $today->format(DateTime::ISO8601);
        }

        //Use 0 FROM price in case only TO price is provided
        if (empty($filters['fromPrice']) && !empty($filters['toPrice'])) {
            $filters['fromPrice'] = 0;
        }

        return $filters;
    }

    /**
     * Get active page number
     *
     * @return int
     */
    protected function getActivePage(): int
    {
        $page = (int)Registry::getRequest()->getRequestEscapedParameter('jumppage');

        return $page > 0 ? $page : 1;
    }

    /**
     * Gets transaction information with applied filters.
     *
     * @return TransactionDetail[]|null
     */
    public function getTransactions(): array
    {
        $transactions = [];
        $response = $this->response;

        if ($response && is_array($response->transaction_details)) {
            $transactions = $response->transaction_details;
        }

        return $transactions;
    }

    /**
     * Set parameters needed for list navigation
     */
    protected function _setListNavigationParams()
    {
        if ($response = $this->response) {
            $this->_iListSize = $response->total_items;
            $this->_iCurrListPos = ($response->page - 1) * $this->getViewListSize();
        }

        parent::_setListNavigationParams();
    }

    /**
     * @inheritDoc
     */
    protected function _getViewListSize()
    {
        return self::DEFAULT_LIST_SIZE;
    }
}
