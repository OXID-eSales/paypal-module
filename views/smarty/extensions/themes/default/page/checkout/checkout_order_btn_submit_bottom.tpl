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
