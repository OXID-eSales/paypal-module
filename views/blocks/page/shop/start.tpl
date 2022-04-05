[{if $oViewConf->isModuleActive('osc_paypal') && $oViewConf->showPayPalBannerOnStartPage()}]
    [{assign var="paypalInstallmentPrice" value=$oxcmp_basket->getBruttoSum()}]
    [{if $oxcmp_basket->isPriceViewModeNetto()}]
        [{assign var="paypalInstallmentPrice" value=$oxcmp_basket->getNettoSum()}]
    [{/if}]

    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','out/src/css/paypal_installment.css')}]
    [{include file="modules/osc/paypal/installment_banners.tpl" amount=$paypalInstallmentPrice selector=$oViewConf->getPayPalBannerStartPageSelector()}]
[{/if}]
[{$smarty.block.parent}]