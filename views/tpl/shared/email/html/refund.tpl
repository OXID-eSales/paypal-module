[{assign var="shop" value=$oEmailView->getShop()}]
[{assign var="oViewConf" value=$oEmailView->getViewConfig()}]

[{capture assign="style"}]
    table.paypalrefund th, table.paypalrefund td {
        border: 1px solid #d4d4d4;
        font-size: 13px;
        padding: 5px;
        white-space: nowrap;
    }

    table.paypalrefund {
        border-collapse: collapse;
    }
[{/capture}]

[{include file="email/html/header.tpl" title="OSC_PAYPAL_REFUND_MAIL_TITLE"|oxmultilangassign|cat:" #"|cat:$order->oxorder__oxordernr->value style=$style}]

    [{block name="paypal_email_html_refund_intro"}]
        <p>
            [{if $isPayPalOwnerMail}]
                [{oxmultilang ident="OSC_PAYPAL_REFUND_MAIL_INTRO_OWNER"}]
            [{else}]
                [{oxmultilang ident="OSC_PAYPAL_REFUND_MAIL_SALUTATION"}]
                [{$order->oxorder__oxbillfname->getRawValue()}] [{$order->oxorder__oxbilllname->getRawValue()}],
            [{/if}]
        </p>
        [{if !$isPayPalOwnerMail}]
            <p>[{oxmultilang ident="OSC_PAYPAL_REFUND_MAIL_INTRO"}]</p>
        [{/if}]
    [{/block}]

    [{block name="paypal_email_html_refund_details"}]
        <table class="paypalrefund" border="0" cellspacing="0" cellpadding="0" width="100%">
            <tbody>
                <tr valign="top">
                    <th align="right" class="text-right">[{oxmultilang ident="ORDER_NUMBER" suffix="COLON"}]</th>
                    <td>[{$order->oxorder__oxordernr->value}]</td>
                </tr>
                <tr valign="top">
                    <th align="right" class="text-right">[{oxmultilang ident="OSC_PAYPAL_REFUND_MAIL_AMOUNT" suffix="COLON"}]</th>
                    <td>[{$payPalRefundedAmount|string_format:"%.2f"}] [{$payPalCurrencyCode}]</td>
                </tr>
                <tr valign="top">
                    <th align="right" class="text-right">[{oxmultilang ident="OSC_PAYPAL_REFUND_MAIL_ORDER_TOTAL" suffix="COLON"}]</th>
                    <td>[{oxprice price=$order->oxorder__oxtotalordersum->value currency=$currency}]</td>
                </tr>
            </tbody>
        </table>
        <br/>
    [{/block}]

    [{block name="paypal_email_html_refund_note"}]
        [{if !$isPayPalOwnerMail}]
            <p>[{oxmultilang ident="OSC_PAYPAL_REFUND_MAIL_NOTE"}]</p>
        [{/if}]
    [{/block}]

[{include file="email/html/footer.tpl"}]
