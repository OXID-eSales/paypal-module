[{if $oView->showPayPalExpressOnDetailsPage()}]
    [{include file="modules/osc/paypal/paymentbuttons.tpl" buttonId="PayPalButtonProductMain" buttonClass="paypal-button-wrapper large" aid=$oDetailsProduct->oxarticles__oxid->value}]
[{/if}]
[{include file="modules/osc/paypal/paypalexpresshint.tpl" withBreak=false}]
