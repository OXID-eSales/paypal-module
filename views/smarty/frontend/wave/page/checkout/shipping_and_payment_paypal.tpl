<div class="row">
    <div class="col-12 col-md-6" id="orderShipping">
        <form action="[{$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]" method="post">
            <div class="hidden">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="cl" value="payment">
                <input type="hidden" name="fnc" value="">
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        [{oxmultilang ident="SHIPPING_CARRIER"}]
                        <button type="submit" class="btn btn-sm btn-warning float-right submitButton largeButton edit-button" title="[{oxmultilang ident="EDIT"}]">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    </h3>
                </div>
                <div class="card-body">
                    [{assign var="oShipSet" value=$oView->getShipSet()}]
                    [{$oShipSet->oxdeliveryset__oxtitle->value}]
                </div>
            </div>
        </form>
    </div>
    <div class="col-12 col-md-6" id="orderPayment">
        <form action="[{$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]" method="post">
            <div class="hidden">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="cl" value="payment">
                <input type="hidden" name="fnc" value="">
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        [{oxmultilang ident="PAYMENT_METHOD"}]
                        <button type="submit" class="btn btn-sm btn-warning float-right submitButton largeButton edit-button" title="[{oxmultilang ident="EDIT"}]">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    </h3>
                </div>
                <div class="card-body">
                    [{assign var="payment" value=$oView->getPayment()}]
                    [{$payment->oxpayments__oxdesc->value}]
                    [{if $oscpaypal_isVaultingPossible}]
                        <br>
                        <br>
                        [{if $oViewConf->getPayPalCheckoutConfig()->isPayPalStandardRedirectFlow()}]
                            [{* redirect flow: no payment controller JS, the checkbox is sent with the order form *}]
                            <input type="checkbox" id="oscPayPalVaultPaymentCheckbox" name="vaultPayment" value="true" form="orderConfirmAgbBottom">
                        [{else}]
                            <input type="checkbox" id="oscPayPalVaultPaymentCheckbox" disabled>
                        [{/if}]
                        <label for="oscPayPalVaultPaymentCheckbox">[{oxmultilang ident="OSC_PAYPAL_VAULTING_SAVE"}]</label>
                    [{/if}]
                </div>
            </div>
        </form>
    </div>
</div>
