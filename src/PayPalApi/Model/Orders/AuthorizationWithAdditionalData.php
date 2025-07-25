<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * The authorization with additional payment details, such as risk assessment and processor response. These
 * details are populated only for certain payment methods.
 *
 * generated from: MerchantsCommonComponentsSpecification-v1-schema-authorization_with_additional_data.json
 */
class AuthorizationWithAdditionalData extends Authorization implements JsonSerializable
{
    use BaseModel;

    /**
     * The risk assessment for a customer account, merchant account, or transaction.
     *
     * @var RiskAssessments | null
     */
    public $risk_assessment;

    /**
     * The processor information. Might be required for payment requests, such as direct credit card transactions.
     *
     * @var ProcessorResponse | null
     */
    public $processor_response;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->risk_assessment) || Assert::isInstanceOf(
            $this->risk_assessment,
            RiskAssessments::class,
            "risk_assessment in AuthorizationWithAdditionalData must be instance of RiskAssessments $within"
        );
        !isset($this->risk_assessment) ||  $this->risk_assessment->validate(AuthorizationWithAdditionalData::class);
        !isset($this->processor_response) || Assert::isInstanceOf(
            $this->processor_response,
            ProcessorResponse::class,
            "processor_response in AuthorizationWithAdditionalData must be instance of ProcessorResponse $within"
        );
        !isset($this->processor_response) ||  $this->processor_response->validate(AuthorizationWithAdditionalData::class);
    }

    private function map(array $data)
    {
        if (isset($data['risk_assessment'])) {
            $this->risk_assessment = new RiskAssessments($data['risk_assessment']);
        }
        if (isset($data['processor_response'])) {
            $this->processor_response = new ProcessorResponse($data['processor_response']);
        }
    }

    public function __construct(array $data = null)
    {
        parent::__construct($data);
        if (isset($data)) {
            $this->map($data);
        }
    }

    public function initRiskAssessment(): RiskAssessments
    {
        return $this->risk_assessment = new RiskAssessments();
    }

    public function initProcessorResponse(): ProcessorResponse
    {
        return $this->processor_response = new ProcessorResponse();
    }
}
