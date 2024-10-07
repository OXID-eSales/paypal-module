[{assign var="payment" value=$oView->getPayment()}]

[{if "oscpaypal_acdc" == $payment->getId() || "oscpaypal_pui" == $payment->getId()}]
    [{assign var="sPaymentID" value=$payment->getId()}]
    [{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
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
                            <a href="[{$sSelfLink|cat:"cl=payment"}]" title="[{oxmultilang ident="EDIT"}]">
                                <span class="btn btn-sm btn-warning float-right submitButton largeButton edit-button">
                                    <i class="fas fa-pencil-alt"></i>
                                </span>
                            </a>
                        </h3>
                    </div>
                    <div class="card-body">
                        [{if !$oscpaypal_executing_order}]
                            [{$payment->oxpayments__oxdesc->value}]
                            [{if $sPaymentID == "oscpaypal_acdc"}]
                                [{include file="@osc_paypal/frontend/acdc.tpl"}]
                            [{elseif $sPaymentID == "oscpaypal_pui"}]
                                [{include file="@osc_paypal/frontend/pui_flow.tpl"}]
                            [{/if}]
                        [{/if}]
                    </div>
                </div>
            </div>
        </div>
    </div>
[{else}]
    [{$smarty.block.parent}]
[{/if}]