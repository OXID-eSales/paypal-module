(function(){

    let PayPalPaymentController = function(){
        this.currentOrder = {
            shop: null,
            paypal: null
        };

        this.setCreatePayPalOrderResponse = function(response){
            if (null !== this.currentOrder.paypal){
                //some order is currently ...
                debugger
            }

            this.currentOrder.paypal = response;
        }

        //@TODO this name is not accurate, change it
        this.setCreateShopOrderResponse = function(response, orderType){
            if(response.status === 'success'){
                if (null !== this.currentOrder.shop){
                    //some order is currently
                    debugger
                }

                this.currentOrder[orderType] = response
            }
        }

        this.getCurrentOrderData = function (name, orderType){
            if (null == this.currentOrder){
                console.error('No current order.');
                return;
            }

            if (undefined === this.currentOrder[orderType][name]){
                console.error('Current order do not have detail named '+ name +'.');
                return;
            }

            return this.currentOrder[orderType][name];
        }

        this.getCurrentOrderOxid = function (){
            return this.getCurrentOrderData('shopOrderId', 'shop');
        }

        this.getCurrentOrderNumber = function (){
            return this.getCurrentOrderData('shopOrderNumber', 'shop');
        }

        this.getCurrentPayPalOrderId = function (){
            return this.getCurrentOrderData('orderID', 'paypal');
        }

    }

    window.PayPalPaymentController = new PayPalPaymentController();

    window.addEventListener('PayPalSDKLoadedEvent', (event) => {

        button = paypal.Buttons({
            createOrder: async function (data, actions) {
                let shopOrderCreationStatus = await fetch(window.PP_DATA_12321.shopOrderCreationStatusUrl, {
                    method: 'post',
                    headers: {
                        'content-type': 'application/json',
                    },
                    body: JSON.stringify({
                        'deladrid': window.PP_DATA_12321.deladrid
                    })
                });

                let response = await shopOrderCreationStatus.json();
                window.PayPalPaymentController.setCreateShopOrderResponse(response, 'shop');
                window.PP_DATA_12321.purchaseUnits.custom_id = response.shopOrderNumber;
                window.PP_DATA_12321.purchaseUnits.custom_id = window.PayPalPaymentController.getCurrentOrderNumber();

                let purchaseUnits = {
                    purchase_units: [
                        { ...window.PP_DATA_12321.purchaseUnits }
                    ]
                }

                return actions.order.create(purchaseUnits);
            },

            onApprove: async function (data, actions) {
                window.PayPalPaymentController.setCreatePayPalOrderResponse(data);

                return actions.order.capture().then(async function (details) {
                    let shopOrderPatchingStatus = await fetch(window.PP_DATA_12321.shopOrderPatchingStatusUrl, {
                        method: 'post',
                        headers: {
                            'content-type': 'application/json',
                        },
                        body: JSON.stringify({
                            'shopOrderId': window.PayPalPaymentController.getCurrentOrderOxid(),
                            'payPalOrderId': window.PayPalPaymentController.getCurrentPayPalOrderId()
                        })
                    });

                    let response = await shopOrderPatchingStatus.json();
                    if(response.status === 'success'){
                        alert('payment Completed successfully: redirect to thankYou page')
                    }
                });
            },

            onCancel: function (data, actions) {
                debugger
                //fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"}]');
            },

            onError: function (data) {
                debugger
                //fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"}]');
            }
        })
        if (button.isEligible()) {
            button.render(window.PP_DATA_12321.buttonSelector);
        }
    });

})()