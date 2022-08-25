# Change Log for PayPal Checkout for OXID (API Client Component)

All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Undecided] - unreleased

### Changed
- Method `OxidSolutionCatalysts\PayPal\Controller\OrderController::renderAcdcRetry()` converted to `OxidSolutionCatalysts\PayPal\Controller\OrderController::renderRetryOrderExecution()`.

### Fixed
- [0007346](https://bugs.oxid-esales.com/view.php?id=7346) Update configuration to be able to force 3DSecure check for ACDC payments. Ensure 3D check result is validated depending on configuration.
- PUI order in case of invalid phone number will now stay on order page and user can retry.

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
