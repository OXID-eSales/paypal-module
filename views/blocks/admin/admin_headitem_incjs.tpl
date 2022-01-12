[{if
    $oViewConf->getTopActiveClassName()|lower=="paypalconfigcontroller" ||
    $oViewConf->getTopActiveClassName()|lower=="paypalordercontroller" ||
    $oViewConf->getTopActiveClassName()|lower=="paypaltransactioncontroller" ||
    $oViewConf->getTopActiveClassName()|lower=="paypalbalancecontroller" ||
    $oViewConf->getTopActiveClassName()|lower=="paypalsubscriptioncontroller" ||
    $oViewConf->getTopActiveClassName()|lower=="paypalsubscriptiondetailscontroller" ||
    $oViewConf->getTopActiveClassName()|lower=="paypalsubscriptiontransactioncontroller" ||
    $oViewConf->getTopActiveClassName()|lower=="paypaldisputedetailscontroller" ||
    $oViewConf->getTopActiveClassName()|lower=="paypaldisputecontroller" ||
    $oViewConf->getTopActiveClassName()|lower=="paypalsubscribecontroller"
}]
    [{oxscript include="js/libs/jquery.min.js" priority=1}]
    [{oxscript add="$.noConflict();" priority=10}]
    [{if $oViewConf->getTopActiveClassName()|lower=="paypalconfigcontroller"}]
        [{oxscript include="js/libs/jquery-ui.min.js"}]
        [{oxscript include="js/widgets/oxmoduleconfiguration.js"}]
        [{oxscript add="$('#configForm').oxModuleConfiguration();" priority=10}]
        [{oxscript add="$.noConflict();" priority=10}]
        [{assign var="sFileMTime" value=$oViewConf->getModulePath('oxscpaypal','out/src/js/paypal-admin.min.js')|filemtime}]
        [{oxscript include=$oViewConf->getModuleUrl('oxscpaypal','out/src/js/paypal-admin.min.js')|cat:"?"|cat:$sFileMTime priority=10}]
    [{/if}]
[{/if}]
[{$smarty.block.parent}]
