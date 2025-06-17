[{if $oViewConf->showPayPalCheckoutBannerOnCheckoutPage()}]
    [{assign var="basketAmount" value=$oxcmp_basket->getPrice()}]
    [{include file="modules/osc/paypal/installment_banners.tpl" amount=$basketAmount->getPrice() selector=$oViewConf->getPayPalCheckoutBannerPaymentPageSelector()}]
[{/if}]

[{$smarty.block.parent}]
