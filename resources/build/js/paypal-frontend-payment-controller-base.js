(function () {
    let PayPalPaymentControllerBase = function (config) {
        this.config = Object.assign(PayPalPaymentControllerConfig, typeof config === 'object' ? config : {});

        this.currentOrderDefaults = {
            shop: null,
            paypal: null,
            vaultPayment: false
        };

        this.currentOrder = null;

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

        // Common initialization methods
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

        this.getCurrentOrderOxid = function () {
            return this.getCurrentOrderData('shopOrderId', 'shop');
        };

        this.getCurrentPayPalOrderId = function () {
            return this.getCurrentOrderData('orderID', 'paypal');
        };

        this.getConfigValue = function (name) {
            return undefined !== this.config[name] ? this.config[name] : null;
        };

        // Common event handlers
        this.onShopOrderCreated = function (data) {
            PayPalPayment.setShopOrderData(data.detail, 'shop');
        };

        this.onPayPalOrderCreated = function (data) {
            PayPalPayment.setCreatePayPalOrderResponse(data.detail);
        };

        this.vaultingSettingSwitch = function (e) {
            PayPalPayment.currentOrder.vaultPayment = e.currentTarget.checked;
        };

        // Common order processing methods
        this.patchOrder = async function (details) {
            return await PayPalPayment.backendRequest('shopOrderPatchingStatusUrl', {}, {
                'shopOrderId': PayPalPayment.getCurrentOrderOxid(),
                'payPalOrderId': PayPalPayment.getCurrentPayPalOrderId()
            });
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

        this.afterCaptureOrder = async function (details) {
            const {paypalOrderDetails} = await PayPalPayment.patchOrder(details);

            if (paypalOrderDetails && paypalOrderDetails.payment_source) {
                await PayPalPayment.vaultPayment(paypalOrderDetails);
            }

            window.location = PayPalPayment.getConfigValue('shopThankYouPageUrl');
        };

        this.handlePaymentAuthorization = async function (details) {
            PayPalPayment.setCreatePayPalOrderResponse(details);
            const {paypalOrderDetails} = await PayPalPayment.patchOrder(details);

            if (paypalOrderDetails && paypalOrderDetails.payment_source) {
                await PayPalPayment.vaultPayment(paypalOrderDetails);
            }

            window.location = PayPalPayment.getConfigValue('shopThankYouPageUrl');
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

        // Common backend request method
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

        // Common initialization
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

        return this;
    };

    // Make the base class available
    window.PayPalPaymentControllerBase = PayPalPaymentControllerBase;
})();