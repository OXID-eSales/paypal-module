[{capture append="oxidBlock_content"}]
    [{assign var="template_title" value="OSC_PAYPAL_VAULTING_MENU_CARD"|oxmultilangassign}]

    <h1 class="page-header">[{oxmultilang ident="OSC_PAYPAL_VAULTING_MENU_CARD"}]</h1>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">[{oxmultilang ident="OSC_PAYPAL_VAULTING_SAVE_INSTRUCTION_CARD"}]</h3>
        </div>
        <div class="card-body">
            <p id="PayPalVaultingSuccess" class="alert alert-success" style="display: none">[{oxmultilang ident="OSC_PAYPAL_VAULTING_SUCCESS"}]</p>
            <p id="PayPalVaultingFailure" class="alert alert-danger" style="display: none">[{oxmultilang ident="OSC_PAYPAL_VAULTING_ERROR"}]</p>
            <div class='card_container' id="payPalVaultingCardContainer">
                <div id='card-holder-name'></div>
                <div id='card-number'></div>
                <div id='expiration-date'></div>
                <div id='cvv'></div>
                <button value='submit' id='submit' class="btn btn-primary">[{oxmultilang ident="OSC_PAYPAL_VAULTING_CARD_SAVE"}]</button>
            </div>
        </div>
    </div>

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
            })


            // Check eligibility and display advanced credit and debit card payments
            if (cardFields.isEligible()) {
                cardFields.NameField().render("#card-holder-name");
                cardFields.NumberField().render("#card-number");
                cardFields.ExpiryField().render("#expiration-date");
                cardFields.CVVField().render("#cvv");
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
                $('#PayPalVaultingSuccess').show();
            }

            function showFailureMessage() {
                $('#PayPalVaultingFailure').show();
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