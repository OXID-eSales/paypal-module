[{if $oViewConf->getIsVaultingActive()}]
    [{if $oViewConf->isVaultingAllowedForPayPal()}]
        <li class="list-group-item[{if $active_link == "oscPayPalVaulting"}] active[{/if}]">
            <a class="list-group-link" href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"|cat:"cl=oscaccountvault"}]" title="[{oxmultilang ident="OSC_PAYPAL_VAULTING_MENU"}]">[{oxmultilang ident="OSC_PAYPAL_VAULTING_MENU"}]</a>
        </li>
    [{/if}]
    [{if $oViewConf->isAcdcEligibility() && $oViewConf->isVaultingAllowedForACDC()}]
        <li class="list-group-item[{if $active_link == "oscPayPalVaultingCard"}] active[{/if}]">
            <a class="list-group-link" href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"|cat:"cl=oscaccountvaultcard"}]" title="[{oxmultilang ident="OSC_PAYPAL_VAULTING_MENU_CARD"}]">[{oxmultilang ident="OSC_PAYPAL_VAULTING_MENU_CARD"}]</a>
        </li>
    [{/if}]
[{/if}]
[{$smarty.block.parent}]
