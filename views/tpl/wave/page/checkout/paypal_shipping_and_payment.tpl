[{assign var="sPaymentID" value=$payment->getId()}]
<div class="row">
    <div class="col-12 col-md-6" id="orderShipping">
        <form action="[{$oViewConf->getSslSelfLink()}]" method="post">
            <div class="hidden">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="cl" value="payment">
                <input type="hidden" name="fnc" value="">
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        [{oxmultilang ident="SHIPPING_CARRIER"}]
                        <button type="submit"
                                class="btn btn-sm btn-warning float-right submitButton largeButton edit-button"
                                title="[{oxmultilang ident="EDIT"}]">
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
        <div>
            <div class="card">
                <form action="[{$oViewConf->getSslSelfLink()}]" method="post">
                    <div class="card-header">
                        <h3 class="card-title">
                            [{oxmultilang ident="PAYMENT_METHOD"}]
                            [{$oViewConf->getHiddenSid()}]
                            <input type="hidden" name="cl" value="payment">
                            <input type="hidden" name="fnc" value="">
                            <button type="submit"
                                    class="btn btn-sm btn-warning float-right submitButton largeButton edit-button"
                                    title="[{oxmultilang ident="EDIT"}]">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                        </h3>
                    </div>
                </form>
                <div class="card-body">
                    [{$payment->oxpayments__oxdesc->value}]
                    [{if $sPaymentID == "oxidpaypal_acdc"}]
                        [{include file="modules/osc/paypal/oscpaypalacdc.tpl"}]
                    [{/if}]
                </div>
            </div>
        </div>
    </div>
</div>