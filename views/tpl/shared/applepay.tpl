[{assign var="sToken" value=$oViewConf->getSessionChallengeToken()}]
[{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
[{assign var="config" value=$oViewConf->getPayPalCheckoutConfig()}]
[{assign var="oConfig" value=$oViewConf->getConfig()}]
[{assign var="bApplePayDelivery" value=$oConfig->getConfigParam('oscPayPalUseApplePayAddress')}]
<style>
    #applepay_button {
        float: right;
    }
    #applepay-container {
        float: right;
    }
</style>
[{if $phpstorm}]<script>[{/if}]
        [{capture name="detailsApplePayScript"}]
    let order_id;
    let global_apple_pay_config;
    let current_ap_session;
    let applepay;
    let apple_pay_email;
    let pp_order_id;
    let applepay_payment_event;
    let globalPaymentRequestData = null;

    // Function to load a script dynamically
    const script_to_head = (attributes_object) => {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            for (const name of Object.keys(attributes_object)) {
                script.setAttribute(name, attributes_object[name]);
            }
            document.head.appendChild(script);
            script.addEventListener('load', resolve);
            script.addEventListener('error', reject);
        });
    };

    // Event listener for document ready
    document.addEventListener('DOMContentLoaded', async () => {
        [{if $config->isSandbox()}]
        console.log('--- DOMContentLoaded ---');
        [{/if}]
        await check_applepay();
    });

    // Function to preload payment request data
    async function preloadPaymentRequestData() {
        [{if $config->isSandbox()}]
        console.log('--- Start preloadPaymentRequestData ---');
        [{/if}]
        let url = "[{$sSelfLink|cat:'cl=oscpaypalproxy&fnc=getPaymentRequestLines&paymentid=oscpaypal_applepay&context=continue&aid='|cat:$aid|cat:'&stoken='|cat:$sToken}]";
        [{if $config->isSandbox()}]
        console.log('Fetching payment request lines from URL:', url);
        [{/if}]
        const response = await fetch(url);
        const payment_request_line = await response.json();

        globalPaymentRequestData = {
            countryCode: global_apple_pay_config.countryCode,
            merchantCapabilities: global_apple_pay_config.merchantCapabilities,
            supportedNetworks: global_apple_pay_config.supportedNetworks,
            currencyCode: global_apple_pay_config.currencyCode,
            requiredShippingContactFields: ["email"],
            requiredBillingContactFields: ["postalAddress"],
            ...payment_request_line
        };
        [{if $config->isSandbox()}]
        console.log('Payment Request Data:', globalPaymentRequestData);
        console.log('--- End preloadPaymentRequestData ---');
    }   [{/if}]

    // Function to handle closing alerts
    const handle_close = (event) => {
        console.log('--- handle_close ---');
        event.target.closest(".ms-alert").remove();
    };

    // Event listener for click events
    const handle_click = (event) => {
        if (event.target.classList.contains("ms-close")) {
            handle_close(event);
        }
    };

    document.addEventListener("click", handle_click);

    // Function to display an error alert
    const display_error_alert = () => {
        console.log('--- display_error_alert ---');
        window.scrollTo({ top: 0, left: 0, behavior: "smooth" });
        document.getElementById("alert").innerHTML = `
    <div class="ms-alert ms-action2 ms-small">
        <span class="ms-close"></span>
        <p>An Error Occurred! (View console for more info)</p>
    </div>`;
    };

    // Function to display a success message
    const display_success_message = (object) => {
        console.log('--- display_success_message ---');
        const { order_details, paypal_buttons } = object;
        const intent_object = intent === "authorize" ? "authorizations" : "captures";

        document.getElementById("alert").innerHTML = `
    <div class='ms-alert ms-action'>
        Thank you ${order_details?.payer?.name?.given_name || ''} ${order_details?.payer?.name?.surname || ''}
        for your payment of ${order_details.purchase_units[0].payments[intent_object][0].amount.value}
        ${order_details.purchase_units[0].payments[intent_object][0].amount.currency_code}!
    </div>`;

        paypal_buttons.close();
        document.getElementById("card-form").classList.add("hide");
        document.getElementById("applepay-container").classList.add("hide");
    };

    // Apple Pay functions
    const check_applepay = async () => {
        [{if $config->isSandbox()}]
        console.log('--- Start check_applepay ---');
        [{/if}]
        let error_message = "";

        if (!window.ApplePaySession) {
            error_message = "This device does not support Apple Pay";
        } else if (!ApplePaySession.canMakePayments()) {
            error_message = "This device, although an Apple device, is not capable of making Apple Pay payments";
        }

        if (error_message !== "") {
            console.error(error_message);
            throw new Error(error_message);
        }

        applepay = paypal.Applepay();

        try {
            const applepay_config = await applepay.config();
            if (applepay_config.isEligible && ApplePaySession.canMakePayments()) {
                global_apple_pay_config = applepay_config;
                await preloadPaymentRequestData();

                document.getElementById("applepay-container").innerHTML = `
            <apple-pay-button id="applepay_button" buttonstyle="black" type="plain" locale="en"></apple-pay-button>`;
                document.getElementById("applepay_button").addEventListener("click", handle_applepay_clicked);
            } else {
                console.error("Apple Pay is not eligible on this device.");
            }
        } catch (error) {
            console.error('Error while fetching Apple Pay configuration:', error);
            throw error;
        }
        [{if $config->isSandbox()}]
        console.log('--- End check_applepay ---');
        [{/if}]
    };

    const handleApplePayPaymentAuthorized = async (event, session) => {
        [{if $config->isSandbox()}]
        console.log('--- Start handleApplePayPaymentAuthorized ---');
        console.log('Payment authorized event:', event);
        [{/if}]
        applepay_payment_event = event.payment;
        let intent = 'captures';
        let intent_object = intent === "authorize" ? "authorizations" : "captures";

        const createOrderUrl = "[{$sSelfLink|cat:'cl=oscpaypalproxy&fnc=createApplepayOrder&paymentid=oscpaypal_apple_pay&context=continue&aid='|cat:$aid|cat:'&stoken='|cat:$sToken}]";
        [{if $config->isSandbox()}]
        console.log('Creating order with URL:', createOrderUrl);
        [{/if}]
        try {
            const response = await fetch(createOrderUrl, {
                method: "post",
                headers: { "Content-Type": "application/json; charset=utf-8" },
                body: JSON.stringify({ "intent": intent_object,"data":applepay_payment_event })
            });
            const pp_data = await response.json();
            [{if $config->isSandbox()}]
            console.log('Order created, PayPal data:', pp_data);
            [{/if}]
            pp_order_id = pp_data.id;
            apple_pay_email = applepay_payment_event.shippingContact.emailAddress;

            try {
                const confirmResult = await applepay.confirmOrder({
                    orderId: pp_order_id,
                    token: applepay_payment_event.token,
                    billingContact: applepay_payment_event.billingContact
                });
                [{if $config->isSandbox()}]
                console.log('Order confirmed, result:', confirmResult);
                [{/if}]
            } catch (confirmError) {
                console.error('Error confirming order:', confirmError);
                session.completePayment(session.STATUS_FAILURE);
                return;
            }
            const approve_order = "[{$sSelfLink|cat:'cl=oscpaypalproxy&fnc=approveOrder&paymentid=oscpaypal_apple_pay&context=continue&aid='|cat:$aid|cat:'&stoken='|cat:$sToken}]";
            const approve_response = await fetch(approve_order, {
                method: "post",
                headers: { "Content-Type": "application/json; charset=utf-8" },
                body: JSON.stringify({
                    "intent": intent,
                    "orderID": pp_order_id,
                    "email": apple_pay_email
                })
            });
            const order_details = await approve_response.json();
            [{if $config->isSandbox()}]
            console.log('Order approval response:', order_details);
            [{/if}]

            if (order_details.status === "APPROVED") {
                await onApprove(order_details);
                await onCreated(order_details);
            } else {
                session.completePayment(session.STATUS_FAILURE);
                throw new Error("payment was not completed, please view console for more information");
            }
        } catch (error) {
            console.error('Error during payment authorization', error);
            session.completePayment(session.STATUS_FAILURE);
            display_error_alert();
        }
        [{if $config->isSandbox()}]
        console.log('--- End handleApplePayPaymentAuthorized ---');
        [{/if}]
    };

    const ap_validate = async (event, session) => {
        [{if $config->isSandbox()}]
        console.log('--- Start ap_validate ---');
        [{/if}]
        try {
            const validateResult = await applepay.validateMerchant({
                validationUrl: event.validationURL,
                displayName: "Oxid Esales"
            });
            [{if $config->isSandbox()}]
            console.log('Merchant validation result:', validateResult);
            [{/if}]
            session.completeMerchantValidation(validateResult.merchantSession);
        } catch (validateError) {
            console.error('Merchant validation error:', validateError);
            session.abort();
        }
        [{if $config->isSandbox()}]
        console.log('--- End ap_validate ---');
        [{/if}]
    };

    async function onApprove(confirmOrderResponse) {
        [{if $config->isSandbox()}]
        console.log('--- Start onApprove ---');
        [{/if}]
        const url = `[{$sSelfLink|cat:'cl=order&fnc=createApplePayOrder&context=continue&aid='|cat:$aid|cat:'&stoken='|cat:$sToken|cat:'&sDeliveryAddressMD5='|cat:$oView->getDeliveryAddressMD5()}]`;
        [{if $config->isSandbox()}]
        console.log('Approving order with URL:', url);
        [{/if}]
        const formData = new FormData();
        formData.append('orderID', confirmOrderResponse.id);

        try {
            const res = await fetch(url, {
                method: 'post',
                body: formData
            });
            const data = await res.json();
            [{if $config->isSandbox()}]
            console.log('Order approval data:', data);
            [{/if}]
            if (data.status === "ERROR") {
                location.reload();
            }
        } catch (error) {
            console.error('Error submitting form:', error);
        }
        [{if $config->isSandbox()}]
        console.log('--- End onApprove ---');
        [{/if}]
    }

    function onCreated(confirmOrderResponse, actions) {
        [{if $config->isSandbox()}]
        console.log('--- Start onCreated ---');
        [{/if}]
        const captureData = new FormData();
        captureData.append('orderID', confirmOrderResponse.id);
        return fetch('[{$sSelfLink|cat:"cl=order&fnc=captureApplePayOrder&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken|cat:"&sDeliveryAddressMD5="|cat:$oView->getDeliveryAddressMD5()}]', {
            method: 'post',
            body: captureData
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            console.log('Order creation capture data:', data);
            var goNext = Array.isArray(data.location) && data.location[0];
            window.location.href = '[{$sSelfLink}]' + goNext;

            if (data.status === "ERROR") {
                location.reload();
            }
        });
        [{if $config->isSandbox()}]
        console.log('--- End onCreated ---');
        [{/if}]
    }

    // Handle Apple Pay button click
    const handle_applepay_clicked = async (event) => {
        [{if $config->isSandbox()}]
        console.log('--- Start handle_applepay_clicked ---');
        [{/if}]
        try {
            let session = new ApplePaySession(3, globalPaymentRequestData);
            [{if $config->isSandbox()}]
            console.log('Apple Pay session created:', session);
            [{/if}]
            session.onvalidatemerchant = (event) => ap_validate(event, session);
            session.onpaymentmethodselected = () => {
                session.completePaymentMethodSelection({ newTotal: globalPaymentRequestData.total });
            };
            session.onpaymentauthorized = (event) => handleApplePayPaymentAuthorized(event, session);
            session.begin();
            [{if $config->isSandbox()}]
            console.log('Apple Pay session begun');
            [{/if}]
        } catch (error) {
            console.error('Error starting ApplePaySession:', error);
        }
        [{if $config->isSandbox()}]
        console.log('--- End handle_applepay_clicked ---');
        [{/if}]
    };

    [{/capture}]

        [{oxscript include="https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js" }]
        [{oxscript add=$smarty.capture.detailsApplePayScript}]
