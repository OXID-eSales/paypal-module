[{$smarty.block.parent}]

[{assign var=vaultSuccess value=$oViewConf->getSessionVaultSuccess()}]
[{assign var=vaultApproved value=$oViewConf->getSessionVaultApproved()}]

[{if $vaultSuccess !== null}]
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
[{/if}]

[{if $vaultApproved !== null}]
    [{if $vaultApproved !== null}]
        [{if $vaultApproved}]
        <p class="alert alert-info">
            [{oxmultilang ident="OSC_PAYPAL_VAULTING_APPROVED"}]
        </p>
        [{/if}]
    [{/if}]
[{/if}]