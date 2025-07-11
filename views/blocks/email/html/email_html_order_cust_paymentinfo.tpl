[{if "oscpaypal_pui" == $payment->oxuserpayments__oxpaymentsid->value && $oViewConf->getPuiPaymentInfo()}]
    <p>[{oxmultilang ident="OSC_PAYPAL_PAYMENT_PUI_NOTE" suffix="COLON"}]</p>
    <p>[{$oViewConf->getPuiPaymentInfo()}]</p>
[{/if}]
[{$smarty.block.parent}]