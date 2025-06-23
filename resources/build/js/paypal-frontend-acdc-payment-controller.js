(function () {
    let PayPalACDCPaymentController = function (config) {
        // Inherit from base controller
        PayPalPaymentControllerBase.call(this, config);

        this.cardFieldsState =
            {
                fields: {
                    "cardNameField": { isValid: null },
                    "cardNumberField": { isValid: null },
                    "cardExpiryField": { isValid: null },
                    "cardCvvField": { isValid: null }
                }
            };

        this.createOrder = async function (data, actions) {
            let result = await PayPalPayment.backendRequest('shopOrderCreateUrl', {}, {
                'deliveryAddressId': PayPalPayment.getConfigValue('deliveryAddressId'),
                'vaultPayment': PayPalPayment.currentOrder.vaultPayment
            });
            if (undefined !== result.error) {
                return false;
            }

            document.dispatchEvent(new CustomEvent('shopOrderCreated', new Object({detail: {...result.shopOrder}})));

            if (result.payPalOrder.status === 'PAYER_ACTION_REQUIRED' || result.payPalOrder.status === 'APPROVED' ){
                for (const i in result.payPalOrder.links) {
                    if (result.payPalOrder.links[i].rel === 'payer-action'){
                        window.location = result.payPalOrder.links[i].href;
                        return;
                    }
                }
            }

            return result.payPalOrder.id;
        };

        this.captureOrder = async function (data, actions) {
            //if we managed to get at this stage, closing the overlay not suppose to be watched anymore
            PayPalPayment.reactOnPayPalOverlayClosed = false;
            let result = await PayPalPayment.backendRequest('shopOrderCaptureUrl', {}, {
                'orderId': data.orderID
            });

            if (result.status === 'success') {
                PayPalPayment.afterCaptureOrder();
            }
        };

        this.afterCaptureOrder = function (details) {
            window.location = PayPalPayment.getConfigValue('shopThankYouPageUrl');
        };

        this.removeErrorMessage = function (className) {
            className = className || '';
            const panelBody = document.querySelector("#card_container").parentElement;
            if (panelBody) {
                const existingError = panelBody.querySelector(".error-message" + (className ? '.' + className : ''));
                if (existingError) {
                    existingError.remove();
                }
            }
        };

        this.showErrorMessage = function (message, className) {
            className = className || '';
            const panelBody = document.querySelector("#card_container").parentElement;

            // Remove existing error if present
            this.removeErrorMessage(className);

            // Create and display a new error message
            const errorMessage = document.createElement("div");
            errorMessage.className = "error-message alert alert-danger " + className;
            errorMessage.textContent = message;

            panelBody.prepend(errorMessage);

            errorMessage.scrollIntoView({
                behavior: 'smooth'
            });
        };

        this.isCardFieldInvalid = function (name)
        {
            let valid = PayPalPayment.cardFieldsState.fields[name].isValid;
            return false === valid || null === valid ;
        };

        this.validateCardFields = function () {
            this.removeErrorMessage(); //clear all errors

            if (PayPalPayment.isCardFieldInvalid('cardNumberField')) {
                this.showErrorMessage(PayPalI18n.OSC_PAYPAL_ACDC_ERROR_MISSING_NUMBER,
                    'cardNumberError');
                return false;
            } else {
                this.removeErrorMessage('cardNumberError');
            }

            if (PayPalPayment.isCardFieldInvalid('cardExpiryField')) {
                this.showErrorMessage(PayPalI18n.OSC_PAYPAL_ACDC_ERROR_MISSING_EXDATE,
                    'cardExpiryError');
                return false;
            } else {
                this.removeErrorMessage('cardExpiryError');
            }

            if (PayPalPayment.isCardFieldInvalid('cardCvvField')) {
                this.showErrorMessage(PayPalI18n.OSC_PAYPAL_ACDC_ERROR_MISSING_CVV,
                    'cardCvvError');
                return false;
            } else {
                this.removeErrorMessage('cardCvvError');
            }

            if (PayPalPayment.isCardFieldInvalid('cardNameField')) {
                this.showErrorMessage(PayPalI18n.OSC_PAYPAL_ACDC_ERROR_MISSING_NAME,
                    'cardNameError');
                return false;
            } else {
                this.removeErrorMessage('cardNameError');
            }

            return true;
        };

        this.renderCardFields = function () {
            this.initializeAcceptPaymentButton();

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
                onError: PayPalPayment.handleError,
                inputEvents: {
                    onChange: (data) => {
                        PayPalPayment.cardFieldsState = data;
                        PayPalPayment.removeSubmitButtonOverlay();
                    }
                }
            });

            // Helper-Function to read the calculated CSS properties of an element
            function getComputedStylesAsObject(selector) {
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
                    'border', 'border-radius', 'padding',
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
                        PayPalPayment.addSubmitButtonOverlay();

                        // Validate fields before submission
                        if (!PayPalPayment.validateCardFields()) {
                            PayPalPayment.removeSubmitButtonOverlay();
                            return;
                        }

                        //enable the PP overlay watcher
                        //PP sdk do not support events, so we have to watch for the overlay
                        PayPalPayment.paypalOverlayWatcher();

                        cardFields.submit().catch(err => {
                            console.info('Error submitting card fields:', err);
                            PayPalPayment.showErrorMessage(PayPalI18n.OSC_PAYPAL_ACDC_ERROR_INBOX);

                            PayPalPayment.removeSubmitButtonOverlay();
                        });
                    });
                }
            }
        };

        return this.init();
    };

    document.addEventListener('paypalOverlayClosed', function() {
        //in some cases we shouldn't go forward
        if (true !== PayPalPayment.reactOnPayPalOverlayClosed) {
            return;
        }
        PayPalPayment.cancelOrder().then((e) => {
            this.removeSubmitButtonOverlay();
        });
    });

    window.addEventListener('load', function () {
        if (typeof PayPalPaymentControllerConfig === 'object') {
            if (PayPalPaymentControllerConfig.paymentId !== 'oscpaypal_acdc') {
                return;
            }

            window.PayPalPayment = new PayPalACDCPaymentController();
            window.PayPalPayment.renderCardFields();
        }
    });
})();
