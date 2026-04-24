[{block name="oscpaypal_paymentbuttons"}]
    [{oxhasrights ident="PAYWITHPAYPALEXPRESS"}]
        [{assign var="oConfig" value=$oViewConf->getConfig()}]
        [{assign var="sDebug" value=""}]
        [{if $oViewConf->isPayPalSandbox()}]
            [{assign var="sDebug" value="&XDEBUG_SESSION_START=1"}]
        [{/if}]
        [{assign var="PayPalSDKJS" value=$oConfig->getGlobalParameter("PayPalSDKJS")}]
        [{if !$PayPalSDKJS}]
            [{capture assign="PayPalSDKJS"}]
                [{include file="modules/osc/paypal/base_js.tpl" commitFlow=false}]
            [{/capture}]
            [{$oConfig->setGlobalParameter("PayPalSDKJS", $PayPalSDKJS)}]
        [{/if}]
        <div id="[{$buttonId}]" class="paypal-button-container [{$buttonClass}]"></div>
        [{if $phpStorm}]<script>[{/if}]
        [{capture assign="paypal_init"}]
            [{if !$aid}]
                [{assign var="aid" value=""}]
            [{/if}]
            [{assign var="sToken" value=$oViewConf->getSessionChallengeToken()}]
            [{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
            // Wrap in IIFE so re-executing this script (e.g. after a PDP AJAX variant
            // swap that re-injects the button markup) does not raise a redeclaration
            // SyntaxError on the `let button` binding — classic scripts share the
            // script-block scope for `let`/`const`. The early-return also guards
            // against double rendering when the container is still populated.
            (function () {
                var paypalButtonContainer = document.getElementById('[{$buttonId}]');
                if (!paypalButtonContainer || paypalButtonContainer.children.length > 0) {
                    return;
                }
            [{if $buttonId == "oscpaypal_sepa" || $buttonId == "oscpaypal_cc_alternative"}]
                FUNDING_SOURCES = [
                    paypal.FUNDING.[{if $buttonId == "oscpaypal_sepa"}]SEPA[{elseif $buttonId == "oscpaypal_cc_alternative"}]CARD[{/if}]
                ];
                // delete the color option, because the SEPA and CC-Alternative has some problems with PayPal-Button colors like gold. So we use the defaults of the special buttons
                delete window.PayPalButtonStyle.color;
                // Loop over each funding source/payment method
                FUNDING_SOURCES.forEach(function (fundingSource) {
                    // Initialize the buttons
                    let button = paypal.Buttons({
                        style: PayPalButtonStyle,
                        fundingSource: fundingSource,
                        createOrder: function (data, actions) {
                            return fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=createOrder&paymentid="|cat:$buttonId|cat:"&context=continue&stoken="|cat:$sToken|cat:$sDebug}]', {
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
                        onApprove: async function (data, actions) {
                            captureData = new FormData();
                            captureData.append('orderID', data.orderID);
                            return await fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=approveOrder&paymentid="|cat:$buttonId|cat:"&context=continue&stoken="|cat:$sToken|cat:$sDebug}]', {
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
                        onCancel: async function (data, actions) {
                            try {
                                const response = await fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"|cat:$sDebug}]');
                                if (!response.ok) {
                                    console.error('Failed to cancel PayPal payment:', response.statusText);
                                }
                            } catch (error) {
                                console.error('Error occurred while canceling PayPal payment:', error);
                            }
                        },
                        [{if $oViewConf->getCountryRestrictionForPayPalExpress()}]
                        onShippingChange: function (data, actions) {
                            if (!countryRestriction.includes(data.shipping_address.country_code)) {
                                return actions.reject();
                            }
                            return actions.resolve();
                        },
                        [{/if}]
                        onError: async function (data) {
                            try {
                                const response = await fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"|cat:$sDebug}]');
                                if (!response.ok) {
                                    console.error('Failed to cancel PayPal payment:', response.statusText);
                                }
                            } catch (error) {
                                console.error('Error occurred while canceling PayPal payment:', error);
                            }
                        }
                    })
                    // Check if the button is eligible
                    if (button.isEligible()) {
                        // Render the standalone button for that funding source
                        button.render('#[{$buttonId}]')
                    } else {
                        //remove SEPA option from payments methods
                        document.querySelector('.paypal-button-wrapper--sepa')
                            .closest('dl')
                            .remove();
                    }
                });
            [{else}]
                let button = paypal.Buttons({
                    style: PayPalButtonStyle,
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
                        let amountElement = document.getElementById("amountToBasket");
                        let amount = amountElement ? amountElement.value : 0;
                        params.append('amountToBasket', amount);
                        let baseUrl = '[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=createOrder&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken|cat:$sDebug}]';
                        let url = baseUrl + (params.toString() ? '&' + params.toString() : '');
                        window.PayPalExpressSession.started = true;
                        window.history.pushState(null, ""); // needed to trigger the popstate event
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
                    onApprove: async function (data, actions) {
                        captureData = new FormData();
                        captureData.append('orderID', data.orderID);
                        return await fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=approveOrder&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken|cat:$sDebug}]', {
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
                    onCancel: async function (data, actions) {
                        try {
                            const response = await fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"|cat:$sDebug}]');
                            if (!response.ok) {
                                console.error('Failed to cancel PayPal payment:', response.statusText);
                            }
                        } catch (error) {
                            console.error('Error occurred while canceling PayPal payment:', error);
                        }
                    },
                    onError: async function (data) {
                        try {
                            const response = await fetch('[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=cancelPayPalPayment"|cat:$sDebug}]');
                        } catch (error) {
                            console.error('Error occurred while canceling PayPal payment:', error);
                        }
                    }
                })
                if (button.isEligible()) {
                    button.render('#[{$buttonId}]');
                }
            [{/if}]
            })();
        [{/capture}]
        [{if $phpStorm}]</script>[{/if}]
        [{oxscript add=$paypal_init}]
    [{/oxhasrights}]
[{/block}]
