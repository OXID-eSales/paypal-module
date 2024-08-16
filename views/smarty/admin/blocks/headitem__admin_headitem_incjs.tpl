[{if
    $oViewConf->getTopActiveClassName()|lower=="oscpaypalconfig" ||
    $oViewConf->getTopActiveClassName()|lower=="oscpaypalorder"
}]
    [{oxscript include="js/libs/jquery.min.js" priority=1}]
    [{oxscript add="$.noConflict();" priority=10}]
    [{if $oViewConf->getTopActiveClassName()|lower == "oscpaypalconfig"}]
        [{oxscript include="js/libs/jquery-ui.min.js"}]
        [{oxscript include="js/widgets/oxmoduleconfiguration.js"}]

        [{oxscript add="$('#configForm').oxModuleConfiguration();" priority=10}]
        [{oxscript add="$.noConflict();" priority=10}]

        [{oxscript include=$oViewConf->getModuleUrl('osc_paypal','js/paypal-admin.min.js') priority=10}]
    [{/if}]
[{/if}]
[{$smarty.block.parent}]
