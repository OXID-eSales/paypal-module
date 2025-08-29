[{if $blCanBuy && $oView->showPayPalExpressOnDetailsPage()}]
    [{include file="@osc_paypal/frontend/shared/paymentbuttons.tpl" buttonId="PayPalButtonProductMain" buttonClass="paypal-button-wrapper large" aid=$oDetailsProduct->oxarticles__oxid->value}]
[{/if}]
[{include file="@osc_paypal/frontend/shared/paypalexpresshint.tpl" withBreak=false}]
