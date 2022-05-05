[{if 'oscpaypal_express'|array_key_exists:$oView->getPaymentList() && $oViewConf->isPayPalExpressSessionActive()}]
    [{assign var="config" value=$oViewConf->getPayPalCheckoutConfig()}]
    <div class="panel panel-default">
        <div class="card">
            <div class="panel-heading">
                <h3 class="panel-title">[{oxmultilang ident="OSC_PAYPAL_PAY_EXPRESS"}]</h3>
            </div>
            <div class="panel-body">
                <div class="pull-left">
                    [{oxmultilang ident="OSC_PAYPAL_PAY_PROCESSED"}]
                </div>
                <div class="pull-right">
                    <a class="btn btn-default" href="[{$oViewConf->getCancelPayPalPaymentUrl()}]">[{oxmultilang ident="OSC_PAYPAL_PAY_UNLINK"}]</a>
                </div>
                [{capture name="hide_payment"}]
                    [{literal}]
                        $(function () {
                            $('#payment > .panel.panel-default:first').hide();
                        });
                    [{/literal}]
                [{/capture}]
                [{oxscript add=$smarty.capture.hide_payment}]
            </div>
        </div>
    </div>
[{/if}]
