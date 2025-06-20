[{if method_exists($oView, 'isPayPalCheckoutPayment') && $oView->isPayPalCheckoutPayment()}]
    [{assign var="payment" value=$oView->getPayment()}]
    [{assign var="paymentId" value=$payment->getId()}]
    [{assign var="sToken" value=$oViewConf->getSessionChallengeToken()}]
    [{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
    [{assign var="purchaseUnits" value=$oView->getPurchaseUnits()}]
    [{assign var="vaultedPaymentSource" value=$oView->getVaultedPaymentSource()}]
    [{assign var="oPPconfig" value=$oViewConf->getPayPalCheckoutConfig()}]
    [{assign var="customerId" value=$oView->getPayPalCustomerId()}]
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
            shopOrderCancelUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=cancelShopOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
            shopOrderFinalizeUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=finalizePayPalSession&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
            payPalOrderDetailsUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=fetchPayPalOrderDetails&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
            errorLogUrl: '[{$sSelfLink|cat:"cl=payment&payerror=2&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
            shopThankYouPageUrl: '[{$sSelfLink|cat:"cl=thankyou&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
            deliveryAddressId: '[{$oView->getDeliveryAddressMD5()}]',
            purchaseUnits: [{$purchaseUnits}],
            vaultedPaymentSource: [{$vaultedPaymentSource}],
            language: '[{$oView->getActiveLangAbbr()|lower}]',
            currency: '[{$currency->name}]',
            customerId: '[{$customerId}]'
        }

        [{if $paymentId == 'oscpaypal'}]
                window.PayPalPaymentControllerConfigurator = function () {
                return Object.assign (PayPalPaymentControllerConfiguratorDefaults, {
                    shopOrderCreateUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=createShopOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
                    payPalOrderCreateUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=createPayPalOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
                    shopOrderCaptureUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=captureOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
                    shopOrderPatchingUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=patchShopOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
                    updateOxUserWithPayPalCustomerIdUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=updateOxUserWithPayPalCustomerId&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
                    buttonSelector: '#[{$paymentId}]',
                    captureStrategy: '[{if $captureStrategy == 'directly'}]CAPTURE[{else}]AUTHORIZE[{/if}]',
                    paymentId: 'oscpaypal'
                });
            };
    [{/if}]

    [{if $paymentId == 'oscpaypal_acdc'}]
                window.PayPalPaymentControllerConfigurator = function () {
                return Object.assign (PayPalPaymentControllerConfiguratorDefaults, {
                    shopOrderCaptureUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=captureOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
                    shopOrderCreateUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=createAcdcOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
                    cardFields: true, //probably not needed when payment controller will be split
                    paymentId: '[{$paymentId}]', //probably not needed when payment controller will be split
                    buttonSelector: 'button#[{$paymentId}]'
                });
            };
    [{/if}]

    [{if $paymentId == 'oscpaypal_googlepay'}]
        [{assign var="bGooglePayDelivery" value=$oConfig->getConfigParam('oscPayPalUseGooglePayAddress')}]

        window.PayPalPaymentControllerConfigurator = function () {
            return Object.assign (PayPalPaymentControllerConfiguratorDefaults, {
                finalizeGooglePayOrder: '[{$sSelfLink|cat:"cl=order&fnc=finalizeGooglePay&paymentid=oscpaypal_googlepay&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
                executeGooglePayOrder: '[{$sSelfLink|cat:"cl=order&fnc=executeGooglePayOrder&paymentid=oscpaypal_googlepay&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
                captureGooglePayOrder: '[{$sSelfLink|cat:"cl=order&fnc=captureGooglePayOrder&paymentid=oscpaypal_googlepay&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]&sDeliveryAddressMD5=[{$oView->getDeliveryAddressMD5()}][{$debug}]',
                googlePayOrderCreateUrl: '[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=createGooglePayOrder&paymentid=oscpaypal_googlepay&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',
                shopOrderCreateUrl: '[{$sSelfLink|cat:"cl=ajaxpay&fnc=createShopOrder&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}][{$debug}]',

                isSandbox: !![{$isSandBox}],
                useGooglePayAddress: !!'[{$bGooglePayDelivery}]',
                merchantName: '[{$oxcmp_shop->oxshops__oxname->value|oxescape}]',
                totalPrice: '[{$oxcmp_basket->getPriceForPayment()}]',
                paymentId: '[{$paymentId}]',
                loadingContainer: 'google-pay-loading-container',
                buttonSelector: 'div#[{$paymentId}]'
            });
        };
    [{/if}]

        if ('undefined' !== typeof PayPalPaymentControllerConfigurator) {
            window.PayPalPaymentControllerConfig = new PayPalPaymentControllerConfigurator();
            document.dispatchEvent(
                new CustomEvent('PayPalPaymentControllerConfigCreated', {detail: window.PayPalPaymentControllerConfig})
            );
        }
    </script>

[{/if}]