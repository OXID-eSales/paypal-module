[{assign var="subscriptionPlansList" value=$oView->getSubscriptionPlans()}]
[{assign var="subscriptionPlansAreSubscriptedList" value=$oView->getSubscriptionPlansAreSubscripted()}]

[{if $subscriptionPlansList}]

    [{foreach from=$subscriptionPlansList item=value key=name}]
        [{if $value->status == 'ACTIVE'}]
            [{assign var="subscriptionPlanId" value=$value->id}]
            <table cellspacing="0" cellpadding="0" border="0" width="98%" style="border: 1px solid #cccccc; padding: 10px; margin: 10px; border-radius: 10px;">
                <tbody>
                    <tr>
                        <td colspan="2">
                            <h3>
                                [{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN" suffix="COLON"}] [{$value->name}]
                                <span class="popUpStyle" id="editsubscription[{$subscriptionPlanId}]" style="position: absolute;visibility: hidden;top:0;left:0;">
                                    [{oxmultilang ident="TOOLTIPS_OSC_PAYPAL_EDITSUBSCRIPTION"}]
                                </span>
                                [{if !$subscriptionPlanId|in_array:$subscriptionPlansAreSubscriptedList}]
                                    <a href="#" onclick="window.editBillingPlanForm('[{$subscriptionPlanId}]')" class="[{$listclass}]" [{include file="help.tpl" helpid="editsubscription"|cat:$subscriptionPlanId}]>
                                        <img src="[{$oViewConf->getImageUrl()}]/editvariant.gif" width="15" height="15" alt="" border="0" align="absmiddle" />
                                    </a>

                                    <span class="popUpStyle" id="deactivatesubscription[{$subscriptionPlanId}]" style="position: absolute;visibility: hidden;top:0;left:0;">
                                        [{oxmultilang ident="TOOLTIPS_OSC_PAYPAL_DEACTIVATESUBSCRIPTION"}]
                                    </span>
                                    <a href="#" onclick="window.deactivateBillingPlanForm('[{$subscriptionPlanId}]')" class="[{$listclass}]" [{include file="help.tpl" helpid="deactivatesubscription"|cat:$subscriptionPlanId}]>
                                        <img src="[{$oViewConf->getImageUrl()}]/delete_button.gif" width="15" height="15" alt="" border="0" align="absmiddle" />
                                    </a>
                                [{/if}]
                            </h3>
                        </td>
                    </tr>
                    <tr>
                        <td class="edittext" style="width: 196px;">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_DESCRIPTION" suffix="COLON"}]</td>
                        <td class="edittext">[{$value->description}]</td>
                    </tr>
                    <tr>
                        <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_AUTOMATICALLY_BILL" suffix="COLON"}]</td>
                        <td class="edittext">
                            [{if $value->payment_preferences->auto_bill_outstanding == true}]
                                [{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_YES"}]
                            [{else}]
                                [{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_NO"}]
                            [{/if}]
                        </td>
                    </tr>
                    <tr>
                        <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_SETUP_FEE" suffix="COLON"}]</td>
                        <td class="edittext">[{$value->payment_preferences->setup_fee->value|number_format:2}] [{$value->payment_preferences->setup_fee->currency_code}]</td>
                    </tr>
                    <tr>
                        <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_TAX_PERCENTAGE" suffix="COLON"}]</td>
                        <td class="edittext">[{$value->taxes->percentage}]</td>
                    </tr>
                    <tr>
                        <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_TAX_INCLUSIVE" suffix="COLON"}]</td>
                        <td class="edittext">
                            [{if $value->taxes->inclusive == 1}]
                                [{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_YES"}]
                            [{else}]
                                [{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_NO"}]
                            [{/if}]
                        </td>
                    </tr>
                    <tr>
                        <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_PAYPAL_ID" suffix="COLON"}]</td>
                        <td class="edittext">[{$subscriptionPlanId}]</td>
                    </tr>

                    <tr>
                        <td colspan="2"><h3>[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_BILLING_CYCLES" suffix="COLON"}]</h3></td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            <table style="width: 50%;">
                                <tr>
                                    <th style="width: 20%; text-align: left">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_PRICE" suffix="COLON"}]</th>
                                    <th style="width: 20%; text-align: left">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_FREQUENCY" suffix="COLON"}]</th>
                                    <th style="width: 15%; text-align: left">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_TENURE" suffix="COLON"}]</th>
                                    <th style="width: 15%; text-align: left">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_CYCLES" suffix="COLON"}]</th>
                                </tr>
                                [{foreach from=$value->billing_cycles item=billing_cycle key=name}]
                                    <tr class="cycleData">
                                        <td align="left">[{$billing_cycle->pricing_scheme->fixed_price->value|number_format:2}] [{$billing_cycle->pricing_scheme->fixed_price->currency_code}]</td>
                                        <td align="left">[{$billing_cycle->frequency->interval_count}] [{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_FREQUENCY_"|cat:$billing_cycle->frequency->interval_unit}]</td>
                                        <td align="left">[{oxmultilang ident="OSC_PAYPAL_TENURE_TYPE_"|cat:$billing_cycle->tenure_type}]</td>
                                        <td align="left">[{$billing_cycle->total_cycles}]</td>
                                    </tr>
                                [{/foreach}]
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        [{/if}]
    [{/foreach}]
[{else}]
    <h3>[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_NO_PLANS"}]</h3>
[{/if}]