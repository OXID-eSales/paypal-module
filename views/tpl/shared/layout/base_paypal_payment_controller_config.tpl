[{if method_exists($oView, 'isPayPalCheckoutPayment') && $oView->isPayPalCheckoutPayment()}]
    [{if method_exists($oView, 'isPayPalCheckoutPayment') && $oView->isPayPalCheckoutPayment()}]
    [{assign var="payment" value=$oView->getPayment()}]
    [{assign var="paymentId" value=$payment->getId()}]
    [{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
    [{assign var="purchaseUnits" value=$oView->getPurchaseUnits()}]
    [{assign var="oPPconfig" value=$oViewConf->getPayPalCheckoutConfig()}]
    [{assign var="isSandBox" value=$oPPconfig->isSandbox()}]
    [{assign var="captureStrategy" value=$oPPconfig->getPayPalStandardCaptureStrategy()}]

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
                captureStrategy: '[{if $captureStrategy == 'directly'}]CAPTURE[{else}]AUTHORIZE[{/if}]',
                paymentId: 'oscpaypal', //this part is changed in the template via event:
                cardFields: false
            }
        };

        window.PayPalPaymentControllerConfig = new PayPalPaymentControllerConfigurator();
        document.dispatchEvent(
            new CustomEvent('PayPalPaymentControllerConfigCreated', { detail: window.PayPalPaymentControllerConfig })
        );
    </script>
[{/if}]