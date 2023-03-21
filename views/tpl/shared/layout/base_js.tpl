[{if $oViewConf->isPayPalCheckoutActive()}]
    [{assign var="className" value=$oViewConf->getTopActiveClassName()}]
    [{if $oViewConf->isPayPalExpressPaymentEnabled() &&
        (($className == 'order' && !$oViewConf->isPayPalACDCSessionActive()) || ($className !== 'order' && $className !== 'payment')) &&
        (
            ($oxcmp_basket->getProductsCount() && $oViewConf->showPayPalMiniBasketButton()) ||
            ($className == 'details' && $oViewConf->showPayPalProductDetailsButton()) ||
            ($className == 'basket' && $oViewConf->showPayPalBasketButton())
        )
    }]
        <script src="[{$oViewConf->getPayPalJsSdkUrl()}]" data-partner-attribution-id="[{$oViewConf->getPayPalPartnerAttributionIdForBanner()}]"></script>
        [{assign var="sCountryRestriction" value=$oViewConf->getCountryRestrictionForPayPalExpress()}]
        [{if $sCountryRestriction}]
            <script>
                const countryRestriction = [[{$sCountryRestriction}]];
            </script>
        [{/if}]
    [{elseif $className == 'order' && $oViewConf->isPayPalACDCSessionActive()}]
        <script src="[{$oViewConf->getPayPalJsSdkUrlForACDC()}]" data-client-token="[{$oViewConf->getDataClientToken()}]"></script>
    [{elseif $className == 'payment'}]
        <script src="[{$oViewConf->getPayPalJsSdkUrlForButtonPayments()}]" data-partner-attribution-id="[{$oViewConf->getPayPalPartnerAttributionIdForBanner()}]}]"></script>
    [{elseif $oViewConf->isPayPalBannerActive() && ($className == 'start' || $className == 'search' || $className == 'details' || $className == 'alist' || $className == 'basket')}]
        <script src="[{$oViewConf->getPayPalApiBannerUrl()}]" data-partner-attribution-id="[{$oViewConf->getPayPalPartnerAttributionIdForBanner()}]"></script>
    [{/if}]
    [{if $submitCart}]
    <script>
        document.getElementById('orderConfirmAgbBottom').submit();
    </script>
    [{/if}]
[{/if}]
