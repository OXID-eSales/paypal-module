<?php

namespace OxidSolutionCatalysts\PayPalApi\Service;

use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ConfirmOrderRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderAuthorizeRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderValidateRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentContextData;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentDetailsRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSessionRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSessionResponse;

class Orders extends BaseService
{
    protected string $basePath = '/v2/checkout';

    /**
     * Creates an order.<blockquote><strong>Note:</strong> For error handling and troubleshooting, see <a
     * href="/docs/api/reference/orders-v2-errors/#create-order">Orders v2 errors</a>.</blockquote>
     *
     * @param $order mixed
     *
     * @param $payPalPartnerAttributionId string
     *
     * @param $payPalClientMetadataId string
     *
     * @param $prefer string The preferred server response upon successful completion of the request. Value
     * is:<ul><li><code>return=minimal</code>. The server returns a minimal response to optimize communication
     * between the API caller and the server. A minimal response includes the <code>id</code>, <code>status</code>
     * and HATEOAS links.</li><li><code>return=representation</code>. The server returns a complete resource
     * representation, including the current state of the resource.</li></ul>
     *
     * @throws ApiException
     * @return Order
     */
    public function createOrder(OrderRequest $order, $payPalPartnerAttributionId, $payPalClientMetadataId, $prefer = 'return=minimal'): Order
    {
        $path = "/orders";

        $headers = [];
        $headers['Content-Type'] = 'application/json';
        $headers['PayPal-Partner-Attribution-Id'] = $payPalPartnerAttributionId;
        $headers['PayPal-Client-Metadata-Id'] = $payPalClientMetadataId;
        $headers['Prefer'] = $prefer;

        $body = json_encode($order, true);
        $response = $this->send('POST', $path, [], $headers, $body);
        $jsonData = json_decode($response->getBody(), true);
        return new Order($jsonData);
    }

    /**
     * Shows details for an order, by ID.<blockquote><strong>Note:</strong> For error handling and troubleshooting,
     * see <a href="/docs/api/reference/orders-v2-errors/#get-order">Orders v2 errors</a>.</blockquote>
     *
     * @param $id string The ID of the order for which to show details.
     *
     * @param $fields string A comma-separated list of fields that should be returned for the order. Valid filter
     * field is `payment_source`.
     *
     * @param $payPalPartnerAttributionId string
     *
     * @return Order
     *@throws ApiException
     */
    public function showOrderDetails($id, $fields, $payPalPartnerAttributionId): Order
    {
        $path = "/orders/{$id}";

        $headers = [];
        $headers['Content-Type'] = 'application/json';
        $headers['PayPal-Partner-Attribution-Id'] = $payPalPartnerAttributionId;

        $params = [];
        $params['fields'] = $fields;

        $body = null;
        $response = $this->send('GET', $path, $params, $headers, $body);
        $jsonData = json_decode($response->getBody(), true);
        return new Order($jsonData);
    }

    /**
     * Updates an order with a `CREATED` or `APPROVED` status. You cannot update an order with the `COMPLETED`
     * status.<br/><br/>To make an update, you must provide a `reference_id`. If you omit this value with an order
     * that contains only one purchase unit, PayPal sets the value to `default` which enables you to use the path:
     * <code>"/purchase_units/@reference_id=='default'/{attribute-or-object}"</code>.<blockquote><strong>Note:</strong>
     * For error handling and troubleshooting, see <a href="/docs/api/reference/orders-v2-errors/#patch-order">Orders
     * v2 errors</a>.</blockquote>Patchable attributes or
     * objects:<br/><br/><table><thead><th>Attribute</th><th>Op</th><th>Notes</th></thead><tbody><tr><td><code>intent</code></td><td>replace</td><td></td></tr><tr><td><code>payer</code></td><td>replace,
     * add</td><td>Using replace op for <code>payer</code> will replace the whole <code>payer</code> object with the
     * value sent in request.</td></tr><tr><td><code>purchase_units</code></td><td>replace,
     * add</td><td></td></tr><tr><td><code>purchase_units[].custom_id</code></td><td>replace, add,
     * remove</td><td></td></tr><tr><td><code>purchase_units[].description</code></td><td>replace, add,
     * remove</td><td></td></tr><tr><td><code>purchase_units[].payee.email</code></td><td>replace</td><td></td></tr><tr><td><code>purchase_units[].shipping.name</code></td><td>replace,
     * add</td><td></td></tr><tr><td><code>purchase_units[].shipping.address</code></td><td>replace,
     * add</td><td></td></tr><tr><td><code>purchase_units[].shipping.type</code></td><td>replace,
     * add</td><td></td></tr><tr><td><code>purchase_units[].soft_descriptor</code></td><td>replace,
     * remove</td><td></td></tr><tr><td><code>purchase_units[].amount</code></td><td>replace</td><td></td></tr><tr><td><code>purchase_units[].invoice_id</code></td><td>replace,
     * add,
     * remove</td><td></td></tr><tr><td><code>purchase_units[].payment_instruction</code></td><td>replace</td><td></td></tr><tr><td><code>purchase_units[].payment_instruction.disbursement_mode</code></td><td>replace</td><td>By
     * default, <code>disbursement_mode</code> is
     * <code>INSTANT</code>.</td></tr><tr><td><code>purchase_units[].payment_instruction.platform_fees</code></td><td>replace,
     * add, remove</td><td></td></tr><tr><td><code>application_context.client_configuration</code></td><td>replace,
     * add</td><td></td></tr></tbody></table>
     *
     * @param $id string The ID of the order to update.
     *
     * @param $patchRequest mixed
     *
     * @param $payPalPartnerAttributionId string
     *
     * @return void
     *@throws ApiException
     */
    public function updateOrder($id, array $patchRequest, string $payPalPartnerAttributionId = ''): void
    {
        $path = "/orders/{$id}";

        $headers = [];
        $headers['Content-Type'] = 'application/json';

        if ($payPalPartnerAttributionId) {
            $headers['PayPal-Partner-Attribution-Id'] = $payPalPartnerAttributionId;
        }

        $body = json_encode($patchRequest, true);
        $response = $this->send('PATCH', $path, [], $headers, $body);
    }

    /**
     * Validates a payment method and checks it for contingencies.
     *
     * @param $payPalClientMetadataId string
     *
     * @param $id string The ID of the order for which to validate the payment method.
     *
     * @param $orderValidateRequest mixed
     *
     * @throws ApiException
     * @return Order
     */
    public function validatePaymentMethod($payPalClientMetadataId, $id, OrderValidateRequest $orderValidateRequest): Order
    {
        $path = "/orders/{$id}/validate-payment-method";

        $headers = [];
        $headers['PayPal-Client-Metadata-Id'] = $payPalClientMetadataId;
        $headers['Content-Type'] = 'application/json';

        $body = json_encode($orderValidateRequest, true);
        $response = $this->send('POST', $path, [], $headers, $body);
        $jsonData = json_decode($response->getBody(), true);
        return new Order($jsonData);
    }

    /**
     * Payer confirms their intent to pay for the the Order with the given payment source.
     *
     * @param $payPalClientMetadataId string
     *
     * @param $id string The ID of the order for which the payer confirms their intent to pay.
     *
     * @param $confirmOrderRequest mixed
     *
     * @param $payPalPartnerAttributionId string
     *
     * @param $prefer string The preferred server response upon successful completion of the request. Value
     * is:<ul><li><code>return=minimal</code>. The server returns a minimal response to optimize communication
     * between the API caller and the server. A minimal response includes the <code>id</code>, <code>status</code>
     * and HATEOAS links.</li><li><code>return=representation</code>. The server returns a complete resource
     * representation, including the current state of the resource.</li></ul>
     *
     * @return Order
     *@throws ApiException
     */
    public function confirmTheOrder($payPalClientMetadataId, $id, ConfirmOrderRequest $confirmOrderRequest, string $payPalPartnerAttributionId = '', $prefer = 'return=minimal'): Order
    {
        $path = "/orders/{$id}/confirm-payment-source";

        $headers = [];
        $headers['PayPal-Client-Metadata-Id'] = $payPalClientMetadataId;
        $headers['Content-Type'] = 'application/json';
        $headers['Prefer'] = $prefer;

        if ($payPalPartnerAttributionId) {
            $headers['PayPal-Partner-Attribution-Id'] = $payPalPartnerAttributionId;
        }

        $body = json_encode($confirmOrderRequest, true);
        $response = $this->send('POST', $path, [], $headers, $body);
        $jsonData = json_decode($response->getBody(), true);
        return new Order($jsonData);
    }

    /**
     * Authorizes payment for an order. To successfully authorize payment for an order, the buyer must first approve
     * the order or a valid payment_source must be provided in the request. A buyer can approve the order upon being
     * redirected to the rel:approve URL that was returned in the HATEOAS links in the create order
     * response.<blockquote><strong>Note:</strong> For error handling and troubleshooting, see <a
     * href="/docs/api/reference/orders-v2-errors/#authorize-order">Orders v2 errors</a>.</blockquote>
     *
     * @param $payPalClientMetadataId string
     *
     * @param $id string The ID of the order for which to authorize.
     *
     * @param $authorizeRequest mixed
     *
     * @param $payPalAuthAssertion string An API-caller-provided JSON Web Token (JWT) assertion that identifies the
     * merchant. For details, see <a
     * href="/docs/api/reference/api-requests/#paypal-auth-assertion">PayPal-Auth-Assertion</a>.
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
     * @return Order
     */
    public function authorizePaymentForOrder($payPalClientMetadataId, $id, OrderAuthorizeRequest $authorizeRequest, $payPalAuthAssertion, string $payPalPartnerAttributionId = '', $prefer = 'return=minimal'): Order
    {
        $path = "/orders/{$id}/authorize";

        $headers = [];
        $headers['PayPal-Client-Metadata-Id'] = $payPalClientMetadataId;
        $headers['Content-Type'] = 'application/json';
        // deprecated: PayPal-Auth-Assertion is not needed any more
        // $headers['PayPal-Auth-Assertion'] = $payPalAuthAssertion;
        $headers['Prefer'] = $prefer;

        if ($payPalPartnerAttributionId) {
            $headers['PayPal-Partner-Attribution-Id'] = $payPalPartnerAttributionId;
        }

        $body = json_encode($authorizeRequest, true);
        $response = $this->send('POST', $path, [], $headers, $body);
        $jsonData = json_decode($response->getBody(), true);
        return new Order($jsonData);
    }

    /**
     * Captures payment for an order. To successfully capture payment for an order, the buyer must first approve the
     * order or a valid payment_source must be provided in the request. A buyer can approve the order upon being
     * redirected to the rel:approve URL that was returned in the HATEOAS links in the create order
     * response.<blockquote><strong>Note:</strong> For error handling and troubleshooting, see <a
     * href="/docs/api/reference/orders-v2-errors/#capture-order">Orders v2 errors</a>.</blockquote>
     *
     * @param $payPalClientMetadataId string
     *
     * @param $id string The ID of the order for which to capture a payment.
     *
     * @param $orderCaptureRequest mixed
     *
     * @param $payPalAuthAssertion string An API-caller-provided JSON Web Token (JWT) assertion that identifies the
     * merchant. For details, see <a
     * href="/docs/api/reference/api-requests/#paypal-auth-assertion">PayPal-Auth-Assertion</a>.
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
     * @return Order
     */
    public function capturePaymentForOrder($payPalClientMetadataId, $id, OrderCaptureRequest $orderCaptureRequest, $payPalAuthAssertion, string $payPalPartnerAttributionId = '', $prefer = 'return=minimal'): Order
    {
        $path = "/orders/{$id}/capture";

        $headers = [];
        $headers['PayPal-Client-Metadata-Id'] = $payPalClientMetadataId;
        $headers['Content-Type'] = 'application/json';
        // deprecated: PayPal-Auth-Assertion is not needed any more
        // $headers['PayPal-Auth-Assertion'] = $payPalAuthAssertion;
        $headers['Prefer'] = $prefer;

        if ($payPalPartnerAttributionId) {
            $headers['PayPal-Partner-Attribution-Id'] = $payPalPartnerAttributionId;
        }

        $body = json_encode($orderCaptureRequest, true);
        $response = $this->send('POST', $path, [], $headers, $body);
        $jsonData = json_decode($response->getBody(), true);
        return new Order($jsonData);
    }

    /**
     * Saves an order. Supports multiple authorize and capture.
     *
     * @param $payPalClientMetadataId string
     *
     * @param $id string The ID of the order to save.
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
     * @return Order
     */
    public function saveOrder($payPalClientMetadataId, $id, string $payPalPartnerAttributionId = '', $prefer = 'return=minimal'): Order
    {
        $path = "/orders/{$id}/save";

        $headers = [];
        $headers['PayPal-Client-Metadata-Id'] = $payPalClientMetadataId;
        $headers['Prefer'] = $prefer;

        if ($payPalPartnerAttributionId) {
            $headers['PayPal-Partner-Attribution-Id'] = $payPalPartnerAttributionId;
        }

        $body = null;
        $response = $this->send('POST', $path, [], $headers, $body);
        $jsonData = json_decode($response->getBody(), true);
        return new Order($jsonData);
    }

    /**
     * Voids an order, by ID.
     *
     * @param $payPalClientMetadataId string
     *
     * @param $id string The ID of the order which to void.
     *
     * @param $prefer string The preferred server response upon successful completion of the request. Value
     * is:<ul><li><code>return=minimal</code>. The server returns a minimal response to optimize communication
     * between the API caller and the server. A minimal response includes the <code>id</code>, <code>status</code>
     * and HATEOAS links.</li><li><code>return=representation</code>. The server returns a complete resource
     * representation, including the current state of the resource.</li></ul>
     *
     * @throws ApiException
     * @return void
     */
    public function voidOrder($payPalClientMetadataId, $id, $prefer = 'return=minimal'): void
    {
        $path = "/orders/{$id}/void";

        $headers = [];
        $headers['PayPal-Client-Metadata-Id'] = $payPalClientMetadataId;
        $headers['Prefer'] = $prefer;


        $body = null;
        $response = $this->send('POST', $path, [], $headers, $body);
    }

    /**
     * Get the payment context data required for processing payments for an order.
     *
     * @param $orderId string The order id for which payment context data is requested.
     *
     * @throws ApiException
     * @return PaymentContextData
     */
    public function getPaymentContextForAnOrder($orderId): PaymentContextData
    {
        $path = "/payment-context";


        $params = [];
        $params['order_id'] = $orderId;

        $body = null;
        $response = $this->send('GET', $path, $params, [], $body);
        $jsonData = json_decode($response->getBody(), true);
        return new PaymentContextData($jsonData);
    }

    /**
     * Update the details of payment(s) processed for an order.
     *
     * @param $id string The ID of the order for which to update payment details.
     *
     * @param $paymentDetails mixed
     *
     * @throws ApiException
     * @return void
     */
    public function updatePaymentDetailsForTheOrder($id, PaymentDetailsRequest $paymentDetails): void
    {
        $path = "/orders/{$id}/update-paymentDetails";

        $headers = [];
        $headers['Content-Type'] = 'application/json';


        $body = json_encode($paymentDetails, true);
        $response = $this->send('POST', $path, [], $headers, $body);
    }

    /**
     * This api is will create external payment session with 3rd party payment gateway system.
     *
     * @param $xPAYPALSECURITYCONTEXT string Contains the credentials to authenticate a user agent with a server.
     *
     * @param $paypalApplicationContext string Contains application permission to execute operation.
     *
     * @param $paymentSessionRequest mixed
     *
     * @throws ApiException
     * @return PaymentSessionResponse
     */
    public function createPaymentSessionWith3rdPartyPaymentGateway($xPAYPALSECURITYCONTEXT, $paypalApplicationContext, PaymentSessionRequest $paymentSessionRequest): PaymentSessionResponse
    {
        $path = "/payment-sessions";

        $headers = [];
        $headers['X-PAYPAL-SECURITY-CONTEXT'] = $xPAYPALSECURITYCONTEXT;
        $headers['paypal-application-context'] = $paypalApplicationContext;
        $headers['Content-Type'] = 'application/json';


        $body = json_encode($paymentSessionRequest, true);
        $response = $this->send('POST', $path, [], $headers, $body);
        $jsonData = json_decode($response->getBody(), true);
        return new PaymentSessionResponse($jsonData);
    }
}
