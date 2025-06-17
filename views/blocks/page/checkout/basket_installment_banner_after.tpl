[{$smarty.block.parent}]

[{if $oViewConf->showPayPalCheckoutBannerOnCheckoutPage()}]
    <div id="basket-paypal-installment-banner"></div>
    [{assign var="basketAmount" value=$oxcmp_basket->getPrice()}]
    [{include file="modules/osc/paypal/installment_banners.tpl" amount=$basketAmount->getPrice() selector=$oViewConf->getPayPalCheckoutBannerCartPageSelector()}]
[{/if}]
