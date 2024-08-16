[{$smarty.block.parent}]

[{if method_exists($oViewConf, 'showPayPalCheckoutBannerOnCheckoutPage') && $oViewConf->showPayPalCheckoutBannerOnCheckoutPage()}]
    <div id="basket-paypal-installment-banner"></div>
    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','css/paypal_installment.css')}]
    [{assign var="basketAmount" value=$oxcmp_basket->getPrice()}]
    [{include file="@osc_paypal/frontend/installment_banners.tpl" amount=$basketAmount->getPrice() selector=$oViewConf->getPayPalCheckoutBannerCartPageSelector()}]
[{/if}]
