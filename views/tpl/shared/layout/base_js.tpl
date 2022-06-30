[{if $oViewConf->isPayPalCheckoutActive()}]
    [{assign var="className" value=$oViewConf->getTopActiveClassName()}]
    [{if $oViewConf->isPayPalExpressPaymentEnabled() && (($className == 'order' && !$oViewConf->isPayPalACDCSessionActive()) || $className !== 'order')}]
        [{oxscript include=$oViewConf->getPayPalJsSdkUrl()}]
    [{elseif $className == 'order' && $oViewConf->isPayPalACDCSessionActive()}]
        <script src="[{$oViewConf->getPayPalJsSdkUrlForACDC()}]" data-client-token="[{$oViewConf->getDataClientToken()}]"></script>
    [{/if}]
    [{if $submitCart}]
    <script>
        document.getElementById('orderConfirmAgbBottom').submit();
    </script>
    [{/if}]
[{/if}]
