<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminListController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Disputes\ResponseDisputeSearch;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;

class DisputeController extends AdminListController
{
    /**
     * @inheritDoc
     */
    protected $filters = null;

    /**
     * @var ResponseDisputeSearch
     */
    private $response;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplateName('pspaypaldisputes.tpl');
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        try {
            $this->addTplParam('filters', $this->getFilters());
            $this->addTplParam('disputes', $this->getResponse());
            $this->addTplParam('nextPageToken', $this->getNextPageToken());
        } catch (ApiException $exception) {
            if ($exception->shouldDisplay()) {
                $this->addTplParam('error', Registry::getLang()->translateString(
                    'OSC_PAYPAL_ERROR_' . $exception->getErrorIssue()
                ));
            }
            Registry::getLogger()->error($exception);
        }

        return parent::render();
    }

    /**
     * @return ResponseDisputeSearch
     * @throws ApiException
     */
    protected function getResponse(): ResponseDisputeSearch
    {
        if (!$this->response) {
            /** @var ServiceFactory $serviceFactory */
            $serviceFactory = Registry::get(ServiceFactory::class);
            $filters = $this->getFilters();

            $filters['startTime'] = strtotime($filters['startTime']);

            $disputeService = $serviceFactory->getDisputeService();
            $this->response = $disputeService->listDisputesSummary(
                //TODO: at this moment combination of page and page_size does not return correctly paginated result.
                0,
                10,
                $filters['transactionId'],
                $filters['disputeState'],
                date('Y-m-d\TH:i:s\.v\Z', $filters['startTime']),
                Registry::getRequest()->getRequestEscapedParameter('pagetoken')
            );
        }

        return $this->response;
    }

    /**
     * Get next page token
     *
     * @return string
     */
    protected function getNextPageToken(): string
    {
        $token = '';
        $response = $this->getResponse();

        foreach ($response->links as $link) {
            if ($link['rel'] == 'next') {
                $parts = parse_url($link['href']);
                parse_str($parts['query'], $params);
                $token = $params['next_page_token'];
            }
        }

        return $token;
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
                $filters['startTime'] = date('Y-m-d', time() - (60 * 60 * 24 * 30));
            }
            $this->filters = $filters;
        }
        return (array) $this->filters;
    }
}
