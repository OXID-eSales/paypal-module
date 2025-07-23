(function () {
    let GooglePayPaymentController = function (config) {
        // Inherit from base controller`
        PayPalPaymentControllerBase.call(this, config);

        this.paymentsClient = null;
        this.baseRequest = {
            apiVersion: 2,
            apiVersionMinor: 0,
        };

        /**
         *  Configure support for the Google Pay API
         */
        this.getGooglePaymentDataRequest = async function () {
            const paymentDataRequest = Object.assign({}, PayPalPayment.baseRequest);
            const {allowedPaymentMethods, merchantInfo} = await this.getGooglePayConfig();

            paymentDataRequest.transactionInfo = PayPalPayment.getGoogleTransactionInfo();
            paymentDataRequest.allowedPaymentMethods = allowedPaymentMethods;
            paymentDataRequest.merchantInfo = merchantInfo;
            paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION"];
            paymentDataRequest.emailRequired = true;
            paymentDataRequest.shippingAddressRequired = PayPalPayment.getConfigValue('useGooglePayAddress');
            paymentDataRequest.shippingAddressParameters = {'phoneNumberRequired': true};

            return paymentDataRequest;
        };

        this.getGoogleTransactionInfo = function () {
            return {
                currencyCode: PayPalPayment.getConfigValue('currency'),
                totalPriceStatus: "FINAL",
                totalPrice: PayPalPayment.getConfigValue('totalPrice'),
                totalPriceLabel: "Total",
            };
        };

        this.getGooglePaymentsClient = function () {
            if (PayPalPayment.paymentsClient === null) {
                PayPalPayment.paymentsClient = new google.payments.api.PaymentsClient({
                    environment: PayPalPayment.getConfigValue('isSandbox') ? "TEST" : "PRODUCTION",
                    paymentDataCallbacks: {
                        onPaymentAuthorized: PayPalPayment.onPaymentAuthorized,
                    },
                });
            }
            return PayPalPayment.paymentsClient;
        };

        this.onGooglePaymentButtonClicked = async function () {
            PayPalPayment.removeErrorMessage();
            let response = await fetch(PayPalPayment.getConfigValue('shopOrderCreateUrl'), {
                method: 'post',
                headers: Object.assign({
                    'content-type': 'application/json',
                }, {}),
                body: JSON.stringify({
                    'deliveryAddressId': PayPalPayment.getConfigValue('deliveryAddressId')
                })
            });

            const result = await response.json();
            if (result.status === 'success') {
                document.dispatchEvent(new CustomEvent('shopOrderCreated', new Object({detail: {...result}})));
            }

            const paymentsClient = PayPalPayment.getGooglePaymentsClient();
            const paymentDataRequest = await PayPalPayment.getGooglePaymentDataRequest();
            paymentDataRequest.transactionInfo = PayPalPayment.getGoogleTransactionInfo();
            if ('function' === typeof paymentsClient.loadPaymentData) {
                try {
                    await paymentsClient.loadPaymentData(paymentDataRequest);
                } catch (err) {
                    // user cancels code
                    if (err.code === 20 ) {
                        PayPalPayment.showErrorMessage(PayPalI18n.OSC_PAYPAL_ORDER_CANCELED);
                        await PayPalPayment.cancelOrder();
                    }
                }
            }
        };

        /**
         *  Fetch Default Config from PayPal via PayPal SDK
         *  */
        this.getGooglePayConfig = async function () {
            if (this.config.allowedPaymentMethods == null || this.config.merchantInfo == null) {
                const googlePayConfig = await paypal.Googlepay().config();
                this.config.allowedPaymentMethods = googlePayConfig.allowedPaymentMethods;
                this.config.merchantInfo = googlePayConfig.merchantInfo;
                this.config.merchantInfo.merchantName = this.config.merchantName;
            }
            return {
                allowedPaymentMethods: this.config.allowedPaymentMethods,
                merchantInfo: this.config.merchantInfo
            };
        };

        this.onPaymentAuthorized = function (paymentData) {
            return new Promise(function (resolve) {
                PayPalPayment.processPayment(paymentData)
                    .then(function () {
                        resolve({transactionState: "SUCCESS"});
                    })
                    .catch(function () {
                        resolve({transactionState: "ERROR"});
                    });
            });
        };

        this.getGooglePaymentsClient = function() {
            if (this.paymentsClient === null) {
                this.paymentsClient = new google.payments.api.PaymentsClient({
                    environment: PayPalPayment.getConfigValue('isSandbox') ? "TEST" : "PRODUCTION",
                    paymentDataCallbacks: {
                        onPaymentAuthorized: this.onPaymentAuthorized.bind(this),
                    },
                });
            }
            return this.paymentsClient;
        };

        this.getGoogleIsReadyToPayRequest = function (allowedPaymentMethods) {
            return Object.assign({}, this.baseRequest, {
                allowedPaymentMethods: allowedPaymentMethods
            });
        };

        this.onShopOrderCreated = function (data) {
            PayPalPayment.setShopOrderData(data.detail, 'shop');
        };

        this.onInit = async function (e) {
            let this_ = e.detail;
            await window.googlePayReady;

            const paymentsClient = PayPalPayment.getGooglePaymentsClient();
            const {allowedPaymentMethods} = await PayPalPayment.getGooglePayConfig();
            paymentsClient
                .isReadyToPay(PayPalPayment.getGoogleIsReadyToPayRequest(allowedPaymentMethods))
                .then(function (response) {
                    if (response.result) {
                        const loadingContainerSelector = PayPalPayment.getConfigValue('loadingContainer');
                        const loading = document.querySelector('.' + loadingContainerSelector);
                        if (loading){
                            loading.style.display = 'none';
                        }

                        PayPalPayment.renderButton();
                    }
                })
                .catch(function (err) {
                    console.error(err);
                });
        };

        this.renderButton = function (e) {
            const paymentsClient = PayPalPayment.getGooglePaymentsClient();
            const button = paymentsClient.createButton({
                buttonType: 'buy',
                buttonLocale: this.language,
                onClick: PayPalPayment.onGooglePaymentButtonClicked,
            });
            document.getElementById("oscpaypal_googlepay").appendChild(button);
        };

        this.processPayment = async function (paymentDataAttr) {
            try {
                const createOrderUrl = PayPalPayment.getConfigValue('googlePayOrderCreateUrl');
                const paymentData = {
                    ...paymentDataAttr,
                    shopOrderId: PayPalPayment.currentOrder.shop.shopOrderId
                };

                const {id: orderId, status} = await fetch(createOrderUrl, {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify(paymentData),
                }).then((res) => res.json());

                if (status === "CREATED") {
                    /* Capture the Order */
                    PayPalPayment.confirmOrder(orderId, paymentData);
                    return {transactionState: "SUCCESS"};
                } else if (status === "APPROVED") {
                    /* Capture the Order */
                    PayPalPayment.captureOrder(orderId);
                    return {transactionState: "SUCCESS"};
                } else {
                    PayPalPayment.showErrorMessage(PayPalI18n.OSC_PAYPAL_AUTHORIZATION_DENIED_ERROR);
                    PayPalPayment.handleError();
                    return {transactionState: "ERROR"};
                }
            } catch (err) {
                return {
                    transactionState: "ERROR",
                    error: {
                        message: err.message,
                    },
                };
            }
        };
        this.confirmOrder= async function (orderId, paymentData) {
            await new Promise(resolve => setTimeout(resolve, 1000));
            confirmOrderResponse = await paypal.Googlepay().confirmOrder({
                orderId: orderId,
                paymentMethodData: paymentData.paymentMethodData
            }).catch(function (PayPalGooglePayError) {
                PayPalPayment.handleError(PayPalGooglePayError);
                return;
            });

            if (confirmOrderResponse.status === "PAYER_ACTION_REQUIRED" || confirmOrderResponse.status === 'APPROVED') {
                PayPalPayment.googlePayUserActionRequired(orderId);
            } else {
                PayPalPayment.handleError();
            }
        };

        this.googlePayUserActionRequired = function (orderId) {
            paypal
                .Googlepay()
                .initiatePayerAction({ orderId: orderId })
                .then(async () => {
                    await PayPalPayment.executeOxidOrder(orderId);
                    await PayPalPayment.captureOrder(orderId);
                });
        };

        this.executeOxidOrder = async function (orderId) {
            const url = PayPalPayment.getConfigValue('executeGooglePayOrder');
            createData = new FormData();
            createData.append('orderID', orderId);

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    body: createData
                });
                const data = await res.json();

                if (data.status === "ERROR") {
                    location.reload();
                }

                return data;
            } catch (error) {
                PayPalPayment.handleError();
                throw error; // Forward the error so that the calling method can react
            }
        };

        this.captureOrder = async function (orderId) {
            const url = PayPalPayment.getConfigValue('captureGooglePayOrder');
            captureData = new FormData();
            captureData.append('orderID', orderId);
            await fetch(url, {
                method: 'post',
                body: captureData
            }).then(function (res) {
                return res.json();
            }).then(function (data) {
                if ('undefined' !== typeof data.token) {
                    window.location = PayPalPayment.getConfigValue('finalizeGooglePayOrder') + '&token=' + data.token;
                    return;
                }

                if (data.status === "ERROR") {
                    PayPalPayment.handleError();
                }
            }).catch(reason => {
                PayPalPayment.handleError();
            });
        };

        document.addEventListener('PayPalPaymentControllerInitialized', this.onInit);
        document.addEventListener('shopOrderCreated', this.onShopOrderCreated);

        return this.init();
    };

    window.addEventListener('load', function() {
        if (typeof PayPalPaymentControllerConfig === 'object' &&
            PayPalPaymentControllerConfig.paymentId === 'oscpaypal_googlepay') {
            window.PayPalPayment = new GooglePayPaymentController();
        }
    });
})();
