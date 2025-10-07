# PayPal Module - Complete Documentation
## Order Processing & Payment Flows

**Module**: osc/paypal v2.6.2-rc.4
**OXID eSales AG**

---

## Table of Contents

1. [Overview](#overview)
2. [Order Creation Process](#order-creation-process)
3. [Payment Capture Process](#payment-capture-process)
4. [Authorization Process](#authorization-process)
5. [Refund Process](#refund-process)
6. [Cancel and Void Process](#cancel-and-void-process)
7. [Webhook System](#webhook-system)
8. [Payment Methods](#payment-methods)
9. [State Transitions](#state-transitions)
10. [Architecture Overview](#architecture-overview)

---

## Overview

The OXID PayPal module provides comprehensive integration with PayPal's v2 APIs, supporting multiple payment methods and complex order workflows. The module handles:

- **Payment Methods**: Standard PayPal, Credit/Debit Cards (ACDC), Pay Upon Invoice (PUI), SEPA, Apple Pay, Google Pay, and various European payment methods
- **Order Modes**: Direct capture, authorization with later capture, or manual capture
- **Webhook Processing**: Asynchronous order status updates from PayPal
- **Vaulting**: Save payment methods for future use
- **3D Secure**: Strong Customer Authentication (SCA) for card payments

---

## Order Creation Process

### Overview

Order creation is the first step in any payment flow. The process varies depending on the payment method but follows a general pattern:

1. Customer selects payment method
2. Shop creates PayPal order via API
3. PayPal order tracked in database
4. Customer authenticates payment
5. Shop order finalized

### Standard PayPal Flow

**Key Files**:
- `Controller/AjaxPaymentController.php` - AJAX order creation
- `Controller/OrderController.php` - Order finalization
- `Service/Payment.php` - Payment processing logic

**Step-by-Step Process**:

```
1. Customer clicks "Pay with PayPal" button
   → AjaxPaymentController::createPayPalOrder()

2. PayPal order created
   → Payment::doCreatePayPalOrder()
   → Creates OrderRequest with basket details
   → API call: orderService->createOrder()
   → Returns PayPal Order ID

3. Shop order created
   → OrderManager::createShopOrder()
   → Status: NOT_FINISHED
   → Linked to PayPal Order ID

4. Order tracked in database
   → Table: oscpaypal_order
   → Fields: oxorderid, oxpaypalorderid, oscpaypalstatus

5. Customer redirected to PayPal
   → Customer logs in and approves
   → PayPal redirects back to shop

6. Order finalized
   → OrderController::finalizepaypalsession()
   → Payment::doExecutePayPalPayment()
   → Captures or authorizes payment
   → Webhook confirms completion
```

**Key Decision Points**:

- **Intent**: CAPTURE (immediate) or AUTHORIZE (later capture)
  - Configured via module setting: `oscPayPalStandardCaptureStrategy`
  - Options: `directly`, `delivery`, `manually`

- **User Action**: PAY_NOW or CONTINUE
  - PAY_NOW: Payment captured immediately
  - CONTINUE: Customer can review before payment

### ACDC (Card Payment) Flow

Advanced Credit and Debit Card payments use hosted fields and 3D Secure authentication.

**Key Files**:
- `Controller/OrderController.php` - ACDC handling
- `Service/SCAValidator.php` - 3D Secure validation

**Step-by-Step Process**:

```
1. Customer enters card details in hosted fields
   → Client-side PayPal SDK handles card data
   → PCI compliance maintained (no card data in shop)

2. Shop order created in progress state
   → OrderController::createAcdcOrder()
   → Status: ORDER_STATE_ACDCINPROGRESS (700)

3. PayPal order created and patched
   → Payment::doCreatePatchedOrder()
   → Order patched with shop order details

4. 3D Secure verification (if required)
   → Client-side authentication challenge
   → Payment::verify3D() validates result
   → SCA contingency settings checked

5. Payment captured
   → OrderController::captureAcdcOrder()
   → Status: ORDER_STATE_ACDCCOMPLETED (750)

6. Order finalized
   → OrderController::finalizeacdc()
   → Waits for webhook (60 seconds)
   → If timeout: fallback to API fetch
```

**3D Secure (SCA) Options**:

- `SCA_ALWAYS`: Always require 3D Secure authentication
- `SCA_WHEN_REQUIRED`: Only when PayPal determines it's needed (recommended)
- `SCA_DISABLED`: Never require (legacy mode)

### Google Pay / Apple Pay Flow

**Google Pay**:
```
1. Google Pay button clicked
   → Native Google Pay sheet displayed
   → Customer selects card/account

2. Token returned to shop
   → OrderController::executeGooglePayOrder()
   → PayPal order already created by button

3. Order patched with shop details
   → AjaxPaymentController::updatePayPalOrder()

4. Payment captured
   → OrderController::captureGooglePayOrder()

5. Order finalized
   → OrderController::finalizeGooglePay()
   → Webhook completes process
```

**Apple Pay**:
```
1. Apple Pay button clicked
   → Native Apple Pay sheet (Safari/iOS only)
   → Biometric authentication (Touch ID/Face ID)

2. Order created
   → OrderController::createApplePayOrder()

3. Payment captured
   → OrderController::captureApplePayOrder()

4. Order finalized
   → OrderController::finalizeapplepay()
```

### PUI (Pay Upon Invoice) Flow

Invoice payment with bank transfer instructions sent after order.

**Step-by-Step Process**:

```
1. Customer selects PUI at checkout
   → Phone number required
   → Amount: €5 to €1,500
   → Germany only

2. PayPal order created with special settings
   → Payment::doExecutePuiPayment()
   → Intent: CAPTURE
   → Processing instruction: ORDER_COMPLETE_ON_PAYMENT_APPROVAL
   → Payment source: pay_upon_invoice
   → FraudNet session ID included

3. PayPal immediately approves
   → No customer redirect needed
   → Order status: COMPLETED

4. Webhook delivers bank details
   → Event: PAYMENT.CAPTURE.COMPLETED
   → Includes: IBAN, BIC, bank name, account holder, payment reference
   → Details stored in oscpaypal_order table

5. Email sent to customer
   → Email::sendPuiInfo()
   → Contains bank transfer instructions
   → Customer transfers money to Ratepay account
```

**Bank Details Fields**:
- `oscpaypalpuipaymentreference` - Payment reference number
- `oscpaypalpuiiban` - IBAN for transfer
- `oscpaypalpuibic` - BIC/SWIFT code
- `oscpaypalpuibankname` - Bank name
- `oscpaypalpuiaccountholdername` - Account holder name

### uAPM (Universal Alternative Payment Methods) Flow

European payment methods like iDEAL, Giropay, SEPA, etc.

**Supported Methods**:
- iDEAL (Netherlands)
- EPS (Austria)
- Bancontact (Belgium)
- BLIK (Poland)
- Przelewy24 (Poland)
- SEPA Direct Debit

**Step-by-Step Process**:

```
1. Customer selects payment method
   → Country-specific options displayed
   → Amount limits apply (typically €1-€10,000)

2. Order created without payment source
   → Payment::doCreateUAPMOrder()
   → Intent: CAPTURE
   → Shop order number set before creation

3. Order confirmed with payment source
   → Payment::doConfirmUAPM()
   → Adds specific payment source to order

4. Customer redirected to payment provider
   → Provider-specific authentication
   → Examples: Bank login (iDEAL), mobile app (BLIK)

5. Customer returns after approval
   → Order status: SESSIONPAYMENT_INPROGRESS (500)
   → Changed to: WAIT_FOR_WEBHOOK_EVENTS (600)

6. Webhook triggers capture
   → Event: CHECKOUT.ORDER.APPROVED
   → Payment::doCapturePayPalOrder() called
   → Order completed

7. Final webhook confirms
   → Event: CHECKOUT.ORDER.COMPLETED
   → Order marked as paid
```

### Vaulted Payment Flow

Using saved payment methods for returning customers.

```
1. Customer selects saved payment method
   → Vaulted payment token retrieved
   → No redirect needed

2. Order created with vault token
   → Payment source includes vault ID
   → Customer ID referenced

3. Payment captured immediately
   → No additional authentication required
   → Webhook confirms

4. Order completed
   → Fast checkout experience
```

---

## Payment Capture Process

### Capture Strategies

The module supports three capture strategies configured via module settings:

#### 1. Direct Capture (Immediately)

**Configuration**: `oscPayPalStandardCaptureStrategy = 'directly'`

**Flow**:
```
Order Created (Intent: CAPTURE)
  ↓
Payment Captured Immediately
  ↓
Webhook: PAYMENT.CAPTURE.COMPLETED
  ↓
Order Marked as Paid
```

**Usage**: Suitable for digital goods, services, or when immediate payment is required.

**Implementation**:
- PayPal order created with `intent: CAPTURE`
- Capture happens during order approval
- No separate capture API call needed
- Webhook confirms completion

#### 2. Capture on Delivery

**Configuration**: `oscPayPalStandardCaptureStrategy = 'delivery'`

**Flow**:
```
Order Created (Intent: AUTHORIZE)
  ↓
Payment Authorized (funds reserved)
  ↓
Admin Sends Order (clicks "Send Now")
  ↓
Controller/Admin/OrderOverview::sendorder()
  ↓
AdminOrderTrait::capturePayPalOrder()
  ↓
API: captureAuthorizedPayment()
  ↓
Webhook: PAYMENT.CAPTURE.COMPLETED
  ↓
Order Marked as Paid
```

**Usage**: Suitable for physical goods - capture when shipped.

**Key Features**:
- Authorization valid: 3 days
- Auto-reauthorization if expired (within 29 days)
- Maximum capture window: 29 days
- After 29 days: order must be voided

#### 3. Manual Capture

**Configuration**: `oscPayPalStandardCaptureStrategy = 'manually'`

**Flow**:
```
Order Created (Intent: AUTHORIZE)
  ↓
Payment Authorized
  ↓
Admin Reviews Order
  ↓
Admin Clicks "Capture" in Backend
  ↓
Controller/Admin/PayPalOrderController::capturePayPalOrder()
  ↓
API: captureAuthorizedPayment()
  ↓
Webhook: PAYMENT.CAPTURE.COMPLETED
  ↓
Order Marked as Paid
```

**Usage**: Suitable for custom validation workflows.

### Capture Implementation

**Primary Method**: `Payment::doCapturePayPalOrder()`

**Logic Flow**:
```php
if (order intent is AUTHORIZE) {
    // Check if authorization needed first
    if (order status is APPROVED) {
        → authorizePaymentForOrder()
        → Store authorization ID
    }

    // Check authorization age
    if (authorization older than 3 days AND younger than 29 days) {
        → reauthorizeAuthorizedPayment()
    }

    if (authorization older than 29 days) {
        → Throw error: Cannot capture
    }

    // Capture the authorization
    → captureAuthorizedPayment(authorizationId)

} else if (order intent is CAPTURE) {
    // Direct capture
    if (order status is not COMPLETED) {
        → capturePaymentForOrder(orderId)
    }
}
```

**Authorization Time Limits**:
- **Initial validity**: 3 days from authorization
- **Reauthorization**: Extends validity by 3 days
- **Maximum window**: 29 days total
- **Constants**:
  - `PAYPAL_AUTHORIZATION_VALIDITY = 3 days`
  - `PAYPAL_MAXIMUM_TIME_FOR_CAPTURE = 29 days`

### Vaulting During Capture

When a payment is captured, PayPal may vault the payment method for future use:

```php
// Check capture response for vault information
if (vault status is VAULTED) {
    → Store PayPal customer ID in user record
    → oxuser__oscpaypalcustomerid = customer_id
    → User can reuse payment method in future
}
```

---

## Authorization Process

### Overview

Authorization reserves funds without capturing them, allowing for order validation before payment capture.

### Authorization Flow

**Primary Method**: `Payment::doAuthorizePayment()`

**Step-by-Step**:
```
1. PayPal order created with intent: AUTHORIZE
   → Order status: CREATED

2. Customer approves payment
   → Order status: APPROVED
   → Funds not yet reserved

3. Authorization triggered
   → API: authorizePaymentForOrder()
   → Authorization ID returned
   → Funds reserved on customer's account

4. Authorization tracked
   → Transaction type: authorization
   → Status: CREATED or PENDING
   → Valid for 3 days

5. Shop order remains NOT_FINISHED
   → Awaits capture to complete
```

**Key Differences from Capture**:
- Funds reserved, not captured
- Customer sees pending charge
- Funds released if not captured within 29 days
- Can be voided before capture

### Authorization States

**PayPal Authorization Statuses**:
- `CREATED` - Authorization successfully created
- `CAPTURED` - Authorization has been captured
- `DENIED` - Authorization was denied by processor
- `EXPIRED` - Authorization expired (not captured in time)
- `PARTIALLY_CAPTURED` - Part of authorization captured
- `VOIDED` - Authorization manually voided
- `PENDING` - Authorization pending (rare)

### Authorization Validity and Reauthorization

**Time Windows**:
```
Day 0: Authorization created (valid)
  ↓
Day 1-3: Authorization valid
  ↓
Day 3: Authorization expires
  ↓
Reauthorization triggered automatically
  ↓
Day 4-6: Reauthorization valid
  ↓
...continues...
  ↓
Day 29: Maximum window reached
  ↓
After Day 29: Cannot capture, order must be voided
```

**Reauthorization Logic**:
```php
// Calculate time since last authorization
time_since_auth = current_time - authorization_update_time

if (time_since_auth > 3 days AND time_since_auth < 29 days) {
    // Reauthorization needed
    → API: reauthorizeAuthorizedPayment(authorizationId)
    → New 3-day validity window
} else if (time_since_auth >= 29 days) {
    // Too old to capture
    → Throw error: Authorization expired
}
```

### 3D Secure Verification

For card payments (ACDC and Google Pay), 3D Secure authentication may be required:

**Method**: `Payment::verify3D()`

**Verification Logic**:
```
1. Check if payment method requires verification
   → ACDC or Google Pay only

2. Check SCA contingency setting
   → SCA_ALWAYS: Must have successful 3DS result
   → SCA_WHEN_REQUIRED: Check if PayPal required it
   → SCA_DISABLED: Skip verification

3. Validate authentication result
   → Check liability_shift: POSSIBLE or NO
   → Check enrollment_status: Y (enrolled)
   → Check authentication_status: Y (authenticated)

4. Return verification result
   → true: Payment can proceed
   → false: Payment declined, customer must retry
```

---

## Refund Process

### Overview

Refunds allow returning funds to customers for completed payments. Both full and partial refunds are supported.

### Refund Flow

**Controller**: `Controller/Admin/PayPalOrderController.php`

**Step-by-Step Process**:
```
1. Admin opens order in backend
   → Navigate to order overview
   → PayPal tab shows payment details

2. Admin initiates refund
   → Clicks "Refund" button
   → Enters refund amount (or selects full refund)
   → Optionally adds note to payer
   → Optionally adds invoice ID

3. Refund request created
   → PayPalOrderController::refund()
   → RefundRequest object created
   → Amount, currency, and note included

4. API call to PayPal
   → API: refundCapturedPayment(captureId, refundRequest)
   → Refund ID returned
   → Status: PENDING or COMPLETED

5. Refund tracked in database
   → Transaction type: refund
   → Linked to original capture
   → Status stored

6. Webhook confirms refund
   → Event: PAYMENT.CAPTURE.REFUNDED
   → Refund amount included
   → Capture status updated

7. Order status updated
   → If full refund: Status = REFUNDED
   → If partial refund: Status = PARTIALLY_REFUNDED
   → Original order remains paid (oxpaid not changed)
```

### Refund Types

#### Full Refund

Refunds the entire capture amount.

**Process**:
```
Admin selects "Refund All"
  ↓
RefundRequest created without amount
  ↓
PayPal refunds full capture amount
  ↓
Capture status: REFUNDED
```

#### Partial Refund

Refunds a portion of the capture amount.

**Process**:
```
Admin enters specific amount (e.g., €50 of €100)
  ↓
RefundRequest created with amount
  ↓
PayPal refunds specified amount
  ↓
Capture status: PARTIALLY_REFUNDED
  ↓
Remaining amount available for additional refunds
```

**Multiple Partial Refunds**:
- Allowed until full capture amount refunded
- Each refund tracked separately
- Running total maintained

### Refund Eligibility

**Method**: `PayPalOrderController::eligibleForRefund()`

**Criteria**:
```
Capture status must be:
  - CAPTURED
  - PARTIALLY_REFUNDED
  - COMPLETED

AND

Remaining refund amount > 0

Remaining amount = Captured amount - Total refunded amount
```

### Refund Tracking

**Database Entries**:
- Original capture: Transaction type = `capture`
- Each refund: Transaction type = `refund`
- Multiple refunds for one capture: Multiple refund entries

**Example Timeline**:
```
Transaction 1: Capture €100 (type: capture)
Transaction 2: Refund €30 (type: refund)
Transaction 3: Refund €20 (type: refund)
Transaction 4: Refund €50 (type: refund)
Total refunded: €100 (fully refunded)
```

### Webhook Handling

**Handler**: `PaymentCaptureRefundedHandler`

**Logic**:
```
1. Webhook received
   → Event: PAYMENT.CAPTURE.REFUNDED
   → Resource: Refund object

2. Extract transaction ID
   → Transaction ID is the capture ID (not refund ID)

3. Find order by transaction ID
   → Query: oscpaypal_order.oscpaypaltransactionid

4. Calculate refund status
   if (refund amount < total order sum):
       → Status: PARTIALLY_REFUNDED
   else:
       → Status: REFUNDED

5. Update capture record
   → Update transaction record with new status

6. Cleanup unfinished orders
   → Triggered after each webhook
```

---

## Cancel and Void Process

### Order Cancellation Types

#### 1. User-Initiated Cancel (Before Completion)

Customer cancels during payment process.

**Flow**:
```
Customer on PayPal page
  ↓
Clicks "Cancel and return to shop"
  ↓
OrderController::cancelpaypalsession()
  ↓
PaymentService::removeTemporaryOrder()
  ↓
Shop order canceled (if exists)
  ↓
Session cleaned up
  ↓
Redirect to payment page
```

**Implementation**:
```php
public function cancelpaypalsession(string $errorcode = null): string
{
    // Remove temporary order
    $this->paymentService->removeTemporaryOrder();

    // Clean up session
    PayPalSession::unsetPayPalOrderId();

    // Redirect back to payment selection
    return $errorcode ? 'payment?payerror=2' : 'payment';
}
```

#### 2. Shop-Side Order Cancel

Admin or customer cancels order after creation.

**Flow**:
```
Cancel request initiated
  ↓
AjaxPaymentController::cancelShopOrder()
  ↓
Permission check performed
  ↓
Order loaded
  ↓
Order::cancelOrder() called
  ↓
Order::markOrderPaymentFailed()
  ↓
Session cleaned up
  ↓
PayPal order ID removed
```

**Security Check**:
```php
// Verify user has permission to cancel
$this->permissionsCheck($shopOrderId, 'User has no permission to cancel order');

// Permission granted if:
// - User owns the order
// - User is admin
// - Session matches order session
```

#### 3. Void Authorization

**Note**: The module does not implement explicit void functionality. Authorizations automatically expire if not captured:

- **After 3 days**: Authorization expires (can be reauthorized)
- **After 29 days**: Authorization cannot be reauthorized
- **Funds released**: Customer's funds returned automatically

### Cleanup of Unfinished Orders

**Method**: `OrderRepository::cleanUpNotFinishedOrders()`

**Purpose**: Automatically cancel orders that were never completed.

**Configuration**:
- Setting: `oscPayPalCleanUpNotFinishedOrdersAutomaticlly` (boolean)
- Timeout: `oscPayPalStartTimeCleanUpOrders` (minutes, default: 60)

**Process**:
```
1. Check if cleanup enabled
   if (not enabled):
       → Skip cleanup

2. Find unfinished orders
   → Status: NOT_FINISHED
   → Payment type: oscpaypal*
   → Age: Older than configured timeout
   → Not already canceled (oxstorno = 0)

3. Cancel each order
   → Load order
   → Order::cancelOrder()
   → Order marked as canceled

4. Log cleanup
   → Debug log: Orders cleaned up
```

**Trigger Points**:
- After each webhook processing
- Can be run as scheduled task
- Admin can trigger manually

---

## Webhook System

### Overview

Webhooks are asynchronous notifications from PayPal about order status changes. They ensure order status stays synchronized with PayPal even if customer closes browser or network issues occur.

### Webhook Architecture

**Components**:

1. **WebhookController** - Entry point for webhook requests
2. **RequestReader** - Reads webhook HTTP request
3. **EventVerifier** - Verifies webhook signature
4. **RequestHandler** - Processes verified webhook
5. **EventDispatcher** - Routes to appropriate handler
6. **Event Handlers** - Handle specific event types

**Flow**:
```
PayPal sends webhook POST request
  ↓
WebhookController::init()
  ↓
RequestReader reads raw POST data and headers
  ↓
EventVerifier verifies signature
  ↓
RequestHandler::process()
  ↓
EventDispatcher dispatches to handler
  ↓
Specific handler processes event
  ↓
Order status updated
  ↓
Return HTTP 200 (success)
```

### Webhook Events Supported

**Event Mapping**:

| Event Type | Handler | Purpose |
|------------|---------|---------|
| `PAYMENT.CAPTURE.COMPLETED` | PaymentCaptureCompletedHandler | Capture successfully completed |
| `CHECKOUT.ORDER.COMPLETED` | CheckoutOrderCompletedHandler | Order fully completed |
| `CHECKOUT.ORDER.APPROVED` | CheckoutOrderApprovedHandler | Order approved, may need capture |
| `CHECKOUT.PAYMENT-APPROVAL.REVERSED` | CheckoutPaymentApprovalReverseHandler | Customer reversed approval |
| `PAYMENT.CAPTURE.DENIED` | PaymentCaptureDeniedHandler | Capture was denied |
| `PAYMENT.CAPTURE.REFUNDED` | PaymentCaptureRefundedHandler | Refund processed |

### Webhook Event Handlers

#### PAYMENT.CAPTURE.COMPLETED

**Purpose**: Confirms that a payment capture completed successfully.

**Flow**:
```
1. Webhook received
   → Extract PayPal order ID from supplementary_data
   → Extract transaction ID (capture ID)

2. Find shop order
   → Query by PayPal order ID
   → Load order object

3. Mark order as paid
   → Order::markOrderPaid()
   → Sets oxpaid timestamp
   → Sets oxtransstatus = 'OK'

4. Set transaction ID
   → Order::setTransId(captureId)
   → Updates oxtransid field

5. Update PayPal order record
   → Status: COMPLETED
   → Transaction ID stored
   → Transaction type: capture

6. Send order email (if not sent)
   → Order confirmation email
   → Invoice (if configured)
```

**Special Cases**:
- **PUI Payment**: Extracts bank details from webhook
- **Vaulted Payment**: Stores customer vault ID

#### CHECKOUT.ORDER.COMPLETED

**Purpose**: Order completed (alternative event for some payment methods).

**Difference from PAYMENT.CAPTURE.COMPLETED**:
- Order ID directly in payload (not in supplementary_data)
- Transaction ID from purchase_units[0].payments.captures[0].id
- Used by some uAPM methods

#### CHECKOUT.ORDER.APPROVED

**Purpose**: Order approved by customer, may need capture.

**Flow**:
```
1. Webhook received
   → Order status: APPROVED

2. Check if capture needed
   if (intent is CAPTURE AND status is not COMPLETED):
       → Capture needed

3. Trigger capture
   → Payment::doCapturePayPalOrder()
   → Capture payment

4. Set order number
   → Order::setOrderNumber()
   → Assigns order number if not set

5. Wait for completion webhook
   → CHECKOUT.ORDER.COMPLETED or PAYMENT.CAPTURE.COMPLETED
   → Final status update
```

**Use Cases**:
- uAPM methods (iDEAL, Giropay, etc.)
- Some wallet methods
- When capture on approval is configured

#### CHECKOUT.PAYMENT-APPROVAL.REVERSED

**Purpose**: Customer or PayPal reversed the payment approval.

**Flow**:
```
1. Webhook received
   → Payment approval was reversed

2. Mark order as failed
   → Order::markOrderPaymentFailed()
   → oxtransstatus = 'ERROR'

3. Set transaction ID
   → Order::setTransId()

4. Update PayPal order status
   → Status: REVERSED or FAILED
```

**Reasons for Reversal**:
- Customer disputed charge immediately
- Fraud detected by PayPal
- Payment source invalid

#### PAYMENT.CAPTURE.DENIED

**Purpose**: Payment capture was denied by payment processor.

**Flow**:
```
1. Webhook received
   → Capture attempt denied

2. Mark order as failed
   → Order::markOrderPaymentFailed()
   → oxtransstatus = 'ERROR'

3. Set transaction ID
   → Even though failed, store for reference

4. Log error
   → Debug log with denial reason
```

**Reasons for Denial**:
- Insufficient funds
- Payment method declined
- Risk/fraud check failed
- Card expired or invalid

#### PAYMENT.CAPTURE.REFUNDED

**Purpose**: Refund has been processed.

**Flow**:
```
1. Webhook received
   → Refund details in payload

2. Extract capture ID
   → Transaction ID is the capture ID being refunded

3. Find order
   → Query by transaction ID

4. Calculate refund status
   if (refunded amount < total order amount):
       → Status: PARTIALLY_REFUNDED
   else:
       → Status: REFUNDED

5. Update capture record
   → Update status in oscpaypal_order

6. Track refund
   → New transaction entry
   → Type: refund
   → Links to original capture
```

### Webhook Signature Verification

**Security**: Every webhook is verified to ensure it came from PayPal.

**Process**:
```
1. Extract headers from request
   → PAYPAL-TRANSMISSION-ID
   → PAYPAL-TRANSMISSION-TIME
   → PAYPAL-TRANSMISSION-SIG
   → PAYPAL-CERT-URL
   → PAYPAL-AUTH-ALGO

2. Get webhook ID from configuration
   → oscPayPalWebhookId (or sandbox variant)

3. Construct verification request
   → Include headers and raw body
   → POST to PayPal verification endpoint

4. PayPal verifies signature
   → Returns VERIFICATION_STATUS: SUCCESS or FAILURE

5. Process webhook if verified
   → If verification fails: throw exception
   → If verified: continue processing
```

**Why Verification is Critical**:
- Prevents spoofed webhooks
- Ensures data integrity
- Prevents fraudulent order completion

### Webhook Retry Mechanism

**PayPal Retry Behavior**:
- If shop returns non-200 status: PayPal retries
- Retry pattern: 25 attempts over 3 days
- Exponential backoff between retries

**Module Handling**:
```
try {
    // Process webhook
    processEvent($webhook);

    // Return 200 even if processing failed
    return '200 OK';

} catch (WebhookEventRetryException $e) {
    // Shop wants PayPal to retry
    return '500 Internal Server Error';

} catch (Exception $e) {
    // Log error but return 200 to prevent retry
    log($e);
    return '200 OK';
}
```

**Idempotency**:
- Webhooks may be delivered multiple times
- Handlers must be idempotent
- Database updates use "upsert" logic where possible

### Webhook Timeout and Fallback

**Problem**: Sometimes webhooks are delayed or don't arrive.

**Solution**: Timeout with fallback mechanism.

**Flow**:
```
Order completed (e.g., ACDC payment)
  ↓
Status: WAIT_FOR_WEBHOOK_EVENTS (600)
  ↓
Wait for webhook (60 seconds)
  ↓
If webhook arrives:
    → Process normally
    → Order completed
  ↓
If timeout reached:
    → Status: TIMEOUT_FOR_WEBHOOK_EVENTS (900)
    → Redirect to fallback finalization
    → Fetch order details from PayPal API
    → Complete order based on API status
```

**Fallback URL Example**:
```
/order/finalizeacdc?fallbackfinalize=1
```

**Implementation**:
```php
if ($forceFetchDetails || $isTimeout) {
    // Fetch current status from PayPal API
    $payPalApiOrder = $this->fetchOrderDetails($payPalOrderId);

    // Check if completed
    if ($this->isPayPalOrderCompleted($payPalApiOrder)) {
        // Complete order manually
        $this->markOrderPaid();
        $this->setTransId($transactionId);
    }
}
```

---

## Payment Methods

### Standard PayPal

**Payment ID**: `oscpaypal`

**Description**: Traditional PayPal wallet payment with redirect to PayPal.

**Features**:
- **Customer has PayPal account**: Login and approve
- **Customer without account**: Can pay as guest with card
- **Vaulting**: Can save PayPal account for future use
- **Express checkout**: Available on product, cart, mini-cart pages
- **Authorization modes**: Supports both capture and authorize

**Flow Variants**:

1. **Express Checkout**:
   - Button on product/cart page
   - No shipping/billing address required initially
   - PayPal provides shipping address
   - Completes on shop

2. **Standard Checkout**:
   - Selected at payment page
   - Address already collected
   - Redirects to PayPal
   - Returns and completes

3. **Vaulted Payment**:
   - Customer logged in
   - Selects saved PayPal account
   - No redirect needed
   - One-click payment

**Configuration**:
- Capture strategy: directly, delivery, manually
- User action: PAY_NOW or CONTINUE
- Show Pay Later button
- Button placement (product, cart, mini-cart)

**Pros**:
- Trusted brand
- Buyer protection
- Fast checkout
- Wide acceptance

**Cons**:
- Redirect required (unless vaulted)
- User must have or create PayPal account

---

### ACDC (Advanced Credit and Debit Card)

**Payment ID**: `oscpaypal_acdc`

**Description**: Credit and debit card payment with hosted fields (no redirect).

**Features**:
- **PCI compliant**: Card data never touches shop server
- **Hosted fields**: PayPal hosts card input fields
- **3D Secure**: Strong Customer Authentication (SCA)
- **Vaulting**: Save cards for future use
- **No redirect**: Seamless checkout experience
- **Card brands**: Visa, Mastercard, American Express, Discover, etc.

**Flow**:
```
Customer enters card details
  ↓
Client-side validation
  ↓
3D Secure challenge (if required)
  ↓
Order created and captured
  ↓
Webhook confirms
  ↓
Order completed
```

**3D Secure (SCA) Configuration**:

- **SCA_ALWAYS**:
  - Always require 3D Secure
  - Maximum security
  - May reduce conversion (more friction)

- **SCA_WHEN_REQUIRED** (Recommended):
  - PayPal determines when SCA needed
  - Based on transaction risk analysis
  - Balances security and convenience

- **SCA_DISABLED**:
  - Never require 3D Secure
  - Legacy mode
  - May violate PSD2 regulations in Europe

**3D Secure Verification**:
```
Card entered → 3DS challenge → Authentication result
  ↓                               ↓
  Liability shift?                Status: Y/N/A/U
  Enrollment status
  Authentication status
  ↓
  Verified → Proceed
  Failed → Decline payment
```

**Card Vaulting**:
- Checkbox: "Save card for future purchases"
- Requires customer consent
- Card token stored (not actual card number)
- Future payments: instant capture with token

**Eligibility**:
- Setting: `oscPayPalAcdcEligibility`
- Must be enabled by PayPal for merchant account
- Requires advanced compliance

**Pros**:
- No redirect (seamless)
- Supports all major cards
- 3D Secure compliant
- Can vault cards

**Cons**:
- Requires PayPal approval
- More complex integration
- 3DS may add friction

---

### PUI (Pay Upon Invoice - Rechnungskauf)

**Payment ID**: `oscpaypal_pui`

**Description**: Invoice payment for German customers - pay later via bank transfer.

**Features**:
- **Country**: Germany only
- **Amount limits**: €5 minimum, €1,500 maximum
- **Payment term**: 30 days to pay
- **Risk assessment**: PayPal assumes risk of non-payment
- **Bank transfer**: Customer transfers to Ratepay (PayPal partner)

**Requirements**:
- Valid German billing address
- Phone number (required for fraud check)
- Amount within limits
- Risk assessment passed

**Flow**:
```
Customer selects PUI
  ↓
Enters phone number
  ↓
Order created (with FraudNet ID)
  ↓
PayPal performs risk assessment
  ↓
If approved:
    → Order immediately completed
    → Webhook delivers bank details
    → Email sent with payment instructions
    ↓
If denied:
    → Payment declined
    → Customer must select different method
```

**Bank Details Provided**:
- **IBAN**: DE##############
- **BIC**: GENODEFFXXX
- **Bank name**: Ratepay Bank
- **Account holder**: Ratepay GmbH
- **Payment reference**: RP-####-####-#### (unique per order)

**Email to Customer**:
```
Your order has been placed successfully.

Please transfer the amount to:
  IBAN: DE##############
  BIC: GENODEFFXXX
  Bank: Ratepay Bank
  Account holder: Ratepay GmbH
  Payment reference: RP-####-####-####

Please use the payment reference in your transfer.
Payment due within 30 days.
```

**FraudNet Integration**:
- Client-side JavaScript collects device fingerprint
- Session ID sent with order creation
- PayPal uses for fraud detection

**Pros**:
- Pay after receiving goods
- No credit card needed
- Familiar payment method in Germany
- PayPal assumes risk

**Cons**:
- Germany only
- Amount limits
- Phone number required
- May be declined for risky customers

---

### Google Pay

**Payment ID**: `oscpaypal_googlepay`

**Description**: Google's digital wallet for fast mobile checkout.

**Features**:
- **Native integration**: Google Pay sheet (no redirect to PayPal)
- **Card tokenization**: Secure card storage
- **Fast checkout**: One tap to pay
- **Mobile optimized**: Best on Android devices
- **Works on**: Chrome browser (desktop/mobile), Android apps

**Flow**:
```
Customer clicks Google Pay button
  ↓
Google Pay sheet opens (native)
  ↓
Customer selects card/account
  ↓
Authenticates (biometrics if available)
  ↓
Token returned to shop
  ↓
Order created with token
  ↓
Payment captured
  ↓
Webhook confirms
  ↓
Order completed
```

**Button Placement**:
- Product page: Quick buy
- Cart page: Express checkout
- Payment page: Alternative to standard methods

**3D Secure**:
- Google Pay may perform 3DS
- Verification result included in token
- Shop validates result

**Address Handling**:
- Google Pay provides shipping address
- Shop can use or ignore (configuration)
- Setting: `oscPayPalUseGooglePayAddress`

**Eligibility**:
- Setting: `oscPayPalGooglePayEligibility`
- Must be enabled by PayPal
- Requires merchant verification

**Pros**:
- Very fast checkout
- Familiar to Android users
- Secure tokenization
- No redirect

**Cons**:
- Limited to Chrome/Android
- Requires PayPal approval
- Cannot vault (tokens are one-time use)

---

### Apple Pay

**Payment ID**: `oscpaypal_applepay`

**Description**: Apple's digital wallet for iOS, macOS, and Safari.

**Features**:
- **Native integration**: Apple Pay sheet (no redirect)
- **Biometric authentication**: Touch ID / Face ID
- **Tokenization**: Secure card storage by Apple
- **Works on**: Safari browser (iOS/macOS), iOS apps

**Requirements**:
- **Merchant ID**: Registered with Apple
- **Certificate**: Apple developer certificate
- **Domain verification**: Special file in .well-known directory
- **HTTPS**: Required for Apple Pay

**Domain Verification**:
```
File location: /.well-known/apple-developer-merchantid-domain-association
Purpose: Proves domain ownership to Apple
Separate files for: Sandbox and Live environments
```

**Flow**:
```
Customer clicks Apple Pay button
  ↓
Apple Pay sheet opens (native)
  ↓
Customer authenticates (Touch ID/Face ID)
  ↓
Card selected
  ↓
Token returned to shop
  ↓
Order created with token
  ↓
Payment captured
  ↓
Order completed
```

**Button Placement**:
- Product page
- Cart page
- Payment page
- Only shown on compatible devices

**Eligibility**:
- Setting: `oscPayPalApplePayEligibility`
- Must be enabled by PayPal
- Requires Apple developer account
- Certificate setup required

**Pros**:
- Extremely fast checkout
- High trust (Apple brand)
- Biometric security
- No redirect

**Cons**:
- Safari/iOS only
- Certificate management required
- Complex setup
- Cannot vault

---

### SEPA Direct Debit

**Payment ID**: `oscpaypal_sepa`

**Description**: Bank account direct debit for Europe.

**Features**:
- **SEPA countries**: Eurozone countries
- **Direct debit**: Funds withdrawn from bank account
- **Mandate**: Customer authorizes debit
- **Settlement time**: 3-5 business days

**Flow**:
```
Customer selects SEPA
  ↓
Enters IBAN
  ↓
Accepts SEPA mandate
  ↓
Order created
  ↓
Mandate stored by PayPal
  ↓
Order completed
  ↓
Debit occurs within days
```

**SEPA Mandate**:
- Legal authorization for direct debit
- Customer signs electronically
- Stored by PayPal for compliance
- Can be revoked by customer

**Express Checkout**:
- SEPA button on product/cart page
- Quick mandate acceptance
- Fast checkout flow

**Pros**:
- No card needed
- Familiar in Europe
- Can be vaulted
- Express checkout

**Cons**:
- Delayed settlement
- Can be charged back by customer
- SEPA countries only

---

### uAPM (Universal Alternative Payment Methods)

**Description**: Country-specific popular payment methods integrated via PayPal.

#### iDEAL (Netherlands)

**Payment ID**: `oscpaypal_ideal`

**Features**:
- Netherlands' most popular payment method
- Direct bank transfer
- Instant confirmation
- No credit card needed

**Flow**:
```
Customer selects iDEAL
  ↓
Selects bank from list
  ↓
Redirected to bank website
  ↓
Logs into online banking
  ↓
Confirms payment
  ↓
Redirected back to shop
  ↓
Webhook confirms payment
  ↓
Order completed
```

#### Giropay (Germany)

**Payment ID**: `oscpaypal_giropay`

**Status**: ⚠️ Deprecated (replaced by other methods)

#### EPS (Austria)

**Payment ID**: `oscpaypal_eps`

**Features**:
- Austrian bank transfer
- Similar to iDEAL
- Instant confirmation

#### Bancontact (Belgium)

**Payment ID**: `oscpaypal_bancontact`

**Features**:
- Belgium's national payment method
- Card-based
- High acceptance

#### BLIK (Poland)

**Payment ID**: `oscpaypal_blik`

**Features**:
- Poland's mobile payment system
- 6-digit code from banking app
- Very fast

**Flow**:
```
Customer selects BLIK
  ↓
Opens banking app on phone
  ↓
Generates 6-digit BLIK code
  ↓
Enters code on shop
  ↓
Confirms in app
  ↓
Payment completed instantly
```

#### Przelewy24 (Poland)

**Payment ID**: `oscpaypal_przelewy24`

**Features**:
- Polish online banking
- Multiple banks supported
- Popular in Poland

**Common uAPM Characteristics**:
- Country-specific
- Redirect to payment provider
- Instant or very fast confirmation
- Currency limitations (usually EUR or local currency)
- Amount ranges (typically €1 - €10,000)
- No vaulting (one-time use)
- Webhook-dependent completion

---

## State Transitions

### Shop Order States (oxtransstatus)

**Standard OXID States**:
- `NOT_FINISHED` - Order created, awaiting completion
- `OK` - Order successfully completed and paid
- `ERROR` - Error occurred during order processing

**Custom PayPal States**:
- `500` - `ORDER_STATE_SESSIONPAYMENT_INPROGRESS` - uAPM payment in progress
- `600` - `ORDER_STATE_WAIT_FOR_WEBHOOK_EVENTS` - Waiting for webhook confirmation
- `700` - `ORDER_STATE_ACDCINPROGRESS` - ACDC payment in progress
- `750` - `ORDER_STATE_ACDCCOMPLETED` - ACDC payment completed
- `800` - `ORDER_STATE_NEED_CALL_ACDC_FINALIZE` - ACDC needs finalization call
- `900` - `ORDER_STATE_TIMEOUT_FOR_WEBHOOK_EVENTS` - Webhook timeout, fallback needed
- `2` - `ORDER_STATE_PAYMENTERROR` - Payment error occurred

### PayPal Order States (oscpaypalstatus)

**Order Level States**:
- `CREATED` - Order created at PayPal
- `SAVED` - Order saved for later processing
- `APPROVED` - Customer approved, awaiting capture
- `VOIDED` - Order voided/canceled
- `COMPLETED` - Order completed successfully
- `PAYER_ACTION_REQUIRED` - Customer action needed

**Capture States**:
- `COMPLETED` - Capture successful
- `DECLINED` - Capture declined
- `PARTIALLY_REFUNDED` - Partial refund issued
- `PENDING` - Capture pending
- `REFUNDED` - Fully refunded
- `FAILED` - Capture failed

**Authorization States**:
- `CREATED` - Authorization created
- `CAPTURED` - Authorization captured (converted to capture)
- `DENIED` - Authorization denied
- `EXPIRED` - Authorization expired (not captured in time)
- `PARTIALLY_CAPTURED` - Partial capture performed
- `VOIDED` - Authorization voided
- `PENDING` - Authorization pending

### State Transition Diagrams

#### Standard PayPal - Direct Capture

```
┌─────────────────────────────────────────────────────┐
│ Customer clicks "Pay with PayPal"                   │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Shop Order Created                                  │
│ State: NOT_FINISHED                                 │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ PayPal Order Created                                │
│ Intent: CAPTURE                                     │
│ State: CREATED                                      │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Customer Redirected to PayPal                       │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Customer Approves Payment                           │
│ PayPal State: APPROVED                              │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Payment Captured Automatically                      │
│ PayPal State: COMPLETED                             │
│ Capture State: COMPLETED                            │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Webhook: PAYMENT.CAPTURE.COMPLETED                  │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Shop Order Completed                                │
│ State: OK                                           │
│ oxpaid: [timestamp]                                 │
│ Order Email Sent                                    │
└─────────────────────────────────────────────────────┘
```

#### Standard PayPal - Authorization with Capture on Delivery

```
┌─────────────────────────────────────────────────────┐
│ Customer Checkout                                   │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ PayPal Order Created                                │
│ Intent: AUTHORIZE                                   │
│ State: CREATED                                      │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Customer Approves                                   │
│ PayPal State: APPROVED                              │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Authorization Created                               │
│ Authorization State: CREATED                        │
│ Funds Reserved (not captured)                       │
│ Valid: 3 days                                       │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Shop Order State: NOT_FINISHED                      │
│ (Awaiting admin to ship)                            │
└───────────────────┬─────────────────────────────────┘
                    ↓
           ┌────────┴────────┐
           ↓                 ↓
┌──────────────────┐  ┌─────────────────────┐
│ < 3 days         │  │ > 3 days, < 29 days │
└────────┬─────────┘  └──────────┬──────────┘
         ↓                       ↓
         │           ┌───────────────────────┐
         │           │ Reauthorization       │
         │           │ New 3-day validity    │
         │           └───────────┬───────────┘
         └───────────────────┬───┘
                             ↓
┌─────────────────────────────────────────────────────┐
│ Admin Clicks "Send Order"                           │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Capture Triggered Automatically                     │
│ captureAuthorizedPayment()                          │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Payment Captured                                    │
│ Authorization State: CAPTURED                       │
│ Capture State: COMPLETED                            │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Webhook: PAYMENT.CAPTURE.COMPLETED                  │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Shop Order Completed                                │
│ State: OK                                           │
│ oxpaid: [timestamp]                                 │
└─────────────────────────────────────────────────────┘
```

#### ACDC (Card Payment) Flow

```
┌─────────────────────────────────────────────────────┐
│ Customer Enters Card Details                        │
│ (Hosted Fields)                                     │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Shop Order Created                                  │
│ State: ACDCINPROGRESS (700)                         │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ PayPal Order Created                                │
│ Intent: CAPTURE                                     │
│ State: CREATED                                      │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Order Patched with Shop Details                     │
└───────────────────┬─────────────────────────────────┘
                    ↓
          ┌─────────┴──────────┐
          ↓                    ↓
┌───────────────────┐   ┌──────────────────┐
│ SCA Required?     │   │ SCA Not Required │
│ Yes               │   │ No               │
└─────────┬─────────┘   └────────┬─────────┘
          ↓                      ↓
┌───────────────────┐            │
│ 3D Secure         │            │
│ Challenge         │            │
│ Customer          │            │
│ Authenticates     │            │
└─────────┬─────────┘            │
          ↓                      ↓
┌─────────────────────────────────────────────────────┐
│ Payment Captured                                    │
│ captureAcdcOrder()                                  │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Shop Order State: ACDCCOMPLETED (750)               │
│ PayPal State: COMPLETED                             │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Finalize Called                                     │
│ finalizeacdc()                                      │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Shop Order State: WAIT_FOR_WEBHOOK_EVENTS (600)     │
│ Timeout: 60 seconds                                 │
└───────────────────┬─────────────────────────────────┘
                    ↓
        ┌───────────┴───────────┐
        ↓                       ↓
┌───────────────┐      ┌────────────────────┐
│ Webhook       │      │ Timeout            │
│ Arrives       │      │ (60 sec)           │
│ < 60 sec      │      │                    │
└───────┬───────┘      └──────────┬─────────┘
        ↓                         ↓
        │              ┌──────────────────────┐
        │              │ State: TIMEOUT (900) │
        │              │ Fallback Mode        │
        │              │ Fetch from API       │
        │              └──────────┬───────────┘
        └──────────────────────┬──┘
                               ↓
┌─────────────────────────────────────────────────────┐
│ Order Finalized                                     │
│ markOrderPaid()                                     │
│ State: OK                                           │
│ oxpaid: [timestamp]                                 │
└─────────────────────────────────────────────────────┘
```

#### uAPM Flow (e.g., iDEAL, EPS, etc.)

```
┌─────────────────────────────────────────────────────┐
│ Customer Selects uAPM Method                        │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Shop Order Created                                  │
│ State: SESSIONPAYMENT_INPROGRESS (500)              │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ PayPal Order Created (no payment source yet)        │
│ Intent: CAPTURE                                     │
│ State: CREATED                                      │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Order Confirmed with Payment Source                 │
│ doConfirmUAPM()                                     │
│ State: PAYER_ACTION_REQUIRED                        │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Customer Redirected to Payment Provider             │
│ (e.g., bank website for iDEAL)                      │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Customer Authenticates at Provider                  │
│ (bank login, PIN, etc.)                             │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Customer Returns to Shop                            │
│ PayPal State: APPROVED                              │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Shop Order State: WAIT_FOR_WEBHOOK_EVENTS (600)     │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Webhook: CHECKOUT.ORDER.APPROVED                    │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Handler Triggers Capture                            │
│ doCapturePayPalOrder()                              │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Payment Captured                                    │
│ PayPal State: COMPLETED                             │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Webhook: CHECKOUT.ORDER.COMPLETED                   │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Order Finalized                                     │
│ State: OK                                           │
│ oxpaid: [timestamp]                                 │
└─────────────────────────────────────────────────────┘
```

#### Refund Flow

```
┌─────────────────────────────────────────────────────┐
│ Completed Order                                     │
│ State: OK                                           │
│ Capture State: COMPLETED                            │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Admin Initiates Refund                              │
│ PayPalOrderController::refund()                     │
└───────────────────┬─────────────────────────────────┘
                    ↓
          ┌─────────┴──────────┐
          ↓                    ↓
┌───────────────────┐   ┌──────────────────┐
│ Full Refund       │   │ Partial Refund   │
│ (all amount)      │   │ (specific amount)│
└─────────┬─────────┘   └────────┬─────────┘
          │                      │
          └──────────┬───────────┘
                     ↓
┌─────────────────────────────────────────────────────┐
│ API Call: refundCapturedPayment()                   │
│ Refund State: PENDING                               │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ PayPal Processes Refund                             │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Refund Completed                                    │
│ Refund State: COMPLETED                             │
└───────────────────┬─────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│ Webhook: PAYMENT.CAPTURE.REFUNDED                   │
└───────────────────┬─────────────────────────────────┘
                    ↓
          ┌─────────┴──────────┐
          ↓                    ↓
┌───────────────────┐   ┌──────────────────────┐
│ Full Refund       │   │ Partial Refund       │
│ Capture State:    │   │ Capture State:       │
│ REFUNDED          │   │ PARTIALLY_REFUNDED   │
└─────────┬─────────┘   └────────┬─────────────┘
          │                      │
          └──────────┬───────────┘
                     ↓
┌─────────────────────────────────────────────────────┐
│ Refund Tracked in Database                          │
│ Transaction Type: refund                            │
│ Linked to Original Capture                          │
└─────────────────────────────────────────────────────┘
│ Note: Shop Order State remains OK                   │
│ oxpaid timestamp not changed                        │
└─────────────────────────────────────────────────────┘
```

---

## Architecture Overview

### Layer Architecture

```
┌─────────────────────────────────────────────────────┐
│                  Presentation Layer                 │
│  ┌───────────────────────────────────────────────┐  │
│  │ Controllers                                   │  │
│  │ - OrderController                             │  │
│  │ - PaymentController                           │  │
│  │ - AjaxPaymentController                       │  │
│  │ - WebhookController                           │  │
│  │ - Admin Controllers                           │  │
│  └───────────────────────────────────────────────┘  │
└──────────────────────┬──────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────┐
│                   Service Layer                     │
│  ┌───────────────────────────────────────────────┐  │
│  │ Payment Service (Core Logic)                  │  │
│  │ - doCreatePayPalOrder()                       │  │
│  │ - doCapturePayPalOrder()                      │  │
│  │ - doAuthorizePayment()                        │  │
│  │ - doExecutePayment()                          │  │
│  └───────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────┐  │
│  │ OrderRepository                               │  │
│  │ - getPayPalOrderByOrderId()                   │  │
│  │ - cleanUpNotFinishedOrders()                  │  │
│  └───────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────┐  │
│  │ OrderManager                                  │  │
│  │ - createShopOrder()                           │  │
│  └───────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────┐  │
│  │ SCAValidator                                  │  │
│  │ - verify3D()                                  │  │
│  └───────────────────────────────────────────────┘  │
└──────────────────────┬──────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────┐
│                    Model Layer                      │
│  ┌───────────────────────────────────────────────┐  │
│  │ Order (Extended)                              │  │
│  │ - finalizeOrderAfterExternalPayment()         │  │
│  │ - markOrderPaid()                             │  │
│  │ - markOrderPaymentFailed()                    │  │
│  └───────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────┐  │
│  │ PayPalOrder                                   │  │
│  │ - Tracks PayPal order data                    │  │
│  │ - Links shop order to PayPal order            │  │
│  └───────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────┐  │
│  │ Basket, User, Payment (Extended)              │  │
│  └───────────────────────────────────────────────┘  │
└──────────────────────┬──────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────┐
│               Integration Layer (API)               │
│  ┌───────────────────────────────────────────────┐  │
│  │ PayPal API SDK                                │  │
│  │ - OrderService                                │  │
│  │   - createOrder()                             │  │
│  │   - capturePaymentForOrder()                  │  │
│  │   - authorizePaymentForOrder()                │  │
│  │   - showOrderDetails()                        │  │
│  │ - PaymentService                              │  │
│  │   - captureAuthorizedPayment()                │  │
│  │   - reauthorizeAuthorizedPayment()            │  │
│  │   - refundCapturedPayment()                   │  │
│  │ - VaultingService                             │  │
│  └───────────────────────────────────────────────┘  │
└──────────────────────┬──────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────┐
│                    PayPal API v2                    │
│                  (External Service)                 │
└─────────────────────────────────────────────────────┘

                       ↑
                       │ (Webhooks)
                       ↓
┌─────────────────────────────────────────────────────┐
│                  Webhook System                     │
│  ┌───────────────────────────────────────────────┐  │
│  │ WebhookController (Entry)                     │  │
│  │   ↓                                            │  │
│  │ RequestReader                                 │  │
│  │   ↓                                            │  │
│  │ EventVerifier (Signature Check)               │  │
│  │   ↓                                            │  │
│  │ RequestHandler                                │  │
│  │   ↓                                            │  │
│  │ EventDispatcher                               │  │
│  │   ↓                                            │  │
│  │ Event Handlers:                               │  │
│  │ - PaymentCaptureCompletedHandler              │  │
│  │ - CheckoutOrderCompletedHandler               │  │
│  │ - CheckoutOrderApprovedHandler                │  │
│  │ - PaymentCaptureRefundedHandler               │  │
│  │ - PaymentCaptureDeniedHandler                 │  │
│  │ - CheckoutPaymentApprovalReverseHandler       │  │
│  └───────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

### Key Classes and Responsibilities

#### Payment Service
**Location**: `src/Service/Payment.php`

**Responsibilities**:
- Create PayPal orders for all payment methods
- Execute payments
- Capture authorized payments
- Authorize payments
- Validate 3D Secure results
- Track PayPal orders in database

**Key Methods**:
- `doCreatePayPalOrder()` - Creates PayPal order
- `doCapturePayPalOrder()` - Captures payment
- `doAuthorizePayment()` - Authorizes payment
- `doExecutePayPalPayment()` - Executes standard PayPal
- `doExecutePuiPayment()` - Executes PUI
- `doExecuteUAPMPayment()` - Executes uAPM
- `trackPayPalOrder()` - Tracks in database

#### Order Repository
**Location**: `src/Service/OrderRepository.php`

**Responsibilities**:
- Query PayPal orders from database
- Map PayPal order IDs to shop orders
- Map transaction IDs to orders
- Clean up unfinished orders

**Key Methods**:
- `getPayPalOrderByOrderId()` - Get by shop order ID
- `getOrderIdByPayPalOrderId()` - Get shop order from PayPal ID
- `getPayPalOrderIdByShopOrderId()` - Get PayPal ID from shop order
- `cleanUpNotFinishedOrders()` - Cleanup job

#### Order Manager
**Location**: `src/Service/OrderManager.php`

**Responsibilities**:
- Create shop orders
- Manage order lifecycle
- Link orders to PayPal orders

#### Order Model
**Location**: `src/Model/Order.php`

**Extends**: `OxidEsales\Eshop\Application\Model\Order`

**Responsibilities**:
- Finalize orders after external payment
- Mark orders as paid
- Mark orders as failed
- Set transaction IDs
- Send order emails

**Key Methods**:
- `finalizeOrderAfterExternalPayment()` - Complete order
- `markOrderPaid()` - Set paid status
- `markOrderPaymentFailed()` - Set failed status
- `setTransId()` - Store transaction ID

#### PayPalOrder Model
**Location**: `src/Model/PayPalOrder.php`

**Table**: `oscpaypal_order`

**Responsibilities**:
- Store PayPal order tracking data
- Link shop orders to PayPal orders
- Store transaction IDs and statuses

**Key Fields**:
- `oxorderid` - Shop order ID (foreign key)
- `oxpaypalorderid` - PayPal order ID
- `oscpaypaltransactionid` - Transaction/Capture ID
- `oscpaypalstatus` - Order status
- `oscpaymentmethodid` - Payment method
- `oscpaypaltransactiontype` - Transaction type

#### Controllers

**OrderController** (`src/Controller/OrderController.php`):
- Handles order finalization
- ACDC order creation and capture
- Google Pay and Apple Pay flows
- Session payment handling

**AjaxPaymentController** (`src/Controller/AjaxPaymentController.php`):
- AJAX endpoints for payment operations
- Creates PayPal orders
- Captures and authorizes payments
- Updates and patches orders
- Cancels orders

**WebhookController** (`src/Controller/WebhookController.php`):
- Entry point for PayPal webhooks
- Coordinates verification and processing
- Returns appropriate HTTP responses

**PayPalOrderController** (`src/Controller/Admin/PayPalOrderController.php`):
- Admin backend for PayPal orders
- Refund processing
- Order history display
- Manual capture

#### Webhook Components

**EventDispatcher** (`src/Core/Webhook/EventDispatcher.php`):
- Routes webhook events to handlers
- Uses event type mapping

**WebhookHandlerBase** (`src/Core/Webhook/Handler/WebhookHandlerBase.php`):
- Base class for all handlers
- Common order update logic
- Cleanup triggering

**Specific Handlers**:
- Each handles one event type
- Updates order status
- Triggers captures if needed
- Sends emails if needed

### Database Schema

**Table: oscpaypal_order**

```sql
CREATE TABLE oscpaypal_order (
    OXID                        CHAR(32) PRIMARY KEY,
    OXORDERID                   CHAR(32),           -- Shop order ID
    OXPAYPALORDERID             VARCHAR(255),       -- PayPal order ID
    OSCPAYPALTRANSACTIONID      VARCHAR(255),       -- Transaction/Capture ID
    OSCPAYPALSTATUS             VARCHAR(50),        -- Order status
    OSCPAYMENTMETHODID          VARCHAR(50),        -- Payment method ID
    OSCPAYPALTRANSACTIONTYPE    VARCHAR(50),        -- capture/authorization/refund

    -- PUI specific fields
    OSCPAYPALPUIPAYMENTREFERENCE VARCHAR(255),
    OSCPAYPALPUIIBAN            VARCHAR(255),
    OSCPAYPALPUIBIC             VARCHAR(255),
    OSCPAYPALPUIBANKNAME        VARCHAR(255),
    OSCPAYPALPUIACCOUNTHOLDERNAME VARCHAR(255),

    OXTIMESTAMP                 TIMESTAMP,

    INDEX idx_orderid (OXORDERID),
    INDEX idx_paypalorderid (OXPAYPALORDERID),
    INDEX idx_transactionid (OSCPAYPALTRANSACTIONID)
);
```

**Relationships**:
- `OXORDERID` → `oxorder.OXID` (one-to-many)
- Multiple transactions per order (auth → capture → refunds)

### Configuration Settings

**Module Settings** (`metadata.php` settings section):

**API Credentials**:
- `oscPayPalClientId` - PayPal client ID (live)
- `oscPayPalClientSecret` - PayPal client secret (live)
- `oscPayPalClientMerchantId` - Merchant ID (live)
- `oscPayPalWebhookId` - Webhook ID (live)
- `oscPayPalSandbox*` - Sandbox equivalents

**Capture Strategy**:
- `oscPayPalStandardCaptureStrategy` - directly/delivery/manually

**Payment Method Eligibility**:
- `oscPayPalAcdcEligibility` - ACDC enabled
- `oscPayPalPuiEligibility` - PUI enabled
- `oscPayPalGooglePayEligibility` - Google Pay enabled
- `oscPayPalApplePayEligibility` - Apple Pay enabled
- `oscPayPalSepaEligibility` - SEPA enabled
- ...etc for other methods

**Button Display**:
- `oscPayPalShowProductDetailsButton` - Show on product page
- `oscPayPalShowBasketButton` - Show on cart page
- `oscPayPalShowMiniBasketButton` - Show in mini-cart

**Security**:
- `oscPayPalSCAContingency` - SCA_ALWAYS/SCA_WHEN_REQUIRED/SCA_DISABLED

**Cleanup**:
- `oscPayPalCleanUpNotFinishedOrdersAutomaticlly` - Auto cleanup enabled
- `oscPayPalStartTimeCleanUpOrders` - Cleanup timeout (minutes)

### Constants

**Important Constants** (`src/Core/Constants.php`):

**Time**:
- `PAYPAL_DAY = 86400` - 1 day in seconds
- `PAYPAL_AUTHORIZATION_VALIDITY = 259200` - 3 days
- `PAYPAL_MAXIMUM_TIME_FOR_CAPTURE = 2505600` - 29 days
- `PAYPAL_WAIT_FOR_WEBOOK_TIMEOUT_IN_SEC = 60` - Webhook timeout

**Order Intent**:
- `PAYPAL_ORDER_INTENT_CAPTURE = 'CAPTURE'`
- `PAYPAL_ORDER_INTENT_AUTHORIZE = 'AUTHORIZE'`

**Transaction Types**:
- `PAYPAL_TRANSACTION_TYPE_CAPTURE = 'capture'`
- `PAYPAL_TRANSACTION_TYPE_AUTH = 'authorization'`
- `PAYPAL_TRANSACTION_TYPE_REFUND = 'refund'`

**Payment Method IDs**:
- `STANDARD_PAYPAL_PAYMENT_ID = 'oscpaypal'`
- `ACDC_PAYPAL_PAYMENT_ID = 'oscpaypal_acdc'`
- `PUI_PAYPAL_PAYMENT_ID = 'oscpaypal_pui'`
- `GOOGLEPAY_PAYPAL_PAYMENT_ID = 'oscpaypal_googlepay'`
- `APPLEPAY_PAYPAL_PAYMENT_ID = 'oscpaypal_applepay'`
- ...etc

**Partner Attribution ID**:
- `PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP = 'OXID_Cart_PPCP_fromv261'`

---

## Conclusion

This documentation provides a comprehensive overview of the OXID PayPal module's order processing and payment flows. The module is well-architected with clear separation of concerns, robust error handling, and support for a wide variety of payment methods and scenarios.

**Key Takeaways**:

1. **Flexible Capture Strategies**: Support for immediate, on-delivery, and manual capture
2. **Multiple Payment Methods**: From traditional PayPal to modern wallets to local payment methods
3. **Robust Webhook System**: Ensures order completion even with network issues
4. **3D Secure Support**: PSD2 compliant authentication for card payments
5. **Vaulting**: Save payment methods for returning customers
6. **Comprehensive Tracking**: Every transaction tracked with full audit trail
7. **Fallback Mechanisms**: Timeout handling and API fallbacks for reliability

**For Developers**:
- Study the Payment Service for core logic
- Understand webhook handlers for asynchronous processing
- Review state transitions for order flow logic
- Check constants for configuration options

**For Merchants**:
- Configure capture strategy based on business model
- Enable payment methods suitable for target markets
- Monitor webhook delivery for order completion
- Use admin tools for refunds and order management
