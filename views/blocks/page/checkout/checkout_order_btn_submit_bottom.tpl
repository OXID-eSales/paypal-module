[{assign var="payment" value=$oView->getPayment()}]
[{if "oscpaypal" == $payment->getId()}]
    <input type="hidden" name="vaultPayment" id="oscPayPalVaultPayment" value="">
    [{capture name="oscpaypal_madClickPrevention"}]
    const submitButton = document.querySelector('#orderConfirmAgbBottom .submitButton');
    const orderConfirmAgbBottom = document.getElementById('orderConfirmAgbBottom');

    submitButton.addEventListener('click', function() {
    event.preventDefault();
    this.disabled = true;
    orderConfirmAgbBottom.submit();
    });
    [{/capture}]
    [{oxscript add=$smarty.capture.oscpaypal_madClickPrevention}]
[{/if}]
[{if "oscpaypal_pui" == $payment->getId()}]
    [{if $oViewConf->isFlowCompatibleTheme()}]
        [{include file="modules/osc/paypal/checkout_order_btn_submit_bottom_flow.tpl"}]
    [{else}]
        [{include file="modules/osc/paypal/checkout_order_btn_submit_bottom_wave.tpl"}]
    [{/if}]
[{/if}]
[{if "oscpaypal_googlepay" == $payment->getId()}]
    [{if $oViewConf->isFlowCompatibleTheme()}]
    [{include file="modules/osc/paypal/googlepay.tpl" buttonId=$payment->getId() buttonClass="paypal-button-wrapper large"}]
    [{else}]
    [{include file="modules/osc/paypal/googlepay.tpl" buttonId=$payment->getId() buttonClass="paypal-button-wrapper large"}]
    [{/if}]
    <div id="[{$payment->getId()}]" class="paypal-button-container paypal-button-wrapper large"></div>

[{elseif "oscpaypal_apple_pay" == $payment->getId()}]
    [{if $oViewConf->isFlowCompatibleTheme()}]
    [{include file="modules/osc/paypal/applepay.tpl" buttonId=$payment->getId() buttonClass="paypal-button-wrapper large"}]
    [{else}]
    [{include file="modules/osc/paypal/applepay.tpl" buttonId=$payment->getId() buttonClass="paypal-button-wrapper large"}]
    [{/if}]
    <div id="applepay-container" class="paypal-button-container paypal-button-wrapper large"></div>
[{else}]
    [{$smarty.block.parent}]
[{/if}]




