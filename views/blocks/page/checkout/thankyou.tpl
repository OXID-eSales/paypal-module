[{$smarty.block.parent}]

[{if $oViewConf->getSessionVaultSuccess() !== null}]
    [{if $oViewConf->getSessionVaultSuccess()}]
        <p class="alert alert-success">
            [{oxmultilang ident="OSC_PAYPAL_VAULTING_SUCCESS"}]
        </p>
    [{else}]
        <p class="alert alert-danger">
            [{oxmultilang ident="OSC_PAYPAL_VAULTING_ERROR"}]
        </p>
    [{/if}]
[{/if}]