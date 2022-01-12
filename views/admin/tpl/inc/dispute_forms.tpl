
<form method="post" action="[{$oViewConf->getSelfLink()}]">
    <input type="hidden" name="oxid" value="[{$dispute->dispute_id}]">
    <input type="hidden" name="fnc" value="makeOffer">
    <input type="hidden" name="cl" value="PayPalDisputeDetailsController">

    <div class="form-group">
        <label for="offerType">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_OFFER_TYPE"}]</label>
        <select name="offerType" class="form-control" id="offerType">
            <option value="REFUND">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_REFUND"}]</option>
            <option value="REFUND_WITH_RETURN">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_REFUND_WITH_RETURN"}]</option>
            <option value="REFUND_WITH_REPLACEMENT">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_REFUND_WITH_REPLACEMENT"}]</option>
            <option value="REPLACEMENT_WITHOUT_REFUND">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_REPLACEMENT_WITHOUT_REFUND"}]</option>
        </select>
    </div>

    <div class="form-group">
        <label for="note">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_NOTE"}]</label>
        <input type="text" class="form-control" id="note" name="note">
    </div>

    <div class="form-group">
        <label for="offerAmount">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_OFFER_AMOUNT"}]</label>
        <input type="number" class="form-control" id="offerAmount" name="offerAmount[value]" disabled>
        <input type="hidden" name="offerAmount[currency_code]" value="" disabled>
    </div>

    <div class="form-group">
        <label for="shippingAddressLine1">[{oxmultilang ident="OSC_PAYPAL_ADDRESS_LINE_1"}]</label>
        <input type="text"
               class="form-control"
               id="shippingAddressLine1"
               name="shippingAddress[address][address_line_1]"
               value="[{$shippingDetails->address->address_line_1}]">
    </div>

    <div class="form-group">
        <label for="shippingAddressLine2">[{oxmultilang ident="OSC_PAYPAL_ADDRESS_LINE_2"}]</label>
        <input type="text"
               class="form-control"
               id="shippingAddressLine2"
               name="shippingAddress[address][address_line_2]"
               value="[{$shippingDetails->address->address_line_2}]">
    </div>

    <div class="form-group">
        <label for="shippingAddressLine3">[{oxmultilang ident="OSC_PAYPAL_ADDRESS_LINE_3"}]</label>
        <input type="text"
               class="form-control"
               id="shippingAddressLine3"
               name="shippingAddress[address][address_line_3]"
               value="[{$shippingDetails->address->address_line_3}]">
    </div>

    <div class="form-group">
        <label for="shippingAddressAdminArea1">[{oxmultilang ident="OSC_PAYPAL_ADMIN_AREA_1"}]</label>
        <input type="text"
               class="form-control"
               id="shippingAddressAdminArea1"
               name="shippingAddress[address][admin_area_1]"
               value="[{$shippingDetails->address->admin_area_1}]">
    </div>

    <div class="form-group">
        <label for="shippingAddressAdminArea2">[{oxmultilang ident="OSC_PAYPAL_ADMIN_AREA_2"}]</label>
        <input type="text"
               class="form-control"
               id="shippingAddressAdminArea2"
               name="shippingAddress[address][admin_area_2]"
               value="[{$shippingDetails->address->admin_area_2}]">
    </div>

    <div class="form-group">
        <label for="shippingAddressAdminArea3">[{oxmultilang ident="OSC_PAYPAL_ADMIN_AREA_3"}]</label>
        <input type="text"
               class="form-control"
               id="shippingAddressAdminArea3"
               name="shippingAddress[address][admin_area_3]"
               value="[{$shippingDetails->address->admin_area_3}]">
    </div>

    <div class="form-group">
        <label for="shippingAddressAdminArea4">[{oxmultilang ident="OSC_PAYPAL_ADMIN_AREA_4"}]</label>
        <input type="text"
               class="form-control"
               id="shippingAddressAdminArea4"
               name="shippingAddress[address][admin_area_4]"
               value="[{$shippingDetails->address->admin_area_4}]">
    </div>

    <div class="form-group">
        <label for="shippingAddressPostalCode">[{oxmultilang ident="OSC_PAYPAL_POSTAL_CODE"}]</label>
        <input type="text"
               class="form-control"
               id="shippingAddressPostalCode"
               name="shippingAddress[address][postal_code]"
               value="[{$shippingDetails->address->postal_code}]">
    </div>

    <div class="form-group">
        <label for="shippingAddressCountryCode">[{oxmultilang ident="OSC_PAYPAL_COUNTRY_CODE"}]</label>
        <input type="text"
               class="form-control"
               id="shippingAddressCountryCode"
               name="shippingAddress[address][country_code]"
               value="[{$shippingDetails->address->country_code}]">
    </div>

    <div class="form-group">
        <label for="invoiceId">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_INVOICE_ID"}]</label>
        <input type="text" class="form-control" id="invoiceId" name="invoiceId" maxlength="127">
    </div>

    <div class="col-sm-12">
        <button class="btn btn-primary" type="submit">[{oxmultilang ident="OSC_PAYPAL_APPLY"}]</button>
    </div>
</form>
</div>
