<?php

namespace OxidSolutionCatalysts\PayPalApi\Service;

use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\ReferralDataCreateReferralDataResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\ReferralDataReferralData;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\ReferralDataReferralDataResponse;

class Partner extends BaseService
{
    protected string $basePath = '/v2/customer';

    /**
     * Creates a partner referral that is shared by the API caller. The referrals contains the client's personal,
     * business, financial and operations that the partner wants to onboard the client.
     *
     * @param $referralData mixed
     *
     * @throws ApiException
     * @return ReferralDataCreateReferralDataResponse
     */
    public function createPartnerReferral(ReferralDataReferralData $referralData): ReferralDataCreateReferralDataResponse
    {
        $path = "/partner-referrals";

        $headers = [];
        $headers['Content-Type'] = 'application/json';


        $body = json_encode($referralData, true);
        $response = $this->send('POST', $path, [], $headers, $body);
        $jsonData = json_decode($response->getBody(), true);
        return new ReferralDataCreateReferralDataResponse($jsonData);
    }

    /**
     * Shows details for referral data, by ID, that was shared by the API caller.
     *
     * @param $partnerReferralId string The ID of the partner-referrals data for which to show details.
     *
     * @throws ApiException
     * @return ReferralDataReferralDataResponse
     */
    public function showReferralData($partnerReferralId): ReferralDataReferralDataResponse
    {
        $path = "/partner-referrals/{$partnerReferralId}";



        $body = null;
        $response = $this->send('GET', $path, [], [], $body);
        $jsonData = json_decode($response->getBody(), true);
        return new ReferralDataReferralDataResponse($jsonData);
    }
}
