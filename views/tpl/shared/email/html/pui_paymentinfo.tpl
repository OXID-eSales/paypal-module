[{assign var="shop"      value=$oEmailView->getShop()}]
[{assign var="oViewConf" value=$oEmailView->getViewConfig()}]

[{capture assign="style"}]
    table.orderarticles th {
        white-space: nowrap;
    }

    table.orderarticles th, table.orderarticles td {
        border: 1px solid #d4d4d4;
        font-size: 13px;
        padding:5px;
        white-space: nowrap;
    }

    table.orderarticles {
        border-collapse: collapse;
    }

    table.orderarticles thead th {
        background-color: #ebebeb;
    }
[{/capture}]

[{include file="email/html/header.tpl" title="OSC_PAYPAL_PAYMENT_PUI_HEADING"|oxmultilangassign|cat:" #"|cat:$order->oxorder__oxordernr->value style=$style}]

    [{block name="email_html_pui_paymentinfo_billingheader"}]
        <h3 class="underline">[{oxmultilang ident="BILLING_ADDRESS"}]</h3>
    [{/block}]

    [{block name="email_html_pui_paymentinfo_billingaddress"}]
        <p>
          [{$order->oxorder__oxbillcompany->value}]<br>
          [{$order->oxorder__oxbillfname->value}] [{$order->oxorder__oxbilllname->value}]<br>
          [{$order->oxorder__oxbillstreet->value}] [{$order->oxorder__oxbillstreetnr->value}]<br>
          [{$order->oxorder__oxbillstateid->value}]
          [{$order->oxorder__oxbillzip->value}] [{$order->oxorder__oxbillcity->value}]
        </p>
        <br/>
    [{/block}]
    
    [{block name="email_html_pui_paymentinfo_shippingheader"}]
        <h3 class="underline">[{oxmultilang ident="SHIPPING_ADDRESS"}]</h3>
    [{/block}]

    [{block name="email_html_pui_paymentinfo_shippingaddress"}]
        <p>
            [{if $order->oxorder__oxdellname->value}]
              [{$order->oxorder__oxdelcompany->value}]<br>
              [{$order->oxorder__oxdelfname->value}] [{$order->oxorder__oxdellname->value}]<br>
              [{$order->oxorder__oxdelstreet->value}] [{$order->oxorder__oxdelstreetnr->value}]<br>
              [{$order->oxorder__oxdelstateid->value}]
              [{$order->oxorder__oxdelzip->value}] [{$order->oxorder__oxdelcity->value}]
            [{else}]
              [{$order->oxorder__oxbillcompany->value}]<br>
              [{$order->oxorder__oxbillfname->value}] [{$order->oxorder__oxbilllname->value}]<br>
              [{$order->oxorder__oxbillstreet->value}] [{$order->oxorder__oxbillstreetnr->value}]<br>
              [{$order->oxorder__oxbillstateid->value}]
              [{$order->oxorder__oxbillzip->value}] [{$order->oxorder__oxbillcity->value}]
            [{/if}]
        </p>
        <br/>
    [{/block}]

    [{block name="email_html_pui_paymentinfo_paymentheader"}]
        <h3 class="underline">[{oxmultilang ident="PAYMENT_INFORMATION"}]</h3>
    [{/block}]

    [{block name="email_html_pui_paymentinfo_paymentbody"}]
        <p>[{oxmultilang ident="OSC_PAYPAL_PAYMENT_PUI_NOTE" suffix="COLON"}]</p>
        <table class="orderarticles" border="0" cellspacing="0" cellpadding="0" width="100%">
            <tbody>
                <tr valign="top">
                    <th align="right" class="text-right">[{oxmultilang ident="OSC_PAYPAL_PAYMENT_PUI_IBAN"}]</th>
                    <td>[{$puiPaymentDetails->iban}]</td>
                </tr>
                <tr valign="top">
                    <th align="right" class="text-right">[{oxmultilang ident="OSC_PAYPAL_PAYMENT_PUI_BIC"}]</th>
                    <td>[{$puiPaymentDetails->bic}]</td>
                </tr>
                <tr valign="top">
                    <th align="right" class="text-right">[{oxmultilang ident="OSC_PAYPAL_PAYMENT_PUI_BANKNAME"}]</th>
                    <td>[{$puiPaymentDetails->bank_name}]</td>
                </tr>
                <tr valign="top">
                    <th align="right" class="text-right">[{oxmultilang ident="OSC_PAYPAL_PAYMENT_PUI_ACCOUNTHOLDER"}]</th>
                    <td>[{$puiPaymentDetails->account_holder_name}]</td>
                </tr>
                <tr valign="top">
                    <th align="right" class="text-right">[{oxmultilang ident="OSC_PAYPAL_PAYMENT_PUI_REFERENCE"}]</th>
                    <td>[{$puiPaymentDetails->payment_reference}]</td>
                </tr>
                <tr valign="top">
                    <th align="right" class="text-right">[{oxmultilang ident="GRAND_TOTAL"}]</th>
                    <td>[{oxprice price=$order->oxorder__oxtotalbrutsum->value currency=$currency}]</td>
                </tr>
            </tbody>
        </table>
        <br />
    [{/block}]

    [{block name="email_html_pui_paymentinfo_oxordernr"}]
        <h3 class="underline">[{oxmultilang ident="ORDER_NUMBER" suffix="COLON"}] [{$order->oxorder__oxordernr->value}]</h3>
    [{/block}]

    <table class="orderarticles" border="0" cellspacing="0" cellpadding="0" width="100%">
        <thead>
            <tr>
                <th class="text-right">[{oxmultilang ident="QUANTITY"}]</th>
                <th>[{oxmultilang ident="PRODUCT"}]</th>
            </tr>
        </thead>
        <tbody>
            [{block name="email_html_pui_paymentinfo_orderarticles"}]
                [{foreach from=$order->getOrderArticles(true) item=oOrderArticle}]
                    <tr valign="top">
                        <td align="right" class="text-right">[{$oOrderArticle->oxorderarticles__oxamount->value}]</td>
                        <td>
                            [{$oOrderArticle->oxorderarticles__oxtitle->value}] [{$oOrderArticle->oxorderarticles__oxselvariant->value}]
                            <br>[{oxmultilang ident="PRODUCT_NO" suffix="COLON"}] [{$oOrderArticle->oxorderarticles__oxartnum->value}]
                        </td>
                    </tr>
                [{/foreach}]
            [{/block}]
        </tbody>
    </table>
    <br/>
[{include file="email/html/footer.tpl"}]