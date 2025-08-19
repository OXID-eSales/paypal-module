[{if "oscpaypal_pui" == $payment->oxuserpayments__oxpaymentsid->value}]
    <p>[{oxmultilang ident="OSC_PAYPAL_PAYMENT_PUI_FOLLOW"}]</p>
[{/if}]
[{$smarty.block.parent}]