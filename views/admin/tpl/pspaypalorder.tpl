[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]

<form name="transfer" id="transfer" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="oxid" value="[{$oxid}]">
    <input type="hidden" name="cl" value="PayPalOrderController">
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
        <table class="paypalActionsTable" width="98%">
            <tr>
                <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_SHOP_PAYMENT_STATUS"}]:</td>
                <td class="edittext">
                    <b>[{oxmultilang ident='OSC_PAYPAL_STATUS_'|cat:$oView->getPayPalPaymentStatus()}]</b>
                </td>
            </tr>
            <tr>
                <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_ORDER_PRICE"}]:</td>
                <td class="edittext">
                    <b>[{$oView->formatPrice($oView->getPayPalTotalOrderSum())}] [{$currency}]</b>
                </td>
            </tr>
            <tr>
                <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_CAPTURED_AMOUNT"}]:</td>
                <td class="edittext">
                    <b>[{$oView->formatPrice($oView->getPayPalCapturedAmount())}] [{$currency}]</b>
                </td>
            </tr>
            <tr>
                <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_REFUNDED_AMOUNT"}]:</td>
                <td class="edittext">
                    <b>[{$oView->formatPrice($oView->getPayPalRefundedAmount())}] [{$currency}]</b>
                </td>
            </tr>
            <tr>
                <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_CAPTURED_NET"}]:</td>
                <td class="edittext">
                    <b>[{$oView->formatPrice($oView->getPayPalRemainingRefundAmount())}] [{$currency}]</b>
                </td>
            </tr>
            <tr>
                <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_VOIDED_AMOUNT"}]:</td>
                <td class="edittext">
                    <b>[{$oView->formatPrice($oView->getPayPalVoidedAmount())}] [{$currency}]</b>
                </td>
            </tr>


            <tr>
                <td class="edittext">[{oxmultilang ident="OSC_PAYPAL_AUTHORIZATIONID"}]:</td>
                <td class="edittext">
                    <b>[{$oView->getPayPalAuthorizationId()}]</b>
                </td>
            </tr>
        </table>

        </br>
        <b>[{oxmultilang ident="OSC_PAYPAL_PAYMENT_HISTORY"}]: </b>
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
        <p><b>[{oxmultilang ident="OSC_PAYPAL_HISTORY_NOTICE"}]: </b>[{oxmultilang ident="OSC_PAYPAL_HISTORY_NOTICE_TEXT"}]
        </p>
    </td>
    <td class="edittext" valign="top" align="left">
        <b>[{oxmultilang ident="OSC_PAYPAL_ORDER_PRODUCTS"}]: </b>
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
    </tbody>
    </table>

    [{oxmultilang ident="OSC_PAYPAL_ACTIONS"}]: <br>
    <form action="[{$oViewConf->getSelfLink()}]" method="post">
        [{$oViewConf->getHiddenSid()}]
        <input type="hidden" name="fnc" value="capture">
        <input type="hidden" name="cl" value="PayPalOrderController">
        <input type="hidden" name="oxid" value="[{$oxid}]">
        <input type="hidden" name="language" value="[{$actlang}]">
        <input type="submit" value="[{oxmultilang ident="OSC_PAYPAL_CAPTURE"}]">
    </form>

    <div style="margin-top: 10px">
        [{oxmultilang ident="OSC_PAYPAL_ISSUE_REFUND"}]
        <form action="[{$oViewConf->getSelfLink()}]" method="post">
            [{$oViewConf->getHiddenSid()}]
            <input type="hidden" name="fnc" value="refund">
            <input type="hidden" name="cl" value="PayPalOrderController">
            <input type="hidden" name="oxid" value="[{$oxid}]">
            <input type="hidden" name="language" value="[{$actlang}]">
            <table class="paypalActionsTable">
                <tr>
                    <td><label for="refundAmount">[{oxmultilang ident="OSC_PAYPAL_REFUND_AMOUNT"}]</label></td>
                    <td><input type="number" id="refundAmount" name="refundAmount"></td>
                </tr>
                <tr>
                    <td><label for="invoiceId">[{oxmultilang ident="OSC_PAYPAL_INVOICE_ID"}]</label></td>
                    <td><input type="text" id="invoiceId" name="invoiceId" maxlength="127"></td>
                </tr>
                <tr>
                    <td><label for="noteToBuyer">[{oxmultilang ident="OSC_PAYPAL_NOTE_TO_BUYER"}]</label></td>
                    <td><textarea id="noteToBuyer" name="noteToBuyer" maxlength="255"></textarea></td>
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
[{/if}]
[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]
