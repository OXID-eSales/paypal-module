<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminListController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Disputes\Money;
use OxidSolutionCatalysts\PayPalApi\Model\Disputes\RequestEscalate;
use OxidSolutionCatalysts\PayPalApi\Model\Disputes\RequestMakeOffer;
use OxidSolutionCatalysts\PayPalApi\Model\Disputes\RequestSendMessage;
use OxidSolutionCatalysts\PayPalApi\Model\Disputes\ResponseDispute;
use OxidSolutionCatalysts\PayPalApi\Model\Disputes\ResponseEvidence;
use OxidSolutionCatalysts\PayPalApi\Model\Disputes\ResponseEvidenceInfo;
use OxidSolutionCatalysts\PayPalApi\Model\Disputes\ResponseTrackingInfo;
use OxidSolutionCatalysts\PayPal\Core\Api\DisputeService as FileAwareDisputeService;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Api\DisputeService;

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
                        'OSC_PAYPAL_ERROR_' . $exception->getErrorIssue()
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
                $evidenceTypes[str_replace('OSC_PAYPAL_EVIDENCE_TYPE_', '', $constant)]
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
