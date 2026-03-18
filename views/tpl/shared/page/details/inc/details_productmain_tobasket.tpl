[{if $blCanBuy && $oDetailsProduct->isBuyable() && $oView->showPayPalExpressOnDetailsPage()}]
    [{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
    <div id="PayPalButtonProductMainConfig" style="display:none"
         data-self-link="[{$sSelfLink}]"
         data-stoken="[{$oViewConf->getSessionChallengeToken()}]"
         data-aid="[{$oDetailsProduct->oxarticles__oxid->value}]"></div>
    [{include file="modules/osc/paypal/paymentbuttons.tpl" buttonId="PayPalButtonProductMain" buttonClass="paypal-button-wrapper large" aid=$oDetailsProduct->oxarticles__oxid->value}]
[{/if}]
[{include file="modules/osc/paypal/paypalexpresshint.tpl" withBreak=false}]
