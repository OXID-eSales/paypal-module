[{$smarty.block.parent}]

[{assign var="config" value=$oViewConf->getPayPalCheckoutConfig()}]
[{assign var="className" value=$oViewConf->getTopActiveClassName()}]
[{if $config->isActive() && !$oViewConf->isPayPalExpressSessionActive() && (($className == 'order' && !$oViewConf->isPayPalACDCSessionActive()) || $className !== 'order') && $config->showPayPalMiniBasketButton()}]
    <div class="float-right pull-right paypal-button-or">
        [{"OR"|oxmultilangassign|oxupper}]
    </div>
    [{include file="@osc_paypal/frontend/paymentbuttons.tpl" buttonId="PayPalPayButtonNextCart1" buttonClass="float-right pull-right paypal-button-wrapper small"}]
[{/if}]
