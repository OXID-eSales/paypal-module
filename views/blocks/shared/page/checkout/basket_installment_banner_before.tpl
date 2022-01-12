[{if $oViewConf->isModuleActive('oxscpaypal') && $oViewConf->showPayPalBannerOnCheckoutPage()}]
    [{oxstyle include=$oViewConf->getModuleUrl('oxscpaypal','out/src/css/paypal_installment.css')}]
    [{assign var="basketAmount" value=$oxcmp_basket->getPrice()}]
    [{include file="tpl/installment_banners.tpl" amount=$basketAmount->getPrice() selector=$oViewConf->getPayPalBannerPaymentPageSelector()}]
[{/if}]

[{$smarty.block.parent}]
