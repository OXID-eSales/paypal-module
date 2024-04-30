[{if 'oscpaypal_express'|array_key_exists:$oView->getPaymentList() && $oViewConf->isPayPalExpressSessionActive()}]
    [{assign var="config" value=$oViewConf->getPayPalCheckoutConfig()}]
    <div class="card-deck">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">[{oxmultilang ident="OSC_PAYPAL_PAY_EXPRESS"}]</h3>
            </div>
            <div class="card-body oxEqualized">
                <div class="row">
                    <div class="col-12 col-md-6">
                        [{oxmultilang ident="OSC_PAYPAL_PAY_PROCESSED"}]
                    </div>
                    <div class="col-12 col-md-6 text-right">
                        <a class="btn btn-outline-dark" href="[{$oViewConf->getCancelPayPalPaymentUrl()}]">[{oxmultilang ident="OSC_PAYPAL_PAY_UNLINK"}]</a>
                    </div>
                </div>
                [{capture name="hide_payment"}]
                    [{literal}]
                        $(function () {
                            $('#payment > .card:first').hide();
                        });
                    [{/literal}]
                [{/capture}]
                [{oxscript add=$smarty.capture.hide_payment}]
            </div>
        </div>
    </div>
[{/if}]

[{$smarty.block.parent}]