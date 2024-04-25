[{assign var="sToken" value=$oViewConf->getSessionChallengeToken()}]
[{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
[{assign var="config" value=$oViewConf->getPayPalCheckoutConfig()}]
[{assign var="oConfig" value=$oViewConf->getConfig()}]
[{assign var="bApplePayDelivery" value=$oConfig->getConfigParam('oscPayPalUseApplePayAddress')}]
<style>
    #oscpaypal_Applepay {
        float: right;
    }
</style>
[{if $phpstorm}]<script>[{/if}]
    [{capture name="detailsApplePayScript"}]

    // Helper / Utility functions
    let order_id;
    let global_apple_pay_config;
    let current_ap_session;
    let applepay;
    let apple_pay_email;
    let pp_order_id;
    let applepay_payment_event;
    let script_to_head = (attributes_object) => {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            for (const name of Object.keys(attributes_object)) {
                script.setAttribute(name, attributes_object[name]);
            }
            document.head.appendChild(script);
            script.addEventListener('load', resolve);
            script.addEventListener('error', reject);
        });
    }
    let reset_purchase_button = () => {
        //document.querySelector("#card-form").querySelector("input[type='submit']").removeAttribute("disabled");
        //document.querySelector("#card-form").querySelector("input[type='submit']").value = "Purchase";
    }


    let handle_close = (event) => {
        event.target.closest(".ms-alert").remove();
    }
    let handle_click = (event) => {
        if (event.target.classList.contains("ms-close")) {
            handle_close(event);
        }
    }
    document.addEventListener("click", handle_click);


    let display_error_alert = () => {
        window.scrollTo({
            top: 0,
            left: 0,
            behavior: "smooth"
        });
        document.getElementById("alert").innerHTML = `<div class="ms-alert ms-action2 ms-small"><span class="ms-close"></span><p>An Error Ocurred! (View console for more info)</p>  </div>`;
    }
    let display_success_message = (object) => {
        order_details = object.order_details;
        paypal_buttons = object.paypal_buttons;
        console.log(order_details); //https://developer.paypal.com/docs/api/orders/v2/#orders_capture!c=201&path=create_time&t=response
        let intent_object = intent === "authorize" ? "authorizations" : "captures";
        //Custom Successful Message
        document.getElementById("alert").innerHTML = `<div class='ms-alert ms-action'>Thank you ${order_details?.payer?.name?.given_name || ''} ${order_details?.payer?.name?.surname || ''} for your payment of ${order_details.purchase_units[0].payments[intent_object][0].amount.value} ${order_details.purchase_units[0].payments[intent_object][0].amount.currency_code}!</div>`;

        //Close out the PayPal buttons that were rendered
        paypal_buttons.close();
        document.getElementById("card-form").classList.add("hide");
        document.getElementById("applepay-container").classList.add("hide");
    }

            //ApplePay Code
            let check_applepay = async () => {
                return new Promise((resolve, reject) => {
                    let error_message = "";
                    if (!window.ApplePaySession) {
                        error_message = "This device does not support Apple Pay";
                    } else
                    if (!ApplePaySession.canMakePayments()) {
                        error_message = "This device, although an Apple device, is not capable of making Apple Pay payments";
                    }
                    if (error_message !== "") {
                        resolve();
                    } else {
                        resolve();
                    }
                });
            };
            //Begin Displaying of ApplePay Button
            check_applepay()
                .then(async () => {
                    applepay = paypal.Applepay();
                    applepay.config()
                        .then(applepay_config => {

                            if (applepay_config.isEligible) {
                                document.getElementById("applepay-container").innerHTML = '<apple-pay-button id="applepay_button" buttonstyle="black" type="plain" locale="en">';
                                global_apple_pay_config = applepay_config;
                                document.getElementById("applepay_button").addEventListener("click", handle_applepay_clicked);
                            }
                        })
                        .catch(applepay_config_error => {
                            console.error('Error while fetching Apple Pay configuration:');
                            console.error(applepay_config_error);
                        });
                })
                .catch((error) => {
                    console.error(error);
                });
            let handleApplePayPaymentAuthorized = (event) => {
                applepay_payment_event = event.payment;
                createOrderUrl = "[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=createOrder&paymentid=oscpaypal_googlepay&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]"
                fetch(createOrderUrl, {
                    method: "post", headers: { "Content-Type": "application/json; charset=utf-8" },
                    body: JSON.stringify({ "intent": intent })
                })
                    .then((response) => response.json())
                    .then((pp_data) => {
                        console.log('pp data start')
                        console.log(pp_data)
                        console.log('pp data end')
                        pp_order_id = pp_data.id;
                        console.log('applepay_payment_event')
                        console.log(applepay_payment_event)
                        console.log('applepay_payment_event END')
                        apple_pay_email = applepay_payment_event.shippingContact.emailAddress;
                        applepay.confirmOrder({
                            orderId: pp_order_id,
                            token: applepay_payment_event.token,
                            billingContact: applepay_payment_event.billingContact
                        })
                            .then(confirmResult => {
                                approve_order = "[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=approveOrder&paymentid=oscpaypal_googlepay&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]"
                                fetch(approve_order, {
                                    method: "post", headers: { "Content-Type": "application/json; charset=utf-8" },
                                    body: JSON.stringify({
                                        "intent": intent,
                                        "order_id": pp_order_id,
                                        "email": apple_pay_email
                                    })
                                })
                                    .then((response) => response.json())
                                    .then((order_details) => {
                                        let intent_object = intent === "authorize" ? "authorizations" : "captures";
                                        if (order_details.purchase_units[0].payments[intent_object][0].status === "COMPLETED") {
                                            current_ap_session.completePayment(ApplePaySession.STATUS_SUCCESS);
                                            display_success_message({"order_details": order_details, "paypal_buttons": paypal_buttons});
                                        } else {
                                            current_ap_session.completePayment(ApplePaySession.STATUS_FAILURE);
                                            console.log(order_details);
                                            throw error("payment was not completed, please view console for more information");
                                        }
                                    })
                                    .catch((error) => {
                                        console.log('more inside')
                                        console.log(error);
                                        display_error_alert();
                                    });
                            })
                            .catch(confirmError => {
                                if (confirmError) {
                                    console.error('Error confirming order with applepay token');
                                    console.error(confirmError);
                                    current_ap_session.completePayment(ApplePaySession.STATUS_FAILURE);
                                    display_error_alert();
                                }
                            });
                    });
            };
            let ap_validate = (event) => {
                applepay.validateMerchant({
                    validationUrl: event.validationURL,
                    // function to get oxid eshop-name
                    displayName: ""
                })
                    .then(validateResult => {
                        current_ap_session.completeMerchantValidation(validateResult.merchantSession);
                    })
                    .catch(validateError => {
                        console.error(validateError);
                        current_ap_session.abort();
                    });
            };
            let handle_applepay_clicked = async (event) => {
                let url = "[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=approveOrder&paymentid=oscpaypal_googlepay&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]";
                const response = await fetch(url);
                const payment_request_line = await response.json();
                const payment_request = {
                    countryCode: global_apple_pay_config.countryCode,
                    merchantCapabilities: global_apple_pay_config.merchantCapabilities,
                    supportedNetworks: global_apple_pay_config.supportedNetworks,
                    currencyCode: global_apple_pay_config.currencyCode,
                    requiredShippingContactFields: ["name", "phone", "email", "postalAddress"],
                    requiredBillingContactFields: ["postalAddress"],
                    ... payment_request_line
                };
                console.log('REQUEST LINE');
                console.log(payment_request_line);
                console.log('PAYMENT REQUEST');
                console.log(payment_request);
                current_ap_session = new ApplePaySession(4, payment_request);
                current_ap_session.onvalidatemerchant = ap_validate;
                current_ap_session.onpaymentauthorized = handleApplePayPaymentAuthorized;
                current_ap_session.begin()
            };
        })
        .catch((error) => {
            reset_purchase_button();
        });

    [{/capture}]

[{oxscript include="https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js" }]
[{oxscript add=$smarty.capture.detailsApplePayScript}]
