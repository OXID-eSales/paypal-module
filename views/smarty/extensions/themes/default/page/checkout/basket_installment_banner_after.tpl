[{$smarty.block.parent}]

[{if $oViewConf->showPayPalCheckoutBannerOnCheckoutPage()}]
    <div id="basket-paypal-installment-banner"></div>
    [{assign var="basketAmount" value=$oxcmp_basket->getPrice()}]
    [{include file="@osc_paypal/frontend/shared/installment_banners.tpl" amount=$basketAmount->getPrice() selector=$oViewConf->getPayPalCheckoutBannerCartPageSelector()}]
[{/if}]
