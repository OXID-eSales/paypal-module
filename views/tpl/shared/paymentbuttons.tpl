[{block name="oscpaypal_paymentbuttons"}]
    <div id="[{$buttonId}]" class="paypal-button-container [{$buttonClass}]"></div>
    [{oxscript include=$oViewConf->getPayPalJsSdkUrl()}]
    [{capture assign="paypal_init"}]
        [{if !$aid}]
            [{assign var="aid" value=""}]
        [{/if}]
        [{assign var="sToken" value=$oViewConf->getSessionChallengeToken()}]

        [{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
        paypal.Buttons({
            createOrder: function(data, actions) {
                return fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=createOrder&context=continue"|cat:"&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]', {
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
                return fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=approveOrder&context=continue"|cat:"&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]', {
                    method: 'post',
                    body: captureData
                }).then(function(res) {
                    return res.json();
                }).then(function(data) {
                    if (data.status == "ERROR") {
                        location.reload();
                    }
                    else if (data.id && data.status == "APPROVED") {
                        location.replace('[{$sSelfLink|cat:"cl=order"}]');
                    }
                })
            },
            onCancel: function(data, actions) {
                fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"}]');
            },
            onError: function (data) {
                fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"}]');
            }
        }).render('#[{$buttonId}]');
    [{/capture}]
    [{oxscript add=$paypal_init}]
[{/block}]