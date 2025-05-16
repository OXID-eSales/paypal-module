[{if $oViewConf->isPayPalCheckoutActive()}]
    [{assign var="sFileMTime" value=$oViewConf->getModulePath('osc_paypal','out/src/js/paypal-frontend.min.js')|filemtime}]
    <script src="[{$oViewConf->getModuleUrl('osc_paypal','out/src/js/paypal-frontend.min.js')|cat:"?"|cat:$sFileMTime}]"></script>
    [{assign var="sFileMTime" value=$oViewConf->getModulePath('osc_paypal','out/src/css/paypal.min.css')|filemtime}]
    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal', 'out/src/css/paypal.min.css')|cat:"?"|cat:$sFileMTime}]
    <script src="[{$oViewConf->getPayPalJsSdkUrl($commitFlow)}]"
        [{if $oViewConf->isVaultingEligibility()}]
            data-user-id-token="[{$oViewConf->getUserIdForVaulting()}]"
        [{/if}]
        data-partner-attribution-id="[{$oViewConf->getPayPalPartnerAttributionIdForBanner()}]"
        [{* data-client-token is only necessary for hosted fields, it could be removed
            data-client-token="[{$oViewConf->getDataClientToken()}]"
        *}]
        onload="undefined === window.OxidPayPal ? null : window.OxidPayPal.onSDKLoaded()"
        ></script>
    [{assign var="sCountryRestriction" value=$oViewConf->getCountryRestrictionForPayPalExpress()}]
    [{if $sCountryRestriction}]
        <script>
            const countryRestriction = [[{$sCountryRestriction}]];
        </script>
    [{/if}]

    [{include file='modules/osc/paypal/base_paypal_payment_controller_config.tpl'}]
    [{include file='modules/osc/paypal/base_paypal_button_config.tpl'}]
[{/if}]
