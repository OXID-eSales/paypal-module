(function () {

    let PayPalPaymentController = function (config) {

        this.config = config

        this.currentOrderDefaults = {
            shop: null,
            paypal: null,
            vaultPayment: false
        };

        this.currentOrder = null;

        this.resetCurrentOrder = function (response) {
            this.currentOrder = {...this.currentOrderDefaults};
        }

        this.setCreatePayPalOrderResponse = function (response) {
            debugger
            if (null !== this.currentOrder.paypal) {
                //some order is currently ...
                //@TODO check if this part is needed. Its the case that pay button is clicked twice (maybe its impossible)
                debugger
            }

            this.currentOrder.paypal = response;
        }

        this.setShopOrderData = function (response, orderType) {
            if (response.status === 'success') {
                if (null !== this.currentOrder.shop) {
                    //Todo Probably the current order will not be finished and it need to be erased from backend
                    this.resetCurrentOrder();
                    //TODO maybe console.log here if sandbox mode on ?
                }

                this.currentOrder[orderType] = response;
                this.config.purchaseUnits.custom_id = response.customId;
            }
        }

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
        }

        this.getPaymentSource = function () {
            return {
                "paypal": {
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
        }

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

            if(PayPalPayment.currentOrder.vaultPayment){
                purchaseUnits.payment_source = this.getPaymentSource();
            }

            return purchaseUnits;
        }

        this.getCurrentOrderOxid = function () {
            return this.getCurrentOrderData('shopOrderId', 'shop');
        }

        this.getCurrentPayPalOrderId = function () {
            return this.getCurrentOrderData('orderID', 'paypal');
        }

        this.getConfigValue = function (name) {
            return undefined !== this.config[name] ? this.config[name] : null;
        }

        this.onShopOrderCreated = function (data) {
            PayPalPayment.setShopOrderData(data.detail, 'shop');
        }

        this.vaultingSettingSwitch = function (e) {
            e.stopPropagation()

            PayPalPayment.currentOrder.vaultPayment = e.currentTarget.checked;
        }

        this.init = function () {
            this.resetCurrentOrder();

            window.onload= function (e){
                const savePaymentChackbox = document.getElementById('oscPayPalVaultPaymentCheckbox');
                savePaymentChackbox.onclick = PayPalPayment.vaultingSettingSwitch;
            };

            document.addEventListener('shopOrderCreated', this.onShopOrderCreated);

            return this;
        }

        this.createOrder = async function (data, actions) {
            let result = await PayPalPayment.backendRequest('shopOrderCreationStatusUrl', {}, {
                'deliveryAddressId': PayPalPayment.getConfigValue('deliveryAddressId')
            });

            document.dispatchEvent(new CustomEvent('shopOrderCreated', new Object({detail: {...result}})));

            return actions.order.create(PayPalPayment.getPurchaseUnits());
        }

        this.vaultPayment = async function (details) {
            try {
                const vaultToken = details.payment_source?.paypal?.attributes?.vault?.id;

                if (!vaultToken) {
                    console.warn('No vault token found in order details');
                    return;
                }

                const result = await PayPalPayment.backendRequest('updateOxUserWithPayPalCustomerIdUrl', {}, {
                    'payPalCustomerId': details.payment_source?.paypal?.attributes?.vault?.customer?.id,
                });

                if (result.status !== 'success') {
                    console.error('Failed to store vault token:', result.message);
                }
            } catch (error) {
                console.error('Error processing vault token:', error);
            }
        }

        this.patchOrder = async function (details) {
            return await PayPalPayment.backendRequest('shopOrderPatchingStatusUrl', {}, {
                'shopOrderId': PayPalPayment.getCurrentOrderOxid(),
                'payPalOrderId': PayPalPayment.getCurrentPayPalOrderId()
            });
        }

        this.afterCaptureOrder = async function (details) {
            const {paypalOrderDetails} = await PayPalPayment.patchOrder(details);

            if (paypalOrderDetails?.payment_source) {
                await PayPalPayment.vaultPayment(paypalOrderDetails);
            }

            window.location = PayPalPayment.getConfigValue('shopThankYouPageUrl');
        }

        this.captureOrder = async function (data, actions) {
            PayPalPayment.setCreatePayPalOrderResponse(data);
            return actions.order.capture().then(await PayPalPayment.afterCaptureOrder);
        }

        this.cancelOrder = async function (data, actions) {
            await PayPalPayment.backendRequest('shopOrderCancelStatusUrl', {}, {
                'shopOrderId': PayPalPayment.getCurrentOrderOxid()
            });

            PayPalPayment.resetCurrentOrder();
        }

        this.handleError = function () {
            debugger
            window.location = PayPalPayment.getConfigValue('shopOrderErrorUrl');
        }

        this.handlePaymentAuthorization = function (details) {
            debugger
        }

        this.renderButton = function () {
            let button = paypal.Buttons(PayPalPayment.getPayButtonSettings());

            if (button.isEligible()) {
                button.render(PayPalPayment.getConfigValue('buttonSelector'));
            }
        }

        this.getPayButtonSettings = function () {
            const buttonSettings = {
                displayOnly: ["vaultable"],
                createOrder: PayPalPayment.createOrder,
                onApprove: PayPalPayment.handlePaymentAuthorization,
                onCancel: PayPalPayment.cancelOrder,
                onError: PayPalPayment.handleError
            };

            if (PayPalPayment.config.captureStrategy === 'directly'){
                buttonSettings.onApprove = PayPalPayment.captureOrder;
            }

            return buttonSettings;
        }

        this.backendRequest = async function (url, headers, body) {
            let response = await fetch(PayPalPayment.getConfigValue(url), {
                method: 'post',
                headers: Object.assign({
                    'content-type': 'application/json',
                }, headers),
                body: JSON.stringify(body)
            });

            const result = await response.json();

            if (result.status !== 'success') {
                PayPalPayment.handleError()
            }

            return result;
        }

        return this.init();
    }

    let PayPalPayment = new PayPalPaymentController(new PayPalPaymentControllerConfigurator());

    window.addEventListener('PayPalSDKLoadedEvent', PayPalPayment.renderButton);
})()