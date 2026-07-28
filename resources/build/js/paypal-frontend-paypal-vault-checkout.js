function deselectRadioButtons(selector) {
    const radioButtons = document.querySelectorAll(selector);
    radioButtons.forEach(item => {
        item.checked = false;
    });
}

/**
 * Default selector for the payment step's "next"/submit button.
 *
 * The button is not reliably selectable across themes: OXID6 core / Smarty themes
 * (and the AmazonPay module) render it with the id "paymentNextStepBottom", whereas
 * OXID7 Apex/Twig renders a button without a stable hook, identifiable only by its
 * inline onclick. The onclick JS differs between theme versions
 * (e.g. document.getElementById('payment').submit() vs
 * document.querySelector('#payment').requestSubmit()), so we match loosely on the
 * onclick referencing the payment form ("payment") and a submit call ("ubmit"
 * covers submit / requestSubmit / Submit).
 *
 * Themes that render this button differently can override the selector by setting
 * window.oscPayPalPaymentSubmitButtonSelector (via a theme template or additional
 * JS). It is read at call time, so the override works regardless of script load order.
 *
 * @type {string}
 */
const OSC_PAYPAL_DEFAULT_PAYMENT_SUBMIT_BUTTON_SELECTOR =
    '#paymentNextStepBottom, button[onclick*="payment"][onclick*="ubmit"]';

/**
 * Helper function to get the payment submit button
 * @returns {Element|null} The payment submit button element or null if not found
 */
function getPaymentSubmitButton() {
    const selector = window.oscPayPalPaymentSubmitButtonSelector ||
        OSC_PAYPAL_DEFAULT_PAYMENT_SUBMIT_BUTTON_SELECTOR;

    return document.querySelector(selector);
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
                const paymentSubmitButton = getPaymentSubmitButton();
                if (paymentSubmitButton) {
                    paymentSubmitButton.disabled = false;
                }
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
                    const paypalVaultCheckoutButton = document.getElementById("paypalVaultCheckoutButton");
                    if (paypalVaultCheckoutButton) {
                        paypalVaultCheckoutButton.disabled = false;
                    }
                    const paymentSubmitButton = getPaymentSubmitButton();
                    if (paymentSubmitButton) {
                        paymentSubmitButton.disabled = true;
                    }
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
                        if (nextPaymentStepButton) {
                            nextPaymentStepButton.disabled = false;
                            nextPaymentStepButton.click();
                            nextPaymentStepButton.disabled = true;
                        }
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
