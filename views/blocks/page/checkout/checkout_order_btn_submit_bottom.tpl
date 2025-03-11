[{assign var="payment" value=$oView->getPayment()}]
[{assign var="paymentId" value=$payment->getId()}]
[{assign var="purchaseUnits" value=$oView->getPurchaseUnits()}]

[{if "oscpaypal" == $payment->getId()}]

{*check if this vaulting is needed*}
<input type="hidden" name="vaultPayment" id="oscPayPalVaultPayment" value="">

    <div id="[{$paymentId}]" class="paypal-button-container [{$buttonClass}]"></div>
    <script>

        //https://developer.paypal.com/sdk/js/reference/#createorder


        window.addEventListener('PayPalSDKLoadedEvent', (event) => {
            button = paypal.Buttons({

                createOrder: function (data, actions) {
                    debugger
                    alert(2132423423);
                    return actions.order.create({
                        purchase_units: [
                            [{$purchaseUnits}]
                        ]
                    });

                },
                onApprove: function (data, actions) {
                    // Capture the funds from the transaction
                    return actions.order.capture().then(function (details) {
                        // Show a success message to your buyer
                        alert('Transaction completed by ' + details.payer.name.given_name);
                        debugger
                        // Call your server to save the transaction
                        return fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=approveOrder&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]', {
                            method: 'post',
                            headers: {
                                'content-type': 'application/json'
                            },
                            body: JSON.stringify({
                                orderID: data.orderID,
                                payerID: data.payerID,
                                paymentDetails: details
                            })
                        });
                    });
                },
                onCancel: function (data, actions) {
                    fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"}]');
                },
                onError: function (data) {
                    fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"}]');
                }
            })
            if (button.isEligible()) {
                button.render('#[{$paymentId}]');
            }
        });


    </script>


    [{/if}]
[{if "oscpaypal_pui" == $payment->getId()}]
    [{if $oViewConf->isFlowCompatibleTheme()}]
    [{include file="modules/osc/paypal/checkout_order_btn_submit_bottom_flow.tpl"}]
    [{else}]
    [{include file="modules/osc/paypal/checkout_order_btn_submit_bottom_wave.tpl"}]
    [{/if}]
    [{/if}]
[{if "oscpaypal_googlepay" == $payment->getId()}]
    [{include file="modules/osc/paypal/googlepay.tpl" buttonClass="paypal-button-wrapper large"}]
    [{elseif "oscpaypal_applepay" == $payment->getId()}]
    [{include file="modules/osc/paypal/applepay.tpl" paymentId=$payment->getId() buttonClass="paypal-button-wrapper large"}]
    <div id="applepay-container" class="paypal-button-container paypal-button-wrapper paypal-button-right large"></div>
    [{/if}]




