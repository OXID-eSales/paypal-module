<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Api;

use DateTime;
use GuzzleHttp\Exception\GuzzleException;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Disputes\ResponseDisputeSearch;
use OxidSolutionCatalysts\PayPalApi\Service\Disputes;
use OxidSolutionCatalysts\PayPalApi\Model\Disputes\ResponseSubsequentAction;

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

    /**
     * Provides evidence for a dispute, by ID. A merchant can provide evidence for disputes with the
     * <code>WAITING_FOR_SELLER_RESPONSE</code> status while customers can provide evidence for disputes with the
     * <code>WAITING_FOR_BUYER_RESPONSE</code> status. Evidence can be a proof of delivery or proof of refund
     * document or notes, which can include logs. A proof of delivery document includes a tracking number while a
     * proof of refund document includes a refund ID. The following rules apply to document file types and
     * sizes:<ul><li>The merchant can upload up to 50 MB of files for a case.</li><li>Individual files must be
     * smaller than 10 MB.</li><li>The supported file formats are JPG, GIF, PNG, and PDF.</li></ul><br/>To make this
     * request, specify the dispute ID in the URI and specify the evidence in the JSON request body. For information
     * about dispute reasons, see <a
     * href="/docs/integration/direct/customer-disputes/integration-guide/#dispute-reasons">dispute reasons</a>.
     *
     * @param $disputeId string The ID of the dispute for which to submit evidence.
     *
     * @param $evidence mixed
     *
     * @param $evidence file A file with evidence.
     *
     * @return ResponseSubsequentAction
     * @throws ApiException
     */
    public function provideEvidence($disputeId, $evidences, $files): ResponseSubsequentAction
    {
        if (!$this->client->isAuthenticated()) {
            $this->client->auth();
        }

        $path = "/disputes/{$disputeId}/provide-evidence";

        $headers = [];
        $headers['Content-Type'] = 'multipart/related; boundary=---- WebKitFormBoundary7MA4YWxkTrZu0gW';

        $fileArray = [];

        foreach ($files as $file) {
            $fileArray[] = [
                'name' => $file['name'],
                'contents' => file_get_contents($file['tmp_name'])
            ];
        }

        $evidences = [
            'name' => 'evidences',
            'contents' => json_encode($evidences)
        ];

        $options = [
            'multipart' => [
                $fileArray[0],
                $evidences
            ],
            'headers' => $this->getAuthHeaders()
        ];

        try {
            $response = $this->sendFileData('POST', $path, $options);
        } catch (GuzzleException $e) {
            throw new ApiException($e);
        }

        return $response;
    }

    protected function sendFileData($method, $path, $options = [])
    {
        $fullPath = $this->basePath . $path;
        $options['stream'] = true;

        try {
            $response = $this->client->request($method, $fullPath, $options);
        } catch (GuzzleException $e) {
            throw new ApiException($e);
        }
        return $response;
    }

    /**
     * @return array
     */
    protected function getAuthHeaders()
    {
        if (!$this->client->isAuthenticated()) {
            $this->client->auth();
        }

        $headers = array();
        $headers['Content-Type'] = 'multipart/related; boundary=---- WebKitFormBoundary7MA4YWxkTrZu0gW';
        $headers['Authorization'] = 'Bearer ' . $this->client->getTokenResponse()['access_token'];

        return $headers;
    }
}
