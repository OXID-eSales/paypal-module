[{if
    $oViewConf->getTopActiveClassName()|lower=="oscpaypalconfig" ||
    $oViewConf->getTopActiveClassName()|lower=="oscpaypalorder" ||
    $oViewConf->getTopActiveClassName()|lower=="oscpaypaltransactions" ||
    $oViewConf->getTopActiveClassName()|lower=="oscpaypalbalance" ||
    $oViewConf->getTopActiveClassName()|lower=="oscpaypalsubscription" ||
    $oViewConf->getTopActiveClassName()|lower=="oscpaypalsubscriptiondetails" ||
    $oViewConf->getTopActiveClassName()|lower=="oscpaypalsubscriptiontransaction" ||
    $oViewConf->getTopActiveClassName()|lower=="oscpaypaldisputedetails" ||
    $oViewConf->getTopActiveClassName()|lower=="oscpaypaldispute" ||
    $oViewConf->getTopActiveClassName()|lower=="oscpaypalsubscribe"
}]
    [{if $oViewConf->getTopActiveClassName()|lower!=="oscpaypalsubscribe"}]
        [{assign var="sFileMTimeBootstrap" value=$oViewConf->getModulePath('osc_paypal','out/src/css/bootstrap.min.css')|filemtime}]
        [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','out/src/css/bootstrap.min.css')|cat:"?"|cat:$sFileMTimeBootstrap priority=10}]
    [{/if}]
    [{assign var="sFileMTime" value=$oViewConf->getModulePath('osc_paypal','out/src/css/paypal-admin.min.css')|filemtime}]
    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','out/src/css/paypal-admin.min.css')|cat:"?"|cat:$sFileMTime priority=10}]
    [{oxstyle}]
[{/if}]
[{$smarty.block.parent}]
