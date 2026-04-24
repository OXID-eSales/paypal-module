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
            PayPalPayment.addBeforeUnloadListener();
            PayPalPayment.reactOnPayPalOverlayClosed = false;

            document.dispatchEvent(new CustomEvent('beforeShopOrderCreated'));

            let checkTermsAndConditions = PayPalPayment.checkTermsAndConditions();
            if(false === checkTermsAndConditions) {
                PayPalPayment.currentError = PayPalI18n.READ_AND_CONFIRM_TERMS;
                PayPalPayment.removeSubmitButtonOverlay();

                PayPalPayment.handleError(new Error());
                return;
            }

            let result = await PayPalPayment.backendRequest('shopOrderCreateUrl', {}, {
                'deliveryAddressId': PayPalPayment.getConfigValue('deliveryAddressId'),
                'vaultPayment': PayPalPayment.currentOrder.vaultPayment,
                'paymentId': PayPalPayment.getConfigValue('paymentId')
            });

            if (result.status === 'error' ){
                // Backend may supply a redirect target (e.g. back to the order overview
                // after a non-recoverable PayPal API error like an invalid address).
                // The accompanying error message is queued via addErrorToDisplay and
                // rendered on the target page.
                if (result.redirect) {
                    PayPalPayment.removeBeforeUnloadListener();
                    window.location = result.redirect;
                    return false;
                }

                PayPalPayment.showErrorMessage(result.message);
                PayPalPayment.handleError(result.message);

                return false;
            }

            document.dispatchEvent(new CustomEvent('shopOrderCreated', new Object({detail: {...result.shopOrder}})));
            document.dispatchEvent(new CustomEvent('payPalOrderCreated', new Object({detail: {...result.payPalOrder}})));
            let shopOrderId = result.shopOrder.shopOrderId;

            //vaulted payment source
            if (result.payPalOrder.status === 'COMPLETED' ){
                let result = await PayPalPayment.backendRequest('shopOrderCompleteUrl', {}, {
                    'orderId': shopOrderId
                });
                if ('success' !== result.status) {
                    return false;
                }

                PayPalPayment.thankYouPageRedirect();
            }

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
            PayPalPayment.removeBeforeUnloadListener();
            //if we managed to get at this stage, closing the overlay not suppose to be watched anymore
            PayPalPayment.reactOnPayPalOverlayClosed = false;
            PayPalPayment.captureInProgress = true;

            let result = await PayPalPayment.backendRequest('shopOrderCaptureUrl', {}, {
                'orderId': data.orderID,
                'paymentId': PayPalPayment.getConfigValue('paymentId'),
                'vaultPayment': PayPalPayment.currentOrder.vaultPayment
            });

            if (result.status === 'error' ){
                PayPalPayment.showErrorMessage(result.message);
                PayPalPayment.handleError(result.message);
            }

            if (result.status === 'success') {
                PayPalPayment.afterCaptureOrder();
            }
        };

        this.afterCaptureOrder = function (details) {
            PayPalPayment.thankYouPageRedirect();
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

        this.handlePaymentAuthorization = async function (details) {
            const result = await PayPalPayment.authorizeOrder({});

            if (result.status === 'error' ){
                PayPalPayment.showErrorMessage(result.message);
                PayPalPayment.handleError(result.message);
            }

            if (result.status === 'success' && result.paymentStatus === 'success'){
                PayPalPayment.thankYouPageRedirect();
            }
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

            const cardFieldsSettings = {
                createOrder: PayPalPayment.createOrder,
                onApprove: PayPalPayment.handlePaymentAuthorization,
                onError: PayPalPayment.handleError,
                onCancel: PayPalPayment.cancelOrder,
                inputEvents: {
                    onChange: (data) => {
                        PayPalPayment.cardFieldsState = data;
                        PayPalPayment.removeSubmitButtonOverlay();
                    }
                }
            };

            if (PayPalPayment.config.captureStrategy === 'CAPTURE') {
                cardFieldsSettings.onApprove = PayPalPayment.captureOrder;
            }

            const cardFields = paypal.CardFields(cardFieldsSettings);

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
                            if(null != PayPalPayment.currentError) {
                                PayPalPayment.showErrorMessage(PayPalPayment.currentError);
                            }

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
            PayPalPayment.removeSubmitButtonOverlay();
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
