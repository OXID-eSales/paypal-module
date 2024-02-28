[{if $vaultedPaymentSources}]
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 id="paymentHeader" class="card-title">[{oxmultilang ident="OSC_PAYPAL_VAULTING_VAULTED_PAYMENTS"}]</h3>
        </div>
        <div class="panel-body">
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
                >
                    [{oxmultilang ident="OSC_PAYPAL_CONTINUE_TO_NEXT_STEP"}] <i class="fa fa-caret-right"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        window.onload = function () {
            document.getElementById("paypalVaultCheckoutButton").onclick = function () {
                document.querySelectorAll(".vaulting_paymentsource").forEach(function (paymentsource) {
                    if (paymentsource.checked) {
                        document.getElementById("payment_oscpaypal").click();

                        let input = document.createElement("input");
                        input.type = "hidden";
                        input.name = "vaultingpaymentsource";
                        input.value = paymentsource.dataset.index;
                        document.getElementById("payment").appendChild(input);

                        document.getElementById("paymentNextStepBottom").click();
                    }
                });
            }
        }
    </script>
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
