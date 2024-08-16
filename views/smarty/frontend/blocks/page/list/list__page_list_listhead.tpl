[{$smarty.block.parent}]

[{if method_exists($oViewConf, 'showPayPalCheckoutBannerOnCategoryPage') && $oViewConf->showPayPalCheckoutBannerOnCategoryPage()}]
    [{assign var="paypalInstallmentPrice" value=$oxcmp_basket->getBruttoSum()}]
    [{if $oxcmp_basket->isPriceViewModeNetto()}]
        [{assign var="paypalInstallmentPrice" value=$oxcmp_basket->getNettoSum()}]
    [{/if}]

    [{oxstyle include=$oViewConf->getModuleUrl('osc_paypal','css/paypal_installment.css')}]
    [{include file="@osc_paypal/frontend/installment_banners.tpl" amount=$paypalInstallmentPrice selector=$oViewConf->getPayPalCheckoutBannerCategoryPageSelector()}]
[{/if}]