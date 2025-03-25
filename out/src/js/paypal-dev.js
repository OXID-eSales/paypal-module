(function(){

    let PayPalPaymentController = function(){
        this.currentOrder = null;

        this.setCreateOrderResponse = function(response){
            if(response.status === 'success'){
                window.PP_DATA_12321.purchaseUnits.custom_id = response.oxordernr;

                if (null !== this.currentOrder){
                    //some order is currently
                    debugger
                }

                this.currentOrder = {
                    oxid: response.oxid,
                    number: response.oxordernr
                }
            }
        }

        this.getCurrentOrderData = function (name){
            if (null == this.currentOrder){
                console.error('No current order.');
                return;
            }

            if (undefined === this.currentOrder[name]){
                console.error('Current order do not have detail named '+ name +'.');
                return;
            }

            return this.currentOrder[name];
        }

        this.getCurrentOrderOxid = function (){
            return this.getCurrentOrderData('oxid');
        }

        this.getCurrentOrderNumber = function (){
            return this.getCurrentOrderData('oxordernr');
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
                window.PayPalPaymentController.setCreateOrderResponse(response);
                window.PP_DATA_12321.purchaseUnits.custom_id = window.PayPalPaymentController.getCurrentOrderNumber();

                let purchaseUnits = {
                    purchase_units: [
                        { ...window.PP_DATA_12321.purchaseUnits }
                    ]
                }

                return actions.order.create(purchaseUnits);
            },

            onApprove: async function (data, actions) {
                return actions.order.capture().then(async function (details) {
                    let shopOrderPatchingStatus = await fetch(window.PP_DATA_12321.shopOrderPatchingStatus, {
                        method: 'post',
                        headers: {
                            'content-type': 'application/json',
                        },
                        body: JSON.stringify({
                            'oxid': window.PayPalPaymentController.getCurrentOrderOxid()
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