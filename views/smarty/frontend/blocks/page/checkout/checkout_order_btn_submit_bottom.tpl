[{assign var="payment" value=$oView->getPayment()}]
[{if "oscpaypal" == $payment->getId()}]
    <input type="hidden" name="vaultPayment" id="oscPayPalVaultPayment" value="">
    [{capture name="oscpaypal_madClickPrevention"}]
    const submitButton = document.querySelector('#orderConfirmAgbBottom .submitButton');
    const orderConfirmAgbBottom = document.getElementById('orderConfirmAgbBottom');

    submitButton.addEventListener('click', function() {
    event.preventDefault();
    this.disabled = true;
    orderConfirmAgbBottom.dispatchEvent(new CustomEvent('submit', {cancelable: true}));
    });
    [{/capture}]
    [{oxscript add=$smarty.capture.oscpaypal_madClickPrevention}]
[{/if}]
[{if "oscpaypal_pui" == $payment->getId()}]
    [{if $oViewConf->isFlowCompatibleTheme()}]
        [{include file="@osc_paypal/frontend/flow/checkout_order_btn_submit_bottom.tpl"}]
    [{else}]
        [{include file="@osc_paypal/frontend/wave/checkout_order_btn_submit_bottom.tpl"}]
    [{/if}]
[{/if}]
[{if "oscpaypal_googlepay" == $payment->getId()}]
    [{include file="@osc_paypal/frontend/shared/googlepay.tpl" buttonClass="paypal-button-wrapper large"}]
[{elseif "oscpaypal_applepay" == $payment->getId()}]
    [{include file="@osc_paypal/frontend/shared/applepay.tpl" paymentId=$payment->getId() buttonClass="paypal-button-wrapper large"}]
    <div id="applepay-container" class="paypal-button-container paypal-button-wrapper paypal-button-right large"></div>
[{else}]
    [{$smarty.block.parent}]
[{/if}]




