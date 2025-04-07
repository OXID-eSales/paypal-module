[{assign var="config" value=$oViewConf->getPayPalCheckoutConfig()}]

[{if $oViewConf->isPayPalCheckoutActive()}]
    <script>
        (function (){
            let PayPalButtonStyleConfigurator = function(){

                this.getButtonStyle = function (){
                    return {
                        layout: '[{$config->getPayPalButtonStyleLayout()}]',
                        color:  '[{$config->getPayPalButtonStyleColor()}]',
                        shape:  '[{$config->getPayPalButtonStyleShape()}]',
                        label:  '[{$config->getPayPalButtonStyleLabel()}]'
                    }
                }
            }

            window.PayPalButtonStyleConfigurator = new PayPalButtonStyleConfigurator();
        })();
    </script>
[{/if}]