# PayPal Module Documentation

Documentation for the OXID PayPal Checkout module (`osc/paypal`). The module version this
belongs to is in `metadata.php`; changes are listed in [../CHANGELOG.md](../CHANGELOG.md).

## 📚 Documentation Files

### Main Documentation

#### 📖 [PayPal_Module_Documentation.md](PayPal_Module_Documentation.md)
**Complete business and technical documentation** (83KB, 15,000+ words)

Topics covered:
- ✅ Order creation for all payment methods
- ✅ Capture strategies (direct, on delivery, manual)
- ✅ Authorization and reauthorization (3-29 days)
- ✅ Refund processing (full and partial)
- ✅ Cancellation and void operations
- ✅ Webhook system (6 event handlers)
- ✅ Payment methods: PayPal, ACDC, PUI, Google Pay, Apple Pay, SEPA, uAPM
- ✅ State transitions and order statuses
- ✅ Architecture and class responsibilities
- ✅ Database schema and configuration

**Best for**: Developers, business analysts, project managers

---

### UML Diagrams

#### 🔷 [PayPal_Module_UML_Diagram.puml](UML/PayPal_Module_UML_Diagram.puml)
**Complete class diagram** (18KB PlantUML)

Includes:
- All major classes (Controllers, Services, Models, Webhooks, API)
- Class relationships and dependencies
- Method signatures
- Package organization
- Explanatory notes

**Best for**: System architects, developers

#### 🔄 [PayPal_Module_Sequence_Diagrams.puml](UML/PayPal_Module_Sequence_Diagrams.puml)
**Payment flow sequence diagrams** (19KB PlantUML)

Contains 6 detailed flows:
1. Standard PayPal (direct capture)
2. ACDC with 3D Secure
3. Authorization with capture on delivery
4. uAPM payment (iDEAL, EPS, etc.)
5. Refund process
6. PUI payment with bank details

**Best for**: Understanding step-by-step operations

---

### Rendering the Diagrams

Only the PlantUML sources in [UML/](UML/) are kept under version control - the rendered images are
generated on demand, so a diagram can never be out of sync with its source. To render them
(needs Docker):

```bash
cd docs
make svg     # renders UML/*.puml into SVG/ (git-ignored)
make clean   # removes the generated SVG files
```

Without a local render, see "Viewing a `.puml` file" below - the online viewer needs nothing
installed at all.

See [UML/README.md](UML/README.md) for what each diagram covers.

---

## 🚀 Quick Start

### For Business Users
1. Read: [PayPal_Module_Documentation.md](PayPal_Module_Documentation.md)
2. Focus on: "Payment Methods" and "State Transitions" sections
3. Review payment flows relevant to your use case

### For Developers
1. Read: [PayPal_Module_Documentation.md](PayPal_Module_Documentation.md)
   - Architecture Overview
   - Key Classes and Responsibilities
2. View: [PayPal_Module_UML_Diagram.puml](UML/PayPal_Module_UML_Diagram.puml)
3. Study: [PayPal_Module_Sequence_Diagrams.puml](UML/PayPal_Module_Sequence_Diagrams.puml)

### For System Architects
1. View: [PayPal_Module_UML_Diagram.puml](UML/PayPal_Module_UML_Diagram.puml)
2. Read: "Architecture Overview" section
3. Study: [PayPal_Module_Sequence_Diagrams.puml](UML/PayPal_Module_Sequence_Diagrams.puml)

---

## 🎨 Viewing and Converting the Diagrams

### Viewing a `.puml` file

1. **Online viewer** - paste the file content into http://www.plantuml.com/plantuml/uml/,
   nothing to install
2. **VS Code** - install the "PlantUML" extension, open the file, `Alt+D` to preview
3. **Rendered locally** - `make svg` in this directory (see above), then open the file in `SVG/`
4. **Command line** - a local PlantUML installation, if you prefer it over the Docker image

### Converting to Visio VSDX

Draw.io produces true VSDX files that Visio can fully edit:

1. Visit https://app.diagrams.net/
2. Arrange → Insert → Advanced → PlantUML
3. Copy/paste the content of the `.puml` file
4. File → Export As → VSDX

Alternatives: render to SVG first (`make svg`, or the online server) and import that into Visio -
editable, but the shapes are grouped vector graphics rather than native Visio stencils.

---

## 📖 Key Concepts

### Payment Methods Supported

| Method | ID | Description |
|--------|----|----|
| PayPal | `oscpaypal` | Standard PayPal wallet |
| ACDC | `oscpaypal_acdc` | Credit/Debit cards with 3DS (not offered to merchants in CH) |
| PUI | `oscpaypal_pui` | Pay Upon Invoice (Germany) |
| Google Pay | `oscpaypal_googlepay` | Google Pay wallet |
| Apple Pay | `oscpaypal_applepay` | Apple Pay wallet |
| SEPA | `oscpaypal_sepa` | SEPA Direct Debit |
| iDEAL | `oscpaypal_ideal` | Dutch bank transfer |
| EPS | `oscpaypal_eps` | Austrian bank transfer |
| Bancontact | `oscpaypal_bancontact` | Belgian payment |
| BLIK | `oscpaypal_blik` | Polish mobile payment |
| P24 | `oscpaypal_p24` | Przelewy24 (Poland) |

### Payment Flow Types

**Synchronous**
- Customer action → immediate result → order completion
- Examples: standard PayPal with direct capture, vaulted payments

**Asynchronous**
- Customer action → webhook delivers the result → order completion
- Examples: uAPM (iDEAL, EPS), PUI, some ACDC / Google Pay cases
- Timeout fallback: after 60 seconds the shop fetches the state from the API instead of waiting

### Capture Strategies

1. **Directly** - Capture immediately (digital goods)
2. **On Delivery** - Authorize first, capture when shipped (physical goods)
3. **Manually** - Admin manually captures (custom workflows)

### Order States

| Code | Description |
|------|-------------|
| `NOT_FINISHED` | Order created, awaiting payment |
| `500` | uAPM payment in progress |
| `600` | Waiting for webhook |
| `700` | ACDC payment in progress |
| `750` | ACDC completed |
| `800` | ACDC needs finalization |
| `900` | Webhook timeout (fallback) |
| `OK` | Order completed and paid |

### Authorization Time Limits

- **Initial validity**: 3 days
- **Reauthorization**: Extends by 3 days
- **Maximum**: 29 days total
- **After expiry**: Cannot capture

---

## 🏗️ Architecture Overview

```
Controllers Layer
    ↓
Service Layer (Payment, OrderRepository, OrderManager)
    ↓
Model Layer (Order, PayPalOrder, User)
    ↓
PayPal API v2 (Orders, Payments, Vaulting)
    ↓
Webhook System ← PayPal Notifications
```

### Key Files in Module

```
src/
├── Controller/
│   ├── OrderController.php              # Order finalization for all payment methods
│   ├── AjaxPaymentController.php        # AJAX payment operations
│   ├── WebhookController.php            # Webhook entry point
│   └── Admin/
│       ├── PayPalOrderController.php    # Admin order management, refunds
│       ├── ModuleConfiguration.php      # Module settings screen, onboarding
│       └── OrderOverview.php            # Capture on delivery trigger
├── Service/
│   ├── Payment.php                      # ⭐ Core payment logic
│   ├── OrderRepository.php              # Data access for PayPal orders
│   ├── OrderManager.php                 # Shop order creation
│   ├── ModuleSettings.php               # All module settings, eligibility
│   └── SCAValidator.php                 # 3D Secure validation
├── Model/
│   ├── Order.php                        # Extended order model
│   └── PayPalOrder.php                  # PayPal order tracking model
└── Core/
    ├── PayPalDefinitions.php            # Payment method definitions
    ├── Constants.php                    # All module constants
    └── Webhook/
        ├── EventDispatcher.php
        └── Handler/
            ├── PaymentCaptureCompletedHandler.php
            ├── CheckoutOrderApprovedHandler.php
            ├── PaymentCaptureRefundedHandler.php
            └── ...
```

### Templates for Both Engines

Unlike the 6.x line, this module ships its frontend and admin templates for **both** template
engines, and a change to one has to be made in the other as well:

```
views/
├── twig/            # Twig templates (default engine from OXID 7)
├── smarty/          # the same templates for the Smarty engine
├── admin_twig/      # admin templates, Twig
├── admin_smarty/    # admin templates, Smarty
├── admin_de/        # admin_translations.php
├── admin_en/        # admin_translations.php
└── blocks/          # template blocks
```

---

## 🗄️ Database

### Main Table: `oscpaypal_order`

Tracks PayPal orders and links them to shop orders.

**Key fields**:
- `OXORDERID` - Shop order ID
- `OXPAYPALORDERID` - PayPal order ID
- `OSCPAYPALTRANSACTIONID` - Transaction/Capture ID
- `OSCPAYPALSTATUS` - Status (COMPLETED, REFUNDED, etc.)
- `OSCPAYMENTMETHODID` - Payment method
- `OSCPAYPALTRANSACTIONTYPE` - capture/authorization/refund

**Relationship**: One shop order → Many PayPal records (auth → capture → refunds)

---

## 🧪 Testing Scenarios

### Scenario 1: Standard PayPal
```
Add to cart → Checkout → PayPal → Approve → Return
Expected: Order OK, oxpaid set, webhook received
```

### Scenario 2: ACDC with 3DS
```
Checkout → Enter card → 3DS challenge → Authenticate → Capture
Expected: Order completed, webhook within 60 sec
```

### Scenario 3: Capture on Delivery
```
Checkout → Authorize → Admin sends order → Auto-capture
Expected: Authorization valid 3-29 days, capture on send
```

### Scenario 4: uAPM (iDEAL)
```
Select iDEAL → Choose bank → Authenticate → Return → Wait webhook
Expected: Webhook completes order
```

### Scenario 5: Refund
```
Complete order → Admin refund (partial) → Webhook
Expected: Status PARTIALLY_REFUNDED, can refund remaining
```

More scenarios in [PayPal_Module_Documentation.md](PayPal_Module_Documentation.md)

---

## 🐛 Troubleshooting

### Webhook Not Received
- ✓ Review the PayPal webhook dashboard for delivery attempts
- ✓ Check webhook URL accessible
- ✓ Verify webhook ID configured
- ✓ Check firewall settings
- ✓ Fallback triggers after 60 seconds

### 3D Secure Failures
- ✓ Check SCA contingency setting
- ✓ Verify test cards support 3DS
- ✓ Review authentication logs
- ✓ Ensure ACDC enabled in PayPal account
- ✓ ACDC is not offered to merchants in Switzerland and is hidden when `aHomeCountry` is `CH`

### Authorization Expired
- ✓ Check order age (max 29 days)
- ✓ Verify reauthorization working
- ✓ Create new order if > 29 days

### Order Stuck
- ✓ Check webhook delivery
- ✓ Run the cleanup job (`Service\OrderRepository::cleanUpNotFinishedOrders()`)
- ✓ Check timeout setting (default 60 min)

### Debug Logging

Set the module setting `oscPayPalDebugLevel` to `debug` (admin > PayPal > Configuration), then read
`log/oxideshop.log`. Useful search terms:

- `PayPal Webhook request` - webhook receipt
- `PayPal API` - API calls
- `doCreatePayPalOrder` - order creation
- `doCapturePayPalOrder` - capture operations

More troubleshooting in [PayPal_Module_Documentation.md](PayPal_Module_Documentation.md)

---

## 🔗 External Resources

### Official Documentation
- **PayPal Orders API v2**: https://developer.paypal.com/docs/api/orders/v2/
- **PayPal Payments API v2**: https://developer.paypal.com/docs/api/payments/v2/
- **PayPal Webhooks**: https://developer.paypal.com/docs/api/webhooks/v1/
- **OXID Module**: https://docs.oxid-esales.com/modules/paypal-checkout/
- **PlantUML**: https://plantuml.com/

### Tools
- **Draw.io**: https://app.diagrams.net/
- **PlantUML Server**: http://www.plantuml.com/plantuml/uml/
- **VS Code PlantUML**: Search "PlantUML" in extensions

---

## 📝 Version Information

- **Module Version**: see `metadata.php` in the module root
- **OXID Version**: OXID eShop 7.x - see "Branch Compatibility" in [../README.md](../README.md)
- **PHP**: >=8.0 (from `composer.json`)
- **PayPal API**: Orders v2 / Payments v2
- **Changes**: [../CHANGELOG.md](../CHANGELOG.md)

---

## 📞 Support

For issues with the PayPal module:
- **Email**: info@oxid-esales.com
- **Website**: https://www.oxid-esales.com
- **Documentation**: https://docs.oxid-esales.com/modules/paypal-checkout/

For documentation issues:
- Review troubleshooting sections
- Check external resources
- Verify PlantUML syntax at online server

---

## 📄 License

This documentation is provided for the OXID PayPal module developed by OXID eSales AG.
