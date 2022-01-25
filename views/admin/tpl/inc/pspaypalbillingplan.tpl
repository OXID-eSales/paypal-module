[{assign var="oxid" value=$oView->getEditObjectId()}]
[{assign var="edit" value=$oView->getEditObject()}]
[{assign var="categories" value=$oView->getCategories()}]
[{assign var="types" value=$oView->getTypes()}]
[{assign var="images" value=$oView->getDisplayImages()}]
[{assign var="productUrl" value=$oView->getProductUrl()}]
[{assign var="hasLinkedObject" value=$oView->hasLinkedObject()}]
[{assign var="hasSubscriptionPlan" value=$oView->hasSubscriptionPlan()}]
[{assign var="defaultIntervals" value=$oView->getIntervalDefaults()}]
[{assign var="defaultTenureTypes" value=$oView->getTenureTypeDefaults()}]
[{assign var="defaultTotalCycles" value=$oView->getTotalCycleDefaults()}]
[{assign var="currencyCodes" value=$oView->getCurrencyCodes()}]
[{assign var="config" value=$oViewConf->getPayPalConfig()}]
[{assign var="BillingPlanAction" value='saveBillingPlans'}]
[{if $editBillingPlanId}]
    [{assign var="BillingPlanAction" value='patch'}]
[{/if}]

<form name="billingPlanForm" id="billingPlanForm" action="[{ $oViewConf->getSelfLink() }]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="cl" value="oscpaypalsubscribe">
    <input type="hidden" name="fnc" value="[{$BillingPlanAction}]">
    <input type="hidden" name="oxid" value="[{$oxid}]">
    [{*
        <input type="hidden" name="paypalProductId" value="[{$oView->getPayPalProductId()}]">
    *}]
    <input type="hidden" name="auto_bill_outstanding" value="[{if $config->getAutoBillOutstanding()}]true[{else}]false[{/if}]">
    <input type="hidden" name="setup_fee_failure_action" value="[{$config->getSetupFeeFailureAction()}]">
    <input type="hidden" name="payment_failure_threshold" value="[{$config->getPaymentFailureThreshold()}]">

    [{assign var="setupFee" value=0}]
    [{assign var="billingPlanName" value=""}]
    [{assign var="billingPlanDescription" value=""}]
    [{assign var="taxPercentage" value=""}]
    [{assign var="taxInclusive" value=0}]
    [{assign var="editBillingPlan" value=""}]
    [{if $editBillingPlanId && $subscriptionPlansList}]
        [{foreach from=$subscriptionPlansList item=value key=name}]
            [{if $editBillingPlanId == $value->id}]
                [{assign var="editBillingPlan" value=$value}]
                [{assign var="setupFee" value=$editBillingPlan->payment_preferences->setup_fee->value|number_format:2}]
                [{assign var="billingPlanName" value=$editBillingPlan->name}]
                [{assign var="billingPlanDescription" value=$editBillingPlan->description}]
                [{assign var="taxPercentage" value=$editBillingPlan->taxes->percentage}]
                [{assign var="taxInclusive" value=$editBillingPlan-taxes->inclusive}]
            [{/if}]
        [{/foreach}]
    [{/if}]

    <table cellspacing="0" cellpadding="0" border="0" width="98%" style="border: 1px solid #cccccc; padding: 10px; margin: 10px; border-radius: 10px;">
        <tbody>
            <tr>
                <td class="edittext">
                    [{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_SETUP_FEE" suffix="COLON"}]
                </td>
                <td class="edittext">
                    <input type="text" required="required" class="editinput" size="20" name="setup_fee" value="[{$setupFee}]" /> EUR
                    <input type="hidden" name="setup_fee_currency" id="setup_fee_currency" value="EUR" />
                </td>
            </tr>

            <tr>
                <td class="edittext">
                    [{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_NAME" suffix="COLON"}]
                </td>
                <td class="edittext">
                     <input type="text" required="required" class="editinput" size="25" name="billing_plan_name" value="[{$billingPlanName}]" />
                     [{*if $editBillingPlanId}]
                        <input type="hidden" name="billing_plan_name" value="[{$billingPlanName}]" />
                        <b>[{$billingPlanName}]</b>
                     [{else}]
                        <input type="text" required="required" class="editinput" size="25" name="billing_plan_name" value="[{$billingPlanName}]" />
                     [{/if*}]
                </td>
            </tr>

            <tr>
                <td class="edittext">
                    [{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_DESCRIPTION" suffix="COLON"}]
                </td>
                <td class="edittext">
                    <input type="text" required="required" class="editinput" size="25" name="billing_plan_description" value="[{$billingPlanDescription}]" />
                </td>
            </tr>

            <tr>
                <td colspan="2"><h3>[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_TAX" suffix="COLON"}]</h3></td>
            </tr>

            <tr>
                <td class="edittext">
                    [{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_TAX_PERCENTAGE" suffix="COLON"}]
                </td>
                <td class="edittext">
                    <input type="text" required="required" class="editinput" size="25"  name="tax_percentage" value="[{$taxPercentage}]" />
                </td>
            </tr>

            <tr>
                <td class="edittext">
                    [{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_TAX_INCLUSIVE" suffix="COLON"}]
                </td>
                <td class="edittext">
                    <select name="tax_inclusive" id="tax_inclusive" style="width: 200px" class="editinput">
                        <option value="true" [{if $taxInclusive}]selected[{/if}]>[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_YES"}]</option>
                        <option value="false"[{if !$taxInclusive}]selected[{/if}]>[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_NO"}]</option>
                    </select>
                    [{*if $editBillingPlanId}]
                        <input type="hidden" name="id" value="[{if $taxInclusive}]true[{else}]false[{/if}]" />
                        <b>[{if $taxInclusive}][{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_YES"}][{else}][{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_NO"}][{/if}]</b>
                    [{else}]
                        <select name="tax_inclusive" id="tax_inclusive" style="width: 200px" class="editinput">
                            <option value="true">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_YES"}]</option>
                            <option value="false">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_NO"}]</option>
                        </select>
                    [{/if*}]
                    <input type="hidden" name="id" value="[{$linkedObject->id}]" />
                    <input type="hidden" name="paypalProductId" value="[{$linkedObject->id}]" />
                </td>
            </tr>

            <tr><td colspan="2"><h3>[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_CYCLES"}] [<span style="cursor: pointer; cursor: hand" id="addBillingCycleAction">+</span>]</h3></td></tr>
            <tr>
                <td colspan="2">
                    <table id="billingCycleList" style="width: 50%;">
                        <tr>
                            <th style="width: 20%; text-align: left">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_PRICE" suffix="COLON"}]</th>
                            <th style="width: 20%; text-align: left">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_FREQUENCY" suffix="COLON"}]</th>
                            <th style="width: 20%; text-align: left">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_TENURE" suffix="COLON"}]</th>
                            <th style="width: 15%; text-align: left">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_CYCLES" suffix="COLON"}]</th>
                            <th style="width: 10%; text-align: left">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_ACTIONS" suffix="COLON"}]</th>
                        </tr>
                        [{if $editBillingPlan}]
                            <input type="hidden" name="editBillingPlanId" value="[{$editBillingPlan->id}]">
                            [{foreach name="billingPlanCycleData" from=$editBillingPlan->billing_cycles item=billing_cycle key=name}]
                                <tr class="cycleData [{$smarty.foreach.billingPlanCycleData.iteration}]Row">
                                    <td align="left">[{$billing_cycle->pricing_scheme->fixed_price->value|number_format:2}]</td>
                                    <td align="left">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_FREQUENCY_"|cat:$billing_cycle->frequency->interval_unit}]</td>
                                    <td align="left">[{oxmultilang ident="OSC_PAYPAL_TENURE_TYPE_"|cat:$billing_cycle->tenure_type}]</td>
                                    <td align="left">[{$billing_cycle->total_cycles}]</td>
                                    <td style="cursor:pointer; cursor: hand" align="left" onclick="window.deleteRow('[{$smarty.foreach.billingPlanCycleData.iteration}]')">[X]
                                        <input type="hidden" name="fixed_price[]" value="[{$billing_cycle->pricing_scheme->fixed_price->value}]" />
                                        <input type="hidden" name="interval[]" value="[{$billing_cycle->frequency->interval_unit}]" />
                                        <input type="hidden" name="tenure[]" value="[{$billing_cycle->tenure_type}]" />
                                        <input type="hidden" name="total_cycles[]" value="[{$billing_cycle->total_cycles}]" />
                                    </td>
                                </tr>
                            [{/foreach}]
                        [{/if}]
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="2"><h3>[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_ACTIONS"}]</h3></td>
            </tr>
            <tr>
                <td colspan="2" class="edittext">
                    <input type="button" class="edittext" name="save" value='[{ oxmultilang ident="GENERAL_SAVE" }]' onClick="window.validateAddBillingPlanForm('[{$BillingPlanAction}]');" />
                </td>
            </tr>
        </tbody>
    </table>
    <table class="addBilling" cellspacing="0" cellpadding="0" border="0" width="98%" style="border: 1px solid #cccccc; padding: 10px; margin: 10px; border-radius: 10px;">
            <tr>
                <td colspan="100">
                    <h3>[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_ADD_CYCLES"}]</h3>
                </td>
            </tr>

            <tr>
                <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_TENURE" suffix="COLON"}]</td>
                <td class="edittext">
                    <select name="tenure_val" id="tenure_val" style="width: 200px" class="editinput">
                        [{foreach from=$defaultTenureTypes item=value key=name}]
                            <option value="[{$value}]">[{oxmultilang ident="OSC_PAYPAL_TENURE_TYPE_"|cat:$value}]</option>
                        [{/foreach}]
                    </select>
                </td>
            </tr>

            <tr>
                <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_PRICE" suffix="COLON"}]</td>
                <td class="edittext">
                    <input type="text" class="editinput" size="25" name="fixed_price_val" id="fixed_price_val" value=""/>
                </td>
            </tr>

            <tr>
                <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_TOTAL_CYCLES" suffix="COLON"}]</td>
                <td class="edittext">
                    <select name="total_cycles_val" id="total_cycles_val" style="width: 200px" class="editinput">
                        [{foreach from=$defaultTotalCycles item=value key=name}]
                        <option value="[{$value}]">[{$value}]</option>
                        [{/foreach}]
                    </select>
                </td>
            </tr>

            <tr>
                <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_FREQUENCY" suffix="COLON"}]</td>
                <td class="edittext">
                    <select name="interval_val" id="interval_val" style="width: 200px" class="editinput">
                        [{foreach from=$defaultIntervals item=value key=name}]
                            <option value="[{$value}]">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_FREQUENCY_"|cat:$value}]</option>
                        [{/foreach}]
                    </select>
                </td>
            </tr>

            <tr>
                <td class="edittext">
                    <input type="button" class="edittext" name="addBillingCycle" value='[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_ADD"}]' onClick="window.addCycle()"><br>
                </td>
            </tr>

        </tbody>
    </table>
</form>

[{capture assign="sPayPalBillingPlanJS"}]
    [{strip}]
        jQuery(document).ready(function(){

            setTimeout(function() {
                jQuery(".addBilling").hide();
            }, 1000);

            jQuery("#addBillingCycleAction").click(function() {
                jQuery(".addBilling").toggle();
            });

            window.validateAddBillingPlanForm = function(saveType) {
                let isValid = true;
                document.billingPlanForm.fnc.value=saveType;
                if(saveType === 'saveBillingPlans' || saveType === 'patch') {
                    jQuery('#billingPlanForm *').filter(':input').each(function(){
                        let thisElement = jQuery(this);
                        if (thisElement.attr('required') === true) {
                            if(thisElement.val().length < 1) {
                                isValid = false;
                                thisElement.css('border', '1px solid #ff0000');
                            } else {
                                thisElement.css('border', '1px solid #cccccc');
                            }
                        }
                    });

                    if (typeof cycleCount !== "undefined") {
                        if(cycleCount < 1) {
                            isValid = false;
                            jQuery('#billingCycleList').css('border', '1px solid #ff0000');
                        } else {
                            jQuery('#billingCycleList').css('border', '1px solid #cccccc');
                        }
                    }

                    if (isValid) {
                        jQuery("#billingPlanForm").submit();
                    }
                };
            };

            window.addCycle = function() {
                let intCount =  (Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15)).trim();

                let price = jQuery('#fixed_price_val').val();
                let frequency = jQuery('#interval_val option:selected').text();
                let tenure = jQuery('#tenure_val option:selected').text();
                let frequency_value = jQuery('#interval_val option:selected').val();
                let tenure_value = jQuery('#tenure_val option:selected').val();
                let totalCycles = jQuery('#total_cycles_val option:selected').text();

                if(price.length < 1) {
                    jQuery('#fixed_price_val').css('border', '1px solid #ff0000');
                } else {
                    jQuery('#fixed_price_val').css('border', '1px solid #cccccc');
                    let html = '<tr class="cycleData ' + intCount + 'Row">';
                    html += '<td align="left">' + price + '</td>';
                    html += '<td align="left">' + frequency + '</td>';
                    html += '<td align="left">' + tenure + '</td>';
                    html += '<td align="left">' + totalCycles + '</td>';
                    html += '<td style="cursor:pointer; cursor: hand" align="left" onclick="window.deleteRow(\'' + intCount + '\')">[X]';
                    html += '<input type="hidden" name="fixed_price[]" value="' + price + '" />';
                    html += '<input type="hidden" name="interval[]" value="' + frequency_value + '" />';
                    html += '<input type="hidden" name="tenure[]" value="' + tenure_value + '" />';
                    html += '<input type="hidden" name="total_cycles[]" value="' + totalCycles + '" /></td>';
                    html += '</tr>';

                    jQuery("#billingCycleList").append(html);
                }
            };

            window.deleteRow = function(id) {
                jQuery('.' + id + 'Row').remove();
            };
        });
    [{/strip}]
[{/capture}]
[{oxscript add=$sPayPalBillingPlanJS}]
