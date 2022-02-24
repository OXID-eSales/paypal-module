[{if 'oscpaypal'|array_key_exists:$oView->getPaymentList()}]
    [{assign var="config" value=$oViewConf->getPayPalConfig()}]
    [{if $oViewConf->isPayPalSessionActive() || $config->showPayPalCheckoutButton()}]
        <div class="card-deck">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">[{oxmultilang ident="OSC_PAYPAL_PAY"}]</h3>
                </div>
                <div class="card-body oxEqualized">
                    [{if $oViewConf->isPayPalSessionActive()}]
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
                    [{elseif $config->showPayPalCheckoutButton()}]
                        <div class="text-left">
                            [{include file="oscpaypalsmartpaymentbuttons.tpl" buttonId="PayPalButtonPaymentPage" buttonClass="col-md-4 col-12"}]
                        </div>
                    [{/if}]
                </div>
            </div>
        </div>
    [{/if}]
[{/if}]
