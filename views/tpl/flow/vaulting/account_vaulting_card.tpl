<h1 class="page-header">[{oxmultilang ident="OSC_PAYPAL_VAULTING_MENU_CARD"}]</h1>

<div class="panel panel-default">
    <div class="panel-heading">[{oxmultilang ident="OSC_PAYPAL_VAULTING_SAVE_INSTRUCTION_CARD"}]</div>
    <div class="panel-body">
        <p id="PayPalVaultingSuccess" class="alert alert-success" style="display: none">[{oxmultilang ident="OSC_PAYPAL_VAULTING_SUCCESS"}]</p>
        <p id="PayPalVaultingFailure" class="alert alert-danger" style="display: none">[{oxmultilang ident="OSC_PAYPAL_VAULTING_ERROR"}]</p>
        <div class="card_container form-horizontal" id="payPalVaultingCardContainer">
            <div class="form-group">
                <label class="control-label col-lg-3 req">[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_NAME_ON_CARD"}]</label>
                <div class="col-lg-9">
                    <div id="card-holder-name"></div>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3 req">[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_NUMBER"}]</label>
                <div class="col-lg-9">
                    <div id="card-number"></div>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3 req">[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_EXDATE"}]</label>
                <div class="col-lg-9">
                    <div id="expiration-date"></div>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3 req">[{oxmultilang ident="OSC_PAYPAL_ACDC_CARD_CVV"}]</label>
                <div class="col-lg-9">
                    <div id="cvv"></div>
                </div>
            </div>
            <button value="submit" id="submit" class="btn btn-primary">[{oxmultilang ident="OSC_PAYPAL_VAULTING_CARD_SAVE"}]</button>
        </div>
    </div>
</div>
