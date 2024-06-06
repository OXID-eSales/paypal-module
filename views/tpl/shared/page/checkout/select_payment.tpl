<input id="payment_[{$sPaymentID}]" type="radio" name="paymentid" value="[{$sPaymentID}]" [{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]checked[{/if}] style="display:none;">
[{if $phpstorm}]<script>[{/if}]
        [{capture name="detailsApplePayScriptPaymentPage"}]
    const check_applepay = async () => {
        console.log('--- Start check_applepay ---');
        let error_message = "";

        if (!window.ApplePaySession) {
            error_message = "This device does not support Apple Pay";
        } else if (!ApplePaySession.canMakePayments()) {
            error_message = "This device, although an Apple device, is not capable of making Apple Pay payments";
        }

        if (error_message !== "") {
            console.error(error_message);
            const applePayInput = document.getElementById('payment_oscpaypal_apple_pay');
            if (applePayInput) {
                applePayInput.closest('.well.well-sm').remove(); // Remove the outer div if Apple Pay is not supported
            }
            throw new Error(error_message);
        }

        console.log('--- End check_applepay ---');
    };

    // Ensure the function runs when the script is loaded
    document.addEventListener('DOMContentLoaded', check_applepay);

    [{/capture}]
        [{if $phpstorm}]</script>[{/if}]
[{oxscript include="https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js" }]
[{oxscript add=$smarty.capture.detailsApplePayScriptPaymentPage}]
