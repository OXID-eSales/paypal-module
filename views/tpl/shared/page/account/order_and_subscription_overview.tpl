[{assign var="payPalSubscription" value=$order->getPayPalSubscriptionForHistory()}]
[{assign var="billingInfo" value=$payPalSubscription->billing_info}]
<p>
    [{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTION" suffix="COLON"}]
    [{foreach from=$order->getOrderArticles(true) item=orderitem name=testOrderItem}]
        <strong>[{$orderitem->oxorderarticles__oxtitle->value}]</strong><br>
    [{/foreach}]
    <strong>[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_MAIN"}]</strong>
</p>
<table class="table table-sm">
    <tr>
        <th>[{oxmultilang ident="OSC_PAYPAL_TENURE_TYPE"}]</th>
        <th>[{oxmultilang ident="OSC_PAYPAL_SEQUENCE"}]</th>
        <th>[{oxmultilang ident="OSC_PAYPAL_CYCLES_COMPLETED"}]</th>
        <th>[{oxmultilang ident="OSC_PAYPAL_CYCLES_REMAINING"}]</th>
        [{*<th>[{oxmultilang ident="OSC_PAYPAL_CURRENT_PRICING_SCHEME_VERSION"}]</th>*}]
        <th>[{oxmultilang ident="OSC_PAYPAL_TOTAL_CYCLES"}]</th>
    </tr>
    [{foreach from=$billingInfo->cycle_executions item="cycleExecution"}]
        [{assign var="cyclesRemaining" value=$cycleExecution->cycles_remaining}]
        [{cycle values='ppmessages,ppaltmessages' assign=cellClass}]
        <tr class="[{$cellClass}]">
            <td>[{oxmultilang ident="OSC_PAYPAL_TENURE_TYPE_"|cat:$cycleExecution->tenure_type}]</td>
            <td>[{$cycleExecution->sequence}]</td>
            <td>[{$cycleExecution->cycles_completed}]</td>
            <td>[{$cyclesRemaining}]</td>
            [{*<td>[{$cycleExecution->current_pricing_scheme_version}]</td>*}]
            <td>[{$cycleExecution->total_cycles}]</td>
        </tr>
    [{/foreach}]
</table>
<p>
    [{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTION_NEXT_BILLING_TIME" suffix="COLON"}]
    <strong>[{$billingInfo->next_billing_time|date_format:"%d.%m.%Y %H:%M"}]</strong>
</p>
[{if $cyclesRemaining > 0}]
    <p>
        <small>[{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTION_UNSUBSCRIBE_NOTE"}]</small>
    </p>
    [{if $order->isCancelRequestSended($order->getId())}]
        <button class="btn btn-primary" disabled="disabled">
            [{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTION_UNSUBSCRIBE_SEND"}]
        </button>
    [{else}]
        <button data-successtext="[{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTION_UNSUBSCRIBE_SEND"}]" data-orderid="[{$order->getId()}]" class="btn btn-primary subscriptionCancelButton">
            [{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTION_UNSUBSCRIBE"}]
        </button>
    [{/if}]
[{/if}]

