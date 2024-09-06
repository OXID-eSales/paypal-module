[{if $oViewConf->isPayPalCheckoutActive()}]
    [{assign var="className" value=$oViewConf->getTopActiveClassName()}]
    <script src="[{$oViewConf->getPayPalJsSdkUrl()}]"
        [{if $oViewConf->isVaultingEligibility()}]
            data-user-id-token="[{$oViewConf->getUserIdForVaulting()}]"
        [{/if}]
        data-partner-attribution-id="[{$oViewConf->getPayPalPartnerAttributionIdForBanner()}]"
        data-client-token="[{$oViewConf->getDataClientToken()}]"
        ></script>
    [{assign var="sCountryRestriction" value=$oViewConf->getCountryRestrictionForPayPalExpress()}]
    [{if $sCountryRestriction}]
        <script>
            const countryRestriction = [[{$sCountryRestriction}]];
        </script>
    [{/if}]
    [{if $submitCart}]
    <script>
        document.getElementById('orderConfirmAgbBottom').submit();
    </script>
    [{/if}]
[{/if}]
