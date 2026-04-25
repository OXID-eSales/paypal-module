# Change Log for PayPal Checkout for OXID (API Client Component)

All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### FIX

- [0007924](https://bugs.oxid-esales.com/view.php?id=7924): Fix non-PayPal orders being rolled back when the payment gateway redirects with `exit()` during `_executePayment()` (e.g. Mollie credit card). `finalizeOrder()` wrapped `parent::finalizeOrder()` in a DB transaction to hold a `SELECT ... FOR UPDATE` stock lock, but redirect-based gateways terminate the PHP process before `commitTransaction()` is reached — MySQL then rolls back the open transaction, the `oxorder` row disappears, and `handleMollieReturn` redirects to the payment page with `MOLLIE_ERROR_ORDER_NOT_FOUND`. Added an early-return for non-PayPal payments after the paranoia-check so the stock-lock transaction (which is only needed for PayPal's AJAX-capture-plus-redirect race) is skipped for foreign gateways.
- [0007923](https://bugs.oxid-esales.com/view.php?id=7923): Fix PayPal checkout blocked on step 4 when the basket contains an intangible (non-material) product and `blEnableIntangibleProdAgreement` is active. `checkTermsAndConditions()` in `paypal-frontend-payment-controller-base.js` only checked `oxdownloadableproductsagreement` — for baskets with only intangible articles that checkbox does not exist in the DOM, so the check always failed even when the user ticked `oxserviceproductsagreement`. Now both checkboxes are evaluated independently: each one, when rendered, must be checked. Source file and concatenated `paypal-frontend.min.js` were updated.
- [0007927](https://bugs.oxid-esales.com/view.php?id=7927): Fix follow-up corruption after a declined credit-card capture (ACDC). When the first capture attempt was rejected by PayPal (HTTP 200, `status:COMPLETED`, but capture `status:DECLINED` — e.g. `CCREJECT-IF` sandbox card or any real decline), the failed shop order was left open and the frontend kept the old order in `currentOrder.shop`/`currentOrder.paypal`. On the next submit `setShopOrderData` then ran `cancelOrder` on the previous shop order *while the new order was already finalized in the session*, which (a) made `removeTemporaryOrder` issue a `GET /orders/<old>` against the wrong order, and (b) deleted `sess_challenge` of the new order — forcing the captureOrder DB-fallback path on every retry and producing `cancel blocked – PayPal order already approved/captured` warnings two retries later. Three coordinated changes: the ACDC frontend (`paypal-frontend-acdc-payment-controller.js::captureOrder`) now resets `currentOrder` and `captureInProgress` when the backend returns `status:'error'`; `AjaxPaymentController::captureOrder()` stornoes the shop order itself in the declined-capture branch instead of leaving it open; and `Payment::removeTemporaryOrder()` now only deletes `sess_challenge` when the order being cancelled is still the one held in the session (defence-in-depth — a stale frontend triggering `cancelShopOrder` on an old order id can no longer wipe the active session).
- [0007928](https://bugs.oxid-esales.com/view.php?id=7928): Harden Pay upon Invoice (PUI) against `DEVICE_DATA_NOT_AVAILABLE` (HTTP 422) responses caused by a missing Fraudnet load in the customer's browser. The primary include (`pui_fraudnet.tpl` via `pui_wave.tpl` / `pui_flow.tpl` from `shipping_and_payment.tpl` on checkout step 3) is lost whenever a custom theme overrides `shipping_and_payment.tpl` without carrying the PUI partial along — the server still sends the `PayPal-Client-Metadata-Id` header, but no device data was ever collected against that id and PayPal rejects the order. Added a second-chance Fraudnet include on step 4 via the existing `checkout_order_btn_submit_bottom` block extension (`views/blocks/page/checkout/checkout_order_btn_submit_bottom.tpl`), guarded by `paymentId == 'oscpaypal_pui'` so it only fires for PUI. Additionally logs the CMID resolved from the session in `PaymentGateway::doExecutePuiPayment()` at debug level to ease future diagnosis when the header is present but Fraudnet data isn't.
- [0007926](https://bugs.oxid-esales.com/view.php?id=7926): Fix `SyntaxError: Identifier 'buttonXYZ' has already been declared` on product detail pages when a third-party theme re-executes the PayPal button markup after an AJAX variant swap. `views/tpl/shared/paymentbuttons.tpl` rendered `let button{buttonId} = paypal.Buttons(...)` at classic-script top-level, and re-running the script in the same script-block scope tripped the `let` redeclaration guard. Wrapped the generated JS in an IIFE (so `let` is function-scoped) and added an early-return when the button container already has children (idempotent re-init). Wave/Flow were not affected because they do not re-execute the script on variant change; themes that evaluate embedded scripts on AJAX replace did.
- [0007925](https://bugs.oxid-esales.com/view.php?id=7925): Add user-visible fallback for ACDC credit card and Google Pay orders when PayPal rejects the order with an API error (e.g. swapped city/postal code causing `CITY_REQUIRED` or `REQUIRED_PARAMETER_FOR_PAYMENT_SOURCE`). Two controllers called a `doCreatePayPalOrder`/`doPatchPayPalOrder`-chain outside the surrounding try/catch, so the `ApiException` bubbled up and the shop served the offline page instead of JSON — the PayPal/Google Pay overlay closed silently and the customer was stranded on step 4 without any feedback. Both call sites are now wrapped in their own try/catch; on `ApiException` the previously finalized shop order is cancelled (`$order->cancelOrder()`), `sess_challenge` is cleared, and a translated message (`OSC_PAYPAL_ERROR_INVALID_ADDRESS`) is queued via `addErrorToDisplay(..., 'paypal_error')`. ACDC: `AjaxPaymentController::createAcdcOrder()` returns `status: 'error'` plus a `redirect` URL back to `cl=order`; the ACDC frontend controller (`paypal-frontend-acdc-payment-controller.js`) now honours `result.redirect` and sends the browser to the order overview. Google Pay: `OrderController::executeGooglePayOrder()` returns `status: 'ERROR'` (as before); the existing `location.reload()` in `paypal-frontend-googlepay-payment-controller.js` picks the queued displayError up on the next render — no JS changes required. In both flows the customer now lands on a clean order overview with a clear error message and can correct the address.

## [2.8.3] - 2026-04-16

### FIX

- Fix ACDC orders incorrectly cancelled (storno) when payment was already captured. When the AJAX `captureOrder()` flow completed before the browser redirect to `finalizeacdc`, `finalizeOrderAfterExternalPayment()` attempted to capture/authorize the already-captured order, failed with an API error, and cancelled the paid order. Added an early-return guard that detects already-paid orders (via `isOrderPaid()` + `oxtransid`) and skips redundant processing — analogous to the existing guard in `finalizeOrder()`.
- Downgrade log level for "PayPal session canceled" from ERROR to INFO — this message is part of the normal flow when a customer revisits checkout step 4 during an active transaction.
- [0007920](https://bugs.oxid-esales.com/view.php?id=7920): Fix credit card fallback button (`oscpaypal_cc_alternative`) incorrectly shown on PayPal buttons (e.g. product detail page) when vaulting (`oscPayPalSetVaulting`) is disabled. The PayPal SDK parameter `enable-funding=card` was hardcoded unconditionally. Now `card` is only added to `enable-funding` when ACDC eligibility is not given AND the `oscpaypal_cc_alternative` payment method is active.

## [2.8.2] - 2026-04-02

### FIX

- [0007914](https://bugs.oxid-esales.com/view.php?id=7914): Fix Pay upon Invoice (PUI) broken after 2.8.1 security patch. The `_executePayment()` reject guard blocked PUI because it matched `isPayPalPayment()` but was not handled by any prior flow (not a button/proxy/UAPM/checkout payment). Added `gatewaypayment` flag to `PayPalDefinitions` and `isGatewayPayment()` method so PUI correctly falls through to OXID's core `PaymentGateway`.
- [0007915](https://bugs.oxid-esales.com/view.php?id=7915): Fix fatal error "Call to a member function getId() on null" on product detail page when an out-of-stock variant is in the basket. `hasProductVariantInBasket()` delegated to `Basket::getArtStockInBasket()` which calls `getArticle(true)` (buyable-check). For out-of-stock variants this returns null. Replaced with direct basket iteration using `getArticle(false)` to skip the buyable check.
- Fix NoArticleException on product detail page when an out-of-stock variant (configured as "offline when sold out") is still in the basket. The installment banner template called `Basket::getArtStockInBasket()` which internally uses `getArticle(true)` and throws for offline articles. Simplified the banner amount calculation to always add the current product price to the basket total, removing the unnecessary basket iteration. Removed now unused `hasProductVariantInBasket()` method.
- Fix module activation error after upgrade from <= v2.6.1: the old `Service\Logger.php` remained in `source/modules/` because composer does not remove deleted files on copy-based installs. Symfony's resource scanner tried to register the orphaned class and failed. Added an `exclude` for `src/Service/Logger.php` in `services.yaml`.
- [0007916](https://bugs.oxid-esales.com/view.php?id=7916): Fix orphaned oscpaypal_order rows with OSCPAYPALSTATUS=COMPLETED and empty OXORDERID. When `sess_challenge` was cleared before `captureOrder()` completed (e.g. by a concurrent cancel request), the capture flow proceeded and created tracking records that could not be matched by webhook processing. Added defense-in-depth validation at four levels: `AjaxPaymentController::captureOrder()` now aborts if the shop order cannot be resolved, `PayPalOrderCompletedSubscriber` rejects events with empty shopOrderId, `Payment::trackPayPalOrder()` throws on empty shopOrderId, and `OrderRepository::paypalOrderByOrderIdAndPayPalId()` refuses to create new records with empty shopOrderId.
- [0007916](https://bugs.oxid-esales.com/view.php?id=7916): Fix root cause of `sess_challenge` being cleared during active capture. `Payment::removeTemporaryOrder()` unconditionally deleted `sess_challenge` even when the cancel was blocked because PayPal had already approved/captured the payment. Now `sess_challenge` is only deleted when the cancel actually succeeds, keeping the session intact for the concurrent capture flow.
- [0007916](https://bugs.oxid-esales.com/view.php?id=7916): Add database fallback for shop order resolution in `AjaxPaymentController::captureOrder()`. If `sess_challenge` is missing, the shop order is now resolved via the persisted `oscpaypal_order` relationship using the PayPal order ID. Additionally rejects cancelled (storno) orders to prevent tracking against invalidated shop orders.
- [0007916](https://bugs.oxid-esales.com/view.php?id=7916): Fix PayPal capture executed before storno check in `AjaxPaymentController::captureOrder()`. The PayPal capture API call was made before validating whether the shop order had been cancelled, allowing funds to be captured for stornoed orders. Moved order resolution and storno check before the `capturePaymentForOrder()` call so that cancelled orders are rejected without contacting the PayPal API.
- [0007916](https://bugs.oxid-esales.com/view.php?id=7916): Fix redirect to start page when retrying PayPal payment after a blocked capture. After a failed `captureOrder` the JS state (`captureInProgress`, `currentOrder`) was not reset. On retry, `setShopOrderData()` found the stale order reference, called `cancelOrder()` for it, received `cancel_blocked` (old PayPal order meanwhile APPROVED), and redirected away. Reset JS state after a non-success capture so that a subsequent `createOrder` starts cleanly.
- [0007916](https://bugs.oxid-esales.com/view.php?id=7916): Fix redirect to start page instead of thank-you page when the customer retries PayPal payment without reloading the checkout page. The `shopThankYouPageUrl` in the JS config was baked in at initial page render and became stale on retry. `captureOrder()` and `authorizePayment()` now return a fresh `redirectUrl` in the success response. `thankYouPageRedirect()` accepts an optional URL parameter and prefers the server-provided URL over the cached config value. Also consolidated all direct `window.location` assignments in the ACDC controller to use `thankYouPageRedirect()` so that `removeBeforeUnloadListener()` is called consistently before every redirect.
- Fix duplicate order creation for stock-1 articles during PayPal checkout. After the AJAX `captureOrder()` flow completed, `PayPalOrderCompletedSubscriber` cleaned up the PayPal session (`PayPalSession::unsetPayPalSession()`). When the browser then redirected to the order confirmation page, `Order::finalizeOrder()` was called again. Because the PayPal session was already cleared, `isOrderExecutionInProgress()` returned false and the webhook-wait guard did not trigger — causing `parent::finalizeOrder()` to create a second shop order that failed stock validation. Added a guard in `Order::finalizeOrder()` that detects already-paid orders (via `isOrderPaid()` + `oxtransid`) and returns early without creating a duplicate.
- [0007917](https://bugs.oxid-esales.com/view.php?id=7917): Fix tracking carrier country not restored on page reload. When a carrier from the "global" country group was saved, the country dropdown was reset to the order's shipping/billing country instead. Added `getCountryCodeByCarrierKey()` to `PayPalTrackingCarrierList` and `getEffectiveTrackingCountryCode()` to `OrderMain` to resolve the saved carrier's country and pre-select it in the dropdown.

## [2.8.1] - 2026-03-19

### Security

- Fix payment bypass: Orders with PayPal payment methods (Standard, Pay Later, Google Pay) could be placed without completing the PayPal authorization flow by submitting the order form directly. Added server-side guards in `Order::_executePayment()` that require a valid PayPal checkout session (`isPayPalPaymentCheckout`) before accepting payment. Without this flag — which is only set by legitimate PayPal JS flows — the payment is now rejected.

### FIX

- [0007910](https://bugs.oxid-esales.com/view.php?id=7910): Fix copy-paste bug in OrderController and ProxyController: `oxserviceproductsagreement` was incorrectly read from `oxdownloadableproductsagreement`. Added `?? false` fallbacks to prevent PHP notices when `$_POST`/`$data` keys are missing (e.g. Apple Pay flow).
- [0007911](https://bugs.oxid-esales.com/view.php?id=7911): Function 'getRenderer' does not exist or is not accessible! PUI mail sending failed on OXID 6.3 because `Email::getRenderer()` is `private` in oxideshop-ce v6.9.x. Replaced call with direct container access via `TemplateRendererBridgeInterface`.
- Fix PayPal Express button still visible on detail page for out-of-stock products that are configured to be shown but not buyable. Added `isBuyable()` check to the template.
- Fix double-submit on PayPal Express checkout: clicking "Jetzt zahlungspflichtig bestellen" multiple times created duplicate shop orders, where the second order failed with ORDER_ALREADY_COMPLETED. Added frontend double-click protection (button disabled after first submit) and backend guard in PaymentGateway that checks via oxtransid whether another shop order already processed the same PayPal order.
- Fix PayPal Express button not re-initializing after variant selection change on product details page. OXID replaces the product content via AJAX on variant change, but the PayPal button init scripts are not re-executed. Added a MutationObserver (`paypal-frontend-variant-observer.js`) and a hidden config element (`#PayPalButtonProductMainConfig`) to detect DOM changes and re-render the PayPal button with the correct variant article ID. Supports both Wave/Flow (innerHTML) and Apex/Twig (outerHTML) themes.
- [0007912](https://bugs.oxid-esales.com/view.php?id=7912): fix To obtain the correct BaseUrl for webhooks in the EE context, `$conf->getSslShopUrl()` / `$conf->getShopUrl()` must be used instead of `$conf->getCurrentShopUrl()`.

## [2.8.0] - 2026-03-11

### FIX

- Safety guard: never cancel an order that has been successfully captured
- Race condition fix: check PayPal API status before cancelling an order to prevent storno when PayPal has already approved/captured the payment
- Webhook guard: skip processing for stornoed orders to prevent webhooks from writing duplicate transaction data into cancelled orders (defense-in-depth)
- CheckoutOrderApprovedHandler: add storno guard and already-captured check to prevent webhook from capturing a PayPal order when the OXID order was already paid or stornoed
- Prevent double-charge on cross-payment-method scenario: when cancel is blocked (PayPal already approved), redirect customer to thank-you page instead of allowing a new order
- Reduce redundant PayPal API calls: in-memory cache for order details in webhook handlers and skip capture when PayPal order is already COMPLETED
- Clean up PayPal session data and basket order reference when cancel is blocked, so the customer can place a new order without the new PayPal order being mapped to the already-captured shop order
- Set oxtransid immediately after capture in doCapturePayPalOrder() to close the race window between frontend capture and webhook capture
- Log Requests only in debug-Mode
- [0007898](https://bugs.oxid-esales.com/view.php?id=7898): Fix: Webhook always responds 200 to prevent PayPal from retrying failed events 25x over 3 days
- [0007900](https://bugs.oxid-esales.com/view.php?id=7900): Fix: Possible maintenance work in the PayPalOrderController if the PayPal order does not match the OXID order.
- [0007887](https://bugs.oxid-esales.com/view.php?id=7887): Force shipping recalculation after address change from PayPal (sShipSet may still contain old delivery set that is invalid for new country)
- [0007908](https://bugs.oxid-esales.com/view.php?id=7908): Fix because the selection list “paypaltrackingcarrierprovider” is not displayed correctly.
- PPExpress, GooglePay, Applepay: Persist mapping in oscpaypal_order immediately so webhooks can find the shop order even if the customer never returns from PayPal.

### Security

- Fix insecure deserialization in PayPalPlusRefund: add `allowed_classes` restriction to `unserialize()` to prevent PHP Object Injection
- Fix DOM-XSS in Apple Pay success message: escape PayPal API response data before rendering via `innerHTML`
- Harden SQL queries in PayPalSoapOrderCommentList and PayPalSoapOrderPaymentList: validate view name and use backtick quoting
- Fix DOM-XSS in admin carrier provider dropdown: use `createElement`/`textContent` instead of `innerHTML` with unsanitized data
- Remove weak cryptographic nonce fallback in PartnerConfig (`md5`/`uniqid`/`mt_rand` dead code)
- Prevent potential open redirect in OrderController: validate redirect URL against shop domain and PayPal. **Note (fixed in 2.8.3):** The initial whitelist only matched `www.paypal.com` / `www.sandbox.paypal.com`, but PayPal returns uAPM redirect URLs (iDeal, EPS, etc.) without `www.` prefix, which caused a silent redirect to the shop start page instead of the payment page.
- Replace weak `md5`/`uniqid` hashes with `random_bytes` and `sha256` in OrderProcessTrackingService, Onboarding and ServiceFactory
- Add SECURITY.md documenting known security considerations and intentionally unfixed items

### NEW

- add documentation to handle PayPal with stock-reservation [stock-protection.md](docs/stock-protection.md)
- [0007901](https://bugs.oxid-esales.com/view.php?id=7901): cancelsession with logging
- [0007895](https://bugs.oxid-esales.com/view.php?id=7895):

#### Payment Method: PP-Standard / ACDC / GooglePay / ApplePay
Steps inside transaction: validateStock (FOR UPDATE) → save (stock↓) →
executePayment (returns true immediately, skipped via
isPayPalPaymentCheckout)
Lock duration: Milliseconds

#### Payment Method: PP-Express
Steps inside transaction: validateStock (FOR UPDATE) → save (stock↓) →
executePayment (PayPal Capture API call)
Lock duration: 1-3 seconds

#### Payment Method: uAPMs (iDeal, Blik, EPS, ...)
Steps inside transaction: validateStock (FOR UPDATE) → save (stock↓) →
executePayment (creates PayPal order + stores redirect link)
Lock duration: 1-3 seconds

#### Payment Method: Order recalculation
Steps inside transaction: No transaction – stock was already deducted
during original order
Lock duration: n/a

On failure (e.g. capture fails, API timeout): automatic rollback reverts
the stock reduction – no manual cleanup needed.


## [2.7.0] - 2026-02-19

### NEW

- Add option for delaying the webhook. Give the frontend time to persist.

### FIX

- fix OrderNumber-Gaps in PayPalExpress-Orders
- use cancel-Process also for PayPalExpress
- Introduce orderModel->cancelPayPalOrder. It takes over a large part of the tasks of the method PaymentService->removeTemporaryOrder
- fix WorstCase: Start a PP-Order, open in a second tab a PP-Express-Order and finish. Now the first order is clean canceled.

## [2.6.5] - 2026-02-02

### FIX

- [0007872](https://bugs.oxid-esales.com/view.php?id=7872): Pay attention to correct return values.
- [0007874](https://bugs.oxid-esales.com/view.php?id=7874): Fix: PayPal stateand country initialisation in purchase units factory
- [0007883](https://bugs.oxid-esales.com/view.php?id=7883): Fix Edge-Case: finalize an order in a second tab with a different payment
- [0007884](https://bugs.oxid-esales.com/view.php?id=7884): Fix: When canceling and paying again with PayPal, the module does not use the shipping method but the shipping costs
- [0007885](https://bugs.oxid-esales.com/view.php?id=7885): Fix error handling for createShopOrder and improve missing cancelOrder calls
- [0007887](https://bugs.oxid-esales.com/view.php?id=7887): Fix that PayPal Express orders always use only the default shipping method
- [0007888](https://bugs.oxid-esales.com/view.php?id=7888): double check capture-status before capture, to block a race condition
- add customId for PP-Express again
- Merchant ID removed from SDK URL (information is already provided via tokens)
- ApplePay: Remove potential triggers for unnecessary session termination

### NEW

- Performance: Improved AJAX controller response time by 56% through lazy-loaded settings cache and reduced controller inheritance
- Performance: Optimized PayPal standard checkout by combining shop and PayPal order creation into single request
- Performance: uAPMs like iDeal, Blik, EPS, P24 and bancontact are completed on approval
- Performance: Faster login and capture in all "popup-payments" by removing a JS call time delay.
- use PayPal-Client v3.0.20
- Allow better override the button-templates for theme

## [2.6.4] - 2025-12-05

### NEW

- add link to PayPal-Backend from PayPal-Order-Overview-Page (see TransactionID)
- provide ArticleNumbers to PayPal-API-Requests

### FIX

- [0007857](https://bugs.oxid-esales.com/view.php?id=7857): Fix: When cancelling a PayPal order with a voucher and attempting to place the order again
- [0007858](https://bugs.oxid-esales.com/view.php?id=7858): Fix: Maintenance mode when the "PayPal Checkout" tab is clicked for cancelled PayPal orders
- If the customer does not wait until they reach the Thankyou page after clicking "Buy Now" in the PayPal pop-up, an order email will now also be sent in "healing mode".
- clear Cache before deactivate the module, prevent possible maintenance mode in case of other installed modules
- Add handling for worst case: If the customer feels during an order process that something isn't progressing and triggers a cancel order, we check whether the order is already OK and redirect them to the thank you page.
- [0007861](https://bugs.oxid-esales.com/view.php?id=7861): If the customer click close the SEPA-Payment-Overlay, We do not delete the Shipping-Session.
- [0007862](https://bugs.oxid-esales.com/view.php?id=7862): fix: if uAPM Bancontact customer is slower than the webhook, then do not throw an error
- [0007863](https://bugs.oxid-esales.com/view.php?id=7863): fix: Wrong return value causes error and canceled payments with captured money
- [0007864](https://bugs.oxid-esales.com/view.php?id=7864): fix: race condition leads to aborted orders
- [0007865](https://bugs.oxid-esales.com/view.php?id=7865): fix: If the basket contains article with decimal-point-amounts (like 0.5m) then we do not provide the basket-items to PayPal. Because PayPal can only handle whole numbers.

## [2.6.3] - 2025-11-07

### NEW

- Cancel a uAPM order that was ended by the customer using the back button.
- drop codeception-tests

### FIX

- fix: GooglePay send OrderMail
- fix: ApplePay send OrderMail
- fix: PUI-EMail-Handling (also use oxtotalordersum instead oxtotalbrutsum and send mail only once)
- fix: Errorhandlings works in WAVE and Flow

## [2.6.2] - 2025-11-04

### NEW

- add Age Control (min age 18) for PUI
- [0007785](https://bugs.oxid-esales.com/view.php?id=7785): Fix: If you only fill in one of the fields Tracking carrier or code for PayPal orders, an alert inform that you need all fields
- show Express-Buttons only if Basket > 0 or ArticlePrice > 0
- [0007821](https://bugs.oxid-esales.com/view.php?id=7821): use finalizeOrder for all payments again
- use PayPal-Client v3.0.19 (same as O7)
- Add handling for worst case: When the order is completed and the customer clicks around, the customer is redirected to the thank you page
- Add new webhook 'VAULT.PAYMENT-TOKEN.CREATED'
- 3ds related mechanisms moved to SCAValidator service

### FIX
- [0007822](https://bugs.oxid-esales.com/view.php?id=7822): use existing DeliveryAddresses instead creating new once
- use the loading-animation from backend also in frontend
- faster Checkout
- fix: GooglePay-Button-Integration
- fix: ApplePay-Button-Integration
- fix: capture in the backend only for PayPal-Orders
- [0007450](https://bugs.oxid-esales.com/view.php?id=7822): Mandatory field is ignored - old bugticket, but occurred again
- do not authorize an order in express-flow, before the "pay now" button is clicked.
- fix: floating point error when performing a manual refund

## [2.6.1] - 2025-08-18

### FIX

- [0007817](https://bugs.oxid-esales.com/view.php?id=7817): Fix: In step 4 (cl=order), the shop freezes with 500 errors - Internal Server Error on POST Request
- Inform the customer that the order cannot be changed after capture
- update the BN-Code to OXID_Cart_PPCP_fromv261
- fix Maintenance for canceled PayPal-Orders in the Order-PayPal-Checkout-Tab
- use Backend-Option SCA-Method for 3dS Handling

## [2.6.0] - 2025-07-28

### NEW

- PayPal-Buttons are configurable
- Use PayPal-Button also on Checkout-Page for PayPal-Standard, this Button triggers a popUp instead of a page redirect
- All PayPal payment methods have "PayPal" as a prefix in a fresh installation to better identify them in the admin panel. When setting up payment methods, the merchant can decide which name best fits their needs.
- Additional check of eligibility for unbranded payments
- CreditCard (ACDC) and Vaulting works now internally with card-fields-component. Fewer PayPal SDK resources are needed
- Move PayPal-Config to Config-Section in Admin > Module-List > Module > Options
- provide orderNumber also for GooglePay
- Better handling when storing credit card data in the customer account
- switch to experience context and drop deprecated application_context
- Better style and Localisations for ACDC and Vaulting
- Saving vaulted payment methods is now only possible via checkout
- Trim the item names for PayPal properly
- Switch to ServerSide-API-Calls instead of deprecated ClientSide-API-Calls
- use PayPal-Client v2.0.22
- send PUI-Bankdata via eMail during Checkout
- Googlepay payment 3ds support
- Refresh button added in the customer account area to update the vaulted payment methods list
- Added additional payment process tracking id for enhanced processes logging

### FIX

- Fix: Locales for PP-Buttons are editable again
- Fix: captured order could not be changed in the backend
- [0007807](https://bugs.oxid-esales.com/view.php?id=7807): Fix: cleanUpNotFinishedOrders() check for cancellation status

## [2.5.3] - 2025-04-04

### FIX

- [0007769](https://bugs.oxid-esales.com/view.php?id=7769): Performance: Cache the Data-Client-Token for 24h & load SDK only if necessary
- use PayPal-Client v2.0.19
- set connect-timeout for 5 Seconds and request-timeout for 30 seconds
- [0007771](https://bugs.oxid-esales.com/view.php?id=7771): PayPal can only work with two decimal places. For shops with configured additional decimal places, the corresponding rounding takes place
- [0007772](https://bugs.oxid-esales.com/view.php?id=7772): Fix pay in nettomode
- Fix line item amounts in case of discounts (Discussion here https://forum.oxid-esales.com/t/paypal-modul-2-5-1-fehler-bei-rabatten-fehler-die-1223354igste/99472)
- Add stronger indication of required domain registration for Apple Pay
- [0007775](https://bugs.oxid-esales.com/view.php?id=7775): Fix Re-starting the PayPal checkout process on the product page duplicates the cart's items
- Fix Issues with refunding in different Currencies
- Fix Losing connection/page refreshing during the PayPal checkout process results in the PayPal option disappearing
- Fix The quantity is overlooked during PayPal checkout initiation from the product page
- Fix Cancellation of PayPal Express Checkout deletes items stored in Cart
- Fix Processes Duplicate Refunds on Rapid Refund Button Clicks
- [0007776](https://bugs.oxid-esales.com/view.php?id=7776): Fix Google Pay and Apple Pay always use the first shipping method
- [0007765](https://bugs.oxid-esales.com/view.php?id=7765): Fix Don't send order mails twice for Google- and Apple Pay

### NEW

- Stronger indication of required domain registration for Apple Pay

## [2.5.2] - 2025-02-06

### FIX

- Catch possible thrown Error by getting DataClientToken
- [0007719](https://bugs.oxid-esales.com/view.php?id=7719): Tracking code also be stored in standard DB field for backwards compatibility
- add possibility to ignore cached tokens. It helps, e.g., for webhook registration
- use PayPal-Client v2.0.18
- [0007744](https://bugs.oxid-esales.com/view.php?id=7744): When using voucher shop jumps back to payment selection
- [0007745](https://bugs.oxid-esales.com/view.php?id=7745): PayPal checkout jumps back to step 2 with an error when an discount in relation to item value is used
- [0007742](https://bugs.oxid-esales.com/view.php?id=7742): You get stuck in the checkout if the "Save payment method" option is activated for credit card payment
- [0007695](https://bugs.oxid-esales.com/view.php?id=7695): Explain better Pseudo delivery costs
- Show vaulted Payments filtered by payment-method in account-view
- fix issue with Google Pay (await for the complete execution)
- fix Remove SEPA payment method of it is not eligible, temporary solution
- [0007760](https://bugs.oxid-esales.com/view.php?id=7760): PayPal return type and B2B Module
- Change the onboarding process to "without return URL & return button"
- [0007764](https://bugs.oxid-esales.com/view.php?id=7764): Fix TotalPrice for Apple Pay

### NEW

- Custom id passed to PayPal as JSON with additional versioning data

## [2.5.1] - 2024-09-20

### FIX

- [0007713](https://bugs.oxid-esales.com/view.php?id=7713): Correct SQL for select temporary Orders
- [0007584](https://bugs.oxid-esales.com/view.php?id=7584): Provide additional oxrights-elements for PayPal-Express, ApplePay and GooglePay-Buttons
- [0007161](https://bugs.oxid-esales.com/view.php?id=7161): Removing payment method deactivation during module deactivation. Merchants must now do this themselves
- provide correct encoded Shopname to PayPal
- Fix order of closing brackets in applepay-template
- [0007711](https://bugs.oxid-esales.com/view.php?id=7711): Temporary orders that are no longer needed and already have an order number will be cancelled. Temporary orders without an order number will still be deleted
- Provide BN codes even to previously overlooked API calls
- [0007706](https://bugs.oxid-esales.com/view.php?id=7706): If a Customer changes the invoice-address on last page in checkout and uses this address as deliveryaddress (checkbox invoiceaddress as deliveryaddress), then this changed address would be transferred to PayPal
- Fix PHP7.3 Compatibility-Issues (remove functionalities that comes with later PHP-Versions)

## [2.5.0] - 2024-08-16

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
- [0007681](https://bugs.oxid-esales.com/view.php?id=7681): fix OXID Logger.ERROR: Call to a member function getFieldData() on bool
- [0007675](https://bugs.oxid-esales.com/view.php?id=7675): fix the possibility to finish order without redirect and login to Paypal
- [0007676](https://bugs.oxid-esales.com/view.php?id=7676): If we have a corrupted generated_services.yaml and try to deactivate the module via the admin, we will display a more understandable error message about what happened.
- introduce ActionHash to make the PayPal-Request-ID more unique

### NEW
- PayPal-Request-Id based on serialized body, no extra PayPal-Request-Id necessary anymore
- Introduce GooglePay-Payment
- Introduce ApplePay-Payment
- use PayPal-Client v2.0.15
- add Default-Shippingcosts for PP-Express to prevent overcharge.
- mark GiroPay as deprecated

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
- [0007536](https://bugs.oxid-esales.com/view.php?id=7536): PayPal Checkout—Values are stored correctly in the YAML
- [0007543](https://bugs.oxid-esales.com/view.php?id=7543): New Color-Codes for Banner: gray, monochrome, greyscale
- [0007547](https://bugs.oxid-esales.com/view.php?id=7547): PayPal error messages are written into separate log (/log/paypal/paypal_YYYY-MM-DD.log)

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
- [0007468](https://bugs.oxid-esales.com/view.php?id=7468): JavaScript error in checkout step 3 for the English language
- [0007465](https://bugs.oxid-esales.com/view.php?id=7465): Creditcard input fields are not available in english language
- [0007470](https://bugs.oxid-esales.com/view.php?id=7470): PayPal Express buttons are missing in the english language
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
- [0007470](https://bugs.oxid-esales.com/view.php?id=7470): PayPal Express buttons are missing in the english language
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
