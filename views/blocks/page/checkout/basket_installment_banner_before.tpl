[{if $oViewConf->isModuleActive('osc_paypal') && $oViewConf->showPayPalCheckoutBannerOnCheckoutPage()}]
    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','out/src/css/paypal_installment.css')}]
    [{assign var="basketAmount" value=$oxcmp_basket->getPrice()}]
    [{include file="modules/osc/paypal/installment_banners.tpl" amount=$basketAmount->getPrice() selector=$oViewConf->getPayPalCheckoutBannerPaymentPageSelector()}]
[{/if}]

[{$smarty.block.parent}]
