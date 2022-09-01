
[{if $oViewConf->isPayPalCheckoutActive()}]
    [{assign var="className" value=$oViewConf->getTopActiveClassName()}]
    [{if $oViewConf->isPayPalExpressPaymentEnabled() && (($className == 'order' && !$oViewConf->isPayPalACDCSessionActive()) || $className !== 'order')}]
       <script src="[{$oViewConf->getPayPalJsSdkUrl()}]" data-partner-attribution-id="[{$oViewConf->getPayPalPartnerAttributionIdForBanner()}]"></script>
    [{elseif $className == 'order' && $oViewConf->isPayPalACDCSessionActive()}]
        <script src="[{$oViewConf->getPayPalJsSdkUrlForACDC()}]" data-client-token="[{$oViewConf->getDataClientToken()}]"></script>
    [{elseif ($className == 'start') || ($className == 'search') || ($className == 'details') || ($className == 'alist') || ($className == 'basket') || ($className == 'payment')}]
        <script src="[{$oViewConf->getPayPalApiBannerUrl()}]" data-partner-attribution-id="[{$oViewConf->getPayPalPartnerAttributionIdForBanner()}]"></script>
    [{/if}]
    [{if $submitCart}]
    <script>
        document.getElementById('orderConfirmAgbBottom').submit();
    </script>
    [{/if}]
[{/if}]
