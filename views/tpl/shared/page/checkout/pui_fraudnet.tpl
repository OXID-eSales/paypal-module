<script type="application/json" fncls="[{$oViewConf->getPayPalPuiFNParams()}]">
{
    [{if $oViewConf->isPayPalSandbox()}]
    "sandbox":true,
    [{/if}]
    "f":"[{$oView->getPayPalPuiFraudnetCmId()}]",
    "s":"[{$oViewConf->getPayPalPuiFlowId()}]"
}
</script>
[{oxscript include="https://c.paypal.com/da/r/fb.js"}]