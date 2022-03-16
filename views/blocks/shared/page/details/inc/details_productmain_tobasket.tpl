[{if !$hasSubscriptionPlans}]
    [{$smarty.block.parent}]
[{/if}]
[{include file='modules/osc/paypal/details_productmain_tobasket.tpl'}]
