function deselectRadioButtons(selector) {
    const radioButtons = document.querySelectorAll(selector);
    radioButtons.forEach(item => {
        item.checked = false;
    });
}

/**
 * Helper function to get the payment submit button
 * @returns {Element|null} The payment submit button element or null if not found
 */
function getPaymentSubmitButton() {

    const smartyButton = document.getElementById('paymentNextStepBottom');
    if (smartyButton) {
        return smartyButton;
    }

    const apexButton = document.querySelector('button[onclick*="document.getElementById(\'payment\').submit();"]');
    if (apexButton) {
        return apexButton;
    }

    return null;
}

function registerClickListenerForPaymentMethodsRadioButtons() {
    const paymentMethodsRadioButtons = document.getElementById('payment').querySelectorAll('[type="radio"]');
    paymentMethodsRadioButtons.forEach(function(paymentMethod) {
        paymentMethod.onclick = function() {
            const paypalVaultCheckoutButton = document.getElementById("paypalVaultCheckoutButton");
            if (paypalVaultCheckoutButton) {
                document.getElementById("paypalVaultCheckoutButton").disabled = true;
            }
            if (paymentMethod.checked) {
                getPaymentSubmitButton().disabled = false;
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
                    getPaymentSubmitButton().disabled = true;
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
                        vaultedPaymentInput.value = paymentsource.dataset.tokenId;
                        document.getElementById("payment").appendChild(vaultedPaymentInput);

                        let paymentIdInput = document.createElement("input");
                        paymentIdInput.type = "hidden";
                        paymentIdInput.name = "paymentid";
                        paymentIdInput.value = paymentId;
                        document.getElementById("payment").appendChild(paymentIdInput);

                        let nextPaymentStepButton = getPaymentSubmitButton();
                        nextPaymentStepButton.disabled = false;
                        nextPaymentStepButton.click();
                        nextPaymentStepButton.disabled = true;
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
