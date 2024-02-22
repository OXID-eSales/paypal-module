[{if $vaultedPaymentSources}]
    <div class="card">
        <div class="card-header">
            <h3 id="paymentHeader" class="card-title">[{oxmultilang ident="OSC_PAYPAL_VAULTING_VAULTED_PAYMENTS"}]</h3>
        </div>
        <div class="card-body">
            [{foreach from=$vaultedPaymentSources item=paymentDescription name="paymentSources" key="paymentType"}]
                [{assign var="iterator" value=$smarty.foreach.paymentSources.iteration-1}]

                <div class="well well-sm">
                    <dl>
                        <dt>
                            <input class="vaulting_paymentsource" name="vaulting_paymentsource" type="radio" id="paymentsource_[{$iterator}]" data-index="[{$iterator}]" data-paymenttype="[{$paymentType}]">
                            <label for="paymentsource_[{$iterator}]">[{$paymentDescription}]</label>
                        </dt>
                    </dl>
                </div>
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
[{if $oViewConf->isFlowCompatibleTheme()}]
    [{include file='modules/osc/paypal/change_payment_flow.tpl'}]
[{else}]
    [{include file='modules/osc/paypal/change_payment_wave.tpl'}]
[{/if}]
[{$smarty.block.parent}]
