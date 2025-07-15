[{if "oscpaypal_pui" == $payment->oxuserpayments__oxpaymentsid->value}]
    [{oxmultilang ident="OSC_PAYPAL_PAYMENT_PUI_FOLLOW"}]
[{/if}]

[{$smarty.block.parent}]
