(function () {

    let PayPalPaymentController = function (config) {

        this.config = config

        this.currentOrderDefaults = {
            shop: null,
            paypal: null
        };

        this.currentOrder = null;

        this.reportError = function (message) {
            console.log(message);
            debugger
        }

        this.resetCurrentOrder = function (response) {
            this.currentOrder = {...this.currentOrderDefaults};
        }

        this.setCreatePayPalOrderResponse = function (response) {
            if (null !== this.currentOrder.paypal) {
                //some order is currently ...
                //@TODO check if this part is needded. Its the case that pay button is clicked twice (maybe its impossibble)
                debugger
            }

            this.currentOrder.paypal = response;
        }

        //@TODO this name is not accurate, change it
        this.setShopOrderData = function (response, orderType) {
            if (response.status === 'success') {
                if (null !== this.currentOrder.shop) {
                    //some order is currently
                    debugger
                }

                this.currentOrder[orderType] = response
                this.config.purchaseUnits.custom_id = response.shopOrderNumber;
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

        this.getPurchaseUnits = function () {
            return {
                purchase_units: [
                    {...this.config.purchaseUnits}
                ]
            }
        }

        this.getCurrentOrderOxid = function () {
            return this.getCurrentOrderData('shopOrderId', 'shop');
        }

        this.getCurrentOrderNumber = function () {
            return this.getCurrentOrderData('shopOrderNumber', 'shop');
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

        this.init = function () {
            this.resetCurrentOrder();

            document.addEventListener('shopOrderCreated', this.onShopOrderCreated);

            return this
        }

        this.createOrder = async function (data, actions) {
            let result = await PayPalPayment.backendRequest('shopOrderCreationStatusUrl', {}, {
                'deladrid': PayPalPayment.getConfigValue('deladrid')
            });

            document.dispatchEvent(new CustomEvent('shopOrderCreated', new Object({detail: {...result}})));

            return actions.order.create(PayPalPayment.getPurchaseUnits());
        }

        this.patchOrder = async function (details) {
            let result = await PayPalPayment.backendRequest('shopOrderPatchingStatusUrl', {}, {
                'shopOrderId': PayPalPayment.getCurrentOrderOxid(),
                'payPalOrderId': PayPalPayment.getCurrentPayPalOrderId()
            });

            if (result.status === 'success') {
                window.location = PayPalPayment.getConfigValue('shopThankYouPageUrl');
            }
        }

        this.captureOrder = async function (data, actions) {
            PayPalPayment.setCreatePayPalOrderResponse(data);

            return actions.order.capture().then(PayPalPayment.patchOrder);
        }

        this.cancelOrder = async function (data, actions) {
            await PayPalPayment.backendRequest('shopOrderCancelStatusUrl', {}, {
                'shopOrderId': PayPalPayment.getCurrentOrderOxid()
            });

            PayPalPayment.resetCurrentOrder();
        }

        this.handleError = function () {
            window.location = PayPalPayment.getConfigValue('shopOrderErrorUrl');
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

    window.addEventListener('PayPalSDKLoadedEvent', (event) => {

        let button = paypal.Buttons({
            createOrder: PayPalPayment.createOrder,
            onApprove: PayPalPayment.captureOrder,
            onCancel: PayPalPayment.cancelOrder,
            onError: PayPalPayment.handleError
        })

        if (button.isEligible()) {
            button.render(PayPalPayment.getConfigValue('buttonSelector'));
        }
    });

})()