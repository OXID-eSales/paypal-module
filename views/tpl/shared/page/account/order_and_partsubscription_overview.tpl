[{assign var="payPalParentSubscriptionOrder" value=$order->getParentSubscriptionOrder($order->getId())}]
<p>
    [{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTION" suffix="COLON"}]
    [{foreach from=$order->getOrderArticles(true) item=orderitem name=testOrderItem}]
        <strong>[{$orderitem->oxorderarticles__oxtitle->value}]</strong><br>
    [{/foreach}]
</p>
[{if $payPalParentSubscriptionOrder}]
    <p>
        [{oxmultilang ident="OSC_PAYPAL_SUBSCRITION_PART_NOTE" suffix="COLON"}]
        [{$payPalParentSubscriptionOrder->oxorder__oxordernr->value}]
    </p>
[{/if}]
