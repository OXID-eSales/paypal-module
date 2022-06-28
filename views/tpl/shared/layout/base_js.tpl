[{if $oViewConf->isPayPalCheckoutActive()}]
    [{if $oViewConf->isPayPalExpressPaymentEnabled()}]
        [{oxscript include=$oViewConf->getPayPalJsSdkUrl()}]
    [{/if}]
    [{if $submitCart}]
    <script>
        document.getElementById('orderConfirmAgbBottom').submit();
    </script>
    [{/if}]
[{/if}]
