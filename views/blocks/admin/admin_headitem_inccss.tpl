[{if
    $oViewConf->getTopActiveClassName()|lower=="oscpaypalconfig" ||
    $oViewConf->getTopActiveClassName()|lower=="oscpaypalorder"
}]
    [{assign var="sFileMTimeBootstrap" value=$oViewConf->getModulePath('osc_paypal','out/src/css/bootstrap.min.css')|filemtime}]
    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','out/src/css/bootstrap.min.css')|cat:"?"|cat:$sFileMTimeBootstrap priority=10}]
    [{assign var="sFileMTime" value=$oViewConf->getModulePath('osc_paypal','out/src/css/paypal-admin.min.css')|filemtime}]
    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','out/src/css/paypal-admin.min.css')|cat:"?"|cat:$sFileMTime priority=10}]
    [{oxstyle}]
[{/if}]
[{$smarty.block.parent}]
