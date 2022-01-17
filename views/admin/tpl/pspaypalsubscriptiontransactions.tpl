[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign box="list"}]

<div class="container-fluid">
    <div id="filters">
        [{if !empty($error)}]
        <div class="alert alert-danger" role="alert">
            [{$error}]
        </div>
        [{/if}]
        <form method="post" action="[{$oViewConf->getSelfLink()}]">
        <div class="row">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="cl" value="oscpaypalsubscriptiontransaction">
                <input type="hidden" name="subscriptionId" value="[{$subscriptionId}]">

                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="startTimeFilter">[{oxmultilang ident="OSC_PAYPAL_FROM"}]</label>
                        <input id="startTimeFilter"
                               class="form-control"
                               type="date"
                               name="filters[startTime]"
                               value="[{if $filters.startTime}][{$filters.startTime}][{/if}]"
                               required>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="endTimeFilter">[{oxmultilang ident="OSC_PAYPAL_TO"}]</label>
                        <input id="endTimeFilter"
                               class="form-control"
                               type="date"
                               name="filters[endTime]"
                               value="[{if $filters.endTime}][{$filters.endTime}][{/if}]"
                               required>
                    </div>
                </div>
        </div>
        <button class="btn btn-primary" type="submit">[{oxmultilang ident="OSC_PAYPAL_APPLY"}]</button>
        </form>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>[{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTION_TRANSACTION_ID"}]</th>
                        <th>[{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTION_TRANSACTION_DATE"}]</th>
                        <th>[{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTION_TRANSACTION_STATUS"}]</th>
                        <th>[{oxmultilang ident="OSC_PAYPAL_FULL_NAME"}]</th>
                        <th>[{oxmultilang ident="OSC_PAYPAL_EMAIL"}]</th>
                        <th>[{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTION_TRANSACTION_GROSS_AMOUNT"}]</th>
                        <th>[{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTION_TRANSACTION_NET_AMOUNT"}]</th>
                        <th>[{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTION_TRANSACTION_FEE_AMOUNT"}]</th>
                    </tr>
                </thead>
                <tbody>
                    [{if $transactions}]
                        [{foreach from=$transactions->transactions item=transaction}]
                            <tr>
                                <td>[{$transaction->id}]</td>
                                <td>[{$transaction->time|date_format:"%Y-%m-%d %H:%M:%S"}]</td>
                                <td>[{$transaction->status}]</td>
                                [{assign var="transactionAmounts" value=$transaction->amount_with_breakdown}]
                                <td>[{$transaction->payer_name->given_name}]&nbsp;[{$transaction->payer_name->surname}]</td>
                                <td>[{$transaction->payer_email}]</td>
                                <td>[{$transactionAmounts->gross_amount->value}]&nbsp;[{$transactionAmounts->gross_amount->currency_code}]</td>
                                <td>[{$transactionAmounts->net_amount->value}]&nbsp;[{$transactionAmounts->net_amount->currency_code}]</td>
                                <td>[{$transactionAmounts->fee_amount->value}]&nbsp;[{$transactionAmounts->fee_amount->currency_code}]</td>
                            </tr>
                        [{/foreach}]
                    [{/if}]
                </tbody>
            </table>
        </div>
    </div>
</div>
