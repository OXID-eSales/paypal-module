(function () {
    let PayPalStandardPaymentController = function (config) {
        // Inherit from base controller
        PayPalPaymentControllerBase.call(this, config);

        // PayPal-specific payment source configuration
        this.getPaymentSource = function () {
            let paymentSource = {
                paypal: {
                    experience_context: {
                        shipping_preference: "SET_PROVIDED_ADDRESS",
                        return_url: PayPalPayment.getConfigValue("updateOxUserWithPayPalCustomerIdUrl"),
                        cancel_url: PayPalPayment.getConfigValue("shopOrderCancelUrl")
                    }
                }
            };

            const customerId = PayPalPayment.getConfigValue('customerId');
            if (customerId) {
                paymentSource.paypal.attributes = {
                    customer: {
                        id: customerId
                    }
                };
            }

            return PayPalPayment.currentOrder.vaultPayment ?
                PayPalPayment.modifyPaymentSourceForVaulting(paymentSource) :
                paymentSource;
        };

        this.modifyPaymentSourceForVaulting = function (paymentSource) {
            paymentSource.paypal.attributes = Object.assign(paymentSource.paypal.attributes || {},
                {
                    vault: {
                        store_in_vault: "ON_SUCCESS",
                        usage_type: "MERCHANT",
                        customer_type: "CONSUMER",
                        permit_multiple_payment_tokens: true
                    }
                }
            );

            return paymentSource;
        };

        // PayPal-specific order creation
        this.createOrder = async function (data, actions) {
            PayPalPayment.removeErrorMessage();
            PayPalPayment.addSubmitButtonOverlay();
            PayPalPayment.paypalOverlayWatcher();
            let checkTermsAndConditions = PayPalPayment.checkTermsAndConditions();

            if(false === checkTermsAndConditions) {
                PayPalPayment.currentError = PayPalI18n.READ_AND_CONFIRM_TERMS;
                PayPalPayment.removeSubmitButtonOverlay();
                return;
            }

            // Create shop order first
            let shopOrderCreateResult = await PayPalPayment.backendRequest('shopOrderCreateUrl', {}, {
                'deliveryAddressId': PayPalPayment.getConfigValue('deliveryAddressId'),
                'paymentId': PayPalPayment.getConfigValue('paymentId')
            });

            document.dispatchEvent(new CustomEvent('shopOrderCreated', new Object({detail: {...shopOrderCreateResult}})));

            // Create PayPal order
            let payPalOrderCreateResult = await PayPalPayment.backendRequest('payPalOrderCreateUrl', {}, {
                'shopOrderId': shopOrderCreateResult.shopOrderId,
                'vaultPayment': PayPalPayment.currentOrder.vaultPayment,
                'useVaultedPayment': PayPalPayment.config.vaultedPaymentSource,
                'deliveryAddressId': PayPalPayment.getConfigValue('deliveryAddressId')
            });

            document.dispatchEvent(new CustomEvent('payPalOrderCreated', new Object({detail: {...payPalOrderCreateResult.payPalOrder}})));

            if (payPalOrderCreateResult.status !== 'success') {
                throw new Error('PayPal order creation failed: ' + payPalOrderCreateResult.message);
            }

            //if the vaulted payment source is used, go to finalize payment
            if (null !== PayPalPayment.config.vaultedPaymentSource && payPalOrderCreateResult.payPalOrder.status === 'COMPLETED') {
                PayPalPayment.afterCaptureOrder();
            }

            return payPalOrderCreateResult.payPalOrder.id;
        };

        this.captureOrder = async function (data, actions) {
            //if we managed to get at this stage, closing the overlay not suppose to be watched anymore
            PayPalPayment.reactOnPayPalOverlayClosed = false;
            let result = await PayPalPayment.backendRequest('shopOrderCaptureUrl', {}, {
                'orderId': data.orderID,
                'paymentId': PayPalPayment.getConfigValue('paymentId')
            });

            if (result.paymentStatus === 'success') {
                PayPalPayment.afterCaptureOrder();
            }
        };

        // PayPal-specific button settings
        this.getPayButtonSettings = function () {
            const buttonSettings = {
                createOrder: PayPalPayment.createOrder,
                onApprove: PayPalPayment.handlePaymentAuthorization,
                onCancel: PayPalPayment.cancelOrder,
                onError: PayPalPayment.handleError
            };

            if (PayPalPayment.config.captureStrategy === 'CAPTURE') {
                buttonSettings.onApprove = PayPalPayment.captureOrder;
            }

            return buttonSettings;
        };

        // PayPal-specific button rendering
        this.renderButton = function (style) {
            const vaultedPaymentSource = null !== PayPalPayment.getConfigValue('vaultedPaymentSource');
            if (vaultedPaymentSource) {
                this.initializeAcceptPaymentButton();
            } else {
                const buttonSettings = Object.assign(
                    PayPalPayment.getPayButtonSettings(),
                    {
                        style: typeof style == 'object' ? style : {}
                    }
                );
                let button = paypal.Buttons(buttonSettings);

                if (button.isEligible()) {
                    button.render(PayPalPayment.getConfigValue('buttonSelector'));
                }
            }
        };

        return this.init();
    };

    window.addEventListener('load', function () {
        if (typeof PayPalPaymentControllerConfig === 'object' &&
            PayPalPaymentControllerConfig.paymentId === 'oscpaypal') {
            window.PayPalPayment = new PayPalStandardPaymentController();

            window.PayPalPayment.renderButton(typeof PayPalButtonStyle === 'object' ? PayPalButtonStyle : {});
        }
    });
})();
