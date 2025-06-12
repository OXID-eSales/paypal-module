[{if
    (
        $oViewConf->getTopActiveClassName()|lower == "module_config" &&
        $oModule->getInfo('id') == "osc_paypal"
    ) ||
    $oViewConf->getTopActiveClassName()|lower=="oscpaypalorder"
}]
    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','css/bootstrap.min.css') priority=10}]
    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','css/paypal-admin.min.css') priority=10}]
    [{oxstyle}]
[{/if}]
[{$smarty.block.parent}]
