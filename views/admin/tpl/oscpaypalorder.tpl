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

    [{assign var="currency" value=$oView->getPayPalCurrency()}]

    <table width="98%" cellspacing="0" cellpadding="0" border="0">
    <tbody>
    <tr>
        <td class="edittext" valign="top">
            <table class="paypalActionsTable">
                <tr>
                    <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_SHOP_PAYMENT_STATUS" suffix="COLON"}]</td>
                    <td class="edittext" align="right">
                        <b>[{oxmultilang ident='OSC_PAYPAL_STATUS_'|cat:$oView->getPayPalPaymentStatus()}]</b>
                    </td>
                </tr>
                <tr>
                    <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_ORDER_PRICE" suffix="COLON"}]</td>
                    <td class="edittext" align="right">
                        <b>[{$oView->formatPrice($oView->getPayPalTotalOrderSum())}] [{$currency}]</b>
                    </td>
                </tr>
                <tr>
                    <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_AUTHORIZED_AMOUNT" suffix="COLON"}]</td>
                    <td class="edittext" align="right">
                        <b>[{$oView->formatPrice($oView->getPayPalAuthorizationAmount())}] [{$currency}]</b>
                    </td>
                </tr>
                <tr>
                    <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_CAPTURED_AMOUNT" suffix="COLON"}]</td>
                    <td class="edittext" align="right">
                        <b>[{$oView->formatPrice($oView->getPayPalCapturedAmount())}] [{$currency}]</b>
                    </td>
                </tr>
                <tr>
                    <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_REFUNDED_AMOUNT" suffix="COLON"}]</td>
                    <td class="edittext" align="right">
                        <b>[{$oView->formatPrice($oView->getPayPalRefundedAmount())}] [{$currency}]</b>
                    </td>
                </tr>
                <tr>
                    <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_CAPTURED_NET" suffix="COLON"}]</td>
                    <td class="edittext" align="right">
                        <b>[{$oView->formatPrice($oView->getPayPalResultedAmount())}] [{$currency}]</b>
                    </td>
                </tr>
            </table>
        </td>
        <td class="edittext" valign="top">
            [{if $payPalOrderDetails->oscpaypal_order__oscpaypalpuipaymentreference->value}]
                <b>[{oxmultilang ident="OSC_PAYPAL_PAYMENT_PUI" suffix="COLON"}]</b>
                <table class="paypalActionsTable">
                    <tr>
                        <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_PAYMENT_PUI_REFERENCE" suffix="COLON"}]</td>
                        <td class="edittext" align="right">
                            <b>[{$payPalOrderDetails->oscpaypal_order__oscpaypalpuipaymentreference->value}]</b>
                        </td>
                    </tr>
                    <tr>
                        <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_PAYMENT_PUI_BIC" suffix="COLON"}]</td>
                        <td class="edittext" align="right">
                            <b>[{$payPalOrderDetails->oscpaypal_order__oscpaypalpuibic->value}]</b>
                        </td>
                    </tr>
                    <tr>
                        <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_PAYMENT_PUI_IBAN" suffix="COLON"}]</td>
                        <td class="edittext" align="right">
                            <b>[{$payPalOrderDetails->oscpaypal_order__oscpaypalpuiiban->value}]</b>
                        </td>
                    </tr>
                    <tr>
                        <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_PAYMENT_PUI_BANKNAME" suffix="COLON"}]</td>
                        <td class="edittext" align="right">
                            <b>[{$payPalOrderDetails->oscpaypal_order__oscpaypalpuibankname->value}]</b>
                        </td>
                    </tr>
                    <tr>
                        <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_PAYMENT_PUI_ACCOUNTHOLDER" suffix="COLON"}]</td>
                        <td class="edittext" align="right">
                            <b>[{$payPalOrderDetails->oscpaypal_order__oscpaypalpuiaccountholdername->value}]</b>
                        </td>
                    </tr>
                </table>
            [{/if}]
        </td>
        <td class="edittext" valign="top" align="left">
            <p><b>[{oxmultilang ident="OSC_PAYPAL_ORDER_PRODUCTS" suffix="COLON"}]</b></p>
            <table cellspacing="0" cellpadding="0" border="0" width="98%">
                <tr>
                    <td class="listheader first">[{oxmultilang ident="GENERAL_SUM"}]</td>
                    <td class="listheader" height="15">&nbsp;&nbsp;&nbsp;[{oxmultilang ident="GENERAL_ITEMNR"}]</td>
                    <td class="listheader">&nbsp;&nbsp;&nbsp;[{oxmultilang ident="GENERAL_TITLE"}]</td>
                    [{if $order->isNettoMode()}]
                    <td class="listheader">[{oxmultilang ident="ORDER_ARTICLE_ENETTO"}]</td>
                    [{else}]
                    <td class="listheader">[{oxmultilang ident="ORDER_ARTICLE_EBRUTTO"}]</td>
                    [{/if}]
                    <td class="listheader">[{oxmultilang ident="GENERAL_ATALL"}]</td>
                    <td class="listheader" colspan="3">[{oxmultilang ident="ORDER_ARTICLE_MWST"}]</td>
                </tr>
                [{assign var="blWhite" value=""}]
                [{foreach from=$order->getOrderArticles() item=listitem name=orderArticles}]
                [{if $listitem->oxorderarticles__oxstorno->value == 1}]
                [{assign var="listclass" value=listitem3}]
                [{else}]
                [{assign var="listclass" value=listitem$blWhite}]
                [{/if}]
                <tr id="art.[{$smarty.foreach.orderArticles.iteration}]">
                    <td valign="top" class="[{$listclass}]">[{$listitem->oxorderarticles__oxamount->value}]</td>
                    <td valign="top" class="[{$listclass}]" height="15">[{$listitem->oxorderarticles__oxartnum->value}]
                    </td>
                    <td valign="top" class="[{$listclass}]">[{$listitem->oxorderarticles__oxtitle->value|oxtruncate:20:""|strip_tags}]
                    </td>
                    [{if $order->isNettoMode()}]
                    <td valign="top" class="[{$listclass}]">[{$listitem->getNetPriceFormated()}]
                        <small>[{$order->oxorder__oxcurrency->value}]</small>
                    </td>
                    <td valign="top" class="[{$listclass}]">[{$listitem->getTotalNetPriceFormated()}]
                        <small>[{$order->oxorder__oxcurrency->value}]</small>
                    </td>
                    [{else}]
                    <td valign="top" class="[{$listclass}]">[{$listitem->getBrutPriceFormated()}]
                        <small>[{$order->oxorder__oxcurrency->value}]</small>
                    </td>
                    <td valign="top" class="[{$listclass}]">[{$listitem->getTotalBrutPriceFormated()}]
                        <small>[{$order->oxorder__oxcurrency->value}]</small>
                    </td>
                    [{/if}]
                    <td valign="top" class="[{$listclass}]">[{$listitem->oxorderarticles__oxvat->value}]</td>
                </tr>
                [{if $blWhite == "2"}]
                [{assign var="blWhite" value=""}]
                [{else}]
                [{assign var="blWhite" value="2"}]
                [{/if}]
                [{/foreach}]
            </table>
        </td>
    </tr>
    <tr>
        <td class="edittext" valign="top" colspan="3">
            <b>[{oxmultilang ident="OSC_PAYPAL_PAYMENT_HISTORY" suffix="COLON"}]</b>
            <table id="historyTable">
                <colgroup>
                    <col width="20%">
                    <col width="20%">
                    <col width="20%">
                    <col width="20%">
                    <col width="20%">
                </colgroup>
                <tr>
                    <td class="listheader first">[{oxmultilang ident="OSC_PAYPAL_HISTORY_DATE"}]</td>
                    <td class="listheader">[{oxmultilang ident="OSC_PAYPAL_HISTORY_ACTION"}]</td>
                    <td class="listheader">[{oxmultilang ident="OSC_PAYPAL_AMOUNT"}]</td>
                    <td class="listheader">
                        [{oxmultilang ident="OSC_PAYPAL_HISTORY_PAYPAL_STATUS"}]
                        [{oxinputhelp ident="OSC_PAYPAL_HISTORY_PAYPAL_STATUS_HELP"}]
                    </td>
                    <td class="listheader">[{oxmultilang ident="OSC_PAYPAL_TRANSACTIONID"}]</td>
                    <td class="listheader">[{oxmultilang ident="OSC_PAYPAL_INVOICE_ID"}]</td>
                    <td class="listheader">[{oxmultilang ident="OSC_PAYPAL_COMMENT"}]</td>
                </tr>
                [{foreach from=$oView->getPayPalHistory() item=listitem name=paypalHistory}]
                [{cycle values='listitem,listitem2' assign='class'}]
                <tr>
                    <td valign="top" class="[{$class}]">[{$listitem.date}]</td>
                    <td valign="top" class="[{$class}]">[{oxmultilang ident='OSC_PAYPAL_'|cat:$listitem.action}]</td>
                    <td valign="top" class="[{$class}]">
                        [{$oView->formatPrice($listitem.amount)}]
                        <small>[{$currency}]</small>
                    </td>
                    <td valign="top" class="[{$class}]">[{oxmultilang ident='OSC_PAYPAL_STATUS_'|cat:$listitem.status}]</td>
                    <td valign="top" class="[{$class}]">[{$listitem.transactionid}]</td>
                    <td valign="top" class="[{$class}]">[{$listitem.invoiceid}]</td>
                    <td valign="top" class="[{$class}]">[{$listitem.comment}]</td>
                </tr>
                [{/foreach}]
            </table>
        </td>
    </tr>

    </tbody>
    </table>
    [{if $oView->getPayPalPaymentStatus() == 'COMPLETED' && $oView->getPayPalRemainingRefundAmount()}]
        <div style="margin-top: 10px">
            <p><b>[{oxmultilang ident="OSC_PAYPAL_ISSUE_REFUND"}]</b></p>
            <form action="[{$oViewConf->getSelfLink()}]" method="post">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="fnc" value="refund">
                <input type="hidden" name="cl" value="oscpaypalorder">
                <input type="hidden" name="oxid" value="[{$oxid}]">
                <input type="hidden" name="language" value="[{$actlang}]">
                <table class="paypalActionsTable">
                    <tr>
                        <td><label for="refundAmount">[{oxmultilang ident="OSC_PAYPAL_REFUND_AMOUNT"}]</label></td>
                        <td><input type="text" id="refundAmount" name="refundAmount" value="[{$oView->formatPrice($oView->getPayPalRemainingRefundAmount())}]"></td>
                    </tr>
                    <tr>
                        <td><label for="invoiceId">[{oxmultilang ident="OSC_PAYPAL_INVOICE_ID"}]</label></td>
                        <td><input type="text" id="invoiceId" name="invoiceId" maxlength="127"></td>
                    </tr>
                    <tr>
                        <td><label for="noteToPayer">[{oxmultilang ident="OSC_PAYPAL_NOTE_TO_BUYER"}]</label></td>
                        <td><textarea id="noteToPayer" required name="noteToPayer" maxlength="255"></textarea></td>
                    </tr>
                    <tr>
                        <td><label for="refundAll">[{oxmultilang ident="OSC_PAYPAL_REFUND_ALL"}]</label></td>
                        <td><input type="checkbox" id="refundAll" name="refundAll"></td>
                    </tr>
                    <tr>
                        <td><input type="submit" value="[{oxmultilang ident="OSC_PAYPAL_REFUND"}]"></td>
                    </tr>
                </table>
            </form>
        </div>
    [{elseif $oView->getPayPalPaymentStatus() !== 'COMPLETED'}]
        <div style="margin-top: 10px">
            <p><b>[{oxmultilang ident="OSC_PAYPAL_ACTIONS" suffix="COLON"}]</b></p>
            <form action="[{$oViewConf->getSelfLink()}]" method="post">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="fnc" value="capturePayPalStandard">
                <input type="hidden" name="cl" value="oscpaypalorder">
                <input type="hidden" name="oxid" value="[{$oxid}]">
                <input type="hidden" name="language" value="[{$actlang}]">
                <input type="submit" value="[{oxmultilang ident="OSC_PAYPAL_CAPTURE"}]">
            </form>
            <p>[{oxmultilang ident="OSC_PAYPAL_CAPTURE_DAYS_LEFT" args=$oView->getTimeLeftForPayPalCapture()}]</p>
        </div>
    [{/if}]
[{/if}]
[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]
