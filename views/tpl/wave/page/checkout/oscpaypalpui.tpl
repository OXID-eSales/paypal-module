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

<p><br />[{oxmultilang ident="OSC_PAYPAL_PUI_HELP"}]</p>

<div id="card_container" class="card_container">
    <form id="pui_form">
        <div class="form-group row oxDate">
            <label class="control-label col-12 col-lg-3 req">[{oxmultilang ident="OSC_PAYPAL_PUI_BIRTHDAY"}]</label>
            <div class="col-3 col-lg-3">
                <input id="oxDay" class="oxDay form-control" name="invadr[oxuser__oxbirthdate][day]" type="text" maxlength="2" value="[{if $iBirthdayDay > 0}][{$iBirthdayDay}][{/if}]" placeholder="[{oxmultilang ident="DAY"}]" required="" />
            </div>
            <div class="col-6 col-lg-3">
                <select class="oxMonth form-control selectpicker" name="invadr[oxuser__oxbirthdate][month]" required="" />
                    <option value="" label="-">-</option>
                    [{section name="month" start=1 loop=13}]
                        <option value="[{$smarty.section.month.index}]" label="[{$smarty.section.month.index}]" [{if $iBirthdayMonth == $smarty.section.month.index}] selected="selected" [{/if}]>
                            [{oxmultilang ident="MONTH_NAME_"|cat:$smarty.section.month.index}]
                        </option>
                    [{/section}]
                </select>
            </div>
            <div class="col-3 col-lg-3">
                <input id="oxYear" class="oxYear form-control" name="invadr[oxuser__oxbirthdate][year]" type="text" maxlength="4" value="[{if $iBirthdayYear}][{$iBirthdayYear}][{/if}]" placeholder="[{oxmultilang ident="YEAR"}]" required="" />
            </div>
            <div class="col-lg-offset-3 col-lg-9 col-12">
                [{include file="message/inputvalidation.tpl" aErrors=$aErrors.oxuser__oxbirthdate}]
                <div class="help-block"></div>
            </div>
        </div>

        <div class="form-group row">
            <label for="pui-phonenumber" class="control-label col-12 col-lg-3 req">[{oxmultilang ident="OSC_PAYPAL_PUI_PHONENUMBER"}]</label>
            <div class="col-12 col-lg-9">
                <input type="text" id="pui-birthday" class="form-control" name="pui-phonenumber" autocomplete="off" placeholder="[{oxmultilang ident="OSC_PAYPAL_PUI_PHONENUMBER_PLACEHOLDER"}]" value="[{if isset( $invadr.oxuser__oxfon )}][{$invadr.oxuser__oxfon}][{else}][{$oxcmp_user->oxuser__oxfon->value}][{/if}]" />
            </div>
        </div>
    </form>
</div>
[{oxifcontent ident="oscpaypalpuiconfirmation" object="oCont"}]
<br /><br /><div>[{$oCont->oxcontents__oxcontent->value}]</div>
[{/oxifcontent}]