[{assign var="payment" value=$oView->getPayment()}]
[{if "oscpaypal" == $payment->getId()}]
    <div class="float-right ml-5 mt-3">
        <input type="hidden" name="vaultPayment" id="oscPayPalVaultPayment" value="">
    </div>
[{/if}]
[{if "oscpaypal_pui" == $payment->getId()}]
    [{if $oViewConf->isFlowCompatibleTheme()}]
        [{include file="modules/osc/paypal/checkout_order_btn_submit_bottom_flow.tpl"}]
    [{else}]
        [{include file="modules/osc/paypal/checkout_order_btn_submit_bottom_wave.tpl"}]
    [{/if}]
[{/if}]
[{$smarty.block.parent}]