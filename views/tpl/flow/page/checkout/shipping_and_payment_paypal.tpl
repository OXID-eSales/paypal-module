<div class="row">
    <div class="col-xs-12 col-md-6" id="orderShipping">
        <form action="[{$oViewConf->getSslSelfLink()}]" method="post">
            <div class="hidden">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="cl" value="payment">
                <input type="hidden" name="fnc" value="">
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        [{oxmultilang ident="SHIPPING_CARRIER"}]
                        <button type="submit" class="btn btn-xs btn-warning pull-right submitButton largeButton" title="[{oxmultilang ident="EDIT"}]">
                            <i class="fa fa-pencil"></i>
                        </button>
                    </h3>
                </div>
                <div class="panel-body">
                    [{assign var="oShipSet" value=$oView->getShipSet()}]
                    [{$oShipSet->oxdeliveryset__oxtitle->value}]
                </div>
            </div>
        </form>
    </div>
    <div class="col-xs-12 col-md-6" id="orderPayment">
        <form action="[{$oViewConf->getSslSelfLink()}]" method="post">
            <div class="hidden">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="cl" value="payment">
                <input type="hidden" name="fnc" value="">
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        [{oxmultilang ident="PAYMENT_METHOD"}]
                        <button type="submit" class="btn btn-xs btn-warning pull-right submitButton largeButton" title="[{oxmultilang ident="EDIT"}]">
                            <i class="fa fa-pencil"></i>
                        </button>
                    </h3>
                </div>
                <div class="panel-body">
                    [{assign var="payment" value=$oView->getPayment()}]
                    [{$payment->oxpayments__oxdesc->value}]
                    [{if $oscpaypal_payment_saveable}]
                        <br>
                        <br>
                        <input type="checkbox" id="oscPayPalVaultPaymentCheckbox" onclick="setVaultingCheckbox()">
                        <label for="oscPayPalVaultPaymentCheckbox">[{oxmultilang ident="OSC_PAYPAL_VAULTING_SAVE"}]</label>
                    [{/if}]
                </div>
            </div>
        </form>
    </div>
</div>

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
</script>