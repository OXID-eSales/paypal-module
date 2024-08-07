[{assign var="sToken" value=$oViewConf->getSessionChallengeToken()}]
[{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
[{assign var="oPPconfig" value=$oViewConf->getPayPalCheckoutConfig()}]
[{assign var="oConfig" value=$oViewConf->getConfig()}]
[{assign var="bGooglePayDelivery" value=$oConfig->getConfigParam('oscPayPalUseGooglePayAddress')}]
<style>
    #oscpaypal_googlepay {
        float: right;
    }
</style>
[{capture name="detailsGooglePayScript"}]
[{if false}]<script>[{/if}]
    document.addEventListener("DOMContentLoaded", (event) => {
        if (google && paypal.Googlepay) {
            onGooglePayLoaded().catch(console.log);
        }
    });

    const baseRequest = {
        apiVersion: 2,
        apiVersionMinor: 0,
    };

    let paymentsClient = null,
        allowedPaymentMethods = null,
        merchantInfo = null;

    /* Configure your site's support for payment methods supported by the Google Pay */
    function getGoogleIsReadyToPayRequest(allowedPaymentMethods) {
        return Object.assign({}, baseRequest, {
            allowedPaymentMethods: allowedPaymentMethods,
            billingAddressRequired: true,
            assuranceDetailsRequired: true,
            billingAddressParameters: {format: 'FULL'},
        });
    }

    /* Fetch Default Config from PayPal via PayPal SDK */
    async function getGooglePayConfig() {
        if (allowedPaymentMethods == null || merchantInfo == null) {
            const googlePayConfig = await paypal.Googlepay().config();

            allowedPaymentMethods = googlePayConfig.allowedPaymentMethods;
            merchantInfo = googlePayConfig.merchantInfo;
            merchantInfo.merchantName = [{$oxcmp_shop->oxshops__oxname->value|json_encode}];
        }
        return {
            allowedPaymentMethods,
            merchantInfo,
        };
    }

    /* Configure support for the Google Pay API */
    async function getGooglePaymentDataRequest() {
        const paymentDataRequest = Object.assign({}, baseRequest);
        const {allowedPaymentMethods, merchantInfo} = await getGooglePayConfig();

        paymentDataRequest.allowedPaymentMethods = allowedPaymentMethods;
        paymentDataRequest.merchantInfo = merchantInfo;

        paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION"];
        paymentDataRequest.emailRequired = true;
        paymentDataRequest.shippingAddressRequired = [{if $bGooglePayDelivery == true}]true[{else}]false[{/if}];
        paymentDataRequest.shippingAddressParameters = {'phoneNumberRequired': true};

        return paymentDataRequest;
    }

    function onPaymentAuthorized(paymentData) {
        return new Promise(function (resolve, reject) {
            processPayment(paymentData)
                .then(function (data) {
                    [{if $oPPconfig->isSandbox()}]
                        console.log('onPaymentAuthorized');
                        console.log(data);
                        console.log('onPaymentAuthorized End');
                    [{/if}]

                    resolve({transactionState: "SUCCESS"});
                })
                .catch(function (errDetails) {
                    resolve({transactionState: "ERROR"});
                });
        });
    }

    function getGooglePaymentsClient() {
        if (paymentsClient === null) {
            paymentsClient = new google.payments.api.PaymentsClient({
                environment: [{if $oPPconfig->isSandbox()}]"TEST"[{else}]"PRODUCTION"[{/if}],
                paymentDataCallbacks: {
                    onPaymentAuthorized: onPaymentAuthorized,
                },
            });
        }
        return paymentsClient;
    }

    async function onGooglePayLoaded() {

        const paymentsClient = getGooglePaymentsClient();
        const {allowedPaymentMethods} = await getGooglePayConfig();
        paymentsClient
            .isReadyToPay(getGoogleIsReadyToPayRequest(allowedPaymentMethods))
            .then(function (response) {
                [{if $oPPconfig->isSandbox()}]
                    console.log('getGoogleIsReadyToPayResponse debug');
                    console.log(response);
                    console.log('getGoogleIsReadyToPayResponse debugend');
                [{/if}]
                if (response.result) {
                    addGooglePayButton();
                }
            })
            .catch(function (err) {
                console.error(err);
            });
    }

    function addGooglePayButton() {
        const paymentsClient = getGooglePaymentsClient();
        const button = paymentsClient.createButton({
            buttonType: 'buy',
            buttonLocale: '[{$oView->getActiveLangAbbr()|oxlower}]',
            onClick: onGooglePaymentButtonClicked,
        });
        document.getElementById("[{$buttonId}]").appendChild(button);
    }

    async function onGooglePaymentButtonClicked() {
        const paymentDataRequest = await getGooglePaymentDataRequest();

        try {
            const getBasketUrl = "[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=getGooglepayBasket&paymentid=oscpaypal_googlepay&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]";
            const basketDetails = await fetch(getBasketUrl);
            const json = await basketDetails.json();

            paymentDataRequest.transactionInfo = {
                displayItems: json.displayItems,
                countryCode: json.countryCode,
                currencyCode: json.currencyCode,
                totalPriceStatus: json.totalPriceStatus,
                totalPrice: json.totalPrice,
                totalPriceLabel: json.totalPriceLabel,
            };

        } catch (error) {
            console.error(error);
        }

        const paymentsClient = getGooglePaymentsClient();
        [{if $oPPconfig->isSandbox()}]
            console.log('paymentDataRequest debug');
            console.log(paymentDataRequest);
            console.log('paymentDataRequest debugend');
        [{/if}]
        paymentsClient.loadPaymentData(paymentDataRequest)
            .then(function () {
                //   location.replace("[{$sSelfLink|cat:"cl=order&fnc=execute"}]");
            })
            .catch(err => {
                [{if $oPPconfig->isSandbox()}]
                    if (err.statusCode !== "CANCELED") {
                        console.log(err);
                    }
                [{/if}]
            });
    }

    async function processPayment(paymentData) {
        try {
            /*** Create oxid Order ***/
            const createOrderUrl = '[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=createGooglepayOrder&paymentid=oscpaypal_googlepay&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]';
            const {id} = await fetch(createOrderUrl, {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify(paymentData),
            }).then((res) => res.json());

            const confirmOrderResponse = await paypal.Googlepay().confirmOrder({
                orderId: id,
                paymentMethodData: paymentData.paymentMethodData,
            });
            [{if $oPPconfig->isSandbox()}]
                console.log('confirmOrderResponse debug');
                console.log(confirmOrderResponse);
                console.log('confirmOrderResponse debugend');
            [{/if}]
            if (confirmOrderResponse.status === "PAYER_ACTION_REQUIRED") {
                [{if $oPPconfig->isSandbox()}]
                console.log("==== Confirm Payment Completed Payer Action Required =====");
                [{/if}]
                paypal.Googlepay().initiatePayerAction({ orderId: id }).then(async () => {
                    [{if $oPPconfig->isSandbox()}]
                    console.log("===== Payer Action Completed =====");
                    [{/if}]
                    /** GET Order */
                    const orderResponse = await fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=approveOrder&paymentid="|cat:$buttonId|cat:"&context=continue&stoken="|cat:$sToken}]', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ orderID: id })
                    }).then((res) => res.json());
                    [{if $oPPconfig->isSandbox()}]
                    console.log("===== 3DS Contingency Result Fetched =====");
                    console.log(orderResponse);
                    [{/if}]
                    if (orderResponse.status === "APPROVED") {
                        [{if $oPPconfig->isSandbox()}]
                        console.log("===== ORDER APPROVED =====");
                        console.log(orderResponse);
                        [{/if}]
                        await onApprove(orderResponse);
                        await onCreated(orderResponse);
                    }
                    function onCreated(confirmOrderResponse, actions) {
                        captureData = new FormData();
                        captureData.append('orderID', confirmOrderResponse.id);
                        return fetch('[{$sSelfLink|cat:"cl=order&fnc=captureGooglePayOrder&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken|cat:"&sDeliveryAddressMD5="|cat:$oView->getDeliveryAddressMD5()}]', {
                            method: 'post',
                            body: captureData
                        }).then(function (res) {
                            return res.json();
                        }).then(function (data) {
                            [{if $oPPconfig->isSandbox()}]
                            console.log('onCreated data debug');
                            console.log(data);
                            console.log('onCreated data debugend');
                            [{/if}]
                            var goNext = Array.isArray(data.location) && data.location[0];

                            window.location.href = '[{$sSelfLink}]' + goNext;
                            [{if $oPPconfig->isSandbox()}]
                            console.log(data);
                            [{/if}]
                            if (data.status === "ERROR") {
                                location.reload();
                            }
                        });
                    }

                    function onApprove(confirmOrderResponse, actions) {

                        const url = '[{$sSelfLink|cat:"cl=order&fnc=createGooglePayOrder&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken|cat:"&sDeliveryAddressMD5="|cat:$oView->getDeliveryAddressMD5()}]';
                        createData = new FormData();
                        createData.append('orderID', confirmOrderResponse.id);
                        fetch(url, {
                            method: 'POST',
                            body: createData
                        }).then(function (res) {
                            return res.json();
                        }).then(function (data) {
                            [{if $oPPconfig->isSandbox()}]
                            console.log('onApprove data debug');
                            console.log(data);
                            console.log('onApprove data debugend');
                            [{/if}]
                            if (data.status === "ERROR") {
                                location.reload();
                            }
                        });
                    }
                });
            }
        } catch (err) {
            return {
                transactionState: "ERROR",
                error: {
                    message: err.message,
                },
            };
        }
    }

    [{if false}]</style>[{/if}]
[{/capture}]
[{oxscript include="https://pay.google.com/gp/p/js/pay.js"}]
[{oxscript add=$smarty.capture.detailsGooglePayScript}]
