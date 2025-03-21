[{if $oViewConf->showPayPalExpressInMiniBasket()}]
    [{include file="@osc_paypal/frontend/shared/paymentbuttons.tpl" buttonId="PayPalPayButtonNextCart1" buttonClass="float-right pull-right paypal-button-wrapper small"}]
    <div class="float-right pull-right paypal-button-or">
        [{"OR"|oxmultilangassign|oxupper}]
    </div>
[{/if}]
[{if $oViewConf->isPayPalExpressSessionActive() }]
    [{oxmultilang ident="OSC_PAYPAL_RUNNING_EXPRESS_CHECKOUT_SESSION_HINT"}]
    [{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
    <p><a href="[{$sSelfLink|cat:"cl=order"}]">[{oxmultilang ident="OSC_PAYPAL_RUNNING_EXPRESS_CHECKOUT_SESSION_HINT_AFREF"}]</a></p>
[{/if}]
