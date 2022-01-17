<div id="[{$buttonId}]" class="paypal-button-container [{$buttonClass}]"></div>
[{oxscript include=$oViewConf->getPayPalJsSdkUrl()}]
[{capture assign="paypal_init"}]
    [{if !$aid}]
        [{assign var="aid" value=""}]
    [{/if}]
    [{assign var="sSelfLink" value=$oViewConf->getSelfLink()|replace:"&amp;":"&"}]
    [{literal}]
    paypal.Buttons({
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
            return fetch('[{/literal}][{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=captureOrder&context=continue"|cat:"&aid="|cat:$aid}][{literal}]', {
                method: 'post',
                body: captureData
            }).then(function(res) {
                return res.json();
            }).then(function(data) {
            [{/literal}]

            [{*if $oViewConf->getTopActiveClassName()=="details"}]
                location.replace('[{$sSelfLink|cat:"cl=basket"}]');
            [{elseif $oViewConf->getTopActiveClassName()=="payment"}]
                if (data.id && data.status == "APPROVED") {
                    $("#payment_oxidpaypal").prop( "checked", true);
                    $('#paymentNextStepBottom').trigger("click");
                }
            [{/if*}]
            if (data.id && data.status == "APPROVED") {
                location.replace('[{$sSelfLink|cat:"cl=order"}]');
            }
            [{literal}]
            })
        },
        onCancel: function(data, actions) {
        },
        onError: function (data) {
        }
    }).render('#[{/literal}][{$buttonId}][{literal}]');
    [{/literal}]
[{/capture}]
[{oxscript add=$paypal_init}]
