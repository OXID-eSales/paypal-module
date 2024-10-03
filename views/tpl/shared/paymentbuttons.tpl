[{block name="oscpaypal_paymentbuttons"}]
    [{oxhasrights ident="PAYWITHPAYPALEXPRESS"}]
    <div id="[{$buttonId}]" class="paypal-button-container [{$buttonClass}]"></div>
    [{if $phpStorm}]<script>[{/if}]
    [{capture assign="paypal_init"}]
        [{if !$aid}]
            [{assign var="aid" value=""}]
        [{/if}]
        [{assign var="sToken" value=$oViewConf->getSessionChallengeToken()}]
        [{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
        [{if $buttonId == "oscpaypal_sepa" || $buttonId == "oscpaypal_cc_alternative"}]
            FUNDING_SOURCES = [
                paypal.FUNDING.[{if $buttonId == "oscpaypal_sepa"}]SEPA[{elseif $buttonId == "oscpaypal_cc_alternative"}]CARD[{/if}]
            ];
            // Loop over each funding source/payment method
            FUNDING_SOURCES.forEach(function (fundingSource) {
                // Initialize the buttons
                let button = paypal.Buttons({
                    fundingSource: fundingSource,
                    createOrder: function (data, actions) {
                        return fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=createOrder&paymentid="|cat:$buttonId|cat:"&context=continue&stoken="|cat:$sToken}]', {
                            method: 'post',
                            headers: {
                                'content-type': 'application/json'
                            }
                        }).then(function (res) {
                            return res.json();
                        }).then(function (data) {
                            return data.id;
                        })
                    },
                    onApprove: function (data, actions) {
                        captureData = new FormData();
                        captureData.append('orderID', data.orderID);
                        return fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=approveOrder&paymentid="|cat:$buttonId|cat:"&context=continue&stoken="|cat:$sToken}]', {
                            method: 'post',
                            body: captureData
                        }).then(function (res) {
                            return res.json();
                        }).then(function (data) {
                            if (data.status == "ERROR") {
                                location.reload();
                            } else if (data.id && data.status == "APPROVED") {
                                location.replace('[{$sSelfLink|cat:"cl=order"}]');
                            }
                        })
                    },
                    onCancel: function (data, actions) {
                        fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"}]');
                    },
                    onError: function (data) {
                        fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"}]');
                    }
                })
                // Check if the button is eligible
                if (button.isEligible()) {
                // Render the standalone button for that funding source
                    button.render('#[{$buttonId}]')
                }
            });
        [{else}]
            button = paypal.Buttons({
                style: {
                    shape: "rect",
                    layout: "vertical",
                    color: "gold",
                    label: "pay",
                },
                [{if $oViewConf->getCountryRestrictionForPayPalExpress()}]
                onShippingChange: function (data, actions) {
                    if (!countryRestriction.includes(data.shipping_address.country_code)) {
                        return actions.reject();
                    }
                    return actions.resolve();
                },
                [{/if}]
                createOrder: function (data, actions) {
                    let selElements = document.querySelectorAll('input[name^="sel"]');
                    let params = new URLSearchParams();
                    if (selElements.length > 0) {
                        selElements.forEach(function(element) {
                            if (element && element.value !== undefined && element.value !== null) {
                                params.append(element.name, element.value);
                            }
                        });
                    }
                    let baseUrl = '[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=createOrder&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]';
                    let url = baseUrl + (params.toString() ? '&' + params.toString() : '');
                    return fetch(url , {
                            method: 'post',
                            headers: {
                                'content-type': 'application/json'
                            }
                        }).then(function (res) {
                        return res.json();
                    }).then(function (data) {
                        return data.id;
                    })
                },
                onApprove: function (data, actions) {
                    captureData = new FormData();
                    captureData.append('orderID', data.orderID);
                    return fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=approveOrder&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]', {
                        method: 'post',
                        body: captureData
                    }).then(function (res) {
                        return res.json();
                    }).then(function (data) {
                        if (data.status == "ERROR") {
                            location.reload();
                        } else if (data.id && data.status == "APPROVED") {
                            location.replace('[{$sSelfLink|cat:"cl=order"}]');
                        }
                    })
                },
                onCancel: function (data, actions) {
                    fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"}]');
                },
                onError: function (data) {
                    fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"}]');
                }
            })
            if (button.isEligible()) {
                button.render('#[{$buttonId}]');
            }
        [{/if}];
    [{/capture}]
    [{if $phpStorm}]</script>[{/if}]
    [{oxscript add=$paypal_init}]
    [{/oxhasrights}]
[{/block}]
