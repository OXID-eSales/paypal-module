[{assign var="payment" value=$oView->getPayment()}]
[{assign var="paymentId" value=$payment->getId()}]
[{$oViewConf->setSDKIsNecessary()}]

[{if "oscpaypal_acdc" == $paymentId}]
    <button id="[{$paymentId}]" type="button" class="btn btn-lg btn-primary pull-right submitButton nextStep largeButton">
        <i class="fa fa-check"></i> [{oxmultilang ident="SUBMIT_ORDER"}]
    </button>
[{/if}]

[{if "oscpaypal" == $paymentId}]
    <div id="[{$paymentId}]" class="paypal-button-container [{$buttonClass}] float-right pull-right"></div>
[{/if}]

[{assign var="sFileMTime" value=$oViewConf->getModulePath('osc_paypal','out/src/js/paypal-frontend-acdc-payment-controller.js')|filemtime}]
<script id="dev_scripts23433" src="[{$oViewConf->getModuleUrl('osc_paypal', 'out/src/js/paypal-frontend-acdc-payment-controller.js')|cat:"?"|cat:$sFileMTime}]"></script>


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
