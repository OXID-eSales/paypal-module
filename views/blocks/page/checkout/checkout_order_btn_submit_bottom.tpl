[{assign var="payment" value=$oView->getPayment()}]
[{assign var="paymentId" value=$payment->getId()}]
[{assign var="oConfig" value=$oViewConf->getConfig()}]
[{assign var="PayPalSDKJS" value=$oConfig->getGlobalParameter("PayPalSDKJS")}]
[{if !$PayPalSDKJS}]
    [{capture assign="PayPalSDKJS"}]
        [{assign var="commitFlow" value=false}]
        [{if "oscpaypal" == $paymentId}]
            [{assign var="commitFlow" value=true}]
        [{/if}]
        [{include file="modules/osc/paypal/base_js.tpl" commitFlow=$commitFlow}]
    [{/capture}]
    [{$oConfig->setGlobalParameter("PayPalSDKJS", $PayPalSDKJS)}]
[{/if}]
[{if "oscpaypal_acdc" == $paymentId}]
    <button id="[{$paymentId}]" type="button" class="btn btn-lg btn-primary float-right pull-right submitButton nextStep largeButton">
        <i class="fa fa-check"></i> [{oxmultilang ident="SUBMIT_ORDER"}]
    </button>
[{/if}]

[{if "oscpaypal" == $paymentId}]
    <div id="[{$paymentId}]" class="paypal-button-container [{$buttonClass}] float-right pull-right"></div>
[{/if}]

[{if "oscpaypal_pui" == $paymentId}]
    [{if $oViewConf->isFlowCompatibleTheme()}]
        [{include file="modules/osc/paypal/checkout_order_btn_submit_bottom_flow.tpl"}]
    [{else}]
        [{include file="modules/osc/paypal/checkout_order_btn_submit_bottom_wave.tpl"}]
    [{/if}]
[{/if}]

[{if "oscpaypal_googlepay" == $paymentId}]
    [{include file="modules/osc/paypal/googlepay.tpl" buttonClass="paypal-button-wrapper large"}]
[{elseif "oscpaypal_applepay" == $paymentId}]
    [{include file="modules/osc/paypal/applepay.tpl" paymentId=$paymentId buttonClass="paypal-button-wrapper large"}]
    <div id="applepay-container" class="paypal-button-container paypal-button-wrapper paypal-button-right large"></div>
[{elseif
    "oscpaypal" != $paymentId &&
    "oscpaypal_acdc" != $paymentId
}]
    [{$smarty.block.parent}]
[{/if}]
