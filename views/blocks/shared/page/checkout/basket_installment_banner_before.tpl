[{if $oViewConf->isModuleActive('osc_paypal') && $oViewConf->showPayPalBannerOnCheckoutPage()}]
    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','out/src/css/paypal_installment.css')}]
    [{assign var="basketAmount" value=$oxcmp_basket->getPrice()}]
    [{include file="tpl/installment_banners.tpl" amount=$basketAmount->getPrice() selector=$oViewConf->getPayPalBannerPaymentPageSelector()}]
[{/if}]

[{$smarty.block.parent}]
