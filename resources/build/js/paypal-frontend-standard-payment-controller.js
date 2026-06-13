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
        // PayPal-specific order creation (optimized - single call)
        this.createOrder = async function (data, actions) {
            PayPalPayment.addBeforeUnloadListener();
            PayPalPayment.removeErrorMessage();
            PayPalPayment.addSubmitButtonOverlay();
            PayPalPayment.paypalOverlayWatcher();

            document.dispatchEvent(new CustomEvent('beforeShopOrderCreated'));

            let checkTermsAndConditions = PayPalPayment.checkTermsAndConditions();

            if(false === checkTermsAndConditions) {
                PayPalPayment.currentError = PayPalI18n.READ_AND_CONFIRM_TERMS;
                PayPalPayment.removeSubmitButtonOverlay();
                return;
            }

            // Combined call - creates both shop order AND PayPal order in one request
            let result = await PayPalPayment.backendRequest('createOrdersForPayPalUrl', {}, {
                'deliveryAddressId': PayPalPayment.getConfigValue('deliveryAddressId'),
                'paymentId': PayPalPayment.getConfigValue('paymentId'),
                'vaultPayment': PayPalPayment.currentOrder.vaultPayment,
                'useVaultedPayment': PayPalPayment.config.vaultedPaymentSource,
                'trackingId': PayPalPayment.getConfigValue('trackingId')
            });

            if (result.status !== 'success') {
                PayPalPayment.removeSubmitButtonOverlay();
                throw new Error('Order creation failed: ' + (result.message || 'Unknown error'));
            }

            // Dispatch events for both orders
            document.dispatchEvent(new CustomEvent('shopOrderCreated', {
                detail: { shopOrderId: result.shopOrderId }
            }));

            document.dispatchEvent(new CustomEvent('payPalOrderCreated', {
                detail: { ...result.payPalOrder }
            }));

            // Handle vaulted payment - redirect if already completed
            if (null !== PayPalPayment.config.vaultedPaymentSource &&
                result.payPalOrder.status === 'COMPLETED') {
                PayPalPayment.thankYouPageRedirect();
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

            PayPalPayment.captureInProgress = false;

            if (result.paymentStatus === 'success') {
                PayPalPayment.thankYouPageRedirect(result.redirectUrl);
            } else {
                // Clean up state so a retry does not attempt to cancel
                // the old, already-handled order via setShopOrderData.
                PayPalPayment.resetCurrentOrder();
            }
        };

        // PayPal-specific button settings
        this.getPayButtonSettings = function () {
            const buttonSettings = {
                // Validate the terms-and-conditions up front, before the SDK opens
                // the popup. The SDK opens the popup synchronously on click and only
                // then calls createOrder; rejecting here keeps the popup from opening
                // at all. Without this, an unconfirmed AGB made createOrder resolve to
                // undefined, which trips the SDK's strict order-id check
                // ("Expected an order id to be passed") and flashes the popup shut.
                // Mirrors the Apple Pay / Google Pay pre-check; the createOrder guard
                // below is kept as a safety net.
                onClick: function (data, actions) {
                    if (false === PayPalPayment.checkTermsAndConditions()) {
                        PayPalPayment.showErrorMessage(PayPalI18n.READ_AND_CONFIRM_TERMS);
                        return actions.reject();
                    }
                    return actions.resolve();
                },
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
