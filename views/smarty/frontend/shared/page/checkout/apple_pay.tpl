[{if $phpstorm}]<script>[{/if}]
[{capture assign="detailsApplePayScriptPaymentPage"}]
    const check_applepay = async () => {
        let error_message = "";

        if (!window.ApplePaySession) {
            error_message = "This device does not support Apple Pay";
        } else if (!ApplePaySession.canMakePayments()) {
            error_message = "This device, although an Apple device, is not capable of making Apple Pay payments";
        }

        if (error_message !== "") {
            const applePayInput = document.getElementById('payment_[{$sPaymentID}]');
            if (applePayInput) {
                applePayInput.closest('.well.well-sm').remove(); // Remove the outer div if Apple Pay is not supported
            }
        }
    };
    // Ensure the function runs when the script is loaded
    document.addEventListener('DOMContentLoaded', check_applepay);

[{/capture}]
[{if $phpstorm}]</script>[{/if}]
[{oxscript include="https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js" }]
[{oxscript add=$detailsApplePayScriptPaymentPage}]
