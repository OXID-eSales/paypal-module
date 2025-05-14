(function () {
    let PayPalACDCPaymentController = function (config) {
        // Inherit from base controller
        PayPalPaymentControllerBase.call(this, config);

        this.createOrder = async function (data, actions) {
            let result = await PayPalPayment.backendRequest('shopOrderCreateUrl', {}, {
                'deliveryAddressId': PayPalPayment.getConfigValue('deliveryAddressId'),
                'vaultPayment': PayPalPayment.currentOrder.vaultPayment
            });

            if (undefined !== result.error){
                //error will be reported in logs via handleError method
                return false;
            }

            if (result.payPalOrder.status === 'PAYER_ACTION_REQUIRED' || result.payPalOrder.status === 'APPROVED' ){
                for (const i in result.payPalOrder.links) {
                    if (result.payPalOrder.links[i].rel === 'payer-action'){
                        window.location = result.payPalOrder.links[i].href;
                        return;
                    }
                }
            }

            document.dispatchEvent(new CustomEvent('shopOrderCreated', new Object({detail: {...result.shopOrder}})));
            document.dispatchEvent(new CustomEvent('payPalOrderCreated', new Object({detail: {...result.payPalOrder}})));

            return result.payPalOrder.id;
        };

        this.captureOrder = async function (data, actions) {
            let result = await PayPalPayment.backendRequest('shopOrderCaptureUrl', {}, {
                'orderId': data.orderID
            });

            if(result.status === 'success'){
                PayPalPayment.afterCaptureOrder();
            }
        };

        this.afterCaptureOrder = function (details) {
            window.location = PayPalPayment.getConfigValue('shopThankYouPageUrl');
        };

        this.initilizeAcceptPaymentButton = function() {
            const submitButton = document.querySelector(PayPalPayment.config.buttonSelector);
            submitButton.addEventListener('click', function (e){
                e.stopPropagation();
                e.preventDefault();
                PayPalPayment.buttonControll('disabled', true);
                if (PayPalPayment.config.vaultedPaymentSource) {
                    PayPalPayment.createOrder();
                }
            });
        };

        this.renderCardFields = function() {
            this.initilizeAcceptPaymentButton();

            if (null !== this.config.vaultedPaymentSource) {
                return;
            }

            if (!paypal.CardFields) {
                console.error('Card Fields not available in this version of PayPal SDK');
                return;
            }

            const cardFields = paypal.CardFields({
                createOrder: PayPalPayment.createOrder,
                onApprove: PayPalPayment.captureOrder,
                onError: PayPalPayment.handleError
            });

            // Helper-Function to read the calculated CSS properties of an element
            function getComputedStylesAsObject(selector) {
                // Find element
                const element = document.querySelector(selector);
                if (!element) {
                    return {};
                }

                // Get all calculated styles
                const computedStyle = window.getComputedStyle(element);

                // Extract relevant properties and convert them into an object
                const styleObject = {};

                // List of properties you want to adopt
                const relevantProperties = [
                    'color', 'font-size', 'font-family', 'font-weight',
                    'background-color', 'border', 'border-radius', 'padding',
                    'box-shadow', 'height', 'line-height'
                ];

                relevantProperties.forEach(prop => {
                    // CSS properties in JavaScript have camelCase (e.g. fontSize instead of font-size)
                    // But we can leave them in CSS format for the PayPal API
                    styleObject[prop] = computedStyle.getPropertyValue(prop);
                });

                return styleObject;
            }

            if (cardFields.isEligible()) {
                const cardNameContainer = document.getElementById("card-name-field-container");
                const cardNumberContainer = document.getElementById("card-number-field-container");
                const cardCvvContainer = document.getElementById("card-cvv-field-container");
                const cardExpiryContainer = document.getElementById("card-expiry-field-container");
                const submitButton = document.getElementById(
                    PayPalPayment.getConfigValue('buttonSelector').split('#').reverse()[0]
                );

                // Retrieve styles from your existing element
                const formControlStyles = getComputedStylesAsObject('input.form-control');
                // Prepare these styles for PayPal cardFields
                const payPalInputStyles = {
                    'input': formControlStyles
                };

                if (cardNameContainer) {
                    cardFields.NameField({
                        placeholder: PayPalI18n.OSC_PAYPAL_ACDC_CARD_NAME_ON_CARD,
                        style: payPalInputStyles
                    }).render(cardNameContainer);
                }
                if (cardNumberContainer) {
                    cardFields.NumberField({
                        placeholder: PayPalI18n.OSC_PAYPAL_ACDC_CARD_NUMBER,
                        style: payPalInputStyles
                    }).render(cardNumberContainer);
                }
                if (cardCvvContainer) {
                    cardFields.CVVField({
                        placeholder: PayPalI18n.OSC_PAYPAL_ACDC_CARD_CVV,
                        style: payPalInputStyles
                    }).render(cardCvvContainer);
                }
                if (cardExpiryContainer) {
                    cardFields.ExpiryField({
                        placeholder: PayPalI18n.OSC_PAYPAL_ACDC_CARD_EXDATE,
                        style: payPalInputStyles
                    }).render(cardExpiryContainer);
                }
                if (submitButton) {
                    submitButton.addEventListener("click", () => {
                        cardFields.submit().catch(err => {
                            console.error('Error submitting card fields:', err);
                        });
                    });
                }
            }
        };

        return this.init();
    };

    window.addEventListener('load', function() {
        if (typeof PayPalPaymentControllerConfig === 'object') {
            if(PayPalPaymentControllerConfig.paymentId !== 'oscpaypal_acdc'){
                return;
            }

            window.PayPalPayment = new PayPalACDCPaymentController();
            window.PayPalPayment.renderCardFields();
        }
    });
})();
