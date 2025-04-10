[{if method_exists($oView, 'isPayPalCheckoutPayment') && $oView->isPayPalCheckoutPayment()}]
    [{assign var="payment" value=$oView->getPayment()}]
    [{assign var="paymentId" value=$payment->getId()}]
    [{assign var="sToken" value=$oViewConf->getSessionChallengeToken()}]
    [{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
    [{assign var="purchaseUnits" value=$oView->getPurchaseUnits()}]
    [{assign var="oPPconfig" value=$oViewConf->getPayPalCheckoutConfig()}]
    [{assign var="isSandBox" value=$oPPconfig->isSandbox()}]
    [{assign var="captureStrategy" value=$oPPconfig->getPayPalStandardCaptureStrategy()}]

    [{if $isSandBox}]
        [{assign var="debug" value="&XDEBUG_SESSION=PHPSTORM"}]
        [{else}]
        [{assign var="debug" value=""}]
    [{/if}]

    <script>
        const PayPalPaymentControllerConfiguratorDefaults = {
            shopOrderErrorUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=logError&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
            shopOrderDeleteUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=deleteShopOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
            payPalOrderDetailsUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=fetchPayPalOrderDetails&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
            errorLogUrl: '[{$sSelfLink|cat:"cl=payment&payerror=2&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
            shopThankYouPageUrl: '[{$sSelfLink|cat:"cl=thankyou&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
            deliveryAddressId: '[{$oView->getDeliveryAddressMD5()}]',
            purchaseUnits: [{$purchaseUnits}],
        }

        [{if $paymentId == 'oscpaypal'}]
            const PayPalPaymentControllerConfigurator = function () {
                return Object.assign (PayPalPaymentControllerConfiguratorDefaults, {
                    shopOrderCreationStatusUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=createShopOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
                    shopOrderPatchingStatusUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=patchShopOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
                    updateOxUserWithPayPalCustomerIdUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=updateOxUserWithPayPalCustomerId&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
                    buttonSelector: 'div#[{$paymentId}]',
                    captureStrategy: '[{if $captureStrategy == 'directly'}]CAPTURE[{else}]AUTHORIZE[{/if}]',
                    paymentId: 'oscpaypal',
                });
            };
    [{/if}]

    [{if $paymentId == 'oscpaypal_acdc'}]
            const PayPalPaymentControllerConfigurator = function () {
                return Object.assign (PayPalPaymentControllerConfiguratorDefaults, {
                    shopACDCOrderCaptureStatusUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=captureOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
                    shopACDCOrderCreationStatusUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=createAcdcOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
                    cardFields: true, //probably not needed when payment controller will be split
                    paymentId: '[{$paymentId}]', //probably not needed when payment controller will be split
                    buttonSelector: 'button#[{$paymentId}]'
                });
            };

    [{/if}]

        window.PayPalPaymentControllerConfig = new PayPalPaymentControllerConfigurator();
        document.dispatchEvent(
            new CustomEvent('PayPalPaymentControllerConfigCreated', { detail: window.PayPalPaymentControllerConfig })
        );
    </script>

[{/if}]