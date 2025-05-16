[{if $oViewConf->showPayPalExpressInMiniBasket() && $oViewConf->getTopActiveClassName() neq "order" && $oViewConf->getTopActiveClassName() neq "payment"}]
    [{include file="modules/osc/paypal/paymentbuttons.tpl" buttonId="PayPalPayButtonNextCart1" buttonClass="float-right pull-right paypal-button-wrapper small"}]
[{/if}]
[{include file="modules/osc/paypal/paypalexpresshint.tpl" withBreak=true}]
