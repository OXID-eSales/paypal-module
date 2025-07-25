<?php

namespace OxidSolutionCatalysts\PayPalApi\Service;

use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\Authorization;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\AuthorizationRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\AuthorizationVoidRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\Capture;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\CaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\OrderCaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\ReauthorizeRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\Refund;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\RefundRequest;

class Payments extends BaseService
{
    protected string $basePath = '/v2/payments';

    /**
     * Authorizes partial or full amount of a saved order payment, by order ID. Authorize partial or full amount of a
     * saved order to ensure that funds are available for capturing the amount.<br/><br/>You can authorize amount for
     * up to 115% of the saved order amount.<br/><br/><blockquote><strong>Note:</strong> This request is currently
     * not supported and available for external API callers..</blockquote>
     *
     * @param $authorizeRequest mixed
     *
     * @param $payPalPartnerAttributionId string
     *
     * @param $prefer string The preferred server response upon successful completion of the request. Value
     * is:<ul><li><code>return=minimal</code>. The server returns a minimal response to optimize communication
     * between the API caller and the server. A minimal response includes the <code>id</code>, <code>status</code>
     * and HATEOAS links.</li><li><code>return=representation</code>. The server returns a complete resource
     * representation, including the current state of the resource.</li></ul>
     *
     * @throws ApiException
     * @return Authorization
     */
    public function authorizePayment(AuthorizationRequest $authorizeRequest, string $payPalPartnerAttributionId = '', $prefer = 'return=minimal'): Authorization
    {
        $path = "/authorizations";

        $headers = [];
        $headers['Content-Type'] = 'application/json';
        $headers['Prefer'] = $prefer;

        if ($payPalPartnerAttributionId) {
            $headers['PayPal-Partner-Attribution-Id'] = $payPalPartnerAttributionId;
        }

        $body = json_encode($authorizeRequest, true);
        $response = $this->send('POST', $path, [], $headers, $body);
        $jsonData = json_decode($response->getBody(), true);
        return new Authorization($jsonData);
    }

    /**
     * Shows details for an authorized payment, by ID.
     *
     * @param $authorizationId string The ID of the authorized payment for which to show details.
     *
     * @throws ApiException
     * @return Authorization
     */
    public function showDetailsForAuthorizedPayment($authorizationId): Authorization
    {
        $path = "/authorizations/{$authorizationId}";



        $body = null;
        $response = $this->send('GET', $path, [], [], $body);
        $jsonData = json_decode($response->getBody(), true);
        return new Authorization($jsonData);
    }

    /**
     * Captures an authorized payment, by ID.
     *
     * @param $authorizationId string The PayPal-generated ID for the authorized payment to capture.
     *
     * @param $capture mixed
     *
     * @param $payPalPartnerAttributionId string
     *
     * @param $prefer string The preferred server response upon successful completion of the request. Value
     * is:<ul><li><code>return=minimal</code>. The server returns a minimal response to optimize communication
     * between the API caller and the server. A minimal response includes the <code>id</code>, <code>status</code>
     * and HATEOAS links.</li><li><code>return=representation</code>. The server returns a complete resource
     * representation, including the current state of the resource.</li></ul>
     *
     * @throws ApiException
     * @return Capture
     */
    public function captureAuthorizedPayment($authorizationId, CaptureRequest $capture, string $payPalPartnerAttributionId = '',$prefer = 'return=minimal'): Capture
    {
        $path = "/authorizations/{$authorizationId}/capture";

        $headers = [];
        $headers['Content-Type'] = 'application/json';
        $headers['Prefer'] = $prefer;

        if ($payPalPartnerAttributionId) {
            $headers['PayPal-Partner-Attribution-Id'] = $payPalPartnerAttributionId;
        }

        $body = json_encode($capture, true);
        $response = $this->send('POST', $path, [], $headers, $body);
        $jsonData = json_decode($response->getBody(), true);
        return new Capture($jsonData);
    }

    /**
     * Reauthorizes an authorized PayPal account payment, by ID. To ensure that funds are still available,
     * reauthorize a payment after its initial three-day honor period expires. Within the 29-day authorization
     * period, you can issue multiple re-authorizations after the honor period expires.<br/><br/>If 30 days have
     * transpired since the date of the original authorization, you must create an authorized payment instead of
     * reauthorizing the original authorized payment.<br/><br/>A reauthorized payment itself has a new honor period
     * of three days.<br/><br/>You can reauthorize an authorized payment once for up to 115% of the original
     * authorized amount, not to exceed an increase of $75 USD.<br/><br/>Supports only the `amount` request
     * parameter.<blockquote><strong>Note:</strong> This request is currently not supported for Partner use
     * cases.</blockquote>
     *
     * @param $authorizationId string The PayPal-generated ID for the authorized payment to reauthorize.
     *
     * @param $reauthorizeRequest mixed
     *
     * @param $payPalPartnerAttributionId string
     *
     * @param $prefer string The preferred server response upon successful completion of the request. Value
     * is:<ul><li><code>return=minimal</code>. The server returns a minimal response to optimize communication
     * between the API caller and the server. A minimal response includes the <code>id</code>, <code>status</code>
     * and HATEOAS links.</li><li><code>return=representation</code>. The server returns a complete resource
     * representation, including the current state of the resource.</li></ul>
     *
     * @return Authorization
     *@throws ApiException
     */
    public function reauthorizeAuthorizedPayment($authorizationId, ReauthorizeRequest $reauthorizeRequest, string $payPalPartnerAttributionId = '', $prefer = 'return=minimal'): Authorization
    {
        $path = "/authorizations/{$authorizationId}/reauthorize";

        $headers = [];
        $headers['Content-Type'] = 'application/json';
        $headers['Prefer'] = $prefer;

        if ($payPalPartnerAttributionId) {
            $headers['PayPal-Partner-Attribution-Id'] = $payPalPartnerAttributionId;
        }

        $body = json_encode($reauthorizeRequest, true);
        $response = $this->send('POST', $path, [], $headers, $body);
        $jsonData = json_decode($response->getBody(), true);
        return new Authorization($jsonData);
    }

    /**
     * Voids, or cancels, an authorized payment, by ID. You cannot void an authorized payment that has been fully
     * captured.
     *
     * @param $authorizationId string The PayPal-generated ID for the authorized payment to void.
     *
     * @param $payPalAuthAssertion string An API-caller-provided JSON Web Token (JWT) assertion that identifies the
     * merchant. For details, see
     * [PayPal-Auth-Assertion](/docs/api/reference/api-requests/#paypal-auth-assertion).<blockquote><strong>Note:</strong>For
     * three party transactions in which a partner is managing the API calls on behalf of a merchant, the partner
     * must identify the merchant using either a PayPal-Auth-Assertion header or an access token with
     * target_subject.</blockquote>
     *
     * @throws ApiException
     * @return void
     */
    public function voidAuthorizedPayment($authorizationId, $payPalAuthAssertion): void
    {
        $path = "/authorizations/{$authorizationId}/void";

        $headers = [];
        // deprecated: PayPal-Auth-Assertion is not needed any more
        // $headers['PayPal-Auth-Assertion'] = $payPalAuthAssertion;


        $body = null;
        $this->send('POST', $path, [], $headers, $body);
    }

    /**
     * Captures a saved order, by ID.
     *
     * @param $capture mixed
     *
     * @param $payPalPartnerAttributionId string
     *
     * @param $prefer string The preferred server response upon successful completion of the request. Value
     * is:<ul><li><code>return=minimal</code>. The server returns a minimal response to optimize communication
     * between the API caller and the server. A minimal response includes the <code>id</code>, <code>status</code>
     * and HATEOAS links.</li><li><code>return=representation</code>. The server returns a complete resource
     * representation, including the current state of the resource.</li></ul>
     *
     * @throws ApiException
     * @return Capture
     */
    public function captureSavedOrderDirectly(OrderCaptureRequest $capture, string $payPalPartnerAttributionId = '', $prefer = 'return=minimal'): Capture
    {
        $path = "/captures";

        $headers = [];
        $headers['Content-Type'] = 'application/json';
        $headers['Prefer'] = $prefer;

        if ($payPalPartnerAttributionId) {
            $headers['PayPal-Partner-Attribution-Id'] = $payPalPartnerAttributionId;
        }

        $body = json_encode($capture, true);
        $response = $this->send('POST', $path, [], $headers, $body);
        $jsonData = json_decode($response->getBody(), true);
        return new Capture($jsonData);
    }

    /**
     * Shows details for a captured payment, by ID.
     *
     * @param $captureId string The PayPal-generated ID for the captured payment for which to show details.
     *
     * @throws ApiException
     * @return Capture
     */
    public function showCapturedPaymentDetails($captureId): Capture
    {
        $path = "/captures/{$captureId}";



        $body = null;
        $response = $this->send('GET', $path, [], [], $body);
        $jsonData = json_decode($response->getBody(), true);
        return new Capture($jsonData);
    }

    /**
     * Refunds a captured payment, by ID. For a full refund, include an empty payload in the JSON request body. For a
     * partial refund, include an <code>amount</code> object in the JSON request body.
     *
     * @param $captureId string The PayPal-generated ID for the captured payment to refund.
     *
     * @param $refundRequest mixed
     *
     * @param $payPalAuthAssertion string An API-caller-provided JSON Web Token (JWT) assertion that identifies the
     * merchant. For details, see
     * [PayPal-Auth-Assertion](/docs/api/reference/api-requests/#paypal-auth-assertion).<blockquote><strong>Note:</strong>For
     * three party transactions in which a partner is managing the API calls on behalf of a merchant, the partner
     * must identify the merchant using either a PayPal-Auth-Assertion header or an access token with
     * target_subject.</blockquote>
     *
     * @param $payPalPartnerAttributionId string
     *
     * @param $prefer string The preferred server response upon successful completion of the request. Value
     * is:<ul><li><code>return=minimal</code>. The server returns a minimal response to optimize communication
     * between the API caller and the server. A minimal response includes the <code>id</code>, <code>status</code>
     * and HATEOAS links.</li><li><code>return=representation</code>. The server returns a complete resource
     * representation, including the current state of the resource.</li></ul>
     *
     * @throws ApiException
     * @return Refund
     */
    public function refundCapturedPayment($captureId, RefundRequest $refundRequest, $payPalAuthAssertion, string $payPalPartnerAttributionId = '', $prefer = 'return=minimal'): Refund
    {
        $path = "/captures/{$captureId}/refund";

        $headers = [];
        $headers['Content-Type'] = 'application/json';
        // deprecated: PayPal-Auth-Assertion is not needed any more
        // $headers['PayPal-Auth-Assertion'] = $payPalAuthAssertion;
        $headers['Prefer'] = $prefer;

        if ($payPalPartnerAttributionId) {
            $headers['PayPal-Partner-Attribution-Id'] = $payPalPartnerAttributionId;
        }

        $body = json_encode($refundRequest, true);
        $response = $this->send('POST', $path, [], $headers, $body);
        $jsonData = json_decode($response->getBody(), true);
        return new Refund($jsonData);
    }

    /**
     * Shows details for a refund, by ID.
     *
     * @param $refundId string The PayPal-generated ID for the refund for which to show details.
     *
     * @throws ApiException
     * @return Refund
     */
    public function showRefundDetails($refundId): Refund
    {
        $path = "/refunds/{$refundId}";



        $body = null;
        $response = $this->send('GET', $path, [], [], $body);
        $jsonData = json_decode($response->getBody(), true);
        return new Refund($jsonData);
    }

    /**
     * Voids, or cancels, an authorized payment, by passing the alternate identifiers like invoice id.
     *
     * @param $paymentVoid mixed
     *
     * @param $payPalAuthAssertion string An API-caller-provided JSON Web Token (JWT) assertion that identifies the
     * merchant. For details, see
     * [PayPal-Auth-Assertion](/docs/api/reference/api-requests/#paypal-auth-assertion).<blockquote><strong>Note:</strong>For
     * three party transactions in which a partner is managing the API calls on behalf of a merchant, the partner
     * must identify the merchant using either a PayPal-Auth-Assertion header or an access token with
     * target_subject.</blockquote>
     *
     * @param $prefer string The preferred server response upon successful completion of the request. Value
     * is:<ul><li><code>return=minimal</code>. The server returns no response with a HTTP <code>204
     * OK</code></li><li><code>return=representation</code>. The server returns a complete resource representation,
     * including the current state of the resource with a HTTP <code>200 OK</code>.</li></ul>
     *
     * @throws ApiException
     * @return void
     */
    public function voidAuthorizedPaymentUsingAlternateIdentifier(AuthorizationVoidRequest $paymentVoid, $payPalAuthAssertion, $prefer = 'return=minimal'): void
    {
        $path = "/void";

        $headers = [];
        $headers['Content-Type'] = 'application/json';
        // deprecated: PayPal-Auth-Assertion is not needed any more
        // $headers['PayPal-Auth-Assertion'] = $payPalAuthAssertion;
        $headers['Prefer'] = $prefer;


        $body = json_encode($paymentVoid, true);
        $response = $this->send('POST', $path, [], $headers, $body);
    }
}
