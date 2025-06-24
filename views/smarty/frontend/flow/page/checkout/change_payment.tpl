[{if $vaultedPaymentSources}]
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 id="paymentHeader" class="card-title">[{oxmultilang ident="OSC_PAYPAL_VAULTING_VAULTED_PAYMENTS"}]</h3>
        </div>
        <div class="panel-body">
            [{foreach from=$vaultedPaymentSources item=vaultedPayment key="paymentTokenId"}]
                <div class="well well-sm">
                    <dl>
                        <dt>
                            <input type="radio" name="vaulting_paymentsource" class="vaulting_paymentsource"
                                   id="paymenttoken_[{$vaultedPayment.id}]"
                                   data-token-id="[{$vaultedPayment.id}]"
                                   data-paymenttype="[{$vaultedPayment.type}]"
                            >
                            <label for="paymenttoken_[{$vaultedPayment.id}]">[{$vaultedPayment.label}]</label>
                        </dt>
                    </dl>
                </div>
            [{/foreach}]

            <div class="text-right">
                <button type="submit" name="userform"
                        class="btn btn-primary pull-right submitButton nextStep largeButton"
                        id="paypalVaultCheckoutButton"
                        disabled
                >
                    [{oxmultilang ident="OSC_PAYPAL_CONTINUE_TO_NEXT_STEP"}] <i class="fa fa-caret-right"></i>
                </button>
            </div>
        </div>
    </div>
[{/if}]

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
