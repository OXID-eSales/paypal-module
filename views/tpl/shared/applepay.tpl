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
    let current_customer_id;
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

    const is_user_logged_in = () => {
        return new Promise((resolve) => {
            customer_id = localStorage.getItem("logged_in_user_id") || "oxdefaultadmin";
            resolve();
        });
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

    const paypal_sdk_url = "https://www.paypal.com/sdk/js";
    const client_id = "REPLACE_WITH_YOUR_CLIENT_ID";
    const currency = "USD";
    const intent = "capture";

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
        document.getElementById("alert").innerHTML = `<div class=\'ms-alert ms-action\'>Thank you ` + (order_details?.payer?.name?.given_name || ``) + ` ` + (order_details?.payer?.name?.surname || ``) + ` for your payment of ` + order_details.purchase_units[0].payments[intent_object][0].amount.value + ` ` + order_details.purchase_units[0].payments[intent_object][0].amount.currency_code + `!</div>`;

        //Close out the PayPal buttons that were rendered
        paypal_buttons.close();
        document.getElementById("card-form").classList.add("hide");
        document.getElementById("applepay-container").classList.add("hide");
    }

    //PayPal Code
    is_user_logged_in()

        .then(() => {
            //Handle loading spinner
            //document.getElementById("loading").classList.add("hide");
            //document.getElementById("content").classList.remove("hide");
            /*let paypal_buttons = paypal.Buttons({ // https://developer.paypal.com/sdk/js/reference
                onClick: (data) => { // https://developer.paypal.com/sdk/js/reference/#link-oninitonclick
                    //Custom JS here
                },
                style: { //https://developer.paypal.com/sdk/js/reference/#link-style
                    shape: 'rect',
                    color: 'gold',
                    layout: 'vertical',
                    label: 'paypal'
                },

                createOrder: function(data, actions) { //https://developer.paypal.com/docs/api/orders/v2/#orders_create
                    return fetch("/create_order", {
                        method: "post", headers: { "Content-Type": "application/json; charset=utf-8" },
                        body: JSON.stringify({ "intent": intent })
                    })
                        .then((response) => response.json())
                        .then((order) => { return order.id; });
                },

                onApprove: function(data, actions) {
                    order_id = data.orderID;
                    console.log(data);
                    return fetch("/complete_order", {
                        method: "post", headers: { "Content-Type": "application/json; charset=utf-8" },
                        body: JSON.stringify({
                            "intent": intent,
                            "order_id": order_id
                        })
                    })
                        .then((response) => response.json())
                        .then((order_details) => {
                            let intent_object = intent === "authorize" ? "authorizations" : "captures";
                            if (order_details.purchase_units[0].payments[intent_object][0].status === "COMPLETED") {
                                display_success_message({"order_details": order_details, "paypal_buttons": paypal_buttons});
                            } else {
                                console.log(order_details);
                                throw error("payment was not completed, please view console for more information");
                            }
                        })
                        .catch((error) => {
                            console.log(error);
                            display_error_alert()
                        });
                },

                onCancel: function (data) {
                    document.getElementById("alerts").innerHTML = `<div class="ms-alert ms-action2 ms-small"><span class="ms-close"></span><p>Order cancelled!</p>  </div>`;
                },

                onError: function(err) {
                    console.log(err);
                }
            });
            paypal_buttons.render('#payment_options');
            //Hosted Fields
            if (paypal.HostedFields.isEligible()) {
                // Renders card fields
                paypal_hosted_fields = paypal.HostedFields.render({
                    // Call your server to set up the transaction
                    createOrder: () => {
                        return fetch("/create_order", {
                            method: "post", headers: { "Content-Type": "application/json; charset=utf-8" },
                            body: JSON.stringify({ "intent": intent })
                        })
                            .then((response) => response.json())
                            .then((order) => { order_id = order.id; return order.id; });
                    },
                    styles: {
                        '.valid': {
                            color: 'green'
                        },
                        '.invalid': {
                            color: 'red'
                        },
                        'input': {
                            'font-size': '16pt',
                            'color': '#ffffff'
                        },
                    },
                    fields: {
                        number: {
                            selector: "#card-number",
                            placeholder: "4111 1111 1111 1111"
                        },
                        cvv: {
                            selector: "#cvv",
                            placeholder: "123"
                        },
                        expirationDate: {
                            selector: "#expiration-date",
                            placeholder: "MM/YY"
                        }
                    }
                }).then((card_fields) => {
                    document.querySelector("#card-form").addEventListener("submit", (event) => {
                        event.preventDefault();
                        document.querySelector("#card-form").querySelector("input[type='submit']").setAttribute("disabled", "");
                        document.querySelector("#card-form").querySelector("input[type='submit']").value = "Loading...";
                        card_fields
                            .submit(
                                //Customer Data BEGIN
                                //This wasn't part of the video guide originally, but I've included it here
                                //So you can reference how you could send customer data, which may
                                //be a requirement of your project to pass this info to card issuers
                                {
                                    // Cardholder's first and last name
                                    cardholderName: "Raúl Uriarte, Jr.",
                                    // Billing Address
                                    billingAddress: {
                                        // Street address, line 1
                                        streetAddress: "123 Springfield Rd",
                                        // Street address, line 2 (Ex: Unit, Apartment, etc.)
                                        extendedAddress: "",
                                        // State
                                        region: "AZ",
                                        // City
                                        locality: "CHANDLER",
                                        // Postal Code
                                        postalCode: "85224",
                                        // Country Code
                                        countryCodeAlpha2: "US",
                                    },
                                }
                                //Customer Data END
                            )
                            .then(() => {
                                return fetch("/complete_order", {
                                    method: "post", headers: { "Content-Type": "application/json; charset=utf-8" },
                                    body: JSON.stringify({
                                        "intent": intent,
                                        "order_id": order_id,
                                        "email": document.getElementById("email").value
                                    })
                                })
                                    .then((response) => response.json())
                                    .then((order_details) => {
                                        let intent_object = intent === "authorize" ? "authorizations" : "captures";
                                        if (order_details.purchase_units[0].payments[intent_object][0].status === "COMPLETED") {
                                            display_success_message({"order_details": order_details, "paypal_buttons": paypal_buttons});
                                        } else {
                                            console.log(order_details);
                                            throw error("payment was not completed, please view console for more information");
                                        }
                                    })
                                    .catch((error) => {
                                        console.log(error);
                                        display_error_alert();
                                    });
                            })
                            .catch((err) => {
                                console.log(err);
                                reset_purchase_button();
                                display_error_alert();
                            });
                    });
                });
            }*/
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
            let ap_payment_authed = (event) => {
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
                    displayName: "sss"
                })
                    .then(validateResult => {
                        current_ap_session.completeMerchantValidation(validateResult.merchantSession);
                    })
                    .catch(validateError => {
                        console.error(validateError);
                        current_ap_session.abort();
                    });
            };
            let handle_applepay_clicked = (event) => {
                const payment_request = {
                    countryCode: global_apple_pay_config.countryCode,
                    merchantCapabilities: global_apple_pay_config.merchantCapabilities,
                    supportedNetworks: global_apple_pay_config.supportedNetworks,
                    currencyCode: global_apple_pay_config.currencyCode,
                    requiredShippingContactFields: ["name", "phone", "email", "postalAddress"],
                    requiredBillingContactFields: ["postalAddress"],
                    total: {
                        label: "My Demo Company",
                        type: "final",
                        amount: 100.0,
                    }
                };
                current_ap_session = new ApplePaySession(4, payment_request);
                current_ap_session.onvalidatemerchant = ap_validate;
                current_ap_session.onpaymentauthorized = ap_payment_authed;
                current_ap_session.begin()
            };
        })
        .catch((error) => {
            reset_purchase_button();
        });

    [{/capture}]

[{oxscript include="https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js" }]
[{oxscript add=$smarty.capture.detailsApplePayScript}]
