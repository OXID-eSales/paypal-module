[{if $oViewConf->isPayPalExpressSessionActive()}]
    <div class="alert alert-info">
        [{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
        [{oxmultilang ident="OSC_PAYPAL_RUNNING_EXPRESS_CHECKOUT_SESSION_HINT"}][{if $withBreak}]<br />[{/if}]
        <a href="[{$sSelfLink|cat:"cl=order"}]">[{oxmultilang ident="OSC_PAYPAL_RUNNING_EXPRESS_CHECKOUT_SESSION_HINT_AFREF"}]</a>
    </div>
[{/if}]