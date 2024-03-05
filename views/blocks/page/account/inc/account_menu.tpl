[{if $oViewConf->getIsVaultingActive()}]
    <li class="list-group-item[{if $active_link == "oscPayPalVaulting"}] active[{/if}]">
        <a class="list-group-link" href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=oscaccountvault"}]" title="[{oxmultilang ident="OSC_PAYPAL_VAULTING_MENU"}]">[{oxmultilang ident="OSC_PAYPAL_VAULTING_MENU"}]</a>
    </li>
    <li class="list-group-item[{if $active_link == "oscPayPalVaultingCard"}] active[{/if}]">
        <a class="list-group-link" href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=oscaccountvaultcard"}]" title="[{oxmultilang ident="OSC_PAYPAL_VAULTING_MENU_CARD"}]">[{oxmultilang ident="OSC_PAYPAL_VAULTING_MENU_CARD"}]</a>
    </li>
[{/if}]
[{$smarty.block.parent}]