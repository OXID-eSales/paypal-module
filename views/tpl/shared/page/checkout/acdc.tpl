<!-- Advanced credit and debit card payments form -->
<div id="card_container" class="card_container">
    <div id="card_form">
        <div class="form-group" id="card-number-field-container">
        </div>
        <div class="form-group" id="card-expiry-field-container">
        </div>
        <div class="form-group" id="card-cvv-field-container">
        </div>
        <div class="form-group" id="card-name-field-container">
        </div>
        [{if $oscpaypal_isVaultingPossible}]
            <input type="checkbox" id="oscPayPalVaultPaymentCheckbox" disabled>
            <label for="oscPayPalVaultPaymentCheckbox">[{oxmultilang ident="OSC_PAYPAL_VAULTING_SAVE"}]</label>
        [{/if}]
        <div class="hidden">
            <input type="hidden" id="card-billing-address-street" name="card-billing-address-street" value="[{if $oxcmp_user->oxuser__oxstreet->value}][{$oxcmp_user->oxuser__oxstreet->value}][{/if}] [{if $oxcmp_user->oxuser__oxstreetnr->value}][{$oxcmp_user->oxuser__oxstreetnr->value}][{/if}]" />
            <input type="hidden" id="card-billing-address-unit" name="card-billing-address-unit" value=""/>
            <input type="hidden" id="card-billing-address-city" name="card-billing-address-city" value="[{if $oxcmp_user->oxuser__oxcity->value}][{$oxcmp_user->oxuser__oxcity->value}][{/if}]" />
            <input type="hidden" id="card-billing-address-state" name="card-billing-address-state" value="[{$oView->getUserStateIso()}]" />
            <input type="hidden" id="card-billing-address-zip" name="card-billing-address-zip" value="[{if $oxcmp_user->oxuser__oxzip->value}][{$oxcmp_user->oxuser__oxzip->value}][{/if}]" />
            <input type="hidden" id="card-billing-address-country" name="card-billing-address-country" value="[{$oView->getUserCountryIso()}]"/>
        </div>
    </div>
</div>
