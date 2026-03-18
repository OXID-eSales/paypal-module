/**
 * PayPal Variant Observer
 * Re-initializes PayPal Express button on the product details page
 * after OXID replaces #details_container via AJAX on variant selection change.
 *
 * Supports both theme families:
 * - Apex/Twig (OXID 7): replaces #details_container via outerHTML
 *   -> mutation fires on the parent element
 * - Wave/Flow/Smarty (OXID 6): replaces content via jQuery .html() (innerHTML)
 *   -> mutation fires on #details_container itself
 *
 * Both observers are set up so the fix works regardless of the theme in use.
 */
(function () {
    var detailsContainer = document.getElementById('details_container');
    if (!detailsContainer || !detailsContainer.parentNode) {
        return;
    }

    var observerTarget = detailsContainer.parentNode;

    function initPayPalButton() {
        var config = document.getElementById('PayPalButtonProductMainConfig');
        var buttonContainer = document.getElementById('PayPalButtonProductMain');

        if (!config || !buttonContainer) {
            return;
        }

        if (buttonContainer.children.length > 0) {
            return;
        }

        if (typeof paypal === 'undefined' || typeof paypal.Buttons !== 'function') {
            return;
        }

        var selfLink = config.getAttribute('data-self-link');
        var sToken = config.getAttribute('data-stoken');
        var aid = config.getAttribute('data-aid');

        if (!selfLink || !sToken) {
            return;
        }

        var buttonConfig = {
            style: window.PayPalButtonStyle || {},
            createOrder: function (data, actions) {
                // Support both Apex (select) and Wave/Flow (input) selection elements
                var selElements = document.querySelectorAll('select[name^="sel"], input[name^="sel"]');
                var params = new URLSearchParams();
                if (selElements.length > 0) {
                    selElements.forEach(function (element) {
                        if (element && element.value !== undefined && element.value !== null) {
                            params.append(element.name, element.value);
                        }
                    });
                }
                var amountElement = document.getElementById('amountToBasket');
                var amount = amountElement ? amountElement.value : 0;
                params.append('amountToBasket', amount);
                var baseUrl = selfLink + 'cl=oscpaypalproxy&fnc=createOrder&aid=' + aid + '&context=continue&stoken=' + sToken;
                var url = baseUrl + (params.toString() ? '&' + params.toString() : '');
                return fetch(url, {
                    method: 'post',
                    headers: {
                        'content-type': 'application/json'
                    }
                }).then(function (res) {
                    return res.json();
                }).then(function (data) {
                    return data.id;
                });
            },
            onApprove: function (data, actions) {
                var captureData = new FormData();
                captureData.append('orderID', data.orderID);
                return fetch(selfLink + 'cl=oscpaypalproxy&fnc=approveOrder&context=continue&aid=' + aid + '&context=continue&stoken=' + sToken, {
                    method: 'post',
                    body: captureData
                }).then(function (res) {
                    return res.json();
                }).then(function (data) {
                    if (data.status === 'ERROR') {
                        location.reload();
                    } else if (data.id && data.status === 'APPROVED') {
                        location.replace(selfLink + 'cl=order');
                    }
                });
            },
            onCancel: async function (data, actions) {
                try {
                    var response = await fetch(selfLink + 'cl=oscpaypalproxy&fnc=cancelPayPalPayment');
                    if (!response.ok) {
                        console.error('Failed to cancel PayPal payment:', response.statusText);
                    }
                } catch (error) {
                    console.error('Error occurred while canceling PayPal payment:', error);
                }
            },
            onError: async function (data) {
                try {
                    await fetch(selfLink + 'cl=oscpaypalproxy&fnc=cancelPayPalPayment');
                } catch (error) {
                    console.error('Error occurred while canceling PayPal payment:', error);
                }
            }
        };

        if (typeof countryRestriction !== 'undefined' && Array.isArray(countryRestriction)) {
            buttonConfig.onShippingChange = function (data, actions) {
                if (!countryRestriction.includes(data.shipping_address.country_code)) {
                    return actions.reject();
                }
                return actions.resolve();
            };
        }

        var button = paypal.Buttons(buttonConfig);
        if (button.isEligible()) {
            button.render('#PayPalButtonProductMain');
        }
    }

    var mutationCallback = function (mutations) {
        for (var i = 0; i < mutations.length; i++) {
            if (mutations[i].type === 'childList' && mutations[i].addedNodes.length > 0) {
                setTimeout(initPayPalButton, 100);
                return;
            }
        }
    };

    // Apex/Twig: outerHTML replaces #details_container -> parent receives childList mutation
    var parentObserver = new MutationObserver(mutationCallback);
    parentObserver.observe(observerTarget, { childList: true });

    // Wave/Flow/Smarty: jQuery .html() replaces innerHTML -> #details_container itself receives childList mutation
    var containerObserver = new MutationObserver(mutationCallback);
    containerObserver.observe(detailsContainer, { childList: true });
})();
