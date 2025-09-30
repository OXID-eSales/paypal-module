[{assign var="vaultedPaymentSources" value=$oViewConf->getVaultPaymentTokens()}]

[{if $vaultedPaymentSources}]
    <div class="card">
        <div class="card-header">
            [{oxmultilang ident="OSC_PAYPAL_VAULTING_VAULTED_PAYMENTS"}]
        </div>
        <div class="card-body" id="savedPaymentCards">
            [{foreach from=$vaultedPaymentSources name=paymentTokens item=paymentToken}]
                <div class="payment-method">
                    <form action="[{$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]" method="post">
                        <div class="d-none">
                            [{$oViewConf->getHiddenSid()}]
                            <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
                            <input type="hidden" name="fnc" value="deleteVaultedPayment">
                            <input type="hidden" name="paymentTokenId" value="[{$paymentToken.id}]">
                        </div>
                        <div class="payment-info">
                            <i class="fa fa-credit-card"></i>
                            [{if $paymentToken.payment_source.card}]
                                [{assign var="brand" value=$paymentToken.payment_source.card.brand}]
                                [{assign var="lastdigits" value=$paymentToken.payment_source.card.last_digits}]
                                <strong>[{$brand}]</strong> [{oxmultilang ident="OSC_PAYPAL_CARD_ENDING_IN"}] [{$lastdigits}]
                            [{elseif $paymentToken.payment_source.paypal}]
                                [{assign var="lastdigits" value=$paymentToken.payment_source.paypal.email_address}]
                                <strong>[{oxmultilang ident="OSC_PAYPAL_CARD_PAYPAL_PAYMENT"}]</strong> [{$lastdigits}]
                            [{/if}]
                        </div>
                        <div class="payment-action mt-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fa fa-trash"></i> [{oxmultilang ident="OSC_PAYPAL_VAULTING_DELETE"}]
                            </button>
                        </div>
                    </form>
                </div>
                [{if !$smarty.foreach.paymentTokens.last }]<hr class="my-3">[{/if}]
                [{/foreach}]
        </div>
    </div>
[{/if}]

<div class="payment-method mt-4 text-right">
    <form action="[{$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]" method="post">
        <div class="d-none">
            [{$oViewConf->getHiddenSid()}]
            <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
            <input type="hidden" name="fnc" value="clearVaultedTokenCache">
        </div>
        <button type="submit" class="btn btn-info">
            <i class="fa fa-refresh"></i> [{oxmultilang ident="OSC_PAYPAL_VAULTING_REFRESH_CACHE"}]
        </button>
    </form>
</div>