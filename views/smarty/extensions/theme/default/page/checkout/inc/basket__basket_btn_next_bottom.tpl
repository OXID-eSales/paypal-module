[{assign var="config" value=$oViewConf->getPayPalCheckoutConfig()}]
[{assign var="className" value=$oViewConf->getTopActiveClassName()}]
[{if $config->isActive() && !$oViewConf->isPayPalExpressSessionActive() && $config->showPayPalBasketButton()}]
    [{include file="@osc_paypal/frontend/paymentbuttons.tpl" buttonId="PayPalPayButtonNextCart2" buttonClass="float-right pull-right paypal-button-wrapper small"}]
    <div class="float-right pull-right paypal-button-or">
        [{"OR"|oxmultilangassign|oxupper}]
    </div>
[{/if}]
