function deselectRadioButtons(selector) {
    const radioButtons = document.querySelectorAll(selector);
    radioButtons.forEach(item => {
        item.checked = false;
    });
}

function registerClickListenerForPaymentMethodsRadioButtons() {
    const paymentMethodsRadioButtons = document.querySelectorAll(".vaulting_paymentsource");

    if (!paymentMethodsRadioButtons) {
        return;
    }

    paymentMethodsRadioButtons.forEach(function(paymentMethod) {
        paymentMethod.onclick = function() {
            if (paymentMethod.checked) {
                document.getElementById("paypalVaultCheckoutButton").disabled = true;
                document.getElementById("paymentNextStepBottom").disabled = false;
                deselectRadioButtons(".vaulting_paymentsource");
            }
        };
    });
}

function registerClickListenerForSavedVaultRadioButtons() {
    const vaultingPaymentSourceRadioButtons = document.querySelectorAll(".vaulting_paymentsource");
    if (vaultingPaymentSourceRadioButtons && vaultingPaymentSourceRadioButtons.length > 0) {
        vaultingPaymentSourceRadioButtons.forEach(function(paymentsource) {
            paymentsource.onclick = function() {
                if (paymentsource.checked) {
                    document.getElementById("paypalVaultCheckoutButton").disabled = false;
                    document.getElementById("paymentNextStepBottom").disabled = true;
                    deselectRadioButtons('#payment [type="radio"]');
                }
            };
        });
    }
}

function registerClickListenerForTheVaultCheckoutButton() {
    const paypalVaultCheckoutButton = document.getElementById("paypalVaultCheckoutButton");
    if (paypalVaultCheckoutButton) {
        paypalVaultCheckoutButton.onclick = function() {
            const vaultingPaymentSourceRadioButtons = document.querySelectorAll(".vaulting_paymentsource");
            if (vaultingPaymentSourceRadioButtons && vaultingPaymentSourceRadioButtons.length > 0) {
                vaultingPaymentSourceRadioButtons.forEach(function(paymentsource) {
                    if (paymentsource.checked) {
                        const paymenttype = paymentsource.dataset.paymenttype;
                        const paymentId = paymenttype === 'card' ? "oscpaypal_acdc" : "oscpaypal";

                        let vaultedPaymentInput = document.createElement("input");
                        vaultedPaymentInput.type = "hidden";
                        vaultedPaymentInput.name = "vaultingpaymentsource";
                        vaultedPaymentInput.value = paymentsource.dataset.index;
                        document.getElementById("payment").appendChild(vaultedPaymentInput);

                        let paymentIdInput = document.createElement("input");
                        paymentIdInput.type = "hidden";
                        paymentIdInput.name = "paymentid";
                        paymentIdInput.value = paymentId;
                        document.getElementById("payment").appendChild(paymentIdInput);

                        document.getElementById("paymentNextStepBottom").disabled = false;
                        document.getElementById("paymentNextStepBottom").click();
                        document.getElementById("paymentNextStepBottom").disabled = true;
                    }
                });
            }
        };
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const paymentIdForm = document.getElementById('payment');
    if (null === paymentIdForm) {
        return;
    }

    registerClickListenerForPaymentMethodsRadioButtons();
    registerClickListenerForSavedVaultRadioButtons();
    registerClickListenerForTheVaultCheckoutButton();
});
