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
    [{assign var="currency" value=$payPalOrder->getCurrency()}]

    <table width="98%" cellspacing="0" cellpadding="0" border="0">
        <tbody>
            <tr>
                <td class="edittext" valign="top">
                    <table class="paypalActionsTable" width="98%">
                        <tr>
                            <td class="edittext">[{oxmultilang ident="OEPAYPAL_SHOP_PAYMENT_STATUS"}]:</td>
                            <td class="edittext">
                                <b>[{oxmultilang ident='OEPAYPAL_STATUS_'|cat:$payPalOrder->getPaymentStatus()}]</b>
                            </td>
                        </tr>
                        <tr>
                            <td class="edittext">[{oxmultilang ident="OEPAYPAL_ORDER_PRICE"}]:</td>
                            <td class="edittext">
                                <b>[{$oView->formatPrice($payPalOrder->getTotalOrderSum())}] [{$currency}]</b>
                            </td>
                        </tr>
                        <tr>
                            <td class="edittext">[{oxmultilang ident="OEPAYPAL_CAPTURED_AMOUNT"}]:</td>
                            <td class="edittext">
                                <b>[{$oView->formatPrice($payPalOrder->getCapturedAmount())}] [{$currency}]</b>
                            </td>
                        </tr>
                        <tr>
                            <td class="edittext">[{oxmultilang ident="OEPAYPAL_REFUNDED_AMOUNT"}]:</td>
                            <td class="edittext">
                                <b>[{$oView->formatPrice($payPalOrder->getRefundedAmount())}] [{$currency}]</b>
                            </td>
                        </tr>
                        <tr>
                            <td class="edittext">[{oxmultilang ident="OEPAYPAL_CAPTURED_NET"}]:</td>
                            <td class="edittext">
                                <b>[{$oView->formatPrice($payPalOrder->getRemainingRefundAmount())}] [{$currency}]</b>
                            </td>
                        </tr>
                        <tr>
                            <td class="edittext">[{oxmultilang ident="OEPAYPAL_VOIDED_AMOUNT"}]:</td>
                            <td class="edittext">
                                <b>[{$oView->formatPrice($payPalOrder->getVoidedAmount())}] [{$currency}]</b>
                            </td>
                        </tr>
                        <tr>
                            <td class="edittext">[{oxmultilang ident="OEPAYPAL_AUTHORIZATIONID"}]:</td>
                            <td class="edittext">
                                <b>[{$order->oxorder__oxtransid->value}]</b>
                            </td>
                        </tr>
                    </table>
                    <br />
                    <b>[{oxmultilang ident="OEPAYPAL_PAYMENT_HISTORY"}]: </b>
                    <table id="historyTable">
                        <colgroup>
                            <col width="20%">
                            <col width="20%">
                            <col width="20%">
                            <col width="20%">
                            <col width="20%">
                        </colgroup>
                        <tr>
                            <td class="listheader first">[{oxmultilang ident="OEPAYPAL_HISTORY_DATE"}]</td>
                            <td class="listheader">[{oxmultilang ident="OEPAYPAL_HISTORY_ACTION"}]</td>
                            <td class="listheader">[{oxmultilang ident="OEPAYPAL_AMOUNT"}]</td>
                            <td class="listheader">
                                [{oxmultilang ident="OEPAYPAL_HISTORY_PAYPAL_STATUS"}]
                                [{oxinputhelp ident="OEPAYPAL_HISTORY_PAYPAL_STATUS_HELP"}]
                            </td>
                            <td class="listheader">[{oxmultilang ident="OEPAYPAL_HISTORY_ACTIONS"}]</td>
                        </tr>
                        [{foreach from=$payPalOrder->getPaymentList() item=listitem name=paypalHistory}]
                        [{cycle values='listitem,listitem2' assign='class'}]
                        <tr>
                            <td valign="top" class="[{$class}]">[{$listitem->getDate()}]</td>
                            <td valign="top" class="[{$class}]">[{$listitem->getAction()}]</td>
                            <td valign="top" class="[{$class}]">
                                [{$listitem->getAmount()}]
                                <small>[{$currency}]</small>
                            </td>
                            <td valign="top" class="[{$class}]">[{$listitem->getStatus()}]</td>
                            <td valign="top" class="[{$class}]">
                                <a class="popUpLink" href="#"
                                   data-block="historyDetailsBlock[{$smarty.foreach.paypalHistory.index}]">[{oxmultilang ident="OEPAYPAL_DETAILS"}]</a>

                                <div id="historyDetailsBlock[{$smarty.foreach.paypalHistory.index}]" class="paypalPopUp">
                                    <h3>[{oxmultilang ident="OEPAYPAL_DETAILS"}] ([{$listitem->getDate()}])</h3>

                                    <p>
                                        [{oxmultilang ident="OEPAYPAL_HISTORY_ACTION"}]: <b>[{$listitem->getAction()}]</b><br/>
                                        [{oxmultilang ident="OEPAYPAL_HISTORY_PAYPAL_STATUS"}]: <b>[{$listitem->getStatus()}]</b><br/>
                                    </p>

                                    <p>
                                        [{if $listitem->getRefundedAmount() > 0}]
                                        [{oxmultilang ident="OEPAYPAL_CAPTURED"}]: </label><b>[{$listitem->getAmount()}]
                                        <small>[{$currency}]</small>
                                    </b><br/>
                                        [{oxmultilang ident="OEPAYPAL_REFUNDED"}]: <b>[{$listitem->getRefundedAmount()}]
                                        <small>[{$currency}]</small>
                                    </b><br/>
                                        [{oxmultilang ident="OEPAYPAL_CAPTURED_NET"}]: <b>[{$listitem->getRemainingRefundAmount()}]
                                        <small>[{$currency}]</small>
                                    </b><br/>
                                        [{else}]
                                        [{oxmultilang ident="OEPAYPAL_AMOUNT"}]: </label><b>[{$listitem->getAmount()}]
                                        <small>[{$currency}]</small>
                                    </b><br/>
                                        [{/if}]
                                    </p>
                                    <p>
                                        <label>[{oxmultilang ident="OEPAYPAL_TRANSACTIONID"}]: </label><b>[{$listitem->getTransactionId()}]</b><br/>
                                        <label>[{oxmultilang ident="OEPAYPAL_CORRELATIONID"}]: </label><b>[{$listitem->getCorrelationId()}]</b><br/>
                                    </p>
                                    [{assign var="comments" value=$listitem->getCommentList()}]
                                    [{if $comments}]
                                    <div class="paypalHistoryComments">
                                        <span>[{oxmultilang ident="OEPAYPAL_COMMENT"}]: </span>
                                        [{foreach from=$comments item=comment}]
                                        <p>
                                            <small>[{$comment->getDate()}]</small>
                                            </br>
                                            [{$comment->getComment()}]
                                        </p>
                                        [{/foreach}]
                                    </div>
                                    [{/if}]
                                </div>
                            </td>
                        </tr>
                        [{/foreach}]
                    </table>
                    <p><b>[{oxmultilang ident="OEPAYPAL_HISTORY_NOTICE"}]: </b>[{oxmultilang ident="OEPAYPAL_HISTORY_NOTICE_TEXT"}]
                    </p>
                </td>
                <td class="edittext" valign="top" align="left">
                    <b>[{oxmultilang ident="OEPAYPAL_ORDER_PRODUCTS"}]: </b>
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
[{/if}]
[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]