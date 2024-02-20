[{$smarty.block.parent}]

[{assign var=vaultSuccess value=$oViewConf->getSessionVaultSuccess()}]

[{if $vaultSuccess !== null}]
    [{if $vaultSuccess}]
        <p class="alert alert-success">
            [{oxmultilang ident="OSC_PAYPAL_VAULTING_SUCCESS"}]
        </p>
    [{else}]
        <p class="alert alert-danger">
            [{oxmultilang ident="OSC_PAYPAL_VAULTING_ERROR"}]
        </p>
    [{/if}]
[{/if}]