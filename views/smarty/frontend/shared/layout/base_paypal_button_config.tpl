[{assign var="config" value=$oViewConf->getPayPalCheckoutConfig()}]

[{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
    <script>
        (function (){
            const PayPalButtonStyleConfigurator = function(){
                return {
                    layout: '[{$config->getPayPalButtonStyleLayout()}]',
                    color:  '[{$config->getPayPalButtonStyleColor()}]',
                    shape:  '[{$config->getPayPalButtonStyleShape()}]',
                    label:  '[{$config->getPayPalButtonStyleLabel()}]'
                }
            }
            window.PayPalButtonStyle = new PayPalButtonStyleConfigurator();
            const PayPalI18nConfigurator = function(){
                return {
                    OSC_PAYPAL_ACDC_CARD_NUMBER : "[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_NUMBER"}]",
                    OSC_PAYPAL_ACDC_CARD_EXDATE : "[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_EXDATE"}]",
                    OSC_PAYPAL_ACDC_CARD_CVV : "[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_CVV"}]",
                    OSC_PAYPAL_ACDC_CARD_NAME_ON_CARD : "[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_NAME_ON_CARD"}]",
                    OSC_PAYPAL_ACDC_ERROR_MISSING_NAME: "[{oxmultilang ident="OSC_PAYPAL_ACDC_ERROR_MISSING_NAME"}]",
                    OSC_PAYPAL_ACDC_ERROR_MISSING_NUMBER: "[{oxmultilang ident="OSC_PAYPAL_ACDC_ERROR_MISSING_NUMBER"}]",
                    OSC_PAYPAL_ACDC_ERROR_MISSING_CVV: "[{oxmultilang ident="OSC_PAYPAL_ACDC_ERROR_MISSING_CVV"}]",
                    OSC_PAYPAL_ACDC_ERROR_MISSING_EXDATE: "[{oxmultilang ident="OSC_PAYPAL_ACDC_ERROR_MISSING_EXDATE"}]",
                    OSC_PAYPAL_ACDC_ERROR_INBOX: "[{oxmultilang ident="OSC_PAYPAL_ACDC_ERROR_INBOX"}]",
                    OSC_PAYPAL_UNKNOWN_ERROR: "[{oxmultilang ident="OSC_PAYPAL_UNKNOWN_ERROR"}]",
                    OSC_PAYPAL_AUTHORIZATION_DENIED_ERROR: "[{oxmultilang ident="OSC_PAYPAL_AUTHORIZATION_DENIED_ERROR"}]",
                    READ_AND_CONFIRM_TERMS: "[{oxmultilang ident="READ_AND_CONFIRM_TERMS"}]",
                }
            }
            window.PayPalI18n = new PayPalI18nConfigurator();
            const PayPalExpressSessionConfigurator = function(){
                return {
                    cancelPayPalExpressSession: async function (){
                        await fetch('[{$sSelfLink|cat:"cl=ajaxpay&fnc=cancelPayPalSession"}]');
                    },
                    started: false
                }
            }
            window.PayPalExpressSession = new PayPalExpressSessionConfigurator();
        })();
    </script>
