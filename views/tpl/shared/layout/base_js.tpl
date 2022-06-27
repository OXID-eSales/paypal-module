[{if $oViewConf->isPayPalCheckoutActive()}]
    [{oxscript include=$oViewConf->getPayPalJsSdkUrl()}]
    [{if $submitCart}]
    <script>
        document.getElementById('orderConfirmAgbBottom').submit();
    </script>
    [{/if}]
[{/if}]
