[{if $oViewConf->isPayPalActive()}]
    [{if $submitCart}]
    <script>
        document.getElementById('orderConfirmAgbBottom').submit();
    </script>
    [{/if}]
[{/if}]
