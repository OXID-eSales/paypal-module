<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Api;

use DateTime;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Disputes\ResponseDisputeSearch;
use OxidSolutionCatalysts\PayPalApi\Service\Disputes;

class DisputeService extends Disputes
{
    /**
     * List disputes with summary data set
     *
     * @param int $page
     * @param int $pageSize
     * @param mixed $disputedTransactionId
     * @param string $disputeState
     * @param string $startTime
     *
     * @param string $nextPageToken
     *
     * @return ResponseDisputeSearch
     * @throws ApiException
     */
    public function listDisputesSummary(
        $page = 0,
        $pageSize = 0,
        $disputedTransactionId = '',
        $disputeState = '',
        $startTime = '',
        $nextPageToken = ''
    ): ResponseDisputeSearch {
        if (is_array($disputeState) && $disputeState) {
            $disputeState = implode(',', $disputeState);
        }

        if ($startTime) {
            $startTime = (new DateTime($startTime))->format('Y-m-d\TH:i:s\.v\Z');
        }

        return $this->listDisputes(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $disputeState,
            $disputedTransactionId,
            null,
            $page,
            $pageSize,
            $nextPageToken,
            false,
            null,
            $startTime
        );
    }
}
