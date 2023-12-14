[{assign var="vaultedPaymentSources" value=$oViewConf->getVaultPaymentTokens()}]

[{if $vaultedPaymentSources}]
    <div class="card">
        <div class="card-header">
            <h3 id="paymentHeader" class="card-title">[{oxmultilang ident="OSC_PAYPAL_VAULTING_VAULTED_PAYMENTS"}]</h3>
        </div>
        <div class="card-body">
            <ul>
                [{foreach from=$vaultedPaymentSources item=paymentToken}]
                    [{if $paymentToken.payment_source.card}]
                            [{assign var="brand" value=$paymentToken.payment_source.card.brand}]
                            [{assign var="lastdigits" value=$paymentToken.payment_source.card.last_digits}]
                        <li>[{$brand}] [{oxmultilang ident="OSC_PAYPAL_CARD_ENDING_IN"}][{$lastdigits}]</li>
                    [{elseif $paymentToken.payment_source.paypal}]
                        [{assign var="lastdigits" value=$paymentToken.payment_source.paypal.email_address}]
                        <li>[{oxmultilang ident="OSC_PAYPAL_CARD_PAYPAL_PAYMENT"}] [{$lastdigits}]</li>
                    [{/if}]
                [{/foreach}]
            </ul>
        </div>
    </div>
[{/if}]