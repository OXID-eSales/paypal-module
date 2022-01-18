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

namespace OxidProfessionalServices\PayPal\Service;

use DateTime;
use OxidProfessionalServices\PayPal\Api\Exception\ApiException;
use OxidProfessionalServices\PayPal\Api\Model\Disputes\ResponseDisputeSearch;
use OxidProfessionalServices\PayPal\Api\Service\Disputes;

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
