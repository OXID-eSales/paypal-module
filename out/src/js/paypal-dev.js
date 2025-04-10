(function () {
    let PayPalACDCPaymentController = function (config) {
        // Inherit from base controller
        PayPalPaymentControllerBase.call(this, config);


        this.createOrder = async function (data, actions) {
            let result = await PayPalPayment.backendRequest('shopACDCOrderCreationStatusUrl', {}, {
                'deliveryAddressId': PayPalPayment.getConfigValue('deliveryAddressId')
            });

            document.dispatchEvent(new CustomEvent('shopOrderCreated', new Object({detail: {...result.shopOrder}})));
            document.dispatchEvent(new CustomEvent('payPalOrderCreated', new Object({detail: {...result.payPalOrder}})));

            return result.payPalOrder.id
        }

        this.captureOrder = async function (data, actions) {
            let result = await PayPalPayment.backendRequest('shopACDCOrderCaptureStatusUrl', {}, {
                'orderId': data.orderID
            });
            debugger
            if(result['status'] === 'success'){
                PayPalPayment.afterCaptureACDCOrder();
            }
        }

        this.afterCaptureACDCOrder = function (details) {
            debugger
            window.location = PayPalPayment.getConfigValue('shopThankYouPageUrl');
        };

        this.renderCardFields = function() {
            if (!paypal.CardFields) {
                console.error('Card Fields not available in this version of PayPal SDK');
                return;
            }

            const cardFields = paypal.CardFields({
                style: {
                    'input': {
                        'color': '#3A3A3A',
                        'transition': 'color 160ms linear',
                        '-webkit-transition': 'color 160ms linear'
                    },
                    ':focus': {
                        'color': '#333333'
                    },
                    '.valid': {
                        'color': 'green'
                    },
                    '.invalid': {
                        'color': 'red'
                    }
                },
                createOrder: PayPalPayment.createOrder,
                onApprove: PayPalPayment.captureOrder,
                onError: PayPalPayment.handleError
            });

            if (cardFields.isEligible()) {
                const cardNameContainer = document.getElementById("card-name-field-container");
                const cardNumberContainer = document.getElementById("card-number-field-container");
                const cardCvvContainer = document.getElementById("card-cvv-field-container");
                const cardExpiryContainer = document.getElementById("card-expiry-field-container");
                const submitButton = document.getElementById(
                    PayPalPayment.getConfigValue('buttonSelector').split('#').reverse()[0]
                );

                if (cardNameContainer) {
                    cardFields.NameField().render(cardNameContainer);
                }
                if (cardNumberContainer) {
                    cardFields.NumberField().render(cardNumberContainer);
                }
                if (cardCvvContainer) {
                    cardFields.CVVField().render(cardCvvContainer);
                }
                if (cardExpiryContainer) {
                    cardFields.ExpiryField().render(cardExpiryContainer);
                }
                if (submitButton) {
                    submitButton.addEventListener("click", () => {
                        cardFields.submit().catch(err => {
                            console.error('Error submitting card fields:', err);
                        });
                    });
                }
            }
        }

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