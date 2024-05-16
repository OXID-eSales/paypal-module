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
    document.addEventListener('DOMContentLoaded', function() {
        check_applepay();
    });
    let globalPaymentRequestData = null;
    async function preloadPaymentRequestData() {
        let url = "[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=getPaymentRequestLines&paymentid=oscpaypal_applepay&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]";
        const response = await fetch(url);
        const payment_request_line = await response.json();

        globalPaymentRequestData = {
            countryCode: global_apple_pay_config.countryCode,
            merchantCapabilities: global_apple_pay_config.merchantCapabilities,
            supportedNetworks: global_apple_pay_config.supportedNetworks,
            currencyCode: global_apple_pay_config.currencyCode,
            requiredShippingContactFields: ["name", "phone", "email", "postalAddress"],
            requiredBillingContactFields: ["postalAddress"],
            ...payment_request_line
        };

        console.log('REQUEST LINE');
        console.log(payment_request_line);
        console.log('PAYMENT REQUEST');
        console.log(globalPaymentRequestData);
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
                let error_message = "";
                if (!window.ApplePaySession) {
                    error_message = "This device does not support Apple Pay";
                } else if (!ApplePaySession.canMakePayments()) {
                    error_message = "This device, although an Apple device, is not capable of making Apple Pay payments";
                }

                if (error_message !== "") {
                    console.error(error_message);
                    throw new Error(error_message); // Fehler werfen, wenn Apple Pay nicht unterstützt wird
                }

                applepay = paypal.Applepay();

                try {
                    const applepay_config = await applepay.config();
                    if (applepay_config.isEligible && ApplePaySession.canMakePayments()) {
                        global_apple_pay_config = applepay_config;
                        await preloadPaymentRequestData(); // Jetzt innerhalb des try-Blocks nach dem Laden der Konfiguration
                        document.getElementById("applepay-container").innerHTML = '<apple-pay-button id="applepay_button" buttonstyle="black" type="plain" locale="en">';
                        document.getElementById("applepay_button").addEventListener("click", handle_applepay_clicked);
                    } else {
                        console.error("Apple Pay is not eligible on this device.");
                    }
                } catch (error) {
                    console.error('Error while fetching Apple Pay configuration:', error);
                    throw error; // Fehler weiter nach außen werfen
                }
            };

            let handleApplePayPaymentAuthorized = (event) => {
                console.log('---- handleApplePayPaymentAuthorized -----')
                console.log('Your billing address is:', event.payment.billingContact);
                console.log('Your shipping address is:', event.payment.shippingContact);
                applepay_payment_event = event.payment;
                let intent = 'captures';
                let intent_object = intent === "authorize" ? "authorizations" : "captures";

                createOrderUrl = "[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=createOrder&paymentid=oscpaypal_applepay&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]"
                fetch(createOrderUrl, {
                    method: "post", headers: { "Content-Type": "application/json; charset=utf-8" },
                    body: JSON.stringify({ "intent": intent_object })
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
                                approve_order = "[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=approveOrder&paymentid=oscpaypal_applepay&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]"
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
                                            current_ap_session.completePayment(session.STATUS_SUCCESS);
                                            display_success_message({"order_details": order_details, "paypal_buttons": paypal_buttons});
                                        } else {
                                            current_ap_session.completePayment(session.STATUS_FAILURE);
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
                                    session.completePayment(session.STATUS_FAILURE);
                                    display_error_alert();
                                }
                            });
                    });
            };
            let ap_validate = (event, session) => {
                console.log('ap_validate')
                console.log(session)
                applepay.validateMerchant({
                    validationUrl: event.validationURL,
                    // function to get oxid eshop-name
                    displayName: "Oxid Esales"
                })
                    .then(validateResult => {
                        session.completeMerchantValidation(validateResult.merchantSession);
                    })
                    .catch(validateError => {
                        console.error(validateError);
                        session.abort();
                    });
            };
    let handle_applepay_clicked = async (event) => {
        console.log('----- CLICKED -------');
        console.log(globalPaymentRequestData);
        try {
            let session = new ApplePaySession(4, globalPaymentRequestData);
            console.log('Session created:', session);
            session.onvalidatemerchant = (event) => {
                console.log('onValidateMerchant')
                applepay
                    .validateMerchant({
                        validationUrl: event.validationURL,
                    })
                    .then((payload) => {
                        session.completeMerchantValidation(payload.merchantSession);
                    })
                    .catch((err) => {
                        console.error(err);
                        session.abort();
                    });
            };

            session.onpaymentmethodselected = () => {
                session.completePaymentMethodSelection({
                    newTotal: globalPaymentRequestData.total,
                });
            };
            session.onpaymentauthorized = (event) => {
                console.log('Payment authorized...', event.payment);
                handleApplePayPaymentAuthorized(event);
            };
            try {
                session.begin();
                console.log('BEGIN SESSION');
            } catch (error) {
                console.error('Error starting ApplePaySession:', error);
            }
        } catch (error) {
            console.log('BLUB')
            console.error('Error starting ApplePaySession:', error);
        }
    };


    [{/capture}]

[{oxscript include="https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js" }]
[{oxscript add=$smarty.capture.detailsApplePayScript}]
