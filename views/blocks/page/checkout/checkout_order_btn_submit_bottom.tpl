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
