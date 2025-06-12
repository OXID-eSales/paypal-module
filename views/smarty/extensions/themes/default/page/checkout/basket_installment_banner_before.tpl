[{if $oViewConf->showPayPalCheckoutBannerOnCheckoutPage()}]
    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','css/paypal.min.css')}]
    [{assign var="basketAmount" value=$oxcmp_basket->getPrice()}]
    [{include file="@osc_paypal/frontend/shared/installment_banners.tpl" amount=$basketAmount->getPrice() selector=$oViewConf->getPayPalCheckoutBannerPaymentPageSelector()}]
[{/if}]

[{$smarty.block.parent}]
