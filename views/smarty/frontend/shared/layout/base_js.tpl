[{if $oViewConf->isPayPalCheckoutActive()}]
    [{assign var="sFileMTime" value=$oViewConf->getModulePath('osc_paypal','js/paypal-frontend.min.js')|filemtime}]
    <script src="[{$oViewConf->getModuleUrl('osc_paypal','js/paypal-frontend.min.js')|cat:"?"|cat:$sFileMTime}]"></script>
    <script src="[{$oViewConf->getPayPalJsSdkUrl()}]"
            [{if $oViewConf->isVaultingEligibility()}]
            data-user-id-token="[{$oViewConf->getUserIdForVaulting()}]"
            [{/if}]
            data-partner-attribution-id="[{$oViewConf->getPayPalPartnerAttributionIdForBanner()}]"
            data-client-token="[{$oViewConf->getDataClientToken()}]"
            onload="window.OxidPayPal.onSDKLoaded()"
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

    [{include file='@osc_paypal/frontend/blocks/layout/base_paypal_payment_controller_config.tpl'}]
    [{include file='@osc_paypal/frontend/blocks/layout/base_paypal_button_config.tpl'}]

[{/if}]
