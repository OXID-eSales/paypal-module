[{capture append="oxidBlock_content"}]
    [{assign var="template_title" value="OSC_PAYPAL_VAULTING_MENU"|oxmultilangassign}]

    <h1 class="page-header">[{oxmultilang ident="OSC_PAYPAL_VAULTING_MENU"}]</h1>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">[{oxmultilang ident="OSC_PAYPAL_VAULTING_SAVE_INSTRUCTION"}]</h3>
        </div>
        <div class="card-body">
            <p id="PayPalVaultingSuccess" class="alert alert-success" style="display: none">[{oxmultilang ident="OSC_PAYPAL_VAULTING_SUCCESS"}]</p>
            <p id="PayPalVaultingFailure" class="alert alert-danger" style="display: none">[{oxmultilang ident="OSC_PAYPAL_VAULTING_ERROR"}]</p>
            <div id="PayPalButtonVaulting" class="paypal-button-container paypal-button-wrapper large"></div>
        </div>
    </div>
    <script>
        window.onload = function () {
            paypal.Buttons({
                               createVaultSetupToken: async () => {
                                   const result = await fetch(
                                       "[{oxgetseourl ident=$oViewConf->getSslSelfLink()}]&cl=osctokencontroller&fnc=generatesetuptoken&XDEBUG_SESSION_START=1",
                                       { method: "POST"
                                       })
                                   const { id } = await result.json();
                                   return id;
                               },
                               onApprove: async ({ vaultSetupToken }) => {
                                   const result = await fetch(
                                       "[{oxgetseourl ident=$oViewConf->getSslSelfLink()}]&cl=osctokencontroller&fnc=generatepaymenttoken&XDEBUG_SESSION_START=1&token="+vaultSetupToken,
                                       {method: 'POST'
                                       })
                                   const status = await result.json();

                                   if(status.state === "SUCCESS") {
                                       showSuccessMessage();
                                   }else {
                                       showFailureMessage();
                                   }
                               },
                               onError: (error) => {
                                   //TODO
                               }
                           }).render("#PayPalButtonVaulting");

            function showSuccessMessage() {
                $('#PayPalButtonVaulting').hide();
                $('#PayPalVaultingSuccess').show();
            }

            function showFailureMessage() {
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