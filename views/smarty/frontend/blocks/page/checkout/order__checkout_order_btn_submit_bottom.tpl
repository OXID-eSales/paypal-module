[{assign var="payment" value=$oView->getPayment()}]
[{if "oscpaypal" == $payment->getId()}]
    <input type="hidden" name="vaultPayment" id="oscPayPalVaultPayment" value="">
    [{capture name="oscpaypal_madClickPrevention"}]
        const submitButton = document.querySelector('#orderConfirmAgbBottom .submitButton');
        const orderConfirmAgbBottom = document.getElementById('orderConfirmAgbBottom');

        submitButton.addEventListener('click', function() {
        event.preventDefault();
        this.disabled = true;
        orderConfirmAgbBottom.submit();
        });
    [{/capture}]
    [{oxscript add=$smarty.capture.oscpaypal_madClickPrevention}]
[{/if}]
[{$smarty.block.parent}]
