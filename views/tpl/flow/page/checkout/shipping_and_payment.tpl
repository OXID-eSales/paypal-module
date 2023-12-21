[{assign var="sPaymentID" value=$payment->getId()}]
[{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
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
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    [{oxmultilang ident="PAYMENT_METHOD"}]
                    <a href="[{$sSelfLink|cat:"cl=payment"}]" title="[{oxmultilang ident="EDIT"}]">
                        <span class="btn btn-xs btn-warning pull-right submitButton largeButton">
                            <i class="fa fa-pencil"></i>
                        </span>
                    </a>
                </h3>
            </div>
            <div class="panel-body">
                [{if $vaultedPaymentDescription}]
                    [{$vaultedPaymentDescription}]
                [{elseif !$oscpaypal_executing_order}]
                    [{$payment->oxpayments__oxdesc->value}]
                    [{if $sPaymentID == "oscpaypal_acdc"}]
                       [{include file="modules/osc/paypal/acdc.tpl"}]
                    [{elseif $sPaymentID == "oscpaypal_pui"}]
                        [{include file="modules/osc/paypal/pui_flow.tpl"}]
                    [{/if}]
                [{/if}]
            </div>
        </div>
    </div>
</div>