[{if
    $oViewConf->getTopActiveClassName()|lower=="paypalconfig" ||
    $oViewConf->getTopActiveClassName()|lower=="paypalorder" ||
    $oViewConf->getTopActiveClassName()|lower=="paypaltransaction" ||
    $oViewConf->getTopActiveClassName()|lower=="paypalbalance" ||
    $oViewConf->getTopActiveClassName()|lower=="paypalsubscription" ||
    $oViewConf->getTopActiveClassName()|lower=="paypalsubscriptiondetails" ||
    $oViewConf->getTopActiveClassName()|lower=="paypalsubscriptiontransaction" ||
    $oViewConf->getTopActiveClassName()|lower=="paypaldisputedetails" ||
    $oViewConf->getTopActiveClassName()|lower=="paypaldispute" ||
    $oViewConf->getTopActiveClassName()|lower=="paypalsubscribe"
}]
    [{if $oViewConf->getTopActiveClassName()|lower!=="paypalsubscribe"}]
        [{assign var="sFileMTimeBootstrap" value=$oViewConf->getModulePath('osc_paypal','out/src/css/bootstrap.min.css')|filemtime}]
        [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','out/src/css/bootstrap.min.css')|cat:"?"|cat:$sFileMTimeBootstrap priority=10}]
    [{/if}]
    [{assign var="sFileMTime" value=$oViewConf->getModulePath('osc_paypal','out/src/css/paypal-admin.min.css')|filemtime}]
    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','out/src/css/paypal-admin.min.css')|cat:"?"|cat:$sFileMTime priority=10}]
    [{oxstyle}]
[{/if}]
[{$smarty.block.parent}]
