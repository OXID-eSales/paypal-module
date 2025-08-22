[{include file="@osc_paypal/frontend/shared/pui_fraudnet.tpl"}]
[{assign var="invadr" value=$oView->getInvoiceAddress()}]
[{if isset( $invadr.oxuser__oxbirthdate.month )}]
    [{assign var="iBirthdayMonth" value=$invadr.oxuser__oxbirthdate.month}]
[{elseif $oxcmp_user->oxuser__oxbirthdate->value && $oxcmp_user->oxuser__oxbirthdate->value != "0000-00-00"}]
    [{assign var="iBirthdayMonth" value=$oxcmp_user->oxuser__oxbirthdate->value|regex_replace:"/^([0-9]{4})[-]/":""|regex_replace:"/[-]([0-9]{1,2})$/":""}]
[{else}]
    [{assign var="iBirthdayMonth" value=0}]
[{/if}]

[{if isset( $invadr.oxuser__oxbirthdate.day )}]
    [{assign var="iBirthdayDay" value=$invadr.oxuser__oxbirthdate.day}]
[{elseif $oxcmp_user->oxuser__oxbirthdate->value && $oxcmp_user->oxuser__oxbirthdate->value != "0000-00-00"}]
    [{assign var="iBirthdayDay" value=$oxcmp_user->oxuser__oxbirthdate->value|regex_replace:"/^([0-9]{4})[-]([0-9]{1,2})[-]/":""}]
[{else}]
    [{assign var="iBirthdayDay" value=0}]
[{/if}]

[{if isset( $invadr.oxuser__oxbirthdate.year )}]
    [{assign var="iBirthdayYear" value=$invadr.oxuser__oxbirthdate.year}]
[{elseif $oxcmp_user->oxuser__oxbirthdate->value && $oxcmp_user->oxuser__oxbirthdate->value != "0000-00-00"}]
    [{assign var="iBirthdayYear" value=$oxcmp_user->oxuser__oxbirthdate->value|regex_replace:"/[-]([0-9]{1,2})[-]([0-9]{1,2})$/":""}]
[{else}]
    [{assign var="iBirthdayYear" value=0}]
[{/if}]

[{* Calculate age for initial display *}]
[{assign var="isUnder18" value=false}]
[{if $iBirthdayDay > 0 && $iBirthdayMonth > 0 && $iBirthdayYear > 0}]
    [{assign var="currentYear" value=$smarty.now|date_format:"%Y"}]
    [{assign var="currentMonth" value=$smarty.now|date_format:"%m"}]
    [{assign var="currentDay" value=$smarty.now|date_format:"%d"}]

    [{assign var="age" value=$currentYear-$iBirthdayYear}]
    [{if $currentMonth < $iBirthdayMonth || ($currentMonth == $iBirthdayMonth && $currentDay < $iBirthdayDay)}]
        [{assign var="age" value=$age-1}]
    [{/if}]

    [{if $age < 18}]
        [{assign var="isUnder18" value=true}]
    [{/if}]
[{/if}]

[{assign var="phonenumber" value=""}]
[{if isset($invadr.oxuser__oxfon)}]
    [{assign var="phonenumber" value=$invadr.oxuser__oxfon}]
[{else}]
    [{assign var="phonenumber" value=$oxcmp_user->oxuser__oxfon->value}]
[{/if}]
<p><br />[{oxmultilang ident="OSC_PAYPAL_PUI_HELP"}]</p>

<div id="card_container" class="card_container">
    <form id="pui_form">
        <div class="form-group oxDate [{if !$iBirthdayMonth || !$iBirthdayDay || !$iBirthdayYear || $isUnder18}]text-danger[{else}]text-success[{/if}]">
            <label class="control-label col-xs-12 col-lg-3 req">[{oxmultilang ident="OSC_PAYPAL_PUI_BIRTHDAY"}]</label>
            <div class="col-xs-3 col-lg-3">
                <input id="pui_required_birthdate_day" class="oxDay form-control" name="pui_required[birthdate][day]" type="text" maxlength="2" value="[{if $iBirthdayDay > 0}][{$iBirthdayDay}][{/if}]" placeholder="[{oxmultilang ident="DAY"}]" required="" />
            </div>
            <div class="col-xs-6 col-lg-3">
                <select id="pui_required_birthdate_month" class="oxMonth form-control selectpicker" name="pui_required[birthdate][month]" required="" />
                    <option value="" label="-">-</option>
                    [{section name="month" start=1 loop=13}]
                        <option value="[{$smarty.section.month.index}]" label="[{oxmultilang ident="MONTH_NAME_"|cat:$smarty.section.month.index}]" [{if $iBirthdayMonth == $smarty.section.month.index}] selected="selected" [{/if}]>
                            [{oxmultilang ident="MONTH_NAME_"|cat:$smarty.section.month.index}]
                        </option>
                    [{/section}]
                </select>
            </div>
            <div class="col-xs-3 col-lg-3">
                <input id="pui_required_birthdate_year" class="oxYear form-control" name="pui_required[birthdate][year]" type="text" maxlength="4" value="[{if $iBirthdayYear}][{$iBirthdayYear}][{/if}]" placeholder="[{oxmultilang ident="YEAR"}]" required="" />
            </div>
            <div class="col-lg-offset-3 col-lg-9 col-xs-12">
                <div class="help-block pui_required_birthdate_day_help pui_required_birthdate_month_help pui_required_birthdate_year_help">
                    <p class="text-danger hidden">[{oxmultilang ident="DD_FORM_VALIDATION_REQUIRED"}]</p>
                </div>
                <div class="help-block">
                    <p id="age-error-message" class="text-danger [{if !$isUnder18}]hidden[{/if}]">
                        [{oxmultilang ident="OSC_PAYPAL_PUI_AGE_WARNING"}]
                    </p>
                </div>
            </div>
        </div>

        <div class="form-group [{if !$phonenumber}]text-danger[{else}]text-success[{/if}]">
            <label for="pui_required_phonenumber" class="control-label col-xs-12 col-lg-3 req">[{oxmultilang ident="OSC_PAYPAL_PUI_PHONENUMBER"}]</label>
            <div class="col-xs-12 col-lg-9">
                <input id="pui_required_phonenumber" type="text" class="form-control" name="pui_required[phonenumber]" autocomplete="off" placeholder="[{oxmultilang ident="OSC_PAYPAL_PUI_PHONENUMBER_PLACEHOLDER"}]" value="[{$phonenumber}]" />
            </div>
            <div class="col-lg-offset-3 col-lg-9 col-xs-12">
                <div class="help-block pui_required_phonenumber_help">
                    <p class="text-danger hidden">[{oxmultilang ident="DD_FORM_VALIDATION_REQUIRED"}]</p>
                </div>
            </div>
        </div>
    </form>
</div>
[{oxifcontent ident="oscpaypalpuiconfirmation" object="oCont"}]
<br /><br /><div>[{$oCont->oxcontents__oxcontent->value}]</div>
[{/oxifcontent}]