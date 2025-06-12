[{if $oViewConf->showPayPalExpressInMiniBasket() && $oViewConf->getTopActiveClassName() neq "order" && $oViewConf->getTopActiveClassName() neq "payment"}]
    [{include file="@osc_paypal/frontend/shared/paymentbuttons.tpl" buttonId="PayPalPayButtonNextCart1" buttonClass="float-right pull-right paypal-button-wrapper small"}]
[{/if}]
[{include file="@osc_paypal/frontend/shared/paypalexpresshint.tpl" withBreak=true}]
