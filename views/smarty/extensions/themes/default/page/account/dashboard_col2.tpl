[{$smarty.block.parent}]
[{* Tiles for the vaulted PayPal accounts and cards - the same entries and conditions as in the
    account menu (account_menu.tpl). Wave uses Bootstrap 4 cards, Flow Bootstrap 3 panels. *}]
[{if $oViewConf->getIsVaultingActive()}]
    [{if $oViewConf->isVaultingAllowedForPayPal()}]
        [{if $oViewConf->isFlowCompatibleTheme()}]
            <div class="panel panel-default">
                <div class="panel-heading">
                    <a id="linkAccountPayPalVaulting" href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=oscaccountvault"}]">[{oxmultilang ident="OSC_PAYPAL_VAULTING_MENU"}]</a>
                    <a href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=oscaccountvault"}]" class="btn btn-default btn-xs pull-right">
                        <i class="fa fa-arrow-right"></i>
                    </a>
                </div>
                <div class="panel-body">[{oxmultilang ident="OSC_PAYPAL_VAULTING_DASHBOARD_PAYPAL"}]</div>
            </div>
        [{else}]
            <div class="card">
                <div class="card-header">
                    <a id="linkAccountPayPalVaulting" href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=oscaccountvault"}]">[{oxmultilang ident="OSC_PAYPAL_VAULTING_MENU"}]</a>
                    <a href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=oscaccountvault"}]" class="btn btn-outline-dark btn-sm float-right edit-button">
                        <i class="fa fa-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">[{oxmultilang ident="OSC_PAYPAL_VAULTING_DASHBOARD_PAYPAL"}]</div>
            </div>
        [{/if}]
    [{/if}]
    [{if $oViewConf->isAcdcEligibility() && $oViewConf->isVaultingAllowedForACDC()}]
        [{if $oViewConf->isFlowCompatibleTheme()}]
            <div class="panel panel-default">
                <div class="panel-heading">
                    <a id="linkAccountPayPalVaultingCard" href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=oscaccountvaultcard"}]">[{oxmultilang ident="OSC_PAYPAL_VAULTING_MENU_CARD"}]</a>
                    <a href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=oscaccountvaultcard"}]" class="btn btn-default btn-xs pull-right">
                        <i class="fa fa-arrow-right"></i>
                    </a>
                </div>
                <div class="panel-body">[{oxmultilang ident="OSC_PAYPAL_VAULTING_DASHBOARD_CARD"}]</div>
            </div>
        [{else}]
            <div class="card">
                <div class="card-header">
                    <a id="linkAccountPayPalVaultingCard" href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=oscaccountvaultcard"}]">[{oxmultilang ident="OSC_PAYPAL_VAULTING_MENU_CARD"}]</a>
                    <a href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=oscaccountvaultcard"}]" class="btn btn-outline-dark btn-sm float-right edit-button">
                        <i class="fa fa-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">[{oxmultilang ident="OSC_PAYPAL_VAULTING_DASHBOARD_CARD"}]</div>
            </div>
        [{/if}]
    [{/if}]
[{/if}]
