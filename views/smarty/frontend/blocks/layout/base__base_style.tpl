[{$smarty.block.parent}]

[{if $oViewConf->isPayPalCheckoutActive()}]
    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal', 'css/paypal.min.css')}]
[{/if}]
