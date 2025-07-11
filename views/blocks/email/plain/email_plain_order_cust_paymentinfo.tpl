[{if "oscpaypal_pui" == $payment->oxuserpayments__oxpaymentsid->value && $oViewConf->getPuiPaymentInfo()}]
    [{oxmultilang ident="OSC_PAYPAL_PAYMENT_PUI_NOTE" suffix="COLON"}]

    [{$oViewConf->getPuiPaymentInfo()|regex_replace:'/<br\s*\/?>/i':"\n"}]
[{/if}]

[{$smarty.block.parent}]
