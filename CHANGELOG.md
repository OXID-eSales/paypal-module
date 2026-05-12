# Change Log for PayPal Checkout for OXID (API Client Component)

All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [3.7.4] - 2026-??-??

### FIX

- [0007929](https://bugs.oxid-esales.com/view.php?id=7929): Fix PayPal refund rejected with HTTP 400 `INVALID_PARAMETER_SYNTAX` on `/amount/value` when the refund amount crosses the thousand. Observed in production with a refund of 5326.00 EUR; PayPal's response: `{"issue":"INVALID_PARAMETER_SYNTAX","field":"/amount/value","value":"5,326.00","description":"The value of the field does not conform to the expected format."}` — the value was sent with a comma thousands separator (`"5,326.00"`), but PayPal's v2 payments API requires the plain dotted-decimal form (`"5326.00"`). Root cause: `Controller/Admin/PayPalOrderController.php::refund()` built the value via `number_format($refundAmount, $currency->decimal, '.', null)`. Since PHP 8.1 `null` is no longer silently coerced to `""` for the fourth `number_format` argument (`thousands_separator`); instead the function falls back to the default value of the parameter, which is `","` — so any refund amount ≥ 1000 produced a comma-separated string. (PHP < 8.0 happened to coerce `null` to `""` and never showed the bug; the regression surfaced for merchants who upgraded their PHP runtime.) Fix: replace the fourth argument `null` with the explicit empty string `''`. All other `number_format()` call sites in the module that feed PayPal-API payloads (`Service/Factory/PayPalPurchaseUnitsFactory.php`, `Service/PayPalAmountValidator.php`, `Core/PayPalRequestAmountFactory.php`, `Controller/ProxyController.php`) already use `''` — only the refund call site had the regression. No automatic input-coercion or user-facing format hint was added: the refund-amount input itself is parsed via the existing `Str2Float::autoParse()` (so localized "5.326,00" / "5,326.00" / "5326,00" entries on the admin side are accepted) — the bug was purely the outgoing API serialization, fixed at the format-edge. To prevent a similar regression from re-entering elsewhere in the module, all module-internal money-value serializations for PayPal-API payloads were consolidated into a new single-point helper `Core/Utils/AmountFormatter` with two static methods: `format(float, int = 2): string` (dot-decimal, no thousands separator — for value strings like "5326.00") and `formatCents(float, int = 2): string` (digit-only, no separators — for currency-API call sites that expect minor-unit integers like "532600"). All `number_format()` call sites that feed PayPal-API payloads were migrated to call through the new helper: `Core/Utils/PriceToMoney::convert` (the existing main entry point for `Money` objects), `Core/Currency::formatAmountInLowestDenominator` (cents-form), `Service/PayPalAmountValidator::formatAmount` (private helper), `Service/Factory/PayPalPurchaseUnitsFactory::buildAmountArray` + `toMoneyValue`, `Core/PayPalRequestAmountFactory::createAmountWithBreakdown`, `Controller/ProxyController` (the three Apple-Pay JSON `lineItems` rows), and `Controller/Admin/PayPalOrderController::refund`. After the migration the only `number_format()` calls left anywhere in `src/` are the two inside `AmountFormatter` itself — future developers cannot accidentally repeat the `null` fourth-argument mistake without touching that one centralized class. The `Model/PayPalSoapOrderPayment::getRemainingRefundAmount` site (which uses `sprintf('%.2f', …)`) was left as-is on purpose: `sprintf` is locale-insensitive in PHP, the SOAP code path is separate from the REST API path, and the call only produces an admin-display string. Files touched: `src/Controller/Admin/PayPalOrderController.php` + the new `src/Core/Utils/AmountFormatter.php` + the seven migrated call sites listed above.
- [0007946](https://bugs.oxid-esales.com/view.php?id=7946): Fix payment-pending PayPal orders being incorrectly stornoed by the automatic cleanup job, which left the customer with a "paid by PayPal but cancelled in OXID" inconsistency once the late capture webhook arrived. Customer paid with a delayed payment method (uAPM such as iDeal / Bancontact / Blik / EPS / P24, or PayPal Standard with deferred capture) where PayPal returns `APPROVED` immediately and `COMPLETED` only later via webhook (typically minutes, but can be hours/days). The PayPal-side state was therefore "payment pending" in the merchant's PayPal transaction log, while the OXID order sat at `oxtransstatus='NOT_FINISHED'` waiting for the resolving webhook. Two coupled defects turned this benign waiting state into a stuck-storno: **(1) Cleanup overreach.** `OrderRepository::cleanUpNotFinishedOrders` (run at the end of every webhook delivery) treated every `NOT_FINISHED` PayPal order older than the configured cleanup window as an abandoned customer session and called `Order::cancelPayPalOrder()` on it. The existing safety guards in `cancelPayPalOrder` only catch the case where PayPal has already moved to `APPROVED`/`COMPLETED` *at the moment of the cancel* — a uAPM order that PayPal reports as `PAYER_ACTION_REQUIRED` for a few extra minutes slips through, gets stornoed, releases its stock, and is then unreachable for the eventual capture webhook (the defense-in-depth `oxstorno=1` skip in `WebhookHandlerBase::handleWebhookTasks` correctly refuses to "heal" a stornoed order). The customer paid, the merchant kept the money, the OXID order shows `oxtransstatus='ERROR'` + `oxstorno=1`, and the stock that was momentarily released to other customers may already be sold to someone else. **(2) DENIED webhook did not release stock.** When PayPal definitively rejects a payment (`PAYMENT.CAPTURE.DENIED` or its sibling `CHECKOUT.PAYMENT-APPROVAL.REVERSED`), `PaymentCaptureDeniedHandler::markShopOrderPaymentStatus` only set `oxtransstatus='ERROR'` via `markOrderPaymentFailed()`. It did **not** call `cancelOrder()`, so the rejected order sat with `oxstorno=0` and the reserved stock stayed locked indefinitely. Two coordinated changes: (1) `OrderRepository::cleanUpNotFinishedOrders` now `LEFT JOIN`s `oscpaypal_order` and excludes any order whose PayPal-side status is `APPROVED` or `COMPLETED` from the cleanup. Orders with no `oscpaypal_order` row, or with status `CREATED` (customer never approved on the PayPal popup), are still cleaned up as before — the change is narrowly scoped to "real payment-pending" orders. (2) `PaymentCaptureDeniedHandler::markShopOrderPaymentStatus` now calls `$order->cancelOrder()` after `markOrderPaymentFailed()` and `setTransId()`, releasing reserved stock and setting `oxstorno=1` so the rejected order behaves like any other cancelled order. `CheckoutPaymentApprovalReverseHandler` extends the denied handler and inherits this behaviour automatically. **Lifecycle after the fix:** at order placement the OXID order is marked `NOT_FINISHED` with stock reserved, and `oscpaypal_order` carries the `APPROVED` status — clearly visible in the PayPal admin block. The cleanup job leaves it alone. When the `PAYMENT.CAPTURE.COMPLETED` webhook finally arrives, the existing handler flips the order to `oxtransstatus='OK'` and `oxpaid=NOW()`. When `PAYMENT.CAPTURE.DENIED` arrives instead, the order is now properly cancelled with stock released. **Known residual:** if PayPal never sends a final webhook at all (extreme edge case — PayPal-side stuck or webhook delivery permanently failing), the order stays in `NOT_FINISHED`/`APPROVED` indefinitely and the stock stays reserved. A timeout-based safety-net cleanup is intentionally not part of this fix; merchants who hit this case can resolve it via the admin (manual cancel) or via the existing "PayPal capture/cancel" admin actions. A follow-up ticket will track adding an opt-in long-window cleanup with PayPal-API status re-check. **Stock-on-low-stock-shops:** during the pending window the stock is now reserved for the duration PayPal needs to decide. This is a deliberate change from the previous "release after sessiontime" behaviour: a low-stock shop will no longer accidentally over-sell an article to two customers when one of them is in the uAPM webhook-wait window. The trade-off — that the article cannot be re-listed for sale until PayPal resolves — was accepted as the safer default. Files touched: `src/Service/OrderRepository.php`, `src/Core/Webhook/Handler/PaymentCaptureDeniedHandler.php`.
- [0007945](https://bugs.oxid-esales.com/view.php?id=7945): Fix PayPal tracking not pushed to the PayPal API when the merchant entered carrier + tracking code and clicked "Jetzt versenden" in the same submit on the OXID order-admin page, **and** stop the PayPal tracking code from silently overwriting `oxorder.oxtrackcode`, **and** make sure warehouse-management systems (Wawi / ERP) that write `oxorder.oxtrackcode` directly also reach PayPal. Three independent defects in the same flow, fixed together: **(1) Submit-order misordering.** `Controller/Admin/OrderMain.php::save()` ran `sendOrder()` *before* persisting the request parameters `paypaltrackingcarrier` / `paypaltrackingcode` via `Order::setPayPalTracking()`. The `sendOrder()` path triggers the OXID-core hook `onOrderSend`, whose PayPal override calls `Order::doProvidePayPalTrackingCarrier()` which in turn reads `getPayPalTrackingCode()` / `getPayPalTrackingCarrier()` — both of which load from the `oscpaypal_order` table. Because the table was not yet written on this same request (the `setPayPalTracking()` call only ran after `sendOrder()` had already returned), the early-return guard `if (!$trackCode || !$trackCarrier || !$transactionId) { return false; }` in `doProvidePayPalTrackingCarrier()` fired and `Tracker::sendtracking()` was never reached — the merchant saw the order marked as sent in OXID but PayPal never received the tracking event. The merchant could work around it by saving the tracking fields first and clicking "Versenden" only on a second submit, but the natural single-submit workflow silently failed. Fix: in `OrderMain::save()` move the `setPayPalTracking()` call (and the surrounding `if ($trackingCarrier && $trackingCode)` guard) **above** the `if ($request->getRequestParameter("sendorder")) { $this->sendOrder(); }` block so the values are already persisted when the `onOrderSend` chain runs. **(2) `oxorder.oxtrackcode` overwrite.** `Model/Order.php::setPayPalTracking()` carried a "for backwards compatibility" block that called `$this->assign(['oxtrackcode' => $trackingCode]); $this->save();` in addition to writing the value to `oscpaypal_order`. This silently overwrote the OXID-native tracking column, with two consequences: any tracking code the shipping operator had previously entered into the OXID-side tracking field was destroyed by the PayPal save, and the OXID order-confirmation / shipment mail (which renders `oxtrackcode` via the standard email templates — both the Smarty `email/html/order_cust.tpl` and the Twig equivalent) suddenly emitted the PayPal tracking code instead of the operator-set OXID code — making it impossible to tell from the order whether an OXID-native tracking code had ever been recorded. Fix: drop the three "backwards compatibility" lines from `setPayPalTracking()`. The PayPal tracking code now lives exclusively in `oscpaypal_order` (where the PayPal admin block already reads it from via `getPayPalTrackingCode()` and where `doProvidePayPalTrackingCarrier()` already pushes from for the API call), and `oxorder.oxtrackcode` stays the operator-managed OXID-side field. Behavioural change to be aware of: if a merchant relied on the side effect that a PayPal tracking code automatically populated the OXID tracking-mail link, that link now needs the operator to fill `oxorder.oxtrackcode` themselves — which is the intended OXID semantics and matches every other payment method. **(3) Wawi-driven send did not reach PayPal at all.** Warehouse-management systems (JTL, Plenty, custom ERPs) typically do not call the admin URL `?cl=order_main&fnc=sendOrder`; instead they load the order, write `oxorder__oxsenddate` + `oxorder__oxtrackcode` directly on the model and `save()`. The OXID-core `OrderMain::sendOrder()` action is the only place that fires the `onOrderSend` controller hook into which the PayPal module previously plugged the `doProvidePayPalTrackingCarrier()` call — so a Wawi-triggered send did not reach PayPal, regardless of whether the merchant ran a single-payment shop or a mixed setup (e.g. Klarna + PayPal, Mollie + PayPal) where the Wawi-side send routine is identical across payment methods. Additionally, the existing PayPal tracking-data was held in a separate `oscpaypal_order.paypaltrackingcode` field — a Wawi setting only `oxorder.oxtrackcode` had no way to populate it. Two coordinated changes: (a) `Model/Order.php::save()` is now overridden to detect the "shipping moment" — an `oxsenddate` transition from empty / `0000-00-00 00:00:00` to a real timestamp — and, if `paidWithPayPal()` is true on that order, calls `doProvidePayPalTrackingCarrier()` once after `parent::save()` returns. The transition is detected by reading the previous `oxsenddate` from the database before `parent::save()` runs (lightweight `SELECT oxsenddate FROM oxorder WHERE oxid=?` — only fired on `isLoaded()` orders, so the regular order-creation save path during checkout is not perturbed). The auto-push is wrapped in `try/catch(\Throwable)` so a PayPal-side error never breaks the OXID save — failures are logged at `warning` with the order id and exception message for ops diagnosis. This covers every send-trigger path: admin "Versenden" button, Wawi direct save, custom controllers, console scripts, and any future call site that ends up at `Order::save()`. (b) `doProvidePayPalTrackingCarrier()` now has two fallbacks: if no PayPal-specific tracking code is set, it inherits `oxorder.oxtrackcode`; if no PayPal carrier is set but a tracking code is present, it uses PayPal's generic `OTHER` carrier. The `OTHER` fallback was chosen over a configurable default-carrier setting to keep the fix zero-config — the customer still sees the tracking code in their PayPal account, just without a clickable carrier link; a merchant who wants the clickable link enters the carrier in the PayPal admin tab as before. Files touched: `src/Controller/Admin/OrderMain.php`, `src/Model/Order.php`.
- [0007944](https://bugs.oxid-esales.com/view.php?id=7944): Fix vouchers not bound to the order and missing from the order-confirmation mail when the customer pays with PayPal Standard (and other proxy-controller-routed payments: ACDC, Google Pay, Apple Pay). Customer applied a voucher in the basket, completed the PayPal Standard checkout successfully, received the confirmation mail — but the mail did not list the voucher, the voucher discount was not visible on the order in the admin backend, and `oxvouchers.oxorderid` for the redeemed voucher remained empty (so the voucher could not be reconciled to the order in reports). Root cause: the override of `Model/Order.php::markVouchers` introduced by the cancel+retry hardening short-circuits with `return null` whenever `PayPalDefinitions::isProxyControllerPayment($sessionPaymentId)` is true. That guard was correct for the **early** call in `afterOrderCleanUp()` and `sendOrderByEmail()` that runs before the customer is redirected to PayPal — without it, a customer who cancelled the PayPal popup and retried the order would have hit "Voucher already used" because the parent `markVouchers` had already flipped `oxreserved`/`oxorderid`. But the guard was never paired with a **late** call that runs after the external payment has actually been confirmed, so for the proxy-controller payments (`oscpaypal`, `oscpaypal_acdc`, `oscpaypal_googlepay`, `oscpaypal_applepay`) the voucher was simply never bound to the resulting order in any code path. Fix in two coordinated parts: (1) the `markVouchers` override gained a private `$skipProxyControllerVoucherGuard` property that, when true, makes the proxy-controller-skip branch fall through to `parent::markVouchers`; the early pre-redirect calls leave the property at its default `false` so the cancel+retry fix stays intact. (2) `Model/Order.php::finalizeOrderAfterExternalPayment` now invokes `$this->markVouchers($basket, $user)` immediately before the closing `sendPayPalOrderByEmail($user, $basket)` call — wrapped in `try/finally` that flips the property to true around the call and resets it afterwards, including on exception. The call is gated by `if ($payPalPaymentSuccess)` so the binding only happens when the external payment was actually confirmed (avoids binding a voucher for the failure branch of `doExecutePayPalPayment` in the PayPal-Standard direct-capture path). Routing via `$this->markVouchers` (rather than `parent::markVouchers`) is deliberate: foreign modules further up the OXID class chain that override `markVouchers` continue to run as part of the call, so module-stacking compatibility is preserved — the property only switches off **our own** skip guard for this one call, not anyone else's logic. Two effects are restored: `oxvouchers.oxorderid` and `oxvouchers.oxdateused` are now set, and `Order::$_aVoucherList` is populated so the existing order-confirmation templates (Smarty `email/html/order_cust.tpl` and the Twig equivalent under `views/twig/.../email/html/order_cust.html.twig`, plus PDF/admin views) render the voucher line. The skip in `markVouchers` itself is left in place for the early pre-redirect path so the cancel+retry fix remains effective. PayPal Express, PayPal Pay Later, PUI and gateway-redirect uAPMs are not affected because they don't route through `isProxyControllerPayment` — for them `markVouchers` is reached directly via the regular `sendOrderByEmail` path. Files touched: `src/Model/Order.php`.
- [0007941](https://bugs.oxid-esales.com/view.php?id=7941): Fix missing order-confirmation email when an Express / uAPM customer returns from the PayPal popup faster than the PayPal order-status API can flip from `PAYER_ACTION_REQUIRED` to `APPROVED`/`COMPLETED`. Observed sequence on production for a sandbox uAPM checkout: the customer clicked "Buy Now" in the PayPal popup, was redirected to `cl=order&fnc=finalizepaypalsession` 22 seconds later, the shop called `fetchOrderFields()` which still returned `PAYER_ACTION_REQUIRED`, `OrderController::finalizepaypalsession` raised `PayPalException::sessionPaymentFail`, the catch-block evaluated `isOrderSuccessfullyPaid()==false` (the webhook arrived only one second later), called `cancelPayPalSession('cannot finalize order')` (which logged `cancel blocked - PayPal order already approved/captured` because PayPal-side was in fact COMPLETED) and returned `payment?payerror=2`. The customer landed on the payment page with an error, the webhook one second later saw the order as already COMPLETED and skipped the capture, and the shop order stayed unfinished — no confirmation mail was ever sent, even though PayPal had successfully captured the payment. Two coordinated changes, both scoped to the redirect-return flows that share `finalizepaypalsession` (PayPal Express, PayPal Standard with redirect, uAPMs like iDeal / Bancontact / Blik / EPS / P24, Vaulting). ACDC, Apple Pay and Google Pay use their own finalize endpoints and AJAX-capture paths, are less prone to this race, and were left untouched. (1) `OrderController::finalizepaypalsession` no longer routes the customer to `payerror=2` on the first PAYER_ACTION_REQUIRED. The body is now wrapped in a small retry loop — three attempts with one second between attempts and a fresh DB-load each iteration. Each iteration first checks `isOrderSuccessfullyPaid()` (the webhook may have arrived in the meantime and flipped the order status) and short-circuits to `thankyou` if so, then re-fetches the PayPal order-status; if it has flipped to APPROVED/COMPLETED, the regular finalize path runs and the customer reaches `thankyou`. Only after all three attempts failed does the bisheriger `cancelPayPalSession + payerror=2` path run. Customer-visible cost in the normal (non-race) case: zero — the first attempt hits APPROVED/COMPLETED immediately and returns. Worst case in the race: two seconds added to the response time, during which the frontend already shows the "Bestellung wird verarbeitet …" submit-button state from 0007940. The retry-exhausted path now logs at `warning` with the suffix `after retries:` so this situation can be distinguished from the 0007923/0007928-style first-shot failures in the logs. (2) `Model/Order.php::finalizeOrderAfterExternalPayment` early-skip branch (now keyed on `isOrderSuccessfullyPaid()`, which already implies `paidWithPayPal && oxtransstatus=='OK' && oxpaid` — the redundant separate `oxtransid`-check from the previous condition has been dropped) now triggers the order-confirmation mail before returning. Without this, when the retry loop above hits the (a) "Webhook glücksfall" path and calls `finalizeOrderAfterExternalPayment`, the early-skip would silently return without sending mail (the regular `sendPayPalOrderByEmail` lives at the bottom of the method, after the markOrderPaid/setTransId block which the early-skip jumps over). The new trigger pulls `Registry::getSession()->getBasket()` and `getUser()` and calls `sendPayPalOrderByEmail` if both are present — the existing `_sendOrderByEmail` override blocks duplicate sends during an in-flight PayPal checkout via the `isPayPalPaymentCheckout` session-variable guard. Files touched: `src/Controller/OrderController.php`, `src/Model/Order.php`. **What this fix does not solve:** when the customer never returns to `finalizepaypalsession` at all (closes the tab after the PayPal popup), the order remains unfinished and no mail is sent — the webhook would have to send the mail itself, but that requires reconstructing user/basket from order data and is deferred (no marker for "mail already sent" available on `oxorder` without a schema change, which is blocked by the parallel security-release pipeline).
- [0007940](https://bugs.oxid-esales.com/view.php?id=7940): Fix duplicate orders after a concurrent double-submit on the PayPal Express order-confirmation step. When a customer clicked "Bestellen" twice (typically a few seconds apart, because no UI feedback indicated that the first click was being processed), the first submit completed the Express payment successfully. The second submit, which had been waiting on the PHP session lock, then entered `Order::_executePayment` after `PayPalSession::unsetPayPalSession()` had already fired in the first submit. With the PayPal session cleared, `PaymentGateway::doExecutePayPalExpressPayment` skipped its entire body (`getCheckoutOrderId()` returned null), `executePayment` returned false, and `_executePayment` fell into `cancelPayPalOrder()`. The cancel was correctly skipped (the existing `cancel skipped — payment already processed` guard fired), but `_executePayment` then returned `ORDER_STATE_PAYMENTERROR` regardless — the customer landed on `cl=payment&payerror=5` ("Autorisierung der Zahlung fehlgeschlagen"), assumed the payment had failed, and placed the same order again with a different payment method. Two coordinated changes, both PayPal-scoped to keep non-PayPal gateways untouched (cf. 0007924, where the previous lax scoping had caused Mollie regressions): (1) in `Model/Order.php::_executePayment`, **inside** the existing `PayPalDefinitions::isButtonPayment($sessionPaymentId)` branch, when `cancelPayPalOrder()` returns false and the order is already successfully paid (`isOrderSuccessfullyPaid()` + non-empty `oxtransid`), short-circuit to `ORDER_STATE_OK` so the customer reaches the thank-you page instead of a misleading payment error; (2) defense-in-depth in `Model/Order.php::finalizeOrder` after the call to `parent::finalizeOrder`: when the parent returns `ORDER_STATE_PAYMENTERROR` but the order is already paid with a transaction id, override to `ORDER_STATE_OK` — guarded by `getPaymentService()->isPayPalPayment()` so this never affects gateway-redirect payments (Mollie etc., which already early-return before the stock-lock transaction). Both guards leave the existing `cancel skipped` log entry intact for diagnostic purposes; the new `finalizeOrder` override emits an `info` entry "parent returned PAYMENTERROR but order is already paid — overriding to OK" so the situation is visible in operations logs. **Frontend double-click protection** is addressed in a separate, complementary entry below — the existing button-disable did not hold for a 2-second gap on iOS Chrome and has been hardened (form-submit-level lock, mobile touch handling, visible "submitting…" state via the existing global PayPal overlay), in both the Smarty and the Twig template flavour.
- [0007940](https://bugs.oxid-esales.com/view.php?id=7940): Harden the PayPal-Express order-confirmation page against double-submits, complementing the backend idempotency entry above. O7 ships two parallel renderings of the order-confirm submit-button block during the Smarty-to-Twig transition, and **both** had a weak doubleclick guard that failed for a customer on iPhone iOS 26.2 / Chrome (CriOS/147) with a roughly 2-second gap between two taps on the "Bestellen" button: (a) the Smarty block extension at `views/smarty/extensions/themes/default/page/checkout/checkout_order_btn_submit_bottom.tpl` used a single per-button `click` handler with `setTimeout(0)` + `{once: true}` setting `disabled = true` on the clicked button only (re-introduced by an earlier "fix selector for checkout-button" commit and weaker than the original form-submit-level guard); (b) the Twig override at `views/twig/extensions/themes/default/page/checkout/order.html.twig` (block `checkout_order_next_step_side`, inside `paymentId == "oscpaypal_express"`) had an even weaker variant — a plain `this.disabled = true` (no setTimeout, no form-submit hook) bound via `[onclick*="orderConfirmAgbBottom"]`. In both flavours the first tap left the page visually unchanged (no spinner, no text change, no overlay), the customer assumed nothing happened and tapped again, both submits raced through the PHP session lock, and the duplicate-order race described in the entry above triggered. Hardening — UA-independent, no `navigator.userAgent` branches, no module-side CSS, applied identically to **both** template flavours so the protection is engine-independent: (1) the lock is anchored on a single `click` listener on every submit-trigger button, sharing one `submitting` guard variable. A `click` covers all three actual submit triggers in this template — native `type="submit"` (Flow), OXID-JS-driven `form.submit()` / inline-onclick handler for `type="button"` and `[onclick*="orderConfirmAgbBottom"]` (Wave + Apex), and the Enter key (the browser dispatches a synthetic click on the form's submit button). A separate `submit` listener on `<form id="orderConfirmAgbBottom">` was deliberately **not** added: at the order-confirm step the user click on a `type="submit"` button raises both events sequentially (`click` → `submit`), and a guard listener on `submit` would see `submitting=true` set by its own preceding `click` and abort the customer's first, legitimate submit. On mobile a second `touchstart` listener on the same buttons blocks the second tap before its synthetic click fires. Both listeners run in capture phase and use `event.stopImmediatePropagation()` on the second event so the lock wins against any other registered handler — including OXID's own submit logic. The actual lock is the `submitting` flag plus `preventDefault()`/`stopImmediatePropagation()`, which is purely JS-level and works regardless of whether `disabled` is honored mid-tap by the browser; the `disabled = true` (still scheduled via `setTimeout(0)` so the in-flight native submit is not stripped of its button-name form-data) and `aria-busy="true"` / `aria-disabled="true"` flags are the second line. The Twig variant uses a widened selector `.submitButton.nextStep, [type="submit"].nextStep, [onclick*="orderConfirmAgbBottom"]` so a future Apex theme tweak that drops the inline-onclick attribute does not silently lose the protection. (2) Visible "submitting…" state via button label swap to a new translation `OSC_PAYPAL_ORDER_SUBMITTING` (DE "Bestellung wird verarbeitet …", EN "Processing your order …") — emitted via `[{oxmultilang}]` in the Smarty file and `{{ translate({ ident: ... }) }}` in the Twig file — with the original innerHTML preserved in `dataset.oscOriginalLabel` for restoration. The grayed-out button via the browser's default `disabled` styling provides the secondary visual signal — no module-side CSS, no fullscreen overlay (the existing `#paypal-overlay` is not reused here because `PayPalPayment` is not loaded on the order-confirm page in the Express flow). (3) BFCache restore handling via `pageshow` + `event.persisted`: when the customer hits the browser Back button after navigating away, the listener resets the `submitting` flag, removes the `aria-*` attributes, restores the original button label, and re-enables the button — otherwise the customer would land on a frozen "submitting…" button. Scope unchanged: only `oscpaypal_express` (kept inside the existing `paymentId == "oscpaypal_express"` guard in both files) — no other payment IDs, no other PayPal flows; the regular PayPal-button checkout uses the AJAX `createOrdersForPayPal` path with the SDK's own spinner and is unaffected. Files touched: `views/smarty/extensions/themes/default/page/checkout/checkout_order_btn_submit_bottom.tpl`, `views/twig/extensions/themes/default/page/checkout/order.html.twig`, `translations/de/oscpaypal_de_lang.php`, `translations/en/oscpaypal_en_lang.php`. The change is template-inline JavaScript only — the concatenated `paypal-frontend.min.js` is **not** affected.
- [0007939](https://bugs.oxid-esales.com/view.php?id=7939): Fix silent-fail of the PayPal-button JS when the server response to `createOrder` or `approveOrder` is anything other than the two expected shapes. The `onApprove` handler in both template flavours (`views/smarty/frontend/shared/paymentbuttons.tpl` for the Smarty themes and `views/twig/frontend/paymentbuttons.html.twig` for the Twig themes — each with the SEPA/Card-Alternative funding-source loop variant and the standalone PayPal/PayLater button variant, so four logical onApprove sites in total) only branched on `data.status == "ERROR"` (→ `location.reload()`) and `data.id && data.status == "APPROVED"` (→ `location.replace('cl=order')`). Anything else — `PAYER_ACTION_REQUIRED`, `PENDING`, an empty body `{}`, missing `id`, HTML returned because the session cookie was lost (e.g. iOS WKWebView in the Google Search App / Facebook in-app browser, where ITP and app-bound-domains kill the cross-domain cookie roundtrip), `fetch()` rejection on connection reset, `res.json()` rejection on non-JSON response — fell through silently with no `else` and no `.catch()`. Customer saw the PayPal-button spinner forever, no message, no retry path, no log entry server-side. The pattern repeated in all four `createOrder` chains. Four coordinated changes: (1) both `onApprove` chains in both template flavours gained a default branch that reports the unexpected response via `cl=ajaxpay&fnc=logError` and redirects the customer to a new module endpoint `cl=order&fnc=paymentInterrupted` instead of leaving the spinner hanging; (2) all four `fetch().then()` chains (two `createOrder`, two `onApprove`) per template gained a `.catch()` that reports the exception type/message before either redirecting (onApprove) or rethrowing so the PayPal SDK fires its own `onError` (createOrder); (3) `res.json()` wraps its own `.catch()` that turns a parse failure into a thrown error with a `not-json:` prefix so the surrounding catch can distinguish it from a network error; (4) the new `OrderController::paymentInterrupted()` endpoint queues a localized `OSC_PAYPAL_PAYMENT_INTERRUPTED` display error via the existing `RedirectWithMessage` mechanism and forwards the customer to `cl=payment` — the message hints that switching to a standard browser (Safari, Chrome, Firefox) may resolve the issue if the customer is currently in an in-app browser, but stops short of asserting that as the certain cause (silent-fail can also be triggered by transient network issues or reverse-proxy hiccups). The diagnostic POSTs include the User-Agent string, which is the discriminator we need to identify the affected app/browser combinations from server logs. As part of this, `AjaxPaymentController::logError()` was upgraded from `debug` to `warning` and the wording adjusted ("PayPal frontend reported error for order …") so these diagnostic entries surface in operations logs without enabling debug verbosity. **What this fix does not solve:** the customer in the failing app/browser still cannot complete a PayPal payment — the underlying cause (WKWebView cookie restrictions, popup-block in in-app browsers) sits at the platform layer and is outside the module's reach. What changes is that the customer now sees a meaningful error message and can switch payment method or browser, and the server now has data points (UA, error class, received status) to size the affected segment and decide on UA-based detection in a follow-up. The change touches only the inline JS rendered from the two `paymentbuttons` templates; the concatenated `paypal-frontend.min.js` is **not** affected because these templates emit their script bodies directly, not via the bundled JS pipeline.
- Reclassify and reword module log entries to stop misreporting expected user behaviour as system errors. The module previously logged pretty much every PayPal API failure as `error`, including the very common case where the customer never approved the PayPal order on the PayPal popup (`ORDER_NOT_APPROVED` returned from `capturePaymentForOrder`). Operations dashboards alerting on `error`-level entries received false positives whenever a customer abandoned a PayPal Express checkout, and merchants asking "is this dangerous?" had no way to tell from the message itself. Reviewed every `$logger->log()` call in the module and adjusted level and wording so the level reflects severity instead of "an exception happened". Concretely: capture/authorize failures from PayPal user abandonment downgraded from `error` to `warning` (`Model/PaymentGateway.php:145`, `Model/Order.php:652`, `Service/Payment.php:420`, `Controller/OrderController.php` GooglePay/ApplePay capture branches), and the matching message text changed from "Error on order capture call." to "PayPal capture failed or refused: …" so the wording matches the new level. Defense-in-depth guard hits (cancel skipped because order already paid, duplicate finalization skipped, audit-trail entries for order cancel/storno) raised from `debug` to `warning`/`info` so they appear in operations logs as the deliberate safety triggers they are (`Model/Order.php:522`, `Model/Order.php:558`, `Model/Order.php:565`, `Model/Order.php:922`). Permission and validation rejections (foreign-order access, missing parameters in AJAX endpoints) downgraded from `error` to `warning` (`Controller/AjaxPaymentController.php` `permissionsCheck` and related callers, `Controller/OrderController.php::captureApplePayOrder`). Webhook handler entries with mismatched level/wording (`'debug'` for messages literally starting with "Error" / "webhook error") changed to `warning` and reworded (`Core/Webhook/Handler/CheckoutOrderApprovedHandler.php:79`, `Core/Webhook/Handler/VaultPaymentTokenCreatedHandler.php:127`, `Core/Webhook/Handler/VaultPaymentTokenCreatedHandler.php:135`). Recoverable failures with cache/empty fallback (`Core/Api/VaultingService.php` vaulted-token fetch, `Core/ViewConfig.php` client-token fetch) downgraded from `error` to `warning`. Onboarding/eligibility refresh during config save downgraded from `error` to `warning` because the surrounding save operation continues regardless (`Controller/Admin/ModuleConfiguration.php`). Also fixed an unrelated copy-paste bug in `Controller/ProxyController.php:317`: the catch block around `showOrderDetails()` logged "Error on order capture call." even though no capture happens at that point — message corrected to "Could not fetch PayPal order details for ProxyController approveOrder". Plain `$exception->getMessage()` log texts everywhere replaced with a context prefix ("ACDC finalizeOrder failed: …", "GooglePay order execute failed: …", "Failed to fetch PayPal credentials during onboarding: …", etc.) so log lines remain useful when read out of context. As a consequence of the level cleanup, every redundant module-internal verbosity gate (`$moduleSettings->getPayPalDebugLevel()` checks of the form `=== 'debug'`, `=== 'error'`, or `in_array(..., ['debug', 'error'])` wrapping a `$logger->log()` call) was removed across the module. The PSR log-level filtering at the logger configuration is now the single source of truth — the second module-side gate had two effects, both undesirable: it suppressed legitimate `warning` entries depending on the *module debug-level* admin setting (a different concept than the PSR log level), and it made every log call duplicated by an `if`-block which had to be edited in lockstep with the `log()` call beneath it. Affected files: `Controller/AjaxPaymentController.php` (`permissionsCheck`, `isOrderNotSuccessfullyDone`, `cancelShopOrder`, `completeOrder`, `logError`, `authorizePayment` × 2), `Controller/WebhookController.php` (webhook trace), `Service/Payment.php` (order create / order patch / vaulting × 2 / capture / pui creation), `Service/GooglePay/GooglePayPayPalService.php` (finalize-after-external-payment), and `Service/ModuleSettings.php::saveMerchantId`. The `ModuleSettings::getPayPalDebugLevel()` getter and the corresponding admin-setting `oscPayPalDebugLog` are still consumed elsewhere (`Core/ServiceFactory.php` for PayPal-API client request/response logging) and are kept for now. No control-flow changes anywhere else — exceptions are still caught, rethrown, or suppressed in exactly the same places as before; only the log level, wording, and the redundant gates around `$logger->log()` calls were touched.
- [0007924](https://bugs.oxid-esales.com/view.php?id=7924): Fix non-PayPal orders being rolled back when the payment gateway redirects with `exit()` during `_executePayment()` (e.g. Mollie credit card). `finalizeOrder()` wrapped `parent::finalizeOrder()` in a DB transaction to hold a `SELECT ... FOR UPDATE` stock lock, but redirect-based gateways terminate the PHP process before `commitTransaction()` is reached — MySQL then rolls back the open transaction, the `oxorder` row disappears, and `handleMollieReturn` redirects to the payment page with `MOLLIE_ERROR_ORDER_NOT_FOUND`. Added an early-return for non-PayPal payments after the paranoia-check so the stock-lock transaction (which is only needed for PayPal's AJAX-capture-plus-redirect race) is skipped for foreign gateways.
- [0007923](https://bugs.oxid-esales.com/view.php?id=7923): Fix PayPal checkout blocked on step 4 when the basket contains an intangible (non-material) product and `blEnableIntangibleProdAgreement` is active. `checkTermsAndConditions()` in `paypal-frontend-payment-controller-base.js` only checked `oxdownloadableproductsagreement` — for baskets with only intangible articles that checkbox does not exist in the DOM, so the check always failed even when the user ticked `oxserviceproductsagreement`. Now both checkboxes are evaluated independently: each one, when rendered, must be checked. Source file and concatenated `paypal-frontend.min.js` were updated.
- [0007927](https://bugs.oxid-esales.com/view.php?id=7927): Fix follow-up corruption after a declined credit-card capture (ACDC). When the first capture attempt was rejected by PayPal (HTTP 200, `status:COMPLETED`, but capture `status:DECLINED` — e.g. `CCREJECT-IF` sandbox card or any real decline), the failed shop order was left open and the frontend kept the old order in `currentOrder.shop`/`currentOrder.paypal`. On the next submit `setShopOrderData` then ran `cancelOrder` on the previous shop order *while the new order was already finalized in the session*, which (a) made `removeTemporaryOrder` issue a `GET /orders/<old>` against the wrong order, and (b) deleted `sess_challenge` of the new order — forcing the captureOrder DB-fallback path on every retry and producing `cancel blocked – PayPal order already approved/captured` warnings two retries later. Three coordinated changes: the ACDC frontend (`paypal-frontend-acdc-payment-controller.js::captureOrder`) now resets `currentOrder` and `captureInProgress` when the backend returns `status:'error'`; `AjaxPaymentController::captureOrder()` stornoes the shop order itself in the declined-capture branch instead of leaving it open; and `Payment::removeTemporaryOrder()` now only deletes `sess_challenge` when the order being cancelled is still the one held in the session (defence-in-depth — a stale frontend triggering `cancelShopOrder` on an old order id can no longer wipe the active session).
- [0007928](https://bugs.oxid-esales.com/view.php?id=7928): Harden Pay upon Invoice (PUI) against `DEVICE_DATA_NOT_AVAILABLE` (HTTP 422) responses caused by a missing Fraudnet load in the customer's browser. For the Smarty flavour the primary include (`pui_fraudnet.tpl` via `pui_wave.tpl` / `pui_flow.tpl` from `shipping_and_payment.tpl` on checkout step 3) is lost whenever a custom theme overrides `shipping_and_payment.tpl` without carrying the PUI partial along — the server still sends the `PayPal-Client-Metadata-Id` header, but no device data was ever collected against that id and PayPal rejects the order. Added a second-chance Fraudnet include on step 4 via the existing `checkout_order_btn_submit_bottom` block extension (`views/smarty/extensions/themes/default/page/checkout/checkout_order_btn_submit_bottom.tpl`), guarded by `paymentId == 'oscpaypal_pui'` so it only fires for PUI. The Twig flavour already emits Fraudnet on step 4 through `pui.html.twig` / `order.html.twig` and is unchanged. Additionally logs the CMID resolved from the session in `PaymentGateway::doExecutePuiPayment()` at debug level to ease future diagnosis when the header is present but Fraudnet data isn't.
- [0007926](https://bugs.oxid-esales.com/view.php?id=7926): Fix `SyntaxError: Identifier 'buttonXYZ' has already been declared` on product detail pages when a third-party theme re-executes the PayPal button markup after an AJAX variant swap. Both `views/smarty/frontend/shared/paymentbuttons.tpl` and `views/twig/frontend/paymentbuttons.html.twig` rendered `let button{buttonId} = paypal.Buttons(...)` at classic-script top-level, and re-running the script in the same script-block scope tripped the `let` redeclaration guard. Wrapped the generated JS in an IIFE (so `let` is function-scoped) and added an early-return when the button container already has children (idempotent re-init). Wave/Flow/Apex were not affected because they do not re-execute the script on variant change; themes that evaluate embedded scripts on AJAX replace did.
- [0007925](https://bugs.oxid-esales.com/view.php?id=7925): Add user-visible fallback for ACDC credit card and Google Pay orders when PayPal rejects the order with an API error (e.g. swapped city/postal code causing `CITY_REQUIRED` or `REQUIRED_PARAMETER_FOR_PAYMENT_SOURCE`). Two controllers called a `doCreatePayPalOrder`/`doPatchPayPalOrder`-chain outside the surrounding try/catch, so the `ApiException` bubbled up and the shop served the offline page instead of JSON — the PayPal/Google Pay overlay closed silently and the customer was stranded on step 4 without any feedback. Both call sites are now wrapped in their own try/catch; on `ApiException` the previously finalized shop order is cancelled (`$order->cancelOrder()`), `sess_challenge` is cleared, and a translated message (`OSC_PAYPAL_ERROR_INVALID_ADDRESS`) is queued via `addErrorToDisplay(..., 'paypal_error')`. ACDC: `AjaxPaymentController::createAcdcOrder()` returns `status: 'error'` plus a `redirect` URL back to `cl=order`; the ACDC frontend controller (`paypal-frontend-acdc-payment-controller.js`) now honours `result.redirect` and sends the browser to the order overview. Google Pay: `OrderController::executeGooglePayOrder()` returns `status: 'ERROR'` (as before); the existing `location.reload()` in `paypal-frontend-googlepay-payment-controller.js` picks the queued displayError up on the next render — no JS changes required. In both flows the customer now lands on a clean order overview with a clear error message and can correct the address.

## [3.7.3] - 2026-04-16

### FIX

- Fix ACDC orders incorrectly cancelled (storno) when payment was already captured. When the AJAX `captureOrder()` flow completed before the browser redirect to `finalizeacdc`, `finalizeOrderAfterExternalPayment()` attempted to capture/authorize the already-captured order, failed with an API error, and cancelled the paid order. Added an early-return guard that detects already-paid orders (via `isOrderPaid()` + `oxtransid`) and skips redundant processing — analogous to the existing guard in `finalizeOrder()`.
- Downgrade log level for "PayPal session canceled" from ERROR to INFO — this message is part of the normal flow when a customer revisits checkout step 4 during an active transaction.
- [0007920](https://bugs.oxid-esales.com/view.php?id=7920): Fix credit card fallback button (`oscpaypal_cc_alternative`) incorrectly shown on PayPal buttons (e.g. product detail page) when vaulting (`oscPayPalSetVaulting`) is disabled. The PayPal SDK parameter `enable-funding=card` was hardcoded unconditionally. Now `card` is only added to `enable-funding` when ACDC eligibility is not given AND the `oscpaypal_cc_alternative` payment method is active.

## [3.7.2] - 2026-04-02

### FIX

- [0007914](https://bugs.oxid-esales.com/view.php?id=7914): Fix Pay upon Invoice (PUI) broken after 3.7.1 security patch. The `executePayment()` reject guard blocked PUI because it matched `isPayPalPayment()` but was not handled by any prior flow (not a button/proxy/UAPM/checkout payment). Added `gatewaypayment` flag to `PayPalDefinitions` and `isGatewayPayment()` method so PUI correctly falls through to OXID's core `PaymentGateway`.
- [0007915](https://bugs.oxid-esales.com/view.php?id=7915): Fix fatal error "Call to a member function getId() on null" on product detail page when an out-of-stock variant is in the basket. `hasProductVariantInBasket()` delegated to `Basket::getArtStockInBasket()` which calls `getArticle(true)` (buyable-check). For out-of-stock variants this returns null. Replaced with direct basket iteration using `getArticle(false)` to skip the buyable check.
- Fix NoArticleException on product detail page when an out-of-stock variant (configured as "offline when sold out") is still in the basket. The installment banner template called `Basket::getArtStockInBasket()` which internally uses `getArticle(true)` and throws for offline articles. Simplified the banner amount calculation to always add the current product price to the basket total, removing the unnecessary basket iteration. Removed now unused `hasProductVariantInBasket()` method.
- [0007916](https://bugs.oxid-esales.com/view.php?id=7916): Fix orphaned oscpaypal_order rows with OSCPAYPALSTATUS=COMPLETED and empty OXORDERID. When `sess_challenge` was cleared before `captureOrder()` completed (e.g. by a concurrent cancel request), the capture flow proceeded and created tracking records that could not be matched by webhook processing. Added defense-in-depth validation at four levels: `AjaxPaymentController::captureOrder()` now aborts if the shop order cannot be resolved, `PayPalOrderCompletedSubscriber` rejects events with empty shopOrderId, `Payment::trackPayPalOrder()` throws on empty shopOrderId, and `OrderRepository::paypalOrderByOrderIdAndPayPalId()` refuses to create new records with empty shopOrderId.
- [0007916](https://bugs.oxid-esales.com/view.php?id=7916): Fix root cause of `sess_challenge` being cleared during active capture. `Payment::removeTemporaryOrder()` unconditionally deleted `sess_challenge` even when the cancel was blocked because PayPal had already approved/captured the payment. Now `sess_challenge` is only deleted when the cancel actually succeeds, keeping the session intact for the concurrent capture flow.
- [0007916](https://bugs.oxid-esales.com/view.php?id=7916): Add database fallback for shop order resolution in `AjaxPaymentController::captureOrder()`. If `sess_challenge` is missing, the shop order is now resolved via the persisted `oscpaypal_order` relationship using the PayPal order ID. Additionally rejects cancelled (storno) orders to prevent tracking against invalidated shop orders.
- [0007916](https://bugs.oxid-esales.com/view.php?id=7916): Fix PayPal capture executed before storno check in `AjaxPaymentController::captureOrder()`. The PayPal capture API call was made before validating whether the shop order had been cancelled, allowing funds to be captured for stornoed orders. Moved order resolution and storno check before the `capturePaymentForOrder()` call so that cancelled orders are rejected without contacting the PayPal API.
- [0007916](https://bugs.oxid-esales.com/view.php?id=7916): Fix redirect to start page instead of thank-you page when the customer retries PayPal payment without reloading the checkout page. The `shopThankYouPageUrl` in the JS config was baked in at initial page render and became stale on retry. `captureOrder()` and `authorizePayment()` now return a fresh `redirectUrl` in the success response. `thankYouPageRedirect()` accepts an optional URL parameter and prefers the server-provided URL over the cached config value. Also consolidated all direct `window.location` assignments in the ACDC controller to use `thankYouPageRedirect()` so that `removeBeforeUnloadListener()` is called consistently before every redirect.
- [0007916](https://bugs.oxid-esales.com/view.php?id=7916): Fix redirect to start page when retrying PayPal payment after a blocked capture. After a failed `captureOrder` the JS state (`captureInProgress`, `currentOrder`) was not reset. On retry, `setShopOrderData()` found the stale order reference, called `cancelOrder()` for it, received `cancel_blocked` (old PayPal order meanwhile APPROVED), and redirected away. Reset JS state after a non-success capture so that a subsequent `createOrder` starts cleanly.
- Fix duplicate order creation for stock-1 articles during PayPal checkout. After the AJAX `captureOrder()` flow completed, `PayPalOrderCompletedSubscriber` cleaned up the PayPal session (`PayPalSession::unsetPayPalSession()`). When the browser then redirected to the order confirmation page, `Order::finalizeOrder()` was called again. Because the PayPal session was already cleared, `isOrderExecutionInProgress()` returned false and the webhook-wait guard did not trigger — causing `parent::finalizeOrder()` to create a second shop order that failed stock validation. Added a guard in `Order::finalizeOrder()` that detects already-paid orders (via `isOrderPaid()` + `oxtransid`) and returns early without creating a duplicate.
- [0007917](https://bugs.oxid-esales.com/view.php?id=7917): Fix tracking carrier country not restored on page reload. When a carrier from the "global" country group was saved, the country dropdown was reset to the order's shipping/billing country instead. Added `getCountryCodeByCarrierKey()` to `PayPalTrackingCarrierList` and `getEffectiveTrackingCountryCode()` to `OrderMain` to resolve the saved carrier's country and pre-select it in the dropdown.

## [3.7.1] - 2026-03-19

### Security

- Fix payment bypass: Orders with PayPal payment methods (Standard, Pay Later, Google Pay) could be placed without completing the PayPal authorization flow by submitting the order form directly. Added server-side guards in `Order::executePayment()` that require a valid PayPal checkout session (`isPayPalPaymentCheckout`) before accepting payment. Without this flag — which is only set by legitimate PayPal JS flows — the payment is now rejected.

### FIX

- [0007910](https://bugs.oxid-esales.com/view.php?id=7910): Fix copy-paste bug in OrderController and ProxyController: `oxserviceproductsagreement` was incorrectly read from `oxdownloadableproductsagreement`. Added `?? false` fallbacks to prevent PHP notices when `$_POST`/`$data` keys are missing (e.g. Apple Pay flow).
- Fix PayPal Express button still visible on detail page for out-of-stock products that are configured to be shown but not buyable. Added `isNotBuyable()` check to the template.
- Fix double-submit on PayPal Express checkout: clicking "Jetzt zahlungspflichtig bestellen" multiple times created duplicate shop orders, where the second order failed with ORDER_ALREADY_COMPLETED. Added frontend double-click protection (button disabled after first submit) and backend guard in PaymentGateway that checks via oxtransid whether another shop order already processed the same PayPal order.
- Fix PayPal Express button not re-initializing after variant selection change on product details page. OXID replaces the product content via AJAX on variant change, but the PayPal button init scripts are not re-executed. Added a MutationObserver (`paypal-frontend-variant-observer.js`) and a hidden config element (`#PayPalButtonProductMainConfig`) to detect DOM changes and re-render the PayPal button with the correct variant article ID. Supports both Apex/Twig (outerHTML) and Wave/Flow/Smarty (innerHTML) themes.
- [0007912](https://bugs.oxid-esales.com/view.php?id=7912): fix To obtain the correct BaseUrl for webhooks in the EE context, `$conf->getSslShopUrl()` / `$conf->getShopUrl()` must be used instead of `$conf->getCurrentShopUrl()`.

## [3.7.0] - 2026-03-11

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
- PPExpress, GooglePay, Applepay: Persist mapping in oscpaypal_order immediately so webhooks can find the shop order even if the customer never returns from PayPal.

### Security

- Fix insecure deserialization in PayPalPlusRefund: add `allowed_classes` restriction to `unserialize()` to prevent PHP Object Injection
- Fix DOM-XSS in Apple Pay success message: escape PayPal API response data before rendering via `innerHTML`
- Harden SQL queries in PayPalSoapOrderCommentList and PayPalSoapOrderPaymentList: validate view name and use backtick quoting
- Fix DOM-XSS in admin carrier provider dropdown: use `createElement`/`textContent` instead of `innerHTML` with unsanitized data
- Remove weak cryptographic nonce fallback in PartnerConfig (`md5`/`uniqid`/`mt_rand` dead code)
- Prevent potential open redirect in OrderController: validate redirect URL against shop domain and PayPal. **Note (fixed in 3.7.3):** The initial whitelist only matched `www.paypal.com` / `www.sandbox.paypal.com`, but PayPal returns uAPM redirect URLs (iDeal, EPS, etc.) without `www.` prefix, which caused a silent redirect to the shop start page instead of the payment page.
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

## [3.6.0] - 2026-02-19

### NEW

- Add option for delaying the webhook. Give the frontend time to persist.

### FIX

- fix OrderNumber-Gaps in PayPalExpress-Orders
- use cancel-Process also for PayPalExpress
- Introduce orderModel->cancelPayPalOrder. It takes over a large part of the tasks of the method PaymentService->removeTemporaryOrder
- fix WorstCase: Start a PP-Order, open in a second tab a PP-Express-Order and finish. Now the first order is clean canceled.
-
## [3.5.3] - 2026-02-02

### FIX

- [0007872](https://bugs.oxid-esales.com/view.php?id=7872): Pay attention to correct return values.
- [0007874](https://bugs.oxid-esales.com/view.php?id=7874): Fix PayPal stateand country initialisation in purchase units factory
- [0007883](https://bugs.oxid-esales.com/view.php?id=7883): Fix Edge-Case: finalize an order in a second tab with a different payment
- [0007884](https://bugs.oxid-esales.com/view.php?id=7884): Fix: When canceling and paying again with PayPal, the module does not use the shipping method but the shipping costs
- [0007885](https://bugs.oxid-esales.com/view.php?id=7885): Fix error handling for createShopOrder and improve missing cancelOrder calls
- [0007887](https://bugs.oxid-esales.com/view.php?id=7887): Fix that PayPal Express orders always use only the default shipping method
- [0007888](https://bugs.oxid-esales.com/view.php?id=7888): double check capture-status before capture, to block a race condition
- add customId for PP-Express again
- Merchant ID removed from SDK URL (information is already provided via tokens)
- Fix template-id in metadata for smarty list.tpl
- use PayPal-Client v3.0.20
- Fix: If Apple Pay is selected with a different language and currency
- ApplePay: Remove potential triggers for unnecessary session termination

### NEW

- Performance: Improved AJAX controller response time by 56% through lazy-loaded settings cache and reduced controller inheritance
- Performance: Optimized PayPal standard checkout by combining shop and PayPal order creation into single request
- Performance: uAPMs like iDeal, Blik, EPS, P24 and bancontact are completed on approval
- Performance: Faster login and capture in all "popup-payments" by removing a JS call time delay.
- Allow better override the button-templates for theme (smarty)

## [3.5.2] - 2025-12-05

### NEW

- add link to PayPal-Backend from PayPal-Order-Overview-Page (see TransactionID)
- provide ArticleNumbers to PayPal-API-Requests

### FIX

- fix: compatibility-issues with telecash
- fix: Banner-Placement on Detailspage
- [0007857](https://bugs.oxid-esales.com/view.php?id=7857): Fix: When cancelling a PayPal order with a voucher and attempting to place the order again
- If the customer does not wait until they reach the Thankyou page after clicking "Buy Now" in the PayPal pop-up, an order email will now also be sent in "healing mode".
- clear Cache before deactivate the module, prevent possible maintenance mode in case of other installed modules
- Add handling for worst case: If the customer feels during an order process that something isn't progressing and triggers a cancel order, we check whether the order is already OK and redirect them to the thank you page.
- [0007861](https://bugs.oxid-esales.com/view.php?id=7861): If the customer click close the SEPA-Payment-Overlay, We do not delete the Shipping-Session.
- [0007862](https://bugs.oxid-esales.com/view.php?id=7862): fix: if uAPM Bancontact customer is slower than the webhook, then do not throw an error
- [0007863](https://bugs.oxid-esales.com/view.php?id=7863): fix: Wrong return value causes error and canceled payments with captured money
- [0007864](https://bugs.oxid-esales.com/view.php?id=7864): fix: race condition leads to aborted orders
- [0007865](https://bugs.oxid-esales.com/view.php?id=7865): fix: If the basket contains article with decimal-point-amounts (like 0.5m) then we do not provide the basket-items to PayPal. Because PayPal can only handle whole numbers.

## [3.5.1] - 2025-11-06

### NEW

- add Age Control (min age 18) for PUI
- [0007785](https://bugs.oxid-esales.com/view.php?id=7785): Fix: If you only fill in one of the fields Tracking carrier or code for PayPal orders, an alert inform that you need all fields
- show Express-Buttons only if Basket > 0 or ArticlePrice > 0
- [0007821](https://bugs.oxid-esales.com/view.php?id=7821): use finalizeOrder for all payments again
- use PayPal-Client v3.0.19
- Add handling for worst case: When the order is completed and the customer clicks around, the customer is redirected to the thank you page
- Add new webhook 'VAULT.PAYMENT-TOKEN.CREATED'
- 3ds related mechanisms moved to SCAValidator service
- Cancel a uAPM order that was ended by the customer using the back button.
- drop codeception-tests

### FIX

- [0007783](https://bugs.oxid-esales.com/view.php?id=7783): fix birthday validation for PUI (Maintenance-Mode)
- use the loading-animation from backend also in frontend
- faster Checkout
- fix: GooglePay-Button-Integration (also send OrderMail)
- fix: ApplePay-Button-Integration (also send OrderMail)
- fix: capture in the backend only for PayPal-Orders
- fix: frontend errors rendering
- fix: PUI-EMail-Handling (also use oxtotalordersum instead oxtotalbrutsum and send mail only once)
- fix: Errorhandlings works in WAVE and Flow

## [3.5.0] - 2025-08-18

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
- Trim the item names for PayPal in a proper way
- Switch to ServerSide-API-Calls instead deprecated ClientSide-API-Calls
- use PayPal-Client v3.0.18

### FIX

- Fix: Locales for PP-Buttons are editable again
- Fix: captured order could not be changed in the backend
- use PayPal-Client v3.0.16
- [0007797](https://bugs.oxid-esales.com/view.php?id=7797): Trim the item names for PayPal in a proper way
- [0007817](https://bugs.oxid-esales.com/view.php?id=7817): Fix: In step 4 (cl=order), the shop freezes with 500 errors - Internal Server Error on POST Request
- Inform the customer that the order cannot be changed after capture
- update the BN-Code to OXID_Cart_PPCP_fromv350
- fix Maintenance for canceled PayPal-Orders in the Order-PayPal-Checkout-Tab
- use Backend-Option SCA-Method for 3dS Handling

## [3.4.1] - 2025-04-08

### Added
- Cypress e2e tests
- Support PayPal-Client v3.0.15
- Support brick/phonenumber ^0.7.0
- Add stronger indication of required domain registration for Apple Pay

### Fixed
- set connect-timeout for 5 Seconds and request-timeout for 30 seconds
- [0007769](https://bugs.oxid-esales.com/view.php?id=7769): Performance: Cache the Data-Client-Token for 24h & load SDK only if necessary
- use PayPal-Client v3.0.15
- set connect-timeout for 5 Seconds and request-timeout for 30 seconds
- Settings for automatic cleanup unfinished orders are read via ModuleSettings
- [0007771](https://bugs.oxid-esales.com/view.php?id=7771): Paypal can only work with two decimal places. For shops with configured additional decimal places, the corresponding rounding takes place
- [0007772](https://bugs.oxid-esales.com/view.php?id=7772): Fix pay in nettomode
- Fix line item amounts in case of discounts (Discussion here https://forum.oxid-esales.com/t/paypal-modul-2-5-1-fehler-bei-rabatten-fehler-die-1223354igste/99472)
- Add stronger indication of required domain registration for Apple Pay
- [0007775](https://bugs.oxid-esales.com/view.php?id=7775): Fix Re-starting the PayPal checkout process on the product page duplicates the cart's items
- Fix Issues with refunding in different Currencies
- Fix Losing connection/page refreshing during the PayPal checkout process results in the PayPal option disappearing
- Fix The quantity is overlooked during PayPal checkout initiation from the product page
- Fix Cancellation of PayPal Express Checkout deletes items stored in Cart
- Fix Processes Duplicate Refunds on Rapid Refund Button Clicks
- [0007776](https://bugs.oxid-esales.com/view.php?id=7776): Fix GooglePay and ApplePay always use first shipping method
- [0007765](https://bugs.oxid-esales.com/view.php?id=7765): Fix Dont send order mails twice for Google- and ApplePay

### Dropped
- Codeception tests
- library giggsey/libphonenumber-for-php

### Deprecated
- set Smarty-Tpl-Check-Methods as deprecated

## [3.4.0] - 2025-02-06

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
- use PayPal-Client v3.0.14
- [0007588](https://bugs.oxid-esales.com/view.php?id=7588): Improve Error handling for Capture Order Requests (thanks to mount7)
- remove Sofort and MyBank, Paymentmethods will soon no longer be accepted via PayPal
- fix: Refund only with note to Buyer (required)
- [0007595](https://bugs.oxid-esales.com/view.php?id=7595): : Fix PayPal Checkout substract discount from coupon series again, if 'Show net prices in frontend (B2B)' is active
- Fix Errorlog-Message "Duplicate entry ..." + fix Update send PUI-Bankdata via Webhook
- [0007656](https://bugs.oxid-esales.com/view.php?id=7656): Fix incompatibility with Klarna-Module
- Fix PayPalExpress Reauth is necessary if the cart amount (total is greater than before) has changed during the checkout process
- [0007666](https://bugs.oxid-esales.com/view.php?id=7666): Fix: Price surcharges on the detail page for selection lists are not taken into account
- [0007695](https://bugs.oxid-esales.com/view.php?id=7695): Fix: if DeliverySet is set in Frontend, then do not add any PseudoDeliveryCosts for PPExpress
- add possibility to ignore cached tokens. It helps e.g. for webhook registration
- [0007695](https://bugs.oxid-esales.com/view.php?id=7695): Explain better Pseudo delivery costs
- fix issue with provided english translations for admin
- fix issue with googlePay (await for the complete execution)
- [0007760](https://bugs.oxid-esales.com/view.php?id=7760): fix Paypal return type and B2B Module
- [0007763](https://bugs.oxid-esales.com/view.php?id=7763): fix Some data have received a strange name suffix
- fix Remove SEPA payment method of it is not eligible, temporary solution
- Change the onboarding process to "without return URL & return button"
- [0007764](https://bugs.oxid-esales.com/view.php?id=7764): Fix TotalPrice for ApplePay

### NEW

- PayPal-Request-Id based on serialized body, no extra PayPal-Request-Id necessary anymore
- Introduce GooglePay-Payment
- Introduce ApplePay-Payment
- add Default-Shippingcosts for PP-Express to prevent overcharge.
- provide Smarty-Templates again for OXID7.0 - thank you to D3-Team
- use PayPal-Request-ID in any API-Call (via Client, v3.0.10)
- add Default-Shippingcosts for PP-Express to prevent overcharge.
- use central logger like in v2 Branch
- mark GiroPay as deprecated
- [0007161](https://bugs.oxid-esales.com/view.php?id=7161): Removing payment method deactivation during module deactivation. Merchants must now do this themselves
- add GooglePay payment method for oxid 7 version


## [3.3.4] - 2024-01-26

- Transfer OXID-Ordernumber to PayPal
- PayPal-Log consider Shop-ErrorLogLevel
- Composer-Installation now via packagist.
  - https://packagist.org/packages/oxid-solution-catalysts/paypal-module
  - https://packagist.org/packages/oxid-solution-catalysts/paypal-client
-  "Repositories"-requirement for Source https://paypal-module.packages.oxid-esales.com/ not need anymore

## [3.3.2] - 2023-11-17

- first Version for OXID7 with APEX-Theme as Twig-Frontend-Standard-Theme, without Smarty-Support

## [2.5.2] - 2024-??-??

### FIX

- Catch possible thrown Error by getting DataClientToken
- [0007719](https://bugs.oxid-esales.com/view.php?id=7719): Tracking code also be stored in standard DB field for backwards compatibility

## [2.5.1] - 2024-09-20

### FIX

- [0007161](https://bugs.oxid-esales.com/view.php?id=7161): Removing payment method deactivation during module deactivation. Merchants must now do this themselves
- [0007584](https://bugs.oxid-esales.com/view.php?id=7584): Provide additional oxrights-elements for PayPal-Express, ApplePay and GooglePay-Buttons
- [0007706](https://bugs.oxid-esales.com/view.php?id=7706): If Customer change the invoice-address on last page in checkout and use this address as deliveryaddress (checkbox invoiceaddress as deliveryaddress), then this changed address would be transferred to PayPal
- [0007711](https://bugs.oxid-esales.com/view.php?id=7711): Temporary orders that are no longer needed and already have an order number will be cancelled. Temporary orders without an order number will still be deleted
- [0007713](https://bugs.oxid-esales.com/view.php?id=7713): Correct SQL for select temporary Orders
- provide correct encoded Shopname to PayPal
- Fix order of closing brackets in applepay-template
- Temporary orders that are no longer needed and already have an order number will be cancelled. Temporary orders without an order number will still be deleted
- Provide BN codes even to previously overlooked API calls
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
- [0007695](https://bugs.oxid-esales.com/view.php?id=7695): Fix: if DeliverySet is set in Frontend, then do not add any PseudoDeliveryCosts for PPExpress

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
