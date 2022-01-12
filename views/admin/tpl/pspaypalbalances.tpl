[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign box="list"}]

<div class="container-fluid">
    <br />
    <button id="toggleFilter" class="btn btn-info">
        [{oxmultilang ident="OSC_PAYPAL_FILTER"}]
    </button>
    [{capture assign="sPayPalBalancesJS"}]
        [{strip}]
            jQuery(document).ready(function(){
                jQuery("#filters").hide();
                jQuery("#toggleFilter").click(function(e) {
                    e.preventDefault();
                    jQuery("#filters").toggle();
                    jQuery("#balances").toggle();
                });
            });
        [{/strip}]
    [{/capture}]
    [{oxscript add=$sPayPalBalancesJS}]
    <form method="post" action="[{$oViewConf->getSelfLink()}]">
        <div id="filters">
            [{if !empty($error)}]
            <div class="alert alert-danger" role="alert">
                [{$error}]
            </div>
            [{/if}]
            <div class="row ppaltmessages">
                [{include file="_formparams.tpl" cl="PayPalBalanceController" lstrt=$lstrt actedit=$actedit oxid=$oxid fnc="" language=$actlang editlanguage=$actlang}]
                <div class="col-sm-4 col-md-3">
                    <div class="form-group">
                        <label for="asOfTimeFilter">[{oxmultilang ident="OSC_PAYPAL_AS_OF_TIME"}]</label>
                        <input type="date"
                               class="form-control"
                               id="asOfTimeFilter"
                               name="asOfTime"
                               value="[{if $oView->getAsOfTime()}][{$oView->getAsOfTime()}][{/if}]">
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="form-group">
                        <label for="currencyCodeFilter">[{oxmultilang ident="OSC_PAYPAL_CURRENCY_CODE"}]</label>
                        <select class="form-control"
                                id="currencyCodeFilter"
                                name="currencyCode">
                            <option value=""></option>
                            [{assign var="selectedCurrencyCode" value=$oView->getCurrencyCode()}]
                            [{foreach from=$oViewConf->getPayPalCurrencyCodes() item="currencyCode"}]
                                <option value="[{$currencyCode}]" [{if $currencyCode == $selectedCurrencyCode}]selected[{/if}]>
                                    [{$currencyCode}]
                                </option>
                            [{/foreach}]
                        </select>
                    </div>
                </div>
            </div>
            <div class="row ppmessages">
                <button class="btn btn-primary" type="submit">[{oxmultilang ident="OSC_PAYPAL_APPLY"}]</button>
            </div>
        </div>
    </form>

    [{if isset($balances)}]
        <div id="balances">
            <div class="row">
                <div class="col-sm-8">
                    <table class="table">
                        <tbody>
                            <tr class="ppaltmessages">
                                <td class="col-sm-2">
                                    <b>[{oxmultilang ident="OSC_PAYPAL_ACCOUNT_ID"}]</b>
                                </td>
                                <td>[{$balances->account_id}]</td>
                            </tr>
                            <tr class="ppmessages">
                                <td>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_AS_OF_TIME"}]</b>
                                </td>
                                <td>
                                    [{$balances->as_of_time|date_format:"%Y-%m-%d %H:%M:%S"}]
                                </td>
                            </tr>
                            <tr class="ppaltmessages">
                                <td>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_LAST_REFRESH_TIME"}]</b>
                                </td>
                                <td>
                                    [{$balances->last_refresh_time|date_format:"%Y-%m-%d %H:%M:%S"}]
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-8">
                    <table class="table">
                        <thead>
                            <tr class="ppaltmessages">
                                <th>[{oxmultilang ident="OSC_PAYPAL_CURRENCY_CODE"}]</th>
                                <th>[{oxmultilang ident="OSC_PAYPAL_AVAILABLE_BALANCE"}]</th>
                                <th>[{oxmultilang ident="OSC_PAYPAL_WITHHELD_BALANCE"}]</th>
                                <th>[{oxmultilang ident="OSC_PAYPAL_TOTAL_BALANCE"}]</th>
                            </tr>
                        </thead>
                        <tbody>
                            [{foreach from=$balances->balances item=balanceDetails}]
                                [{cycle values='ppmessages,ppaltmessages' assign=cellClass}]
                                <tr class="[{$cellClass}]">
                                    <td>[{$balanceDetails->currency}][{if $balanceDetails->primary}] <small>([{oxmultilang ident="OSC_PAYPAL_PRIMARY"}])</small>[{/if}]</td>
                                    <td>[{$balanceDetails->available_balance->value}]</td>
                                    <td>[{$balanceDetails->withheld_balance->value}]</td>
                                    <td>[{$balanceDetails->total_balance->value}]</td>
                                </tr>
                            [{/foreach}]
                       </tbody>
                    </table>
                </div>
            </div>
        </div>
    [{/if}]
</div>
[{include file="bottomitem.tpl"}]
