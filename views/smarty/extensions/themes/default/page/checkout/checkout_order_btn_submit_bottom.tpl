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
