<dl>
    <dt>
        [{include file="modules/osc/paypal/select_payment.tpl"}]
        <label for="payment_[{$sPaymentID}]"><b>[{$paymentmethod->oxpayments__oxdesc->value}]</b></label>
        [{include file="modules/osc/paypal/googlepay.tpl" buttonId=$sPaymentID buttonClass="paypal-button-wrapper large"}]
    </dt>
</dl>
