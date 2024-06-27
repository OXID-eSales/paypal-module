[{if method_exists($oViewConf, 'showPayPalCheckoutBannerOnCheckoutPage') && $oViewConf->showPayPalCheckoutBannerOnCheckoutPage()}]
    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','css/paypal_installment.css')}]
    [{assign var="basketAmount" value=$oxcmp_basket->getPrice()}]
    [{include file="@osc_paypal/frontend/installment_banners.tpl" amount=$basketAmount->getPrice() selector=$oViewConf->getPayPalCheckoutBannerPaymentPageSelector()}]
[{/if}]

[{$smarty.block.parent}]