# PayPal Module Documentation

Complete documentation package for OXID PayPal Module (osc/paypal v2.6.2-rc.4)

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

#### 🔷 [PayPal_Module_UML_Diagram.puml](PayPal_Module_UML_Diagram.puml)
**Complete class diagram** (18KB PlantUML)

Includes:
- All major classes (Controllers, Services, Models, Webhooks, API)
- Class relationships and dependencies
- Method signatures
- Package organization
- Explanatory notes

**Best for**: System architects, developers

#### 🔄 [PayPal_Module_Sequence_Diagrams.puml](PayPal_Module_Sequence_Diagrams.puml)
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

### Guides and Tools

#### 📘 [PayPal_Documentation_README.md](PayPal_Documentation_README.md)
**Quick start guide** for using the documentation

- How to read each document
- Viewing PlantUML diagrams
- Key concepts explained
- Testing scenarios
- Troubleshooting tips

#### 🎨 [VSDX_CONVERSION_GUIDE.md](VSDX_CONVERSION_GUIDE.md)
**Comprehensive guide for converting PlantUML to Visio VSDX**

5 conversion methods:
1. ⭐ Draw.io (recommended - creates true VSDX)
2. PlantUML Server (quick online)
3. Local PlantUML (batch processing)
4. VS Code Extension (interactive)
5. Visio Plugin (professional)

Step-by-step instructions for each method.

#### 🔧 [convert_plantuml.py](convert_plantuml.py)
**Python helper script** for automated conversion

```bash
# Check system and show instructions
python3 convert_plantuml.py

# Convert to SVG/PNG (requires PlantUML installed)
python3 convert_plantuml.py --convert
```

#### 📝 [convert_instructions.md](convert_instructions.md)
Quick reference for conversion methods

#### 🛠️ [convert_to_vsdx.sh](convert_to_vsdx.sh)
Bash script showing conversion options

```bash
./convert_to_vsdx.sh
```

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
2. View: [PayPal_Module_UML_Diagram.puml](PayPal_Module_UML_Diagram.puml)
3. Study: [PayPal_Module_Sequence_Diagrams.puml](PayPal_Module_Sequence_Diagrams.puml)

### For System Architects
1. View: [PayPal_Module_UML_Diagram.puml](PayPal_Module_UML_Diagram.puml)
2. Read: "Architecture Overview" section
3. Study: [PayPal_Module_Sequence_Diagrams.puml](PayPal_Module_Sequence_Diagrams.puml)

---

## 🎨 Converting to Visio VSDX

**Need VSDX files for Microsoft Visio?**

👉 See: [VSDX_CONVERSION_GUIDE.md](VSDX_CONVERSION_GUIDE.md)

### Fastest Method (Recommended):

1. Visit: https://app.diagrams.net/
2. Arrange → Insert → Advanced → PlantUML
3. Copy/paste content from `.puml` files
4. File → Export As → VSDX

**Result**: True VSDX files that Visio can fully edit!

### Alternative Methods:
- Online PlantUML Server → SVG → Import to Visio
- Local PlantUML installation → Batch conversion
- VS Code extension → Interactive editing

All methods detailed in [VSDX_CONVERSION_GUIDE.md](VSDX_CONVERSION_GUIDE.md)

---

## 📖 Key Concepts

### Payment Methods Supported

| Method | ID | Description |
|--------|----|----|
| PayPal | `oscpaypal` | Standard PayPal wallet |
| ACDC | `oscpaypal_acdc` | Credit/Debit cards with 3DS |
| PUI | `oscpaypal_pui` | Pay Upon Invoice (Germany) |
| Google Pay | `oscpaypal_googlepay` | Google Pay wallet |
| Apple Pay | `oscpaypal_applepay` | Apple Pay wallet |
| SEPA | `oscpaypal_sepa` | SEPA Direct Debit |
| iDEAL | `oscpaypal_ideal` | Dutch bank transfer |
| EPS | `oscpaypal_eps` | Austrian bank transfer |
| Bancontact | `oscpaypal_bancontact` | Belgian payment |
| BLIK | `oscpaypal_blik` | Polish mobile payment |
| P24 | `oscpaypal_p24` | Przelewy24 (Poland) |

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
│   ├── OrderController.php              # Order finalization
│   ├── AjaxPaymentController.php        # AJAX operations
│   └── WebhookController.php            # Webhook entry
├── Service/
│   ├── Payment.php                      # ⭐ Core logic
│   ├── OrderRepository.php              # Data access
│   └── SCAValidator.php                 # 3D Secure
├── Model/
│   ├── Order.php                        # Extended order
│   └── PayPalOrder.php                  # PayPal tracking
└── Core/Webhook/Handler/
    ├── PaymentCaptureCompletedHandler.php
    ├── CheckoutOrderApprovedHandler.php
    └── ...
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
- ✓ Check webhook URL accessible
- ✓ Verify webhook ID configured
- ✓ Check firewall settings
- ✓ Fallback triggers after 60 seconds

### 3D Secure Failures
- ✓ Check SCA contingency setting
- ✓ Verify test cards support 3DS
- ✓ Review authentication logs
- ✓ Ensure ACDC enabled in PayPal account

### Authorization Expired
- ✓ Check order age (max 29 days)
- ✓ Verify reauthorization working
- ✓ Create new order if > 29 days

### Order Stuck
- ✓ Check webhook delivery
- ✓ Run cleanup job
- ✓ Check timeout setting (default 60 min)

More troubleshooting in [PayPal_Documentation_README.md](PayPal_Documentation_README.md)

---

## 📊 File Sizes

| File | Size | Type |
|------|------|------|
| PayPal_Module_Documentation.md | 83 KB | Text |
| PayPal_Module_UML_Diagram.puml | 18 KB | PlantUML |
| PayPal_Module_Sequence_Diagrams.puml | 19 KB | PlantUML |
| VSDX_CONVERSION_GUIDE.md | 8.7 KB | Text |
| PayPal_Documentation_README.md | 11 KB | Text |
| convert_plantuml.py | 6.3 KB | Python |
| convert_to_vsdx.sh | 2.0 KB | Bash |
| convert_instructions.md | 2.3 KB | Text |

**Total Documentation Package**: ~150 KB

---

## 🔗 External Resources

### Official Documentation
- **PayPal API**: https://developer.paypal.com/docs/api/
- **OXID Module**: https://docs.oxid-esales.com/modules/paypal-checkout/
- **PlantUML**: https://plantuml.com/

### Tools
- **Draw.io**: https://app.diagrams.net/
- **PlantUML Server**: http://www.plantuml.com/plantuml/uml/
- **VS Code PlantUML**: Search "PlantUML" in extensions

---

## 📝 Version Information

- **Module Version**: 2.6.2-rc.4
- **OXID Version**: Compatible with OXID 6.x
- **PayPal API**: v2
- **Documentation Date**: 2025-10-07

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

---

## ✨ What's Included

- ✅ Complete business documentation (15,000+ words)
- ✅ UML class diagram (all classes and relationships)
- ✅ 6 sequence diagrams (payment flows)
- ✅ VSDX conversion guide (5 methods)
- ✅ Python conversion script (automated)
- ✅ Quick start guides
- ✅ Testing scenarios
- ✅ Troubleshooting tips
- ✅ Architecture overview
- ✅ Database schema
- ✅ Configuration reference

**Everything you need to understand and work with the PayPal module!**

---

**Happy coding! 🚀**
