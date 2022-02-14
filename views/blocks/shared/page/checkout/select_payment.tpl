[{if $sPaymentID == "oxidpaypal"}]
    [{include file='tpl/page/checkout/select_payment.tpl'}]
[{elseif $sPaymentID  == 'oxidpaypal_acdc'}]
   TODO: check eligibility
    [{$smarty.block.parent}]
[{else}]
    [{$smarty.block.parent}]
[{/if}]
