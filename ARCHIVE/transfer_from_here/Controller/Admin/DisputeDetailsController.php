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
use OxidProfessionalServices\PayPal\Api\Model\Disputes\Money;
use OxidProfessionalServices\PayPal\Api\Model\Disputes\RequestEscalate;
use OxidProfessionalServices\PayPal\Api\Model\Disputes\RequestMakeOffer;
use OxidProfessionalServices\PayPal\Api\Model\Disputes\RequestSendMessage;
use OxidProfessionalServices\PayPal\Api\Model\Disputes\ResponseDispute;
use OxidProfessionalServices\PayPal\Api\Model\Disputes\ResponseEvidence;
use OxidProfessionalServices\PayPal\Api\Model\Disputes\ResponseEvidenceInfo;
use OxidProfessionalServices\PayPal\Api\Model\Disputes\ResponseTrackingInfo;
use OxidProfessionalServices\PayPal\Controller\Admin\Service\DisputeService as FileAwareDisputeService;
use OxidProfessionalServices\PayPal\Core\ServiceFactory;
use OxidProfessionalServices\PayPal\Service\DisputeService;

class DisputeDetailsController extends AdminListController
{
    /**
     * @var ResponseDispute
     */
    private $dispute;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplateName('pspaypaldisputedetails.tpl');
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        try {
            $this->addTplParam('dispute', $this->getDispute());
            $this->addTplParam('evidenceTypes', $this->getEvidenceTypes());
            $this->addTplParam('loops', [1,2,3,4,5]);
        } catch (ApiException $exception) {
            if ($exception->shouldDisplay()) {
                $this->addTplParam(
                    'error',
                    Registry::getLang()->translateString(
                        'OXPS_PAYPAL_ERROR_' . $exception->getErrorIssue()
                    )
                );
            }
            Registry::getLogger()->error($exception);
        }

        return parent::render();
    }

    /**
     * @inheritDoc
     */
    public function executeFunction($functionName)
    {
        try {
            parent::executeFunction($functionName);
        } catch (ApiException $exception) {
            $this->addTplParam('error', $exception->getErrorDescription());
            Registry::getLogger()->error($exception);
        }
    }

    /**
     * @return array
     */
    public function getEvidenceTypes()
    {
        $oClass = new \ReflectionClass(ResponseEvidence::class);
        $constants = $oClass->getConstants();
        $lang = Registry::getLang();

        $evidenceTypes = [];

        foreach ($constants as $constant => $value) {
            if (substr($constant, 0, 14) === "EVIDENCE_TYPE_") {
                $evidenceTypes[str_replace('OXPS_PAYPAL_EVIDENCE_TYPE_', '', $constant)]
                    = $lang->translateString($constant);
            }
        }

        return $evidenceTypes;
    }

    /**
     * Get dispute
     *
     * @return ResponseDispute
     */
    private function getDispute(): ResponseDispute
    {
        if (!$this->dispute) {
            /** @var ServiceFactory $serviceFactory */
            $disputeService = Registry::get(ServiceFactory::class)->getDisputeService();
            $this->dispute = $disputeService->showDisputeDetails($this->getEditObjectId());
        }

        return $this->dispute;
    }

    /**
     * Sends merchants dispute message
     *
     * @throws ApiException
     */
    public function sendMessage(): void
    {
        $disputeId = $this->getEditObjectId();
        $messageRequest = new RequestSendMessage();
        $messageRequest->message = Registry::getRequest()->getRequestEscapedParameter('message');
        $this->getDisputeService()->sendMessageAboutDisputeToOtherParty($disputeId, $messageRequest);
    }

    /**
     * Action for making offers to resolve disputes
     *
     * @throws ApiException
     */
    public function makeOffer(): void
    {
        $request = Registry::getRequest();
        $disputeId = $this->getEditObjectId();

        $offerRequest = new RequestMakeOffer();
        $offerRequest->note = (string) $request->getRequestEscapedParameter('note');
        $offerRequest->offer_type = (string) $request->getRequestEscapedParameter('offerType');

        $offerAmount = (array) $request->getRequestEscapedParameter('offerAmount');
        if (!empty($offerAmount['value'])) {
            $offerRequest->offer_amount = new Money($offerAmount);
        }

        $this->getDisputeService()->makeOfferToResolveDispute($disputeId, $offerRequest);
    }

    /**
     * Action for making offers to resolve disputes
     *
     * @throws ApiException
     */
    public function escalate(): void
    {
        $disputeId = $this->getEditObjectId();
        $request = new RequestEscalate(['note' => Registry::getRequest()->getRequestEscapedParameter('note')]);

        $this->getDisputeService()->escalateDisputeToClaim($disputeId, $request);
    }

    public function provideEvidence()
    {
        $disputeId = $this->getEditObjectId();
        $disputeService = $this->getFileAwareDisputeService();

        $fileArray = [];
        $evidenceArray = [];
        $lang = Registry::getLang();

        if (!empty($_FILES)) {
            for ($i = 1; $i < 6; $i++) {
                $file = $_FILES['evidenceFile' . $i];
                if (!empty($file['name'])) {
                    $fileArray[] = $file;
                    $evidence = new ResponseEvidence();
                    $evidence->evidence_type = $lang->translateString(
                        Registry::getRequest()->getRequestEscapedParameter('evidenceType' . $i)
                    );


                    $evidenceInfo = new ResponseEvidenceInfo();

                    $trackingInfo = new ResponseTrackingInfo();
                    $trackingInfo->carrier_name = 'FEDEX';
                    $trackingInfo->tracking_number = '122533485';

                    $evidenceInfo->tracking_info = [];
                    $evidenceInfo->tracking_info[] = $trackingInfo;

                    $evidence->evidence_info = $evidenceInfo;
                    $evidence->notes = 'test';
                    $evidenceArray[] = $evidence;
                }
            }
        }

        $disputeService->provideEvidence($disputeId, $evidenceArray, $fileArray);
    }

    /**
     * @return DisputeService
     */
    protected function getDisputeService(): DisputeService
    {
        return Registry::get(ServiceFactory::class)->getDisputeService();
    }

    /**
     * @return FileAwareDisputeService
     */
    protected function getFileAwareDisputeService(): FileAwareDisputeService
    {
        return Registry::get(ServiceFactory::class)->getFileAwaredisputeService();
    }
}
