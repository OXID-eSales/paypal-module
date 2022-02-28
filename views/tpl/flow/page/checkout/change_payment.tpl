[{if 'oscpaypal'|array_key_exists:$oView->getPaymentList()}]
    [{assign var="config" value=$oViewConf->getPayPalConfig()}]
    [{if $oViewConf->isPayPalSessionActive() || $config->showPayPalCheckoutButton()}]
        <div class="panel panel-default">
            <div class="card">
                <div class="panel-heading">
                    <h3 class="panel-title">[{oxmultilang ident="OSC_PAYPAL_PAY"}]</h3>
                </div>
                <div class="panel-body">
                    [{if $oViewConf->isPayPalSessionActive()}]
                        <div class="pull-left">
                            [{oxmultilang ident="OSC_PAYPAL_PAY_PROCESSED"}]
                        </div>
                        <div class="pull-right">
                            <a class="btn btn-default" href="[{$oViewConf->getCancelPayPalPaymentUrl()}]">[{oxmultilang ident="OSC_PAYPAL_PAY_UNLINK"}]</a>
                        </div>
                        [{capture name="hide_payment"}]
                            [{literal}]
                                $(function () {
                                    $('#payment > .card:first').hide();
                                });
                            [{/literal}]
                        [{/capture}]
                        [{oxscript add=$smarty.capture.hide_payment}]
                    [{elseif $config->showPayPalCheckoutButton()}]
                        <div class="text-left">
                            [{include file="oscpaypalsmartpaymentbuttons.tpl" buttonId="PayPalButtonPaymentPage" buttonClass="col-md-4 col-xs-12"}]
                        </div>
                    [{/if}]
                </div>
            </div>
        </div>
    [{/if}]
[{/if}]
