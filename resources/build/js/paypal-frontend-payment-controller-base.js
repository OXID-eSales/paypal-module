(function () {
    let PayPalPaymentControllerBase = function (config) {
        this.config = Object.assign(PayPalPaymentControllerConfig, typeof config === 'object' ? config : {});

        this.currentOrderDefaults = {
            shop: null,
            paypal: null,
            vaultPayment: false
        };
        this.currentOrder = null;
        this.currentError = null;
        this.reactOnPayPalOverlayClosed = false;

        this.getPaymentData = function () {
            let purchaseUnits = {
                intent: PayPalPayment.getConfigValue('captureStrategy'),
                purchase_units: [
                    {...this.config.purchaseUnits}
                ]
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
            // check if part: this.getCurrentOrderData('orderID', 'paypal') is needed, maybe in ACDC
            const payPalOrderId =
                this.getCurrentOrderData('orderID', 'paypal') || this.getCurrentOrderData('id', 'paypal');

            return payPalOrderId || null;
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
            const shopOrderId = PayPalPayment.getCurrentOrderOxid();
            const payPalOrderId = PayPalPayment.getCurrentPayPalOrderId();

            if (!shopOrderId) {
                console.error('Missing shop order ID for order patching');
                return { error: 'Missing shop order ID' };
            }

            if (!payPalOrderId) {
                console.error('Missing PayPal order ID for order patching');
                return { error: 'Missing PayPal order ID' };
            }

            if (!PayPalPayment.currentOrder || typeof PayPalPayment.currentOrder.vaultPayment === 'undefined') {
                console.error('Missing vault payment information for order patching');
                return { error: 'Missing vault payment information' };
            }

            return await PayPalPayment.backendRequest('shopOrderPatchingUrl', {}, {
                'shopOrderId': shopOrderId,
                'payPalOrderId': payPalOrderId,
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
            const result = await PayPalPayment.patchOrder(details);

            if (result.error) {
                console.error('Failed to patch order:', result.error);
                PayPalPayment.handleError();
                return;
            }

            const {paypalOrderDetails} = result;

            if (paypalOrderDetails && paypalOrderDetails.payment_source) {
                await PayPalPayment.vaultPayment(paypalOrderDetails);
            }

            window.location = PayPalPayment.getConfigValue('shopThankYouPageUrl');
        };

        this.handlePaymentAuthorization = async function (details) {
            PayPalPayment.setCreatePayPalOrderResponse(details);
            const patchResult = await PayPalPayment.patchOrder(details);

            if (patchResult.error) {
                console.error('Failed to patch order:', patchResult.error);
                PayPalPayment.handleError();
                return;
            }

            const {paypalOrderDetails} = patchResult;

            if (PayPalPayment.currentOrder.vaultPayment && paypalOrderDetails && paypalOrderDetails.payment_source) {
                await PayPalPayment.vaultPayment(paypalOrderDetails);
            }

            const result = await PayPalPayment.authorizeOrder(paypalOrderDetails);

            if (result.paymentStatus === 'success' ){
                window.location = PayPalPayment.getConfigValue('shopThankYouPageUrl');
                return;
            }

            PayPalPayment.handleError();
        };

        this.authorizeOrder = async function (data) {
            const orderId = data.id || PayPalPayment.getCurrentPayPalOrderId();
            const shopOrderId = PayPalPayment.getCurrentOrderOxid();
            const paymentId = PayPalPayment.getConfigValue('paymentId');

            if (!orderId) {
                console.error('No PayPal order ID available for authorization');
                return;
            }

            try {
                const result = await PayPalPayment.backendRequest('shopOrderAuthorizeUrl', {}, {
                    'orderId': orderId,
                    'shopOrderId': shopOrderId,
                    'paymentId': paymentId
                });

                if (result.status !== 'success') {
                    console.error('Order authorization failed:', result.message);
                    PayPalPayment.handleError();
                }

                return result;
            } catch (error) {
                console.error('Error during order authorization:', error);
                PayPalPayment.handleError();
            }

            return {status: 'error'};
        };

        this.cancelOrder = async function () {
            // Don't remove the overlay, as we'll reload the page at the end.
            // During this time, no one should be able to click anything.
            // PayPalPayment.removeSubmitButtonOverlay();

            let shopOrderId = PayPalPayment.getCurrentOrderOxid();
            if (null == shopOrderId) {
                return;
            }

            await PayPalPayment.backendRequest('shopOrderCancelUrl', {}, {
                'shopOrderId': PayPalPayment.getCurrentOrderOxid()
            });

            PayPalPayment.resetCurrentOrder();
            window.location.reload();
        };

        this.handleError = async function (data) {
            if ('undefined' !== data && data instanceof Error){
                PayPalPayment.showErrorMessage(
                    PayPalPayment.currentError ?
                        PayPalPayment.currentError : PayPalI18n.OSC_PAYPAL_AUTHORIZATION_DENIED_ERROR
                );
            }

            let shopOrderId = PayPalPayment.getCurrentOrderOxid();
            if (null == shopOrderId) {
                return;
            }

            await PayPalPayment.cancelOrder();
        };

        // Common backend request method
        this.backendRequest = async function (urlSlug, headers, body) {
            let response;
            let result = {status: 'pending'};

            try {
                body.trackingId = PayPalPayment.getConfigValue('trackingId');
                response = await fetch(PayPalPayment.getConfigValue(urlSlug), {
                    method: 'post',
                    headers: Object.assign({
                        'content-type': 'application/json',
                    }, headers),
                    body: JSON.stringify(body)
                });

                result = await response.json();
            } catch (error) {
                console.error('Operation failed:', error);
                result = {
                    status: 'error',
                    error: error.message
                };
                // Ensure order cancellation if operation fails
                await PayPalPayment.handleError();
                return result;
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
            const config = {childList: true, subtree: true};

            // Create an observer instance
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    let addedNodes = mutation.addedNodes;

                    addedNodes.forEach(function (node) {
                        if (node.nodeType === Node.ELEMENT_NODE) {

                            if (node.id.startsWith('paypal-overlay-uid_')) {
                                const overlayObserver = new MutationObserver(function (ovMutations, ovObserver) {
                                    ovMutations.forEach(function (ovMutation) {
                                        ovMutation.removedNodes.forEach(function (removedNode) {
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
                                    overlayObserver.observe(node.parentNode, {childList: true});
                                }
                            }
                        }
                    });
                });
            });
            observer.observe(document.body, config);

            PayPalPayment.reactOnPayPalOverlayClosed = true;

            return observer;
        };

        this.initializeAcceptPaymentButton = function () {
            const submitButton = document.querySelector(PayPalPayment.config.buttonSelector);
            submitButton.addEventListener('click', function (e) {
                e.stopPropagation();
                e.preventDefault();
                PayPalPayment.addSubmitButtonOverlay();

                if (PayPalPayment.config.vaultedPaymentSource) {
                    PayPalPayment.createOrder();
                }
            });
        };

        this.addSubmitButtonOverlay = function() {
            const overlay = document.getElementById('paypal-overlay');
            if (overlay) {
                overlay.style.display = 'block';
            } else {
                console.warn('PayPal overlay element not found');
            }
        };

        this.removeSubmitButtonOverlay = function() {
            const overlay = document.getElementById('paypal-overlay');
            if (overlay) {
                overlay.style.display = 'none';
            } else {
                console.warn('PayPal overlay element not found');
            }
        };

        this.showErrorMessage = function (message, className) {
            if(message.length === 0) {
                message = PayPalI18n.OSC_PAYPAL_UNKNOWN_ERROR;
            }

            className = className || '';
            const panelBody = document.querySelector("#orderPayment").querySelector(".panel-body");

            // Remove existing error if present
            PayPalPayment.removeErrorMessage(className);
            PayPalPayment.currentError = message;
            // Create and display a new error message
            const errorMessage = document.createElement("div");
            errorMessage.className = "error-message alert alert-danger " + className;
            errorMessage.textContent = message;

            panelBody.prepend(errorMessage);

            errorMessage.scrollIntoView({
                behavior: 'smooth'
            });
        };

        this.removeErrorMessage = function (className) {
            className = className || '';
            const panelBody = document.querySelector("#orderPayment").querySelector(".panel-body");
            if (panelBody) {
                const existingError = panelBody.querySelector(".error-message" + (className ? '.' + className : ''));
                if (existingError) {
                    existingError.remove();
                }
            }
            PayPalPayment.currentError = null;
        };

        this.checkTermsAndConditions = function () {
            var checksOk = true;

            if (PayPalPayment.config.confirmAGBRequired) {
                const checkAgbTop = document.getElementById('checkAgbTop');
                checksOk = !!(checkAgbTop && checkAgbTop.checked);
            }

            if (PayPalPayment.config.confirmAGBForIntangibleRequired) {
                const oxdownloadableproductsagreement = document.getElementById('oxdownloadableproductsagreement');
                checksOk = !!(oxdownloadableproductsagreement && oxdownloadableproductsagreement.checked);
            }

            return checksOk;
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
