[{assign var="payment" value=$oView->getPayment()}]
[{assign var="paymentId" value=$payment->getId()}]

[{if "oscpaypal" == $paymentId}]
    <div id="[{$paymentId}]" class="paypal-button-container [{$buttonClass}]"></div>
[{/if}]

[{if "oscpaypal_pui" == $paymentId}]
    [{if $oViewConf->isFlowCompatibleTheme()}]
    [{include file="modules/osc/paypal/checkout_order_btn_submit_bottom_flow.tpl"}]
    [{else}]
    [{include file="modules/osc/paypal/checkout_order_btn_submit_bottom_wave.tpl"}]
    [{/if}]
    [{/if}]

[{if "oscpaypal_googlepay" == $paymentId}]
    [{include file="modules/osc/paypal/googlepay.tpl" buttonClass="paypal-button-wrapper large"}]
    [{elseif "oscpaypal_applepay" == $paymentId}]
    [{include file="modules/osc/paypal/applepay.tpl" paymentId=$paymentId buttonClass="paypal-button-wrapper large"}]
    <div id="applepay-container" class="paypal-button-container paypal-button-wrapper paypal-button-right large"></div>
[{elseif "oscpaypal" != $paymentId}]
    [{$smarty.block.parent}]
[{/if}]
