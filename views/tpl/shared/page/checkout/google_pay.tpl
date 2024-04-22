<div class="well well-sm">
    <dl>
        <dt>
            <input id="payment_[{$sPaymentID}]" type="radio" name="paymentid" value="[{$sPaymentID}]" [{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]checked[{/if}]>
            <label for="payment_[{$sPaymentID}]"><b>[{$paymentmethod->oxpayments__oxdesc->value}]</b></label>
        </dt>
    </dl>
</div>
