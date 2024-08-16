[{if
    $oViewConf->getTopActiveClassName()|lower=="oscpaypalconfig" ||
    $oViewConf->getTopActiveClassName()|lower=="oscpaypalorder"
}]
    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','css/bootstrap.min.css') priority=10}]
    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','css/paypal-admin.min.css') priority=10}]
    [{oxstyle}]
[{/if}]
[{$smarty.block.parent}]
