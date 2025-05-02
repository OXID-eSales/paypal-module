[{assign var="config" value=$oViewConf->getPayPalCheckoutConfig()}]
[{capture append="oxidBlock_content"}]
    [{assign var="template_title" value="OSC_PAYPAL_VAULTING_MENU"|oxmultilangassign}]

    [{if $oViewConf->isFlowCompatibleTheme()}]
        [{include file='modules/osc/paypal/account_vaulting_paypal_flow.tpl'}]
    [{else}]
        [{include file='modules/osc/paypal/account_vaulting_paypal_wave.tpl'}]
    [{/if}]

    <script>
        window.onload = function () {
            paypal.Buttons({
                   style: Object.assign(
                       PayPalButtonStyle,
                       {
                           label: 'checkout'
                       }
                   ),
                   createVaultSetupToken: async () => {
                       const result = await fetch(
                           "[{oxgetseourl ident=$oViewConf->getGenerateSetupTokenLink()}]",
                           { method: "POST"
                           })
                       const { id } = await result.json();
                       return id;
                   },
                   onApprove: async ({ vaultSetupToken }) => {
                       const result = await fetch(
                           "[{oxgetseourl ident=$oViewConf->getGeneratePaymentTokenLink()}]"+vaultSetupToken,
                           {method: 'POST'
                           })
                       const status = await result.json();

                       if (status.state === "SUCCESS") {
                           showSuccessMessage();
                       } else {
                           showFailureMessage();
                       }
                   },
                   onError: (error) => {
                       console.log(error);
                   },
               }).render("#PayPalButtonVaulting");

            function showSuccessMessage() {
                $('#PayPalButtonVaulting').hide();
                $('#PayPalVaultingFailure').hide();
                $('#PayPalVaultingSuccess').show();
            }

            function showFailureMessage() {
                $('#PayPalVaultingSuccess').hide();
                $('#PayPalVaultingFailure').show();
            }
        };
    </script>

    [{include file="modules/osc/paypal/vaultedpaymentsources.tpl"}]

    [{insert name="oxid_tracker" title=$template_title}]
[{/capture}]

[{capture append="oxidBlock_sidebar"}]
    [{include file="page/account/inc/account_menu.tpl" active_link="oscPayPalVaulting"}]
[{/capture}]
[{include file="layout/page.tpl" sidebar="Left"}]
