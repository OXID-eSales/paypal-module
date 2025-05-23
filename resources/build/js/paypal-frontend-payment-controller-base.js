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
                experience_context: {
                    return_url: PayPalPayment.getConfigValue('updateOxUserWithPayPalCustomerIdUrl'),
                    cancel_url: PayPalPayment.getConfigValue('shopOrderCancelUrl')
                }
            };

            purchaseUnits.payment_source = this.getPaymentSource();

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
                await PayPalPayment.cancelOrder().then(function (data) {
                    PayPalPayment.resetCurrentOrder();
                });
            }

            PayPalPayment.currentOrder[orderType] = response;
            PayPalPayment.config.purchaseUnits.custom_id = response.customId;
        };

        this.getCurrentOrderData = function (name, orderType) {
            if (null == this.currentOrder[orderType]) {
                console.warn('No current order.');
                return null;
            }

            if (undefined === this.currentOrder[orderType][name]) {
                console.warn('Current order do not have detail named ' + name + '.');
                return null;
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
            return await PayPalPayment.backendRequest('shopOrderPatchingUrl', {}, {
                'shopOrderId': PayPalPayment.getCurrentOrderOxid(),
                'payPalOrderId': PayPalPayment.getCurrentPayPalOrderId(),
                'vaultPayment': PayPalPayment.currentOrder.vaultPayment
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

        this.cancelOrder = async function () {
            let shopOrderId = PayPalPayment.getCurrentOrderOxid();
            if (null == shopOrderId){
                return;
            }

            await PayPalPayment.backendRequest('shopOrderCancelUrl', {}, {
                'shopOrderId': PayPalPayment.getCurrentOrderOxid()
            });

            PayPalPayment.resetCurrentOrder();
        };

        this.handleError = async function (data) {
            PayPalPayment.buttonControl('disabled', false);

            let shopOrderId = PayPalPayment.getCurrentOrderOxid();
            if (null == shopOrderId){
                return;
            }

            /*await PayPalPayment.backendRequest('shopOrderErrorUrl', {}, {
                'shopOrderId': PayPalPayment.getCurrentOrderOxid()
            });*/

            await PayPalPayment.cancelOrder();
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

            let result = {status: 'pending'};

            try {
                result = await response.json();
            } catch (e) {
                result = {
                    status: 'error',
                    error: e.message
                };
            }

            console.log(result.status, result.message, result.data);
            await new Promise(resolve => setTimeout(resolve, 5000));
            if (result.status !== 'success') {
                PayPalPayment.handleError();
            }

            return result;
        };

        this.buttonControl = function (property, value) {
            const submitButton = document.querySelector(PayPalPayment.config.buttonSelector);
            if (undefined !== submitButton[property]) {
                submitButton[property] = value;
            }
        };


        this.paypalOverlayWatcher = function () {
            const overlayClosedEvent = new Event('paypalOverlayClosed');

            // Options for the observer (which mutations to observe)
            const config = { childList: true, subtree: true };

            // Create an observer instance
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    let addedNodes = mutation.addedNodes;

                    addedNodes.forEach(function(node) {
                        if (node.nodeType === Node.ELEMENT_NODE) {

                            if (node.id.startsWith('paypal-overlay-uid_')) {
                                const overlayObserver = new MutationObserver(function(ovMutations, ovObserver) {
                                    ovMutations.forEach(function(ovMutation) {
                                        ovMutation.removedNodes.forEach(function(removedNode) {
                                            if (removedNode === node ||
                                                (removedNode.contains && removedNode.contains(node))) {


                                                document.dispatchEvent(overlayClosedEvent);
                                                ovObserver.disconnect();
                                            }
                                        });
                                    });
                                });

                                // Start observing the parent of the iframe for removal
                                if (node.parentNode) {
                                    overlayObserver.observe(node.parentNode, { childList: true });
                                }
                            }
                        }
                    });
                });
            });
            observer.observe(document.body, config);

            return observer;
        };

        // Common initialization
        this.init = function () {
            this.resetCurrentOrder();

            const savePaymentChackbox = document.getElementById('oscPayPalVaultPaymentCheckbox');
            if (savePaymentChackbox) {
                savePaymentChackbox.onclick = this.vaultingSettingSwitch;
            }

            document.addEventListener('shopOrderCreated', this.onShopOrderCreated);
            document.addEventListener('payPalOrderCreated', this.onPayPalOrderCreated);

            document.dispatchEvent(new CustomEvent('PayPalPaymentControllerInitialized', new Object({detail: this})));

            return this;
        };

        return this;
    };

    // Make the base class available
    window.PayPalPaymentControllerBase = PayPalPaymentControllerBase;
})();
