[{if $vaultedPaymentSources}]
    <div class="card">
        <div class="card-header">
            <h3 id="paymentHeader" class="card-title">[{oxmultilang ident="OSC_PAYPAL_VAULTING_VAULTED_PAYMENTS"}]</h3>
        </div>
        <div class="card-body">
            [{assign var="iterator" value=0}]
            [{foreach from=$vaultedPaymentSources item=vaultedPayment key="paymentType"}]
                [{foreach from=$vaultedPayment item=paymentDescription name="paymentSources"}]
                    <div class="well well-sm">
                        <dl>
                            <dt>
                                <input class="vaulting_paymentsource" name="vaulting_paymentsource" type="radio" id="paymentsource_[{$iterator}]" data-index="[{$iterator}]" data-paymenttype="[{$paymentType}]">
                                <label for="paymentsource_[{$iterator}]">[{$paymentDescription}]</label>
                            </dt>
                        </dl>
                    </div>
                    [{math assign="iterator" equation="x+1" x=$iterator}]
                [{/foreach}]
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
