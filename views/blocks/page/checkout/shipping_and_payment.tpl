[{assign var="payment" value=$oView->getPayment()}]
[{if "oscpaypal_acdc" == $payment->getId() || "oscpaypal" == $payment->getId()}]
    <script>
        function setVaultingCheckbox() {
            let checkbox = document.getElementById("oscPayPalVaultPaymentCheckbox");
            let vaultingInput = document.getElementById("oscPayPalVaultPayment");

            if (checkbox.checked) {
                vaultingInput.value = "true";
            }else {
                vaultingInput.value = "";
            }
        }

        window.onload = function () {
            let cardContent = document.querySelector('#orderPayment form .card .card-body');
            let checkboxDiv = document.getElementById("vaultingCheckboxDiv");

            cardContent.appendChild(checkboxDiv);
            checkboxDiv.style.display = "block";
        }
    </script>

    <div id="vaultingCheckboxDiv" style="display: none;">
        <br>
        <input type="checkbox" id="oscPayPalVaultPaymentCheckbox" onclick="setVaultingCheckbox()">
        <label for="oscPayPalVaultPaymentCheckbox">[{oxmultilang ident="OSC_PAYPAL_VAULTING_SAVE"}]</label>
    </div>
[{/if}]

[{if "oscpaypal_acdc" == $payment->getId() || "oscpaypal_pui" == $payment->getId() || $vaultedPaymentDescription}]
    [{if $oViewConf->isFlowCompatibleTheme()}]
        [{include file="modules/osc/paypal/shipping_and_payment_flow.tpl"}]
    [{else}]
        [{include file="modules/osc/paypal/shipping_and_payment_wave.tpl"}]
    [{/if}]
[{else}]
    [{$smarty.block.parent}]
[{/if}]