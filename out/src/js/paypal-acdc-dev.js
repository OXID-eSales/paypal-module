(function () {

    let PayPalPaymentController = function (config) {

        this.config = Object.assign(PayPalPaymentControllerConfig, typeof config === 'object' ? config : {});

        this.currentOrderDefaults = {
            shop: null,
            paypal: null,
            vaultPayment: false
        };

        this.currentOrder = null;

        this.resetCurrentOrder = function () {
            this.currentOrder = {...this.currentOrderDefaults};
        };

        this.setCreatePayPalOrderResponse = function (response) {
            this.currentOrder.paypal = response;
        };

        this.setShopOrderData = async function (response, orderType) {
            if (null !== this.currentOrder.shop) {
                await PayPalPayment.deleteOrder().then(function (data) {
                    PayPalPayment.resetCurrentOrder();
                });
            }

            PayPalPayment.currentOrder[orderType] = response;
            PayPalPayment.config.purchaseUnits.custom_id = response.customId;
        };

        this.getCurrentOrderData = function (name, orderType) {
            if (null == this.currentOrder) {
                console.error('No current order.');
                return;
            }

            if (undefined === this.currentOrder[orderType][name]) {
                console.error('Current order do not have detail named ' + name + '.');
                return;
            }

            return this.currentOrder[orderType][name];
        };

        this.getPaymentSource = function () {
            return {
                "card": {
                    "attributes": {
                        "vault": {
                            "store_in_vault": "ON_SUCCESS",
                            "usage_type": "MERCHANT",
                            "customer_type": "CONSUMER",
                            "permit_multiple_payment_tokens": true
                        }
                    },
                }
            };
        };

        this.getPurchaseUnits = function () {
            let purchaseUnits = {
                intent: PayPalPayment.getConfigValue('captureStrategy'),
                purchase_units: [
                    {...this.config.purchaseUnits}
                ],
                application_context: {
                    return_url: PayPalPayment.getConfigValue('updateOxUserWithPayPalCustomerIdUrl'),
                    cancel_url: PayPalPayment.getConfigValue('shopOrderCancelStatusUrl')
                }
            };

            if (PayPalPayment.currentOrder.vaultPayment) {
                purchaseUnits.payment_source = this.getPaymentSource();
            }

            return purchaseUnits;
        };

        this.getCurrentOrderOxid = function () {
            return this.getCurrentOrderData('shopOrderId', 'shop');
        };

        this.getCurrentPayPalOrderId = function () {
            return this.getCurrentOrderData('orderID', 'paypal');
        };

        this.getConfigValue = function (name) {
            return undefined !== this.config[name] ? this.config[name] : null;
        };

        this.onShopOrderCreated = function (data) {
            PayPalPayment.setShopOrderData(data.detail, 'shop');
        };

        this.onPayPalOrderCreated = function (data) {
            PayPalPayment.setCreatePayPalOrderResponse(data.detail);
        };

        this.vaultingSettingSwitch = function (e) {
            PayPalPayment.currentOrder.vaultPayment = e.currentTarget.checked;
        };

        this.init = function () {
            this.resetCurrentOrder();

            window.onload = function (e) {
                const savePaymentChackbox = document.getElementById('oscPayPalVaultPaymentCheckbox');
                if (savePaymentChackbox) {
                savePaymentChackbox.onclick = PayPalPayment.vaultingSettingSwitch;
                }
            };

            document.addEventListener('shopOrderCreated', this.onShopOrderCreated);
            document.addEventListener('payPalOrderCreated', this.onPayPalOrderCreated);

            return this;
        };


        this.createOrder = async function (data, actions) {
            let result = await PayPalPayment.backendRequest('shopOrderCreationStatusUrl', {}, {
                'deliveryAddressId': PayPalPayment.getConfigValue('deliveryAddressId')
            });

            document.dispatchEvent(new CustomEvent('shopOrderCreated', new Object({detail: {...result}})));

            return actions.order.create(PayPalPayment.getPurchaseUnits());
        };

        this.vaultPayment = async function (details) {
            try {
                if (details.payment_source.paypal) {
                    const vaultToken = details.payment_source.paypal.attributes.vault.id;

                    if (!vaultToken) {
                        console.warn('No PayPal vault token found in order details');
                        return;
                    }

                    const result = await PayPalPayment.backendRequest('updateOxUserWithPayPalCustomerIdUrl', {}, {
                        'payPalCustomerId': details.payment_source.paypal.attributes.vault.customer.id,
                    });

                    if (result.status !== 'success') {
                        console.error('Failed to store PayPal vault token:', result.message);
                    }
                } else if (details.payment_source.card) {
                    const cardToken = details.payment_source.card.attributes.vault.id;
                    if (!cardToken) {
                        console.warn('No card vault token found in order details');
                        return;
                    }

                    const result = await PayPalPayment.backendRequest('updateOxUserWithCardTokenUrl', {}, {
                        'cardToken': cardToken,
                        'cardDetails': details.payment_source.card
                    });

                    if (result.status !== 'success') {
                        console.error('Failed to store card vault token:', result.message);
                    }
                }
            } catch (error) {
                console.error('Error processing vault token:', error);
            }
        };

        this.patchOrder = async function (details) {
            return await PayPalPayment.backendRequest('shopOrderPatchingStatusUrl', {}, {
                'shopOrderId': PayPalPayment.getCurrentOrderOxid(),
                'payPalOrderId': PayPalPayment.getCurrentPayPalOrderId()
            });
        };
        this.afterCaptureOrder = async function (details) {
            const {paypalOrderDetails} = await PayPalPayment.patchOrder(details);

            if (paypalOrderDetails && paypalOrderDetails.payment_source) {
                await PayPalPayment.vaultPayment(paypalOrderDetails);
            }

            window.location = PayPalPayment.getConfigValue('shopThankYouPageUrl');
        };

        this.handlePaymentAuthorization = async function (details) {
            debugger
            PayPalPayment.setCreatePayPalOrderResponse(details);
            const {paypalOrderDetails} = await PayPalPayment.patchOrder(details);

            if (paypalOrderDetails && paypalOrderDetails.payment_source) {
                await PayPalPayment.vaultPayment(paypalOrderDetails);
            }

            window.location = PayPalPayment.getConfigValue('shopThankYouPageUrl');
        };

        this.captureOrder = async function (data, actions) {
            PayPalPayment.setCreatePayPalOrderResponse(data);
            return actions.order.capture().then(await PayPalPayment.afterCaptureOrder);
        };

        this.deleteOrder = async function () {
            await PayPalPayment.backendRequest('shopOrderDeleteUrl', {}, {
                'shopOrderId': PayPalPayment.getCurrentOrderOxid()
            });

            PayPalPayment.resetCurrentOrder();
        };

        this.handleError = async function (data) {
            await PayPalPayment.backendRequest('shopOrderErrorUrl', {}, {
                'shopOrderId': PayPalPayment.getCurrentOrderOxid()
            });
            await PayPalPayment.deleteOrder().then(function (response) {
                if (response.status === 'success') {
                    PayPalPayment.resetCurrentOrder();
                }
            });

            window.location = PayPalPayment.getConfigValue('shopOrderErrorUrl');
        };

        this.renderButton = function (style) {
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
        };
        this.getPayButtonSettings = function () {
            const buttonSettings = {
                createOrder: PayPalPayment.createOrder,
                onApprove: PayPalPayment.handlePaymentAuthorization,
                onCancel: PayPalPayment.deleteOrder,
                onError: PayPalPayment.handleError
            };

            if (PayPalPayment.config.captureStrategy === 'CAPTURE') {
                buttonSettings.onApprove = PayPalPayment.captureOrder;
            }

            return buttonSettings;
        };

        this.backendRequest = async function (urlSlug, headers, body) {
            let response = await fetch(PayPalPayment.getConfigValue(urlSlug), {
                method: 'post',
                headers: Object.assign({
                    'content-type': 'application/json',
                }, headers),
                body: JSON.stringify(body)
            });

            const result = await response.json();

            if (result.status !== 'success') {
                PayPalPayment.handleError();
            }

            return result;
        };

        return this.init();
    };

    this.createACDCOrder = async function (data, actions) {
        let result = await PayPalPayment.backendRequest('shopACDCOrderCreationStatusUrl', {}, {
            'deliveryAddressId': PayPalPayment.getConfigValue('deliveryAddressId')
        });

        document.dispatchEvent(new CustomEvent('shopOrderCreated', new Object({detail: {...result.shopOrder}})));
        document.dispatchEvent(new CustomEvent('payPalOrderCreated', new Object({detail: {...result.payPalOrder}})));

        return result.payPalOrder.id
    }

    this.captureACDCOrder = async function (data, actions) {
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
            createOrder: PayPalPayment.createACDCOrder,
            onApprove: PayPalPayment.captureACDCOrder,
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
    };

    window.addEventListener('load', function() {
        if (typeof PayPalPaymentControllerConfig === 'object') {
            if(PayPalPaymentControllerConfig.paymentId !== 'oscpaypal_acdc'){
                return;
            }

            window.PayPalPayment = new PayPalPaymentController();
            window.PayPalPayment.renderCardFields();
        }
    });
})();