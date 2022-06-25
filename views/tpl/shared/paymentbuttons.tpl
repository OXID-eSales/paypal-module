[{block name="oscpaypal_paymentbuttons"}]
    <div id="[{$buttonId}]" class="paypal-button-container [{$buttonClass}]"[{if $buttonId == 'PayPalButtonProductMain'}] data-disable-buttons="[{if $blCanBuy}]true[{else}]false[{/if}]"[{/if}]></div>
    [{oxscript include=$oViewConf->getPayPalJsSdkUrl()}]
    [{capture assign="paypal_init"}]
        [{if !$aid}]
            [{assign var="aid" value=""}]
        [{/if}]
        [{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
        [{literal}]
        let buttons = paypal.Buttons({
            createOrder: function(data, actions) {
                return fetch('[{/literal}][{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=createOrder&context=continue"|cat:"&aid="|cat:$aid}][{literal}]', {
                    method: 'post',
                    headers: {
                        'content-type': 'application/json'
                    }
                }).then(function(res) {
                    return res.json();
                }).then(function(data) {
                    return data.id;
                })
            },
            onApprove: function(data, actions) {
                captureData = new FormData();
                captureData.append('orderID', data.orderID);
                return fetch('[{/literal}][{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=approveOrder&context=continue"|cat:"&aid="|cat:$aid}][{literal}]', {
                    method: 'post',
                    body: captureData
                }).then(function(res) {
                    return res.json();
                }).then(function(data) {
                [{/literal}]

                if (data.status == "ERROR") {
                    location.reload();
                }
                else if (data.id && data.status == "APPROVED") {
                    location.replace('[{$sSelfLink|cat:"cl=order"}]');
                }
                [{literal}]
                })
            },
            onCancel: function(data, actions) {
                fetch('[{/literal}][{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"}][{literal}]');
            },
            onError: function (data) {
                fetch('[{/literal}][{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"}][{literal}]');
            }
        });

        [{/literal}][{if $buttonId == 'PayPalButtonProductMain'}][{literal}]
            // Check is product can buy
            let paypal_button_container = document.querySelector('#[{/literal}][{$buttonId}][{literal}]');
            let is_product_buyable = paypal_button_container.getAttribute('data-disable-buttons');

            // Enable or disable the buttons when product is buyable or not buyable
            if (is_product_buyable == 'true')  {
                buttons.render('#[{/literal}][{$buttonId}][{literal}]');
            }
        [{/literal}][{else}][{literal}]
            buttons.render('#[{/literal}][{$buttonId}][{literal}]');
        [{/literal}][{/if}][{literal}]
        [{/literal}]
    [{/capture}]
    [{oxscript add=$paypal_init}]
[{/block}]