[{assign var="vaultedPaymentSources" value=$oViewConf->getVaultPaymentTokens()}]

[{if $vaultedPaymentSources}]
    <div class="card">
        <div class="card-header">
            <h3 id="paymentHeader" class="card-title">[{oxmultilang ident="OSC_PAYPAL_VAULTING_VAULTED_PAYMENTS"}]</h3>
        </div>
        <div class="card-body">
            <ul>
                [{foreach from=$vaultedPaymentSources item=paymentToken}]
                    <li class="mt-3">
                        <form action="[{$oViewConf->getSslSelfLink()}]" method="post">
                            <div class="hidden">
                                [{$oViewConf->getHiddenSid()}]
                                <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
                                <input type="hidden" name="fnc" value="deleteVaultedPayment">
                                <input type="hidden" name="paymentTokenId" value="[{$paymentToken.id}]">
                            </div>

                            [{if $paymentToken.payment_source.card}]
                                [{assign var="brand" value=$paymentToken.payment_source.card.brand}]
                                [{assign var="lastdigits" value=$paymentToken.payment_source.card.last_digits}]
                                [{$brand}] [{oxmultilang ident="OSC_PAYPAL_CARD_ENDING_IN"}][{$lastdigits}]
                            [{elseif $paymentToken.payment_source.paypal}]
                                [{assign var="lastdigits" value=$paymentToken.payment_source.paypal.email_address}]
                                [{oxmultilang ident="OSC_PAYPAL_CARD_PAYPAL_PAYMENT"}] [{$lastdigits}]
                            [{/if}]

                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                [{oxmultilang ident="OSC_PAYPAL_VAULTING_DELETE"}]
                            </button>
                        </form>
                    </li>
                [{/foreach}]
            </ul>
        </div>
    </div>
[{/if}]
