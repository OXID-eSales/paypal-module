[{if
    $oViewConf->getTopActiveClassName()|lower=="oscpaypalconfig" ||
    $oViewConf->getTopActiveClassName()|lower=="oscpaypalorder"
}]
    [{oxscript include="js/libs/jquery.min.js" priority=1}]
    [{oxscript add="$.noConflict();" priority=10}]
    [{if $oViewConf->getTopActiveClassName()|lower=="oscpaypalconfig"}]
        [{oxscript include="js/libs/jquery-ui.min.js"}]
        [{oxscript include="js/widgets/oxmoduleconfiguration.js"}]
        [{oxscript add="$('#configForm').oxModuleConfiguration();" priority=10}]
        [{oxscript add="$.noConflict();" priority=10}]
        [{assign var="sFileMTime" value=$oViewConf->getModulePath('osc_paypal','out/src/js/paypal-admin.min.js')|filemtime}]
        [{oxscript include=$oViewConf->getModuleUrl('osc_paypal','out/src/js/paypal-admin.min.js')|cat:"?"|cat:$sFileMTime priority=10}]
    [{/if}]
[{/if}]
[{$smarty.block.parent}]
