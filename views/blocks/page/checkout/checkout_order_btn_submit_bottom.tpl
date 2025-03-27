[{assign var="payment" value=$oView->getPayment()}]
[{assign var="paymentId" value=$payment->getId()}]
[{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
[{assign var="purchaseUnits" value=$oView->getPurchaseUnits()}]

[{if "oscpaypal" == $payment->getId()}]
    <input type="hidden" name="vaultPayment" id="oscPayPalVaultPayment" value="">
    <div id="[{$paymentId}]" class="paypal-button-container [{$buttonClass}]"></div>

    <script>
        window.PP_DATA_12321 = {
            shopOrderCreationStatusUrl: '[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=createShopOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]&XDEBUG_SESSION=PHPSTORM',
            shopOrderPatchingStatusUrl: '[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=patchShopOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]&XDEBUG_SESSION=PHPSTORM',
            shopOrderCancelStatusUrl: '[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelShopOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]&XDEBUG_SESSION=PHPSTORM',

            shopThankYouPageUrl: '[{$sSelfLink|cat:"cl=thankyou&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]&XDEBUG_SESSION=PHPSTORM',
            deladrid: '[{$oView->getDeliveryAddressMD5()}]',

shopOrderOnErrorUrl: '[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"}]',
            purchaseUnits: [{$purchaseUnits}],
            buttonSelector: '#[{$paymentId}]'
        }
        //https://developer.paypal.com/sdk/js/reference/#createorder

    </script>
    [{assign var="sFileMTime" value=$oViewConf->getModulePath('osc_paypal','out/src/js/paypal-dev.js')|filemtime}]
    <script id="dev_scripts23432" src="[{$oViewConf->getModuleUrl('osc_paypal', 'out/src/js/paypal-dev.js')|cat:"?"|cat:$sFileMTime}]"></script>


    [{/if}]
[{if "oscpaypal_pui" == $payment->getId()}]
    [{if $oViewConf->isFlowCompatibleTheme()}]
    [{include file="modules/osc/paypal/checkout_order_btn_submit_bottom_flow.tpl"}]
    [{else}]
    [{include file="modules/osc/paypal/checkout_order_btn_submit_bottom_wave.tpl"}]
    [{/if}]
    [{/if}]
[{if "oscpaypal_googlepay" == $payment->getId()}]
    [{include file="modules/osc/paypal/googlepay.tpl" buttonClass="paypal-button-wrapper large"}]
    [{elseif "oscpaypal_applepay" == $payment->getId()}]
    [{include file="modules/osc/paypal/applepay.tpl" paymentId=$payment->getId() buttonClass="paypal-button-wrapper large"}]
    <div id="applepay-container" class="paypal-button-container paypal-button-wrapper paypal-button-right large"></div>
    [{/if}]




