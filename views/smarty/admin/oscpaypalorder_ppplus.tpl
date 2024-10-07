[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]

<form name="transfer" id="transfer" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="oxid" value="[{$oxid}]">
    <input type="hidden" name="cl" value="oscpaypalorder">
</form>

[{if $error}]
    <div class="errorbox">[{$error}]</div>
[{/if}]

[{if $order && $payPalOrder}]
    [{assign var="dRefundedAmount" value=$payPalOrder->getTotalAmountRefunded()}]
    [{assign var="oPaymentInstructions" value=$payPalOrder->getPaymentInstructions()}]
    <table width="98%" cellspacing="0" cellpadding="0" border="0">
        <tbody>
        <tr>
            <td class="edittext" valign="top" width="50%">
                <b>[{oxmultilang ident="OSC_PAYPALPLUS_PAYMENT_OVERVIEW"}]</b>
                <table class="paypPayPalPlusOverviewTable">
                    <tbody>
                    <tr>
                        <td class="edittext">[{oxmultilang ident="OSC_PAYPALPLUS_PAYMENT_STATUS"}]:</td>
                        <td class="edittext"><b>[{$payPalOrder->getStatus()}]</b></td>
                    </tr>
                    <tr>
                        <td class="edittext">[{oxmultilang ident="OSC_PAYPALPLUS_ORDER_AMOUNT"}]:</td>
                        <td class="edittext"><b>[{$oView->formatPrice($payPalOrder->getTotal())}]</b></td>
                    </tr>
                    [{if $dRefundedAmount}]
                        <tr>
                            <td class="edittext">[{oxmultilang ident="OSC_PAYPALPLUS_REFUNDED_AMOUNT"}]:</td>
                            <td class="edittext"><b>[{$oView->formatPrice($dRefundedAmount)}]</b></td>
                        </tr>
                    [{/if}]
                    <tr>
                        <td class="edittext">[{oxmultilang ident="OSC_PAYPALPLUS_PAYMENT_ID"}]:</td>
                        <td class="edittext"><b>[{$payPalOrder->getPaymentId()}]</b></td>
                    </tr>
                    <tr>
                        <td class="edittext">[{oxmultilang ident="OSC_PAYPALPLUS_PAYMENT_METHOD"}]:</td>
                        <td class="edittext"><b>[{if $oPaymentInstructions}][{oxmultilang ident="OSC_PAYPALPLUS_PUI"}][{else}]PayPal[{/if}]</b></td>
                    </tr>
                    </tbody>
                </table>
                [{if $oPaymentInstructions}]
                    <div style="height: 25px">&nbsp;</div>
                    <b>[{oxmultilang ident="OSC_PAYPALPLUS_PUI_PAYMENT_INSTRUCTIONS"}]</b>
                    <table>
                        <tr>
                            <td>[{oxmultilang ident="OSC_PAYPALPLUS_PUI_TERM"}]:</td>
                            <td>[{$oPaymentInstructions->getDueDate()|replace:" 00:00:00":""}]</td>
                        </tr>
                        <tr>
                            <td>[{oxmultilang ident="OSC_PAYPALPLUS_PUI_ACCOUNT_HOLDER"}]:</td>
                            <td>[{$oPaymentInstructions->getAccountHolder()}]</td>
                        </tr>
                        <tr>
                            <td>[{oxmultilang ident="OSC_PAYPALPLUS_PUI_BANK_NAME"}]:</td>
                            <td>[{$oPaymentInstructions->getBankName()}]</td>
                        </tr>
                        <tr>
                            <td>[{oxmultilang ident="OSC_PAYPALPLUS_PUI_REFERENCE_NUMBER"}]:</td>
                            <td>[{$oPaymentInstructions->getReferenceNumber()}]</td>
                        </tr>
                        <tr>
                            <td>[{oxmultilang ident="OSC_PAYPALPLUS_PUI_IBAN"}]:</td>
                            <td>[{$oPaymentInstructions->getIban()}]</td>
                        </tr>
                        <tr>
                            <td>[{oxmultilang ident="OSC_PAYPALPLUS_PUI_BIC"}]:</td>
                            <td>[{$oPaymentInstructions->getBic()}]</td>
                        </tr>
                    </table>
                [{/if}]
            </td>
            <td class="edittext" valign="top" align="left" width="50%">
                [{if $payPalOrder->isRefundable()}]
                    [{assign var="payPalOrderRefunds" value=$payPalOrder->getRefundsList()}]
                    [{assign var="dRemainingRefundAmount" value=$oView->getPayPalPlusRemainingRefundAmount()}]
                    <b>[{oxmultilang ident="OSC_PAYPALPLUS_PAYMENT_REFUNDING"}]</b>
                    <table class="paypPayPalPlusOverviewTable" cellpadding="0" border="0">
                        <tbody>
                        [{if $dRemainingRefundAmount}]
                            <tr>
                                <td width="5%">&nbsp;</td>
                                <td width="40%" class="edittext">[{oxmultilang ident="OSC_PAYPALPLUS_AVAILABLE_REFUNDS"}]</td>
                                <td width="35%" class="edittext"><b>[{$oView->getPayPalPlusRemainingRefundsCount()}]</b></td>
                                <td width="20%">&nbsp;</td>
                            </tr>
                        [{/if}]
                        <tr>
                            <td>&nbsp;</td>
                            <td class="edittext">[{oxmultilang ident="OSC_PAYPALPLUS_AVAILABLE_REFUND_AMOUNT"}]</td>
                            <td class="edittext"><b>[{$oView->formatPrice($dRemainingRefundAmount)}]</b></td>
                            <td>&nbsp;</td>
                        </tr>
                        [{if $payPalOrderRefunds and $payPalOrderRefunds->count()}]
                            <tr>
                                <td colspan="4">&nbsp;</td>
                            </tr>
                            <tr>
                                <th class="listheader first">&nbsp;</th>
                                <th class="listheader">[{oxmultilang ident="OSC_PAYPALPLUS_DATE"}]</th>
                                <th class="listheader" height="15">[{oxmultilang ident="OSC_PAYPALPLUS_AMOUNT"}]</th>
                                <th class="listheader">[{oxmultilang ident="OSC_PAYPALPLUS_STATUS"}]</th>
                            </tr>
                            [{foreach name='refunds_list' from=$payPalOrderRefunds item=payPalOrderRefund}]
                                <tr>
                                    <td valign="top" class="listitem edittext">#[{$smarty.foreach.refunds_list.iteration}]</td>
                                    <td valign="top" class="listitem edittext">[{$payPalOrderRefund->getDateCreated()}]</td>
                                    <td valign="top" class="listitem edittext" height="15">[{$oView->formatPrice($payPalOrderRefund->getTotal())}]</td>
                                    <td valign="top" class="listitem edittext">[{$payPalOrderRefund->getStatus()}]</td>
                                </tr>
                            [{/foreach}]
                        [{/if}]
                        </tbody>
                    </table>
                [{/if}]
            </td>
        </tr>
        </tbody>
    </table>
[{else}]
    <div class="messagebox">[{oxmultilang ident="OSC_PAYPALPLUS_ONLY_FOR_PAYPAL_PLUS_PAYMENT"}]</div>
[{/if}]
[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]
