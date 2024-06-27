[{if $sPaymentID == "oscpaypal_express"}]
    [{include file='@osc_paypal/frontend/select_payment.tpl'}]
[{elseif $sPaymentID == "oscpaypal_sepa" || $sPaymentID == "oscpaypal_cc_alternative"}]
    [{assign var="config" value=$oViewConf->getPayPalCheckoutConfig()}]
    [{if $config->isActive() && !$oViewConf->isPayPalExpressSessionActive()}]
        <dl>
            <dt>
                [{include file="@osc_paypal/frontend/select_payment.tpl"}]
                <label for="payment_[{$sPaymentID}]"><b>[{$paymentmethod->oxpayments__oxdesc->value}]</b></label>
                <div class="paypal-sepa-option-info[{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}] activePayment[{/if}]">
                    [{foreach from=$paymentmethod->getDynValues() item="value" name="dynvalues"}]
                        <div class="form-floating mb-3">
                            <input type="text" size="20"
                                   maxlength="64" name="dynvalue[[{$value->name}]]" value="[{$value->value}]"
                                   class="form-control" id="[{$sPaymentID}]_[{$smarty.foreach.dynvalues.index}]"
                                   placeholder="name@example.com">
                            <label for="[{$sPaymentID}]_[{$smarty.foreach.dynvalues.index}]">[{$value->name}]</label>
                        </div>
                    [{/foreach}]

                    [{include file="@osc_paypal/frontend/paymentbuttons.tpl" buttonId=$sPaymentID buttonClass="paypal-button-wrapper large"}]

                    [{block name="checkout_payment_longdesc"}]
                        [{if $paymentmethod->oxpayments__oxlongdesc->value|strip_tags|trim}]
                            <div class="desc">
                                [{$paymentmethod->oxpayments__oxlongdesc->value}]
                            </div>
                        [{/if}]
                    [{/block}]
                </div>
            </dt>
            [{if $paymentmethod->getPrice()}]
                <dt>
                    [{assign var="oPaymentPrice" value=$paymentmethod->getPrice()}]
                    [{if $oViewConf->isFunctionalityEnabled('blShowVATForPayCharge')}][{strip}]
                        [{oxprice price=$oPaymentPrice->getNettoPrice() currency=$currency}]
                        [{if $oPaymentPrice->getVatValue() > 0}]
                            [{oxmultilang ident="PLUS_VAT"}] [{oxprice price=$oPaymentPrice->getVatValue() currency=$currency}]
                        [{/if}]
                    [{/strip}][{else}]
                        [{oxprice price=$oPaymentPrice->getBruttoPrice() currency=$currency}]
                    [{/if}]
                </dt>
            [{/if}]
        </dl>
    [{/if}]
[{else}]
    [{$smarty.block.parent}]
[{/if}]
