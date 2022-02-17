[{block name="oscpaypal_acdc"}]
    [{* use original script tag instead of oxscript because of the additional params *}]
    <script src="[{$oViewConf->getPayPalJsSdkUrlForACDC()}]" data-client-token="[{$oViewConf->getDataClientToken()}]"></script>
[{/block}]

<!-- Advanced credit and debit card payments form -->
<div id="card_container" class="card_container">
    <form id="card_form">
        <div class="form-group">
            <label for="card-number" class="control-label">[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_NUMBER"}]</label>
            <div id="card-number" class="form-control card_field"></div>
        </div>
        <div class="form-group">
            <label for="expiration-date" class="control-label">[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_EXDATE"}]</label>
            <div id="expiration-date" class="form-control card_field"></div>
        </div>
        <div class="form-group">
            <label for="cvv" class="control-label">[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_CVV"}]</label>
            <div id="cvv" class="form-control card_field"></div>
        </div>
        <div class="form-group">
            <label for="card-holder-name" class="control-label">[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_NAME_ON_CARD"}]</label>
            <div class="controls">
                <input type="text" id="card-holder-name" class="form-control" name="card-holder-name" autocomplete="off" placeholder="[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_NAME_ON_CARD"}]"/>
            </div>
        </div>
        <div class="hidden">
            <input type="hidden" id="card-billing-address-street" name="card-billing-address-street" value="[{if $oxcmp_user->oxuser__oxstreet->value}][{$oxcmp_user->oxuser__oxstreet->value}][{/if}] [{if $oxcmp_user->oxuser__oxstreetnr->value}][{$oxcmp_user->oxuser__oxstreetnr->value}][{/if}]" />
            <input type="hidden" id="card-billing-address-unit" name="card-billing-address-unit" value=""/>
            <input type="hidden" id="card-billing-address-city" name="card-billing-address-city" value="[{if $oxcmp_user->oxuser__oxcity->value}][{$oxcmp_user->oxuser__oxcity->value}][{/if}]" />
            <input type="hidden" id="card-billing-address-state" name="card-billing-address-state" value="[{$oView->getUserStateIso()}]" />
            <input type="hidden" id="card-billing-address-zip" name="card-billing-address-zip" value="[{if $oxcmp_user->oxuser__oxzip->value}][{$oxcmp_user->oxuser__oxzip->value}][{/if}]" />
            <input type="hidden" id="card-billing-address-country" name="card-billing-address-country" value="[{$oView->getUserCountryIso()}]"/>
        </div>
    </form>
</div>

<!-- Implementation -->
[{assign var="sSelfLink" value=$oViewConf->getSelfLink()|replace:"&amp;":"&"}]

<script>

    var PayPalHostedFields = function () {
        let orderId;

        // If this returns false or the card fields aren't visible, see Step #1.
        if (paypal.HostedFields.isEligible()) {

            // Renders card fields
            paypal.HostedFields.render({

                // Call your server to set up the transaction
                createOrder: function(data, actions) {
                    return fetch('[{$sSelfLink|cat:"cl=order&fnc=createAcdcOrder&ord_agb=1&challenge="}]' + '[{$oViewConf->getSessionChallengeToken()}]' + '&sDeliveryAddressMD5=' + '[{$oView->getDeliveryAddressMD5()}]', {
                        method: 'post',
                        headers: {
                            'content-type': 'application/json'
                        }
                    }).then(function(res) {
                        return res.json();
                    }).then(function(orderData) {
                        orderId = orderData.id;
                        return orderId;
                    });
                },
                styles: {
                    'input': {
                        'color': '#3A3A3A',
                        'transition': 'color 160ms linear',
                        '-webkit-transition': 'color 160ms linear'
                    },
                    ':focus': {
                        'color': '#333333'
                    },
                    '.valid': {
                        'color': 'green'
                    },
                    '.invalid': {
                        'color': 'red'
                    }
                },
                fields: {
                    number: {
                        selector: "#card-number",
                        placeholder: "4111 1111 1111 1111"
                    },
                    cvv: {
                        selector: "#cvv",
                        placeholder: "123"
                    },
                    expirationDate: {
                        selector: "#expiration-date",
                        placeholder: "MM/YY"
                    }
                }
            }).then(function (cardFields) {
                document.querySelector("#orderConfirmAgbBottom").addEventListener('submit', (event) => {
                    event.preventDefault();

                    cardFields.submit({
                        // Cardholder's first and last name
                        cardholderName: document.getElementById('card-holder-name').value,
                        // Billing Address
                        billingAddress: {
                            // Street address, line 1
                            streetAddress: document.getElementById('card-billing-address-street').value,
                            // Street address, line 2 (Ex: Unit, Apartment, etc.)
                            extendedAddress: document.getElementById('card-billing-address-unit').value,
                            // State
                            region: document.getElementById('card-billing-address-state').value,
                            // City
                            locality: document.getElementById('card-billing-address-city').value,
                            // Postal Code
                            postalCode: document.getElementById('card-billing-address-zip').value,
                            // Country Code
                            countryCodeAlpha2: document.getElementById('card-billing-address-country').value
                        }
                    }).then(function () {
                        fetch('[{$sSelfLink|cat:"cl=order&fnc=captureAcdcOrder&acdcorderid="}]' + orderId, {
                            method: 'post'
                        }).then(function (res) {
                            return res.json();
                        }).then(function (orderData) {
                            var errorDetail = Array.isArray(orderData.details) && orderData.details[0];
                            var goNext = Array.isArray(orderData.location) && orderData.location[0];

                            window.location.href = '[{$sSelfLink}]' + goNext;
                        })
                    }).catch(function (err) {
                        console.log('Payment could not be processed! ' + JSON.stringify(err))
                        window.location.href = '[{$sSelfLink|cat:"cl=order&acdcretry=true"}]'
                    })
                })
            });
        } else {
            // Hides card fields if the merchant isn't eligible
            document.querySelector("#card_form").style = 'display: none';
        }

    }

    var initWhenPayPalHostedFieldsAvailable = function (){
        if (typeof paypal !== 'undefined' && typeof paypal.HostedFields !== 'undefined') {
            document.querySelector("#card_form").style = 'display: block';
            PayPalHostedFields();
        } else {
            setTimeout(function(){
                document.querySelector("#card_form").style = 'display: none';
                initWhenPayPalHostedFieldsAvailable();
            }, 100);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWhenPayPalHostedFieldsAvailable);
    } else {
        initWhenPayPalHostedFieldsAvailable();
    }

    window.onresize = function () {
        initWhenPayPalHostedFieldsAvailable();
    }


</script>
