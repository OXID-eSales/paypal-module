# Change Log for PayPal Checkout for OXID (API Client Component)

All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [2.5.0] - 2024-??-??

### FIX

- Fix admin block parent call, thanks to Alpha-Sys
- Fix Errorlog-Message "Duplicate entry ..." + fix Update send PUI-Bankdata via Webhook
- Fix PayPalExpress Reauth is necessary if the cart amount (total is greater than before) has changed during the checkout process
- Fix, don't show vaulting-Boxes if it is deactivated in Backend
- [0007656](https://bugs.oxid-esales.com/view.php?id=7656): Fix incompatibility with Klarna-Module
- better Vaulting-Check in PaymentController
- disable Vaulting-Setting if Vaulting not possible
- [0007666](https://bugs.oxid-esales.com/view.php?id=7666): Fix: Price surcharges on the detail page for selection lists are not taken into account
- disable Vaulting-Option of Creditcard if Creditcard are not eligible
- Automatically save Apple Pay certificates during the Apple Pay eligibility check

### NEW
- PayPal-Request-Id based on serialized body, no extra PayPal-Request-Id necessary anymore
- Introduce GooglePay-Payment
- Introduce ApplePay-Payment
- use PayPal-Client v2.0.14
- add Default-Shippingcosts for PP-Express to prevent overcharge.

## [2.4.0] - 2024-04-04

### FIX
- [0007588](https://bugs.oxid-esales.com/view.php?id=7588): Improve Error handling for Capture Order Requests (thanks to mount7)
- remove Sofort and MyBank, Paymentmethods will soon no longer be accepted via PayPal
- fix: Refund only with note to Buyer (required)
- [0007595](https://bugs.oxid-esales.com/view.php?id=7595): : Fix PayPal Checkout substract discount from coupon series again, if 'Show net prices in frontend (B2B)' is active
- use PayPal-Request-Id for every api-call
- use PayPal-Client v2.0.12

### NEW
- PayPal Vaulting https://developer.paypal.com/braintree/docs/guides/paypal/checkout-with-vault/

## [2.3.4] - 2024-01-26

- Transfer OXID-Ordernumber to PayPal
- PayPal-Log consider Shop-ErrorLogLevel
- Composer-Installation now via packagist.
  - https://packagist.org/packages/oxid-solution-catalysts/paypal-module
  - https://packagist.org/packages/oxid-solution-catalysts/paypal-client
-  "Repositories"-requirement for Source https://paypal-module.packages.oxid-esales.com/ not need anymore

## [2.3.3] - 2023-11-16

- [0007549](https://bugs.oxid-esales.com/view.php?id=7549): Optional field in shop admin -> refund "Note to buyer" is transmitted to PayPal
- reduce transmitted BN Codes from three to one

## [2.3.2] - 2023-10-05

- [0007537](https://bugs.oxid-esales.com/view.php?id=7537): Show PayNow-Button on PP-Standard instead of Continue-Button
- [0007531](https://bugs.oxid-esales.com/view.php?id=7531): Correct Handling of Vouchers from Voucher-Series
- [0007536](https://bugs.oxid-esales.com/view.php?id=7536): PayPal Checkout - Values are stored correctly in the YAML
- [0007543](https://bugs.oxid-esales.com/view.php?id=7543): New Color-Codes for Banner: gray, monochrome, greyscale
- [0007547](https://bugs.oxid-esales.com/view.php?id=7547): PayPal error messages are written into seperate log (/log/paypal/paypal_YYYY-MM-DD.log)

## [2.3.1] - 2023-08-17

# Fixed
- [0007493](https://bugs.oxid-esales.com/view.php?id=7468): Dont cleanup possible valid orders
- [0007502](https://bugs.oxid-esales.com/view.php?id=7502): Better Mandantory-Fields-Errormessage for PayPalExpress. And got phonenumber from Customer via PPExpress. (only if PP-Merchant has activated in Merchant-Account. -> [Merchant-Preferences Sandbox](https://www.sandbox.paypal.com/businessmanage/preferences/website)
 [Merchant-Preferences Live](https://www.paypal.com/businessmanage/preferences/website)). This is a solution when phone numbers are mandatory fields in the store
- [0007497](https://bugs.oxid-esales.com/view.php?id=7497): OXTRANSSTATUS is changed to OK after successful PPStandard-Payment with direct-capture
- Fix compatibility-Issue with parallel-operation between PPCheckout and Old PP-Module
- Costs or discounts for PayPal payment methods no longer block the checkout

## [2.3.0] - 2023-05-24

### Added
- active payment methods are recognized after deactivating and activating the module and activated again correctly
- in the backend there are new options for control the deleting of unfinished orders
- New Payment "SEPA" and an alternative CreditCard-Payment as fallback if CreditCard via ACDC is not possible
- Send Tracking-Information to PayPal
- improved tests and static code analysis

# Fixed
- [0007468](https://bugs.oxid-esales.com/view.php?id=7468): Javascript Error - in checkout step 3 for the English language
- [0007465](https://bugs.oxid-esales.com/view.php?id=7465): Creditcard input fields are not available in english language
- [0007470](https://bugs.oxid-esales.com/view.php?id=7470): PayPal Express buttons are missing in english language
- [0007467](https://bugs.oxid-esales.com/view.php?id=7467): Javascript Error - not clickable payment button
- [0007466](https://bugs.oxid-esales.com/view.php?id=7466): SEPA / CC Fallback - Same name for different payment methods
- [0007384](https://bugs.oxid-esales.com/view.php?id=7384): Order and Mail for rejected credit card payment
- [0007394](https://bugs.oxid-esales.com/view.php?id=7394): Price reduction by payment method blocks order
- [0007422](https://bugs.oxid-esales.com/view.php?id=7422): Same state/county IDs may lead to wrong display on PayPal page
- [0007448](https://bugs.oxid-esales.com/view.php?id=7448): In case of full refund the value will be refunded according to the full euro
- [0007449](https://bugs.oxid-esales.com/view.php?id=7449): Surcharges with negative Discounts are not forseen
- [0007450](https://bugs.oxid-esales.com/view.php?id=7450): Mandatory tac field is ignored
- [0007451](https://bugs.oxid-esales.com/view.php?id=7451): Creditcard payment works without CVV and Name
- [0007417](https://bugs.oxid-esales.com/view.php?id=7417): It is therefore not possible to order this intangible item
- [0007464](https://bugs.oxid-esales.com/view.php?id=7464): Pending GiroPay payment leads to maintenance mode, after doing a log in
- [0007470](https://bugs.oxid-esales.com/view.php?id=7470): PayPal Express buttons are missing in english language
- [0007466](https://bugs.oxid-esales.com/view.php?id=7466): SEPA / CC Fallback - Same name for different payment methods
- [0007390](https://bugs.oxid-esales.com/view.php?id=7390): New Installation - Save Configuration not possible
- [0007465](https://bugs.oxid-esales.com/view.php?id=7465): Creditcard input fields are not available in english language
- [0007465](https://bugs.oxid-esales.com/view.php?id=7465): Creditcard input fields are not available in english language
- [0007440](https://bugs.oxid-esales.com/view.php?id=7440) Pending orders with oxordernr 0 are deleted before the payment process can be completed
- [0007413](https://bugs.oxid-esales.com/view.php?id=7413) set PPExpress independently of ShippingSets (They will be set later)
- remove an issue with having installed unzer module in parallel



## [2.2.3] - 2023-01-26

### Fixed
- [0007394](https://bugs.oxid-esales.com/view.php?id=7394) Price reduction by payment method blocks order
- onBoarding-Process with fixed PopUps from PayPal
- [0007389](https://bugs.oxid-esales.com/view.php?id=7389) reformat large refund amounts
- [0007388](https://bugs.oxid-esales.com/view.php?id=7388) remove Fraudnet CmId for PUI in any case (success, error ...)
- [0007387](https://bugs.oxid-esales.com/view.php?id=7387) check basketcount to avoid createOrder with zero articles
- [0007382](https://bugs.oxid-esales.com/view.php?id=7382) add the customers to the correct usergroup during PP-checkout
- [0007380](https://bugs.oxid-esales.com/view.php?id=7380) patch the order only if paypalOrderId exists
- [0007377](https://bugs.oxid-esales.com/view.php?id=7377) fix wrong deliveryset during pp-express
- [0007385](https://bugs.oxid-esales.com/view.php?id=7385) Handle PayLater-Failed-Orders as same as PayPal-Standard-Orders
- [0007374](https://bugs.oxid-esales.com/view.php?id=7374) Fixed maintenance during manual saving of configuration
- [0007376](https://bugs.oxid-esales.com/view.php?id=7376) use same declaration as in Core (_executePayment)

## [2.2.2] - 2022-10-18

### Fixed
- [0007366](https://bugs.oxid-esales.com/view.php?id=7366) Not only cancel unsuccessful orders, but also delete them

## [2.2.1] - 2022-10-14

### Fixed
- [0007363](https://bugs.oxid-esales.com/view.php?id=7363) Updated PaymentController to correctly display other non-Paypal payments when net mode is enabled

## [2.2.0] - 2022-10-05

### Added
- Column `oscpaypal_order.oscpaypaltransactiontype` to distinguish capture, authorization, refund transactions when tracking.
- Default values for `oscpaypal_order.oscpaypaltransactionid` and `oscpaypal_order.oscpaypalstatus`.
- Webhook handler `OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\PaymentCaptureRefundedHandler` for `PAYMENT.CAPTURE.REFUNDED`.
- Exception class `OxidEsales\Eshop\Core\Exception\StandardException\CardValidation`.
- Class `OxidSolutionCatalysts\PayPal\Service\SCAValidator` and interface `OxidSolutionCatalysts\PayPal\Service\SCAValidatorInterface`
- Public methods
  - `OxidSolutionCatalysts\PayPal\Core\Config::getPayPalSCAContingency()`
  - `OxidSolutionCatalysts\PayPal\Core\Config::alwaysIgnoreSCAResult()`
  - `OxidSolutionCatalysts\PayPal\Core\PayPalSession::unsetPayPalSession()`
  - `OxidSolutionCatalysts\PayPal\Core\ViewConfig::isPayPalBannerActive()`
  - `OxidSolutionCatalysts\PayPal\Core\ViewConfig::showPayPalBasketButton()`
  - `OxidSolutionCatalysts\PayPal\Core\ViewConfig::showPayPalMiniBasketButton()`
  - `OxidSolutionCatalysts\PayPal\Core\ViewConfig::showPayPalProductDetailsButton()`
  - `OxidSolutionCatalysts\PayPal\Core\ViewConfig::getPayPalSCAContingency()`
  - `OxidSolutionCatalysts\PayPal\Exception\PayPalException::cannotFinalizeOrderAfterExternalPayment()`
  - `OxidSolutionCatalysts\PayPal\Model\Order::setOrderNumber()`
  - `OxidSolutionCatalysts\PayPal\Model\Order::isOrderFinished()`
  - `OxidSolutionCatalysts\PayPal\Model\Order::isOrderPaid()`
  - `OxidSolutionCatalysts\PayPal\Model\Order::isWaitForWebhookTimeoutReached()`
  - `OxidSolutionCatalysts\PayPal\Model\Order::hasOrderNumber()`
  - `OxidSolutionCatalysts\PayPal\Model\Order::isPayPalOrderCompleted()`
  - `OxidSolutionCatalysts\PayPal\Service\ModuleSettings::getPayPalSCAContingency()`
  - `OxidSolutionCatalysts\PayPal\Service\ModuleSettings::alwaysIgnoreSCAResult()`
  - `OxidSolutionCatalysts\PayPal\Service\OrderRepository::getPayPalOrderIdByShopOrderId()`
  - `OxidSolutionCatalysts\PayPal\Service\Payment::isOrderExecutionInProgress()`
  - `OxidSolutionCatalysts\PayPal\Service\Payment::setPaymentExecutionError()`
  - `OxidSolutionCatalysts\PayPal\Service\Payment::getPaymentExecutionError()`
  - `OxidSolutionCatalysts\PayPal\Service\Payment::verify3D()`
  - `OxidSolutionCatalysts\PayPal\Service\Payment::getPaymentExecutionError()`

### Changed
- Method `OxidSolutionCatalysts\PayPal\Controller\OrderController::renderAcdcRetry()` converted to `OxidSolutionCatalysts\PayPal\Controller\OrderController::renderRetryOrderExecution()`.
- New Class `OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\WebhookHandlerBase` as base class for all webhook handlers.
- Refactored Webhook Handlers to extend from `OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\WebhookHandlerBase`.
- Use addresses from PayPal only for anonymus PP-Express.
- During module ativation check necessity before running module migrations.
- Do not show orders with `oxorder.oxordernr` equal to zero to customers. Those can be caused by uAPM dropoff scenarios.
- Preparation for: Do not activate Payments during installation

### Removed
- Trait `OxidSolutionCatalysts\PayPal\Traits\WebhookHandlerTrait`
- Interface `OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\WebhookHandlerInterface`, extend Handlers from `OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\WebhookHandlerBase` instead.

### Fixed
- [0007346](https://bugs.oxid-esales.com/view.php?id=7346) Update configuration to be able to force 3DSecure check for ACDC payments. Ensure 3D check result is validated depending on configuration.
- PUI order in case of invalid phone number will now stay on order page and user can retry.
- Fixed missing installment banners and shop start page, search etc.
- [0007357](https://bugs.oxid-esales.com/view.php?id=7357) Product "If out of stock, offline" then the order confirmation mail is missing that item.
- If in progress order with PayPal payment is detected in last order step do not start another payment process, show message instead.
- PayPalExpress detecting non guest shop user account no longer loses PayPal session after login.
- fix CountryCode for United Kindom -> GB

## [2.1.6] - 2022-08-05

- Set ACDC-Orders first in PayPal-Status "CREATED" / OXID-Order-Status "NOT_FINISHED" and later via Webhook into the right status

## [2.1.5] - 2022-08-01

- admin: better reload after refund
- reset not finished order via webhook
- add Country-Restriction for PayPal Express
- write first captured transaction id to oxorder->oxtransid
- change country-restriction from delivery-country to invoice-country
- allow creditcard worldwide
- remove irritating error message in case last item was purchased

## [2.1.4] - 2022-07-01

- add currencies as requirements (see list on in Documentation)
- fix ACDC-Checkout against PPExpress-Button on Order-Page
- additional allow creditcard in Countries: CA, FR, AU, IT, ES, UK, US
- allow PayLater only for: DE, ES, FR, UK, IT, US, AU
- remove Payment OXXO, Trustly, Boleto, Multibanco
- PUI only allowed in Brutto-Shops (normally B2C)
- Basket-Articles transfered only for PUI-Orders to PayPal

## [2.1.3] - 2022-06-28

- fix difference between VAT-Calculation in OXID-Nettomode and PayPal-API
- fix Login with PayPal
- add PayPal Mini-Basket-Buttons

## [2.1.2] - 2022-06-22

- dont show Express-buttons if express-payment is deactivated
- deactivate and reactivate Payments if Module is deactivate and reactivate
- fix translations and errorhandling on PUI

## [2.1.1] - 2022-06-16

- fix wrong basket-calculation in netto-mode

## [2.1.0] - 2022-06-01

- show PUI Banking-Data
- add Option for capture later on PayPal Standard
- fix save Credentials for Subshops

## [2.0.0] - 2022-05-20

- own Version for OXID 6.1 (v1.0)
- own Version for OXID>=6.2 (v2.0)

## [1.0.0] - 2022-03-10

### Changed
- initial release
