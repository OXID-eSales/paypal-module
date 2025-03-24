(function(){

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
                if(response.status === 'created'){
                    window.PP_DATA_12321.purchaseUnits.custom_id = response.oxordernr;
                }

                let purchaseUnits = {
                    purchase_units: [
                        { ...window.PP_DATA_12321.purchaseUnits }
                    ]
                }

                return actions.order.create(purchaseUnits);

            },
            onApprove: async function (data, actions) {
                // Capture the funds from the transaction
                return actions.order.capture().then(async function (details) {

                    let shopOrderPatchingStatus = await fetch(window.PP_DATA_12321.shopOrderPatchingStatusUrl, {
                        method: 'post',
                        headers: {
                            'content-type': 'application/json',
                        },
                        body: JSON.stringify({
                            'deladrid': window.PP_DATA_12321.deladrid
                        })
                    });

                    let response = await shopOrderPatchingStatus.json();
                    if(response.status === 'success'){
                        window.PP_DATA_12321.purchaseUnits.custom_id = response.oxordernr;
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