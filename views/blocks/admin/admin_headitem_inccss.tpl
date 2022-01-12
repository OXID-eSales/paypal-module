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
    [{if $oViewConf->getTopActiveClassName()|lower!=="paypalsubscribecontroller"}]
        [{assign var="sFileMTimeBootstrap" value=$oViewConf->getModulePath('oxscpaypal','out/src/css/bootstrap.min.css')|filemtime}]
        [{oxstyle include=$oViewConf->getModuleUrl('oxscpaypal','out/src/css/bootstrap.min.css')|cat:"?"|cat:$sFileMTimeBootstrap priority=10}]
    [{/if}]
    [{assign var="sFileMTime" value=$oViewConf->getModulePath('oxscpaypal','out/src/css/paypal-admin.min.css')|filemtime}]
    [{oxstyle include=$oViewConf->getModuleUrl('oxscpaypal','out/src/css/paypal-admin.min.css')|cat:"?"|cat:$sFileMTime priority=10}]
    [{oxstyle}]
[{/if}]
[{$smarty.block.parent}]
