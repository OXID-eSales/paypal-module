[{assign var="config" value=$oViewConf->getPayPalCheckoutConfig()}]
[{assign var="className" value=$oViewConf->getTopActiveClassName()}]
[{if $config->isActive() && !$oViewConf->isPayPalExpressSessionActive() && $config->showPayPalBasketButton()}]
    <div class="clearfix" style="margin-bottom: 15px;"></div>
    [{include file="modules/osc/paypal/paymentbuttons.tpl" buttonId="PayPalPayButtonNextCart2" buttonClass="float-right pull-right paypal-button-wrapper small"}]
[{/if}]
