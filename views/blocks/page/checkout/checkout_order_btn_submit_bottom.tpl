[{if $oViewConf->isFlowCompatibleTheme()}]
    [{include file="modules/osc/paypal/checkout_order_btn_submit_bottom_flow.tpl"}]
[{else}]
    [{include file="modules/osc/paypal/checkout_order_btn_submit_bottom_wave.tpl"}]
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
        the collected device data in sync regardless of theme overrides. *]}]
    [{include file="modules/osc/paypal/pui_fraudnet.tpl"}]
[{/if}]

[{if "oscpaypal_express" == $paymentId}]
    [{capture name="oscpaypal_express_doubleclick_guard"}]
        [{if $phpstorm}]<script>[{/if}]
        document.addEventListener('DOMContentLoaded', function() {
            var buttons = document.querySelectorAll(
                '.submitButton.nextStep, [type="submit"].nextStep'
            );
            for (var i = 0; i < buttons.length; i++) {
                buttons[i].addEventListener('click', function() {
                    var btn = this;
                    setTimeout(function() {
                        btn.disabled = true;
                    }, 0);
                }, {once: true});
            }
        });
        [{if $phpstorm}]</script>[{/if}]
    [{/capture}]
    [{oxscript add=$smarty.capture.oscpaypal_express_doubleclick_guard}]
[{/if}]
