//enable vaulted payment 'Continue with saved payment method'button
function registerClickListenerForSavedVaultRadioButtons() {
    const vaultingPaymentsourceRadioButtons = document.querySelectorAll(".vaulting_paymentsource");
    if (vaultingPaymentsourceRadioButtons && vaultingPaymentsourceRadioButtons.length > 0) {
        vaultingPaymentsourceRadioButtons.forEach(function (paymentsource) {
            paymentsource.onclick = function () {
                if (paymentsource.checked) {
                    document.getElementById("paypalVaultCheckoutButton").disabled = false;
                }
            };
        });
    }
}

// clicking the vault checkout button will submit the form for payment
function registerClickListenerForTheVaultCheckoutButton() {
    const paypalVaultCheckoutButton = document.getElementById("paypalVaultCheckoutButton");
    if (paypalVaultCheckoutButton) {
        paypalVaultCheckoutButton.onclick = function () {
            const vaultingPaymentsourceRadioButtons = document.querySelectorAll(".vaulting_paymentsource");
            if (vaultingPaymentsourceRadioButtons && vaultingPaymentsourceRadioButtons.length > 0) {
                vaultingPaymentsourceRadioButtons.forEach(function (paymentsource) {
                    if (paymentsource.checked) {
                        const paymenttype = paymentsource.dataset.paymenttype;
                        const paymentSourceId = paymenttype === 'card' ? "payment_oscpaypal_acdc" : "payment_oscpaypal";
                        document.getElementById(paymentSourceId).click();

                        let input = document.createElement("input");
                        input.type = "hidden";
                        input.name = "vaultingpaymentsource";
                        input.value = paymentsource.dataset.index;
                        document.getElementById("payment").appendChild(input);

                        document.getElementById("paymentNextStepBottom").click();
                    }
                });
            }
        };
    }
}

document.addEventListener("DOMContentLoaded", function() {
    registerClickListenerForSavedVaultRadioButtons();
    registerClickListenerForTheVaultCheckoutButton();
});
