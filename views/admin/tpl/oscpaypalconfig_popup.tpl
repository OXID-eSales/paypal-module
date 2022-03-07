[{include file="headitem.tpl" title="paypal"}]
[{capture assign="sPayPalJS"}]
    [{strip}]
        window.isSandBox = '[{$isSandBox}]';
        window.selfLink = '[{$oViewConf->getSelfLink()|replace:"&amp;":"&"}]';
        window.addEventListener('beforeunload', function (e) {
            localStorage.reloadoscpaypalconfig = "1";
        });
    [{/strip}]
[{/capture}]

[{oxscript add=$sPayPalJS}]
<div id="content">
    <div id="overlay"><div class="loader"></div></div>
    [{if !$ready}]
        [{if $isSandBox}]
            <p class="sandbox"><a target="_blank"
                  href="[{$oView->getSandboxSignUpMerchantIntegrationLink()}]"
                  class="boardinglink"
                  id="paypalonboardingsandbox"
                  data-paypal-onboard-complete="onboardedCallbackSandbox"
                  data-paypal-button="PPLtBlue">
                    [{oxmultilang ident="OSC_PAYPAL_SANDBOX_BUTTON_CREDENTIALS"}]
                </a>
            </p>
        [{else}]
            <p class="live"><a target="_blank"
                  href="[{$oView->getLiveSignUpMerchantIntegrationLink()}]"
                  class="boardinglink"
                  id="paypalonboardinglive"
                  data-paypal-onboard-complete="onboardedCallbackLive"
                  data-paypal-button="PPLtBlue">
                    [{oxmultilang ident="OSC_PAYPAL_LIVE_BUTTON_CREDENTIALS"}]
               </a>
            </p>
        [{/if}]
        <p class="help-block">[{oxmultilang ident="OSC_PAYPAL_ONBOARD_CLICK_HELP"}]</p>
    [{else}]
        <p class="help-block">[{oxmultilang ident="OSC_PAYPAL_ONBOARD_CLOSE_HELP"}]</p>
    [{/if}]
</div>
[{include file="bottomitem.tpl"}]
<script id="paypal-js" src="https://www.sandbox.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js"></script>
