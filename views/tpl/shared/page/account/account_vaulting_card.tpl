[{capture append="oxidBlock_content"}]
    [{assign var="template_title" value="OSC_PAYPAL_VAULTING_MENU_CARD"|oxmultilangassign}]

    [{if $oViewConf->isFlowCompatibleTheme()}]
    [{include file='modules/osc/paypal/account_vaulting_card_flow.tpl'}]
    [{else}]
    [{include file='modules/osc/paypal/account_vaulting_card_wave.tpl'}]
    [{/if}]

    <script>
        window.onload = function () {
            const cardFields = paypal.CardFields({
            createVaultSetupToken: async () => {
                // Call your server API to generate a vaultSetupToken
                // and return it here as a string
                const result = await fetch(
                    "[{oxgetseourl ident=$oViewConf->getGenerateSetupTokenLink(true)}]",
                    { method: "POST"
                })
                const { id } = await result.json();
                return id;
            },
            onApprove: async (data) => {
                // Only for 3D Secure
                if(data.liabilityShift){
                 // Handle liability shift
                }

                const result = await fetch(
                    "[{oxgetseourl ident=$oViewConf->getGeneratePaymentTokenLink()}]"+data.vaultSetupToken,
                    {
                        method: "POST",
                        body: JSON.stringify(data)
                    }
                );
                const status = await result.json();

                if(status.state !== "SUCCESS") {
                    //log error?
                }
            },
            onError: (error) =>
                console.error('Something went wrong:', error)
            });

            // Retrieve styles from your existing element
            const formControlStyles = getComputedStylesAsObject('input.form-control');
            // Prepare these styles for PayPal cardFields
            const payPalInputStyles = {
                'input': formControlStyles
            };

            // Check eligibility and display advanced credit and debit card payments
            if (cardFields.isEligible()) {
                cardFields.NameField({
                    placeholder: "[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_NAME_ON_CARD"}]",
                    style: payPalInputStyles
                }).render("#card-holder-name");
                cardFields.NumberField({
                    placeholder: "[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_NUMBER"}]",
                    style: payPalInputStyles
                }).render("#card-number");
                cardFields.ExpiryField({
                    placeholder: "[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_EXDATE"}]",
                    style: payPalInputStyles
                }).render("#expiration-date");
                cardFields.CVVField({
                    placeholder: "[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_CVV"}]",
                    style: payPalInputStyles
                }).render("#cvv");
            } else {
                // Handle the workflow when credit and debit cards are not available
            }

            const submitButton = document.getElementById("submit");
            submitButton.addEventListener("click", () => {
                cardFields
                    .submit()
                    .then(() => {
                        showSuccessMessage();
                    })
                    .catch((error) => {
                        showFailureMessage();
                    });
            });

            function showSuccessMessage() {
                $('#payPalVaultingCardContainer').hide();
                $('#PayPalVaultingFailure').hide();
                $('#PayPalVaultingSuccess').show();
            }

            function showFailureMessage() {
                $('#PayPalVaultingSuccess').hide();
                $('#PayPalVaultingFailure').show();
            }

            // Function to read the calculated CSS properties of an element
            function getComputedStylesAsObject(selector) {
                // Find element
                const element = document.querySelector(selector);
                if (!element) return {};

                // Get all calculated styles
                const computedStyle = window.getComputedStyle(element);

                // Extract relevant properties and convert them into an object
                const styleObject = {};

                // List of properties you want to adopt
                const relevantProperties = [
                    'color', 'font-size', 'font-family', 'font-weight',
                    'background-color', 'border', 'border-radius', 'padding',
                    'box-shadow', 'height', 'line-height'
                ];

                relevantProperties.forEach(prop => {
                    // CSS properties in JavaScript have camelCase (e.g. fontSize instead of font-size)
                    // But we can leave them in CSS format for the PayPal API
                    styleObject[prop] = computedStyle.getPropertyValue(prop);
                });

                return styleObject;
            }
        }
    </script>
    <div id="payments-sdk__contingency-lightbox"></div>

    [{include file="modules/osc/paypal/vaultedpaymentsources.tpl"}]

    [{insert name="oxid_tracker" title=$template_title}]
[{/capture}]

[{capture append="oxidBlock_sidebar"}]
    [{include file="page/account/inc/account_menu.tpl" active_link="oscPayPalVaultingCard"}]
[{/capture}]
[{include file="layout/page.tpl" sidebar="Left"}]
