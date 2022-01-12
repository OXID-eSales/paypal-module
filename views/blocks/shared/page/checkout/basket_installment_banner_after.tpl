[{$smarty.block.parent}]

[{if $oViewConf->isModuleActive('oxscpaypal') && $oViewConf->showPayPalBannerOnCheckoutPage()}]
    <div id="basket-paypal-installment-banner"></div>
    [{oxstyle include=$oViewConf->getModuleUrl('oxscpaypal','out/src/css/paypal_installment.css')}]
    [{assign var="basketAmount" value=$oxcmp_basket->getPrice()}]
    [{include file="tpl/installment_banners.tpl" amount=$basketAmount->getPrice() selector=$oViewConf->getPayPalBannerCartPageSelector()}]
[{/if}]
