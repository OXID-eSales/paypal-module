[{block name="oscpaypal_googlepay"}]
    [{oxhasrights ident="PAYWITHGOOGLEPAY"}]
        [{assign var="oConfig" value=$oViewConf->getConfig()}]
    [{assign var="PayPalSDKJS" value=$oConfig->getGlobalParameter("PayPalSDKJS")}]
    [{if !$PayPalSDKJS}]
        [{capture assign="PayPalSDKJS"}]
            [{include file="@osc_paypal/frontend/shared/layout/base_js.tpl" commitFlow=$commitFlow}]
        [{/capture}]
    [{$oConfig->setGlobalParameter("PayPalSDKJS", $PayPalSDKJS)}]
    [{/if}]
        [{assign var="sToken" value=$oViewConf->getSessionChallengeToken()}]
        [{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
        [{assign var="oPPconfig" value=$oViewConf->getPayPalCheckoutConfig()}]
        [{assign var="oConfig" value=$oViewConf->getConfig()}]
        [{assign var="bGooglePayDelivery" value=$oConfig->getConfigParam('oscPayPalUseGooglePayAddress')}]
        <div class="google-pay-loading-container paypal-button-right">
            <img src="[{$oViewConf->getModuleUrl('osc_paypal', 'img/loading.svg')}]" width="24" height="24" alt="loading animation"/>
        </div>
        <div id="google_pay_button_data_container"
             class="paypal-button-container paypal-button-wrapper paypal-button-right large"
             data-token="[{$sToken}]"
             data-self-link="[{$sSelfLink}]"
             data-is-sandbox="[{$oPPconfig->isSandbox()}]"
            data-use-google-pay-address="[{$bGooglePayDelivery}]"
             data-merchant-name="[{$oxcmp_shop->oxshops__oxname->value|oxescape}]"
             data-total-price="[{$oxcmp_basket->getPriceForPayment()}]"
             data-currency="[{$currency->name}]"
             data-delivery-address-md5="[{$oView->getDeliveryAddressMD5()}]"
             data-language="[{$oView->getActiveLangAbbr()|lower}]"
             data-loading-container-class-name="google-pay-loading-container"
        ></div>

        <script>
            window.googlePayReady = new Promise(resolve => {
                window.onGooglePayLoaded = resolve;
            });
        </script>
        <script async src="https://pay.google.com/gp/p/js/pay.js" onload="window.onGooglePayLoaded()"></script>
        <div id="[{$payment->getId()}]" class="paypal-button-container paypal-button-wrapper paypal-button-right large"></div>
    [{/oxhasrights}]
[{/block}]

