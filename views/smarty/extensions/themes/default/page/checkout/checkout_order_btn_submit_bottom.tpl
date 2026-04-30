[{if $oViewConf->isFlowCompatibleTheme()}]
    [{include file="@osc_paypal/frontend/flow/checkout_order_btn_submit_bottom.tpl"}]
[{else}]
    [{include file="@osc_paypal/frontend/wave/checkout_order_btn_submit_bottom.tpl"}]
[{/if}]

[{assign var="paymentId" value=$payment->getId()}]
[{if "oscpaypal_googlepay" != $paymentId &&
    "oscpaypal_applepay" != $paymentId &&
    "oscpaypal" != $paymentId &&
    "oscpaypal_acdc" != $paymentId}]
    [{$smarty.block.parent}]
[{/if}]

[{if "oscpaypal_pui" == $paymentId}]
    [{* Second-chance Fraudnet include on step 4: the primary include on step 3
        lives inside pui_wave.tpl / pui_flow.tpl and is lost when a custom theme
        overrides shipping_and_payment.tpl without carrying the PUI partial along.
        Re-emitting the JSON + fb.js here keeps the PayPal-Client-Metadata-Id and
        the collected device data in sync regardless of theme overrides. *}]
    [{include file="@osc_paypal/frontend/shared/pui_fraudnet.tpl"}]
[{/if}]

[{if "oscpaypal_express" == $paymentId}]
    [{capture name="oscpaypal_express_doubleclick_guard"}]
        [{if $phpstorm}]<script>[{/if}]
        document.addEventListener('DOMContentLoaded', function () {
            var buttons = document.querySelectorAll(
                '.submitButton.nextStep, [type="submit"].nextStep'
            );
            var submitting = false;

            function lock(ev) {
                if (submitting) {
                    if (ev) {
                        ev.preventDefault();
                        ev.stopImmediatePropagation();
                    }
                    return;
                }
                submitting = true;
                for (var i = 0; i < buttons.length; i++) {
                    var b = buttons[i];
                    b.setAttribute('aria-busy', 'true');
                    b.setAttribute('aria-disabled', 'true');
                    if (!b.dataset.oscOriginalLabel) {
                        b.dataset.oscOriginalLabel = b.innerHTML;
                        b.innerHTML = '[{oxmultilang ident="OSC_PAYPAL_ORDER_SUBMITTING"}]';
                    }
                    // 1-tick delay: the native submit / OXID-JS click action
                    // must dispatch before disabled flips form-data inclusion
                    // for the submit button.
                    (function (btn) {
                        setTimeout(function () { btn.disabled = true; }, 0);
                    }(b));
                }
            }

            for (var i = 0; i < buttons.length; i++) {
                // Click covers: native type="submit", OXID-JS-driven form.submit()
                // for type="button" (Wave), and Enter key (browser dispatches
                // synthetic click on the form's submit button).
                // Capture phase + stopImmediatePropagation on the second event
                // so we win against any other registered handler.
                buttons[i].addEventListener('click', lock, true);
                // Mobile: block the second tap before its synthetic click fires.
                buttons[i].addEventListener('touchstart', function (ev) {
                    if (submitting) {
                        ev.preventDefault();
                        ev.stopImmediatePropagation();
                    }
                }, { capture: true, passive: false });
            }

            // BFCache restore — unlock on Back-button reload so the customer
            // does not land on a frozen "submitting…" button.
            window.addEventListener('pageshow', function (ev) {
                if (!ev.persisted) return;
                submitting = false;
                for (var i = 0; i < buttons.length; i++) {
                    var b = buttons[i];
                    b.disabled = false;
                    b.removeAttribute('aria-busy');
                    b.removeAttribute('aria-disabled');
                    if (b.dataset.oscOriginalLabel) {
                        b.innerHTML = b.dataset.oscOriginalLabel;
                        delete b.dataset.oscOriginalLabel;
                    }
                }
            });
        });
        [{if $phpstorm}]</script>[{/if}]
    [{/capture}]
    [{oxscript add=$smarty.capture.oscpaypal_express_doubleclick_guard}]
[{/if}]
