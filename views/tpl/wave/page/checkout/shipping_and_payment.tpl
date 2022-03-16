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
                <div class="card-header">
                    <h3 class="card-title">
                        [{oxmultilang ident="PAYMENT_METHOD"}]
                        <a href="[{$oViewConf->getSslSelfLink()|cat:"cl=payment"}]" title="[{oxmultilang ident="EDIT"}]">
                            <span class="btn btn-sm btn-warning float-right submitButton largeButton edit-button">
                                <i class="fas fa-pencil-alt"></i>
                            </span>
                        </a>
                    </h3>
                </div>
                <div class="card-body">
                    [{$payment->oxpayments__oxdesc->value}]
                    [{if $sPaymentID == "oscpaypal_acdc"}]
                        [{include file="modules/osc/paypal/acdc_wave.tpl"}]
                    [{elseif $sPaymentID == "oscpaypal_pui"}]
                        [{include file="modules/osc/paypal/pui_wave.tpl"}]
                    [{/if}]
                </div>
            </div>
        </div>
    </div>
</div>