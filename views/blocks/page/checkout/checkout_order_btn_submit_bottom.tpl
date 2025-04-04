[{assign var="payment" value=$oView->getPayment()}]
[{assign var="paymentId" value=$payment->getId()}]
[{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
[{assign var="purchaseUnits" value=$oView->getPurchaseUnits()}]
[{assign var="oPPconfig" value=$oViewConf->getPayPalCheckoutConfig()}]
[{assign var="isSandBox" value=$oPPconfig->isSandbox()}]
[{assign var="captureStrategy" value=$oPPconfig->getPayPalStandardCaptureStrategy()}]

[{if "oscpaypal" == $paymentId}]
    <div id="[{$paymentId}]" class="paypal-button-container [{$buttonClass}]"></div>
    <script>
        const PayPalPaymentControllerConfigurator = function () {
            return {
                shopOrderErrorUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=logError&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{if $isSandBox}]&XDEBUG_SESSION=PHPSTORM[{/if}]',
                shopOrderCreationStatusUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=createShopOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{if $isSandBox}]&XDEBUG_SESSION=PHPSTORM[{/if}]',
                shopOrderPatchingStatusUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=patchShopOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{if $isSandBox}]&XDEBUG_SESSION=PHPSTORM[{/if}]',
                shopOrderDeleteUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=deleteShopOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{if $isSandBox}]&XDEBUG_SESSION=PHPSTORM[{/if}]',
                payPalOrderDetailsUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=fetchPayPalOrderDetails&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{if $isSandBox}]&XDEBUG_SESSION=PHPSTORM[{/if}]',
                updateOxUserWithPayPalCustomerIdUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=updateOxUserWithPayPalCustomerId&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{if $isSandBox}]&XDEBUG_SESSION=PHPSTORM[{/if}]',
                errorLogUrl: '[{$sSelfLink|cat:"cl=payment&payerror=2&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{if $isSandBox}]&XDEBUG_SESSION=PHPSTORM[{/if}]',
                shopThankYouPageUrl: '[{$sSelfLink|cat:"cl=thankyou&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{if $isSandBox}]&XDEBUG_SESSION=PHPSTORM[{/if}]',
                deliveryAddressId: '[{$oView->getDeliveryAddressMD5()}]',
                purchaseUnits: [{$purchaseUnits}],
                buttonSelector: '#[{$paymentId}]',
                captureStrategy: '[{if $captureStrategy == 'directly'}]CAPTURE[{else}]AUTHORIZE[{/if}]'
            }
        };
    </script>
    [{assign var="sFileMTime" value=$oViewConf->getModulePath('osc_paypal','out/src/js/paypal-dev.js')|filemtime}]
    <script id="dev_scripts23432" src="[{$oViewConf->getModuleUrl('osc_paypal', 'out/src/js/paypal-dev.js')|cat:"?"|cat:$sFileMTime}]"></script>

    [{/if}]

[{if "oscpaypal_pui" == $paymentId}]
    [{if $oViewConf->isFlowCompatibleTheme()}]
    [{include file="modules/osc/paypal/checkout_order_btn_submit_bottom_flow.tpl"}]
    [{else}]
    [{include file="modules/osc/paypal/checkout_order_btn_submit_bottom_wave.tpl"}]
    [{/if}]
    [{/if}]

[{if "oscpaypal_googlepay" == $paymentId}]
    [{include file="modules/osc/paypal/googlepay.tpl" buttonClass="paypal-button-wrapper large"}]
    [{elseif "oscpaypal_applepay" == $paymentId}]
    [{include file="modules/osc/paypal/applepay.tpl" paymentId=$paymentId buttonClass="paypal-button-wrapper large"}]
    <div id="applepay-container" class="paypal-button-container paypal-button-wrapper paypal-button-right large"></div>
[{elseif "oscpaypal" != $paymentId}]
    [{$smarty.block.parent}]
[{/if}]
