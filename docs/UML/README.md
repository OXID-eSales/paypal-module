# PayPal Module UML Documentation

This directory contains comprehensive PlantUML diagrams documenting the PayPal module architecture and payment flows.

## Overview

The documentation has been updated on **2025-10-07** to include complete coverage of all payment methods, system architectures, and workflows.

## Diagram Files

### 1. Core Architecture

#### PayPal_Module_UML_Diagram.puml
**Main class diagram showing the complete module architecture**

Components documented:
- **Controllers Layer**: All frontend and admin controllers including vaulting controllers
- **Service Layer**: 20+ services including:
  - Core payment service
  - Order management
  - Vaulting services
  - Google Pay integration
  - Various helper services
- **Model Layer**: Database models and entities
- **Webhook System**: Complete webhook architecture
- **Event System**: Symfony EventDispatcher integration
- **PayPal API**: Integration with PayPal API v2

### 2. Payment Flow Sequence Diagrams

#### PayPal_Module_Sequence_Diagrams.puml
**Complete payment flows for all payment methods**

Includes:
- **Standard PayPal Payment** - Direct capture flow
- **ACDC (Card) Payment Flow** - With 3D Secure authentication
- **Authorization with Capture on Delivery** - For delayed capture
- **Google Pay Flow** - Tokenized Google Pay payments
- **Refund Flow** - Full and partial refunds
- **PUI (Pay Upon Invoice) Flow** - German invoice payment method
- **uAPM Flow** - Universal Alternative Payment Methods (Giropay, SEPA, iDEAL, EPS, BLIK, Przelewy24, Bancontact)

### 3. Apple Pay

#### PayPal_ApplePay_Flow.puml
**Apple Pay payment integration**

Documents:
- Apple Pay SDK initialization
- Native iOS/Safari payment sheet
- Biometric authentication (Touch ID/Face ID)
- Token-based payment processing
- 3D Secure validation
- Webhook confirmation

### 4. Vaulting System

#### PayPal_Vaulting_Architecture.puml
**Complete vaulting architecture and workflows**

Includes 4 diagrams:
1. **Vaulting Architecture** - Component diagram showing system design
2. **Vaulting During Purchase Flow** - Save payment method during checkout
3. **Using Vaulted Payment Flow** - Express checkout with saved methods
4. **Managing Vaulted Payments** - Account page token management

Features:
- Save payment methods for future use
- PayPal customer ID management
- Payment token lifecycle
- Account page integration

### 5. Webhook System

#### PayPal_Webhook_System_Architecture.puml
**Webhook event processing architecture**

Includes 3 diagrams:
1. **Webhook System Components** - Component architecture
2. **Webhook Processing Sequence** - Complete event handling flow
3. **Webhook Handler Base Class** - Template method pattern

Features:
- Signature verification
- Event dispatching
- Handler mapping
- Error handling and retry logic
- 6 event handlers:
  - PAYMENT.CAPTURE.COMPLETED
  - CHECKOUT.ORDER.COMPLETED
  - CHECKOUT.ORDER.APPROVED
  - PAYMENT.CAPTURE.REFUNDED
  - PAYMENT.CAPTURE.DENIED
  - CHECKOUT.PAYMENT-APPROVAL.REVERSED

### 6. Event System

#### PayPal_Event_System_Architecture.puml
**Symfony EventDispatcher integration**

Includes 4 diagrams:
1. **Event System Components** - Component architecture
2. **Order Completed Event Flow** - PayPalOrderCompletedEvent processing
3. **Vaulting Succeeded Event Flow** - PayPalVaultingSucceededEvent handling
4. **Subscriber Registration** - Configuration and setup
5. **Event System Benefits** - Advantages of event-driven architecture

Events:
- **PayPalOrderCompletedEvent** - Dispatched when payment is completed
- **PayPalVaultingSucceededEvent** - Dispatched when vaulting succeeds

## Payment Methods Supported

1. **Standard PayPal** - PayPal account payments
2. **ACDC (Advanced Credit and Debit Card)** - Direct card payments with 3DS
3. **Google Pay** - Google Wallet integration
4. **Apple Pay** - Apple Wallet integration
5. **PUI (Pay Upon Invoice)** - German bank transfer method
6. **Giropay** - German online banking
7. **SEPA Direct Debit** - European bank transfer
8. **iDEAL** - Dutch online banking
9. **EPS** - Austrian online banking
10. **BLIK** - Polish mobile payment
11. **Przelewy24** - Polish online banking
12. **Bancontact** - Belgian online banking

## Generated SVG Files

All PUML diagrams have been converted to SVG format for easy viewing and documentation:

**Total: 22 SVG diagrams**

Located in: `../SVG/`

### Class Diagrams
- PayPal Module Class Diagram.svg (256 KB)

### Sequence Diagrams
- Standard PayPal Payment - Direct Capture.svg
- ACDC Card Payment Flow.svg
- Authorization with Capture on Delivery.svg
- Google Pay Flow.svg
- Apple Pay Payment Flow.svg
- PUI Payment Flow.svg
- uAPM Payment Flow.svg
- Refund Flow.svg

### Vaulting Diagrams
- Vaulting Architecture.svg
- Vaulting During Purchase Flow.svg
- Using Vaulted Payment Flow.svg
- Managing Vaulted Payments.svg

### Webhook System Diagrams
- Webhook System Components.svg
- Webhook Processing Sequence.svg
- Webhook Handler Base Class.svg

### Event System Diagrams
- Event System Components.svg
- Order Completed Event Flow.svg
- Vaulting Succeeded Event Flow.svg
- Subscriber Registration.svg
- Event System Benefits.svg

## Generating SVG Files

To regenerate all SVG files from PUML sources:

```bash
cd /path/to/paypal/docs
make svg
```

This uses Docker to run PlantUML and generate SVG files in the `SVG/` directory.

### Makefile Commands

- `make svg` - Generate all SVG files
- `make clean` - Remove all generated SVG files
- `make help` - Show available commands

## Architecture Highlights

### Service-Oriented Architecture
- Clean separation of concerns
- Single Responsibility Principle
- Repository pattern for data access
- Factory pattern for complex object creation

### Event-Driven Design
- Symfony EventDispatcher integration
- Loose coupling between components
- Easy extensibility
- Asynchronous processing support

### Webhook Processing
- Signature verification for security
- Event dispatching to specialized handlers
- Automatic retry logic
- Error handling and logging

### Payment Security
- 3D Secure (SCA) authentication
- PCI-compliant hosted card fields
- Tokenized payments (Google Pay, Apple Pay)
- Signature verification for webhooks

### Transaction Tracking
- Complete audit trail in database
- Multiple transaction types per order (authorization → capture → refund)
- Status tracking at each stage
- Refund history

## Database Schema

### Main Tables

**oscpaypal_order**
- Links shop orders to PayPal orders
- Tracks transactions (capture, authorization, refund)
- Stores payment method details
- PUI-specific fields (IBAN, BIC, payment reference)

**oxorder** (extended)
- Custom transaction statuses for PayPal
- Payment tracking fields
- Integration with webhook system

**oxuser** (extended)
- `oscpaypalcustomerid` field for vaulting
- Enables saved payment methods

## Key Features Documented

### Capture Strategies
1. **Direct Capture** - Immediate payment capture
2. **Capture on Delivery** - Delayed capture when order ships
3. **Authorization** - Hold funds, capture later

### 3D Secure Validation
- Enrollment status checking
- Authentication result validation
- Liability shift verification
- Configurable contingency strategies

### Vaulting
- Save payment methods during purchase
- Manage saved payment tokens
- Express checkout with vaulted methods
- Delete payment tokens from account page

### Webhook Reliability
- PayPal signature verification
- 25 retries over 3 days
- Fallback polling mechanism
- 60-second webhook timeout

## Integration Points

### PayPal APIs Used
- **Orders API v2** - Create, capture, authorize orders
- **Payments API v2** - Capture authorizations, refunds
- **Vaulting API v1** - Manage payment tokens
- **Webhook API v1** - Verify signatures
- **Identity API** - User information

### OXID Integration
- Module system
- Service container
- Event dispatcher
- Email system
- User management
- Order management

## Documentation Standards

All diagrams follow PlantUML best practices:
- Clear component separation
- Consistent color schemes
- Detailed notes and annotations
- Sequence diagram numbering
- Actor and participant labeling

## Updates and Maintenance

**Last Updated:** 2025-10-07

**Changes:**
- Added missing services to class diagram
- Created PUI payment flow diagram
- Created uAPM payment flow diagram
- Created Apple Pay flow diagram
- Created complete vaulting architecture
- Created webhook system architecture
- Created event system architecture
- Updated Makefile for all diagrams
- Generated all SVG files

## Future Enhancements

Potential additions:
- Database schema diagram
- Order state machine diagram
- 3D Secure decision tree flowchart
- API integration patterns
- Error handling flowcharts

## Contact

For questions about this documentation:
- Module: OXID PayPal Module v6
- Documentation Date: October 2025
- PlantUML Version: Latest (via Docker)

---

**Note:** These diagrams are automatically generated from `.puml` source files. To make changes, edit the PUML files and regenerate the SVG files using `make svg`.
