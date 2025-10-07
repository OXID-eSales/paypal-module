# PayPal Module Documentation Package

This package contains comprehensive documentation for the OXID PayPal module (osc/paypal v2.6.2-rc.4).

## Files Included

### 1. PayPal_Module_Documentation.md
**Human-readable documentation** covering:
- Complete order creation process for all payment methods
- Payment capture strategies (direct, on delivery, manual)
- Authorization and reauthorization logic
- Refund processing
- Order cancellation and void operations
- Webhook system architecture and event handling
- Payment method specifications (PayPal, ACDC, PUI, Google Pay, Apple Pay, SEPA, uAPM)
- State transitions and order status flow
- Architecture overview with class responsibilities
- Database schema and configuration settings

**Best for**: Developers, business analysts, project managers who need to understand the complete payment flow and business logic.

### 2. PayPal_Module_UML_Diagram.puml
**PlantUML class diagram** showing:
- All major classes (Controllers, Services, Models, Webhooks, API clients)
- Class relationships and dependencies
- Method signatures for key operations
- Package organization and layering
- Notes explaining critical components

**Best for**: System architects, developers who need to understand the code structure and relationships.

**How to view**:
- Option 1: Use PlantUML online viewer at http://www.plantuml.com/plantuml/uml/
- Option 2: Install PlantUML extension in VS Code
- Option 3: Use PlantUML command-line tool: `plantuml PayPal_Module_UML_Diagram.puml`
- Option 4: Import into MS Visio using PlantUML plugin

### 3. PayPal_Module_Sequence_Diagrams.puml
**PlantUML sequence diagrams** showing:
- Standard PayPal payment flow (direct capture)
- ACDC payment flow with 3D Secure
- Authorization with capture on delivery
- uAPM payment flow (iDEAL, EPS, etc.)
- Refund process flow
- PUI payment flow with bank details

**Best for**: Understanding the step-by-step flow of payment operations, timing, and interactions between components.

**How to view**: Same as UML diagram above.

## Quick Start Guide

### For Business Users
1. Start with **PayPal_Module_Documentation.md**
2. Read the "Overview" and "Payment Methods" sections
3. Review "State Transitions" to understand order statuses
4. Check specific payment method sections for features and limitations

### For Developers
1. Read **PayPal_Module_Documentation.md** sections:
   - Architecture Overview
   - Key Classes and Responsibilities
   - Order Creation Process (for your payment method)
   - Webhook System
2. Open **PayPal_Module_UML_Diagram.puml** in a PlantUML viewer
3. Reference **PayPal_Module_Sequence_Diagrams.puml** for specific flows you're working on

### For System Architects
1. Review **PayPal_Module_UML_Diagram.puml** for overall architecture
2. Read "Architecture Overview" in **PayPal_Module_Documentation.md**
3. Review **PayPal_Module_Sequence_Diagrams.puml** for integration patterns
4. Check "Integration Points with PayPal API" section

## Viewing PlantUML Diagrams

### Option 1: Online Viewer (Easiest)
1. Go to http://www.plantuml.com/plantuml/uml/
2. Copy the contents of the `.puml` file
3. Paste into the text area
4. View the rendered diagram

### Option 2: VS Code Extension
1. Install "PlantUML" extension by jebbs
2. Open the `.puml` file in VS Code
3. Press `Alt+D` to preview diagram

### Option 3: Command Line
```bash
# Install PlantUML (requires Java)
sudo apt-get install plantuml  # Ubuntu/Debian
brew install plantuml          # macOS

# Generate PNG
plantuml PayPal_Module_UML_Diagram.puml
plantuml PayPal_Module_Sequence_Diagrams.puml

# Generate SVG (scalable)
plantuml -tsvg PayPal_Module_UML_Diagram.puml
plantuml -tsvg PayPal_Module_Sequence_Diagrams.puml
```

### Option 4: MS Visio Import
1. Install PlantUML plugin for Visio
2. Open Visio
3. Use "Import PlantUML" feature
4. Select the `.puml` file
5. Edit and customize as needed

## Key Concepts Explained

### Order States
The module uses custom order states to track payment progress:
- **500**: uAPM payment in progress
- **600**: Waiting for webhook confirmation
- **700**: ACDC payment in progress
- **750**: ACDC payment completed
- **800**: ACDC needs finalization
- **900**: Webhook timeout (fallback mode)

### Payment Flow Types

#### Synchronous Flow
- Customer action → Immediate result → Order completion
- Examples: Standard PayPal with direct capture, Vaulted payments

#### Asynchronous Flow
- Customer action → Webhook delivers result → Order completion
- Examples: uAPM (iDEAL, EPS), PUI, Some ACDC/Google Pay cases
- Timeout fallback: 60 seconds, then fetch from API

### Capture Strategies
1. **Directly**: Capture immediately when order placed (digital goods)
2. **On Delivery**: Authorize first, capture when shipped (physical goods)
3. **Manually**: Authorize first, admin manually captures (custom workflows)

### Authorization Time Limits
- **Initial**: 3 days
- **Reauthorization**: Extends by 3 days
- **Maximum**: 29 days total
- **After expiry**: Cannot capture, must void or let expire

## Important File Locations in Module

Reference these locations when implementing or debugging:

```
src/
├── Controller/
│   ├── OrderController.php          # Order finalization for all payment methods
│   ├── AjaxPaymentController.php    # AJAX payment operations
│   ├── WebhookController.php        # Webhook entry point
│   └── Admin/
│       ├── PayPalOrderController.php # Admin order management, refunds
│       └── OrderOverview.php         # Capture on delivery trigger
├── Service/
│   ├── Payment.php                  # Core payment logic (MOST IMPORTANT)
│   ├── OrderRepository.php          # Data access for PayPal orders
│   ├── OrderManager.php             # Shop order creation
│   └── SCAValidator.php             # 3D Secure validation
├── Model/
│   ├── Order.php                    # Extended order model
│   └── PayPalOrder.php              # PayPal order tracking model
├── Core/
│   ├── Webhook/
│   │   ├── EventDispatcher.php
│   │   └── Handler/
│   │       ├── PaymentCaptureCompletedHandler.php
│   │       ├── CheckoutOrderApprovedHandler.php
│   │       ├── PaymentCaptureRefundedHandler.php
│   │       └── ...
│   └── Constants.php                # All module constants
└── ...
```

## Database Tables

### oscpaypal_order
Main tracking table linking shop orders to PayPal orders.

**Key fields**:
- `OXORDERID`: Shop order ID (FK to oxorder.OXID)
- `OXPAYPALORDERID`: PayPal order ID
- `OSCPAYPALTRANSACTIONID`: Transaction/Capture ID
- `OSCPAYPALSTATUS`: PayPal status (COMPLETED, REFUNDED, etc.)
- `OSCPAYMENTMETHODID`: Payment method (oscpaypal, oscpaypal_acdc, etc.)
- `OSCPAYPALTRANSACTIONTYPE`: capture, authorization, or refund

**Relationships**:
- One shop order → Many PayPal order records (auth → capture → refunds)

## Payment Method IDs

When referencing payment methods in code:

```php
oscpaypal            // Standard PayPal
oscpaypal_acdc       // Credit/Debit Cards
oscpaypal_pui        // Pay Upon Invoice
oscpaypal_googlepay  // Google Pay
oscpaypal_applepay   // Apple Pay
oscpaypal_sepa       // SEPA Direct Debit
oscpaypal_ideal      // iDEAL (Netherlands)
oscpaypal_eps        // EPS (Austria)
oscpaypal_bancontact // Bancontact (Belgium)
oscpaypal_blik       // BLIK (Poland)
oscpaypal_p24        // Przelewy24 (Poland)
```

## Testing Scenarios

Use these scenarios to test the implementation:

### Scenario 1: Standard PayPal Direct Capture
1. Add product to cart
2. Checkout, select PayPal
3. Approve on PayPal
4. Return to shop
5. Verify: Order status OK, oxpaid set, webhook received

### Scenario 2: ACDC with 3D Secure
1. Select ACDC at checkout
2. Enter test card requiring 3DS
3. Complete 3DS challenge
4. Verify: Order captured, webhook received within 60 sec
5. Test timeout: Delay webhook, verify fallback works

### Scenario 3: Authorization and Capture on Delivery
1. Configure: Capture strategy = "delivery"
2. Complete PayPal checkout
3. Verify: Order NOT_FINISHED, authorization created
4. Admin: Send order
5. Verify: Capture triggered, order marked paid

### Scenario 4: uAPM (iDEAL)
1. Select iDEAL, choose bank
2. Authenticate at bank
3. Return to shop
4. Wait for webhook
5. Verify: Order completed, status OK

### Scenario 5: Refund
1. Complete paid order
2. Admin: Open order, click Refund
3. Enter partial amount
4. Verify: Refund transaction created, status PARTIALLY_REFUNDED
5. Refund remaining amount
6. Verify: Status REFUNDED

## Support and Troubleshooting

### Common Issues

#### Webhook Not Received
- Check webhook URL is accessible from internet
- Verify webhook ID configured correctly
- Check firewall/security settings
- Review PayPal webhook dashboard for delivery attempts
- Fallback will trigger after 60 seconds

#### 3D Secure Failures
- Check SCA contingency setting
- Verify test cards support 3DS
- Review authentication result in logs
- Ensure PayPal account has ACDC enabled

#### Authorization Expired
- Check order age (max 29 days)
- Verify reauthorization is working
- If > 29 days: Must create new order

#### Order Stuck in Progress State
- Check webhook delivery
- Run cleanup job: `OrderRepository::cleanUpNotFinishedOrders()`
- Check timeout setting (default 60 minutes)

### Debug Logging

Enable debug logging in module settings:
```
oscPayPalDebugLevel = debug
```

Logs location: `log/oxideshop.log`

Search for:
- `PayPal Webhook request` - Webhook receipt
- `PayPal API` - API calls
- `doCreatePayPalOrder` - Order creation
- `doCapturePayPalOrder` - Capture operations

## API Documentation References

- **PayPal Orders API v2**: https://developer.paypal.com/docs/api/orders/v2/
- **PayPal Payments API v2**: https://developer.paypal.com/docs/api/payments/v2/
- **PayPal Webhooks**: https://developer.paypal.com/docs/api/webhooks/v1/
- **OXID Module Documentation**: https://docs.oxid-esales.com/modules/paypal-checkout/

## Version Information

- **Module Version**: 2.6.2-rc.4
- **OXID Version**: Compatible with OXID 6.x
- **PayPal API**: v2
- **Documentation Date**: 2025-10-07

## License

This documentation is provided for the OXID PayPal module developed by OXID eSales AG.
For module licensing, see the module's LICENSE file.

## Contact

For issues with the PayPal module:
- **Email**: info@oxid-esales.com
- **Website**: https://www.oxid-esales.com
- **Documentation**: https://docs.oxid-esales.com/modules/paypal-checkout/

---

**Note**: This documentation is based on code analysis of the osc/paypal module version 2.6.2-rc.4.
Always refer to the official OXID documentation for the most up-to-date information and best practices.
