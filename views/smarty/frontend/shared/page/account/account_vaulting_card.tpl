[{capture append="oxidBlock_content"}]
    [{assign var="template_title" value="OSC_PAYPAL_VAULTING_MENU_CARD"|oxmultilangassign}]

    <h1 class="page-header">[{oxmultilang ident="OSC_PAYPAL_VAULTING_MENU_CARD"}]</h1>

    [{if $oViewConf->isFlowCompatibleTheme()}]
        [{include file='@osc_paypal/frontend/flow/vaultedpaymentsources.tpl'}]
    [{else}]
        [{include file='@osc_paypal/frontend/wave/vaultedpaymentsources.tpl'}]
    [{/if}]

    [{insert name="oxid_tracker" title=$template_title}]
    [{/capture}]

[{capture append="oxidBlock_sidebar"}]
    [{include file="page/account/inc/account_menu.tpl" active_link="oscPayPalVaultingCard"}]
    [{/capture}]
[{include file="layout/page.tpl" sidebar="Left"}]
