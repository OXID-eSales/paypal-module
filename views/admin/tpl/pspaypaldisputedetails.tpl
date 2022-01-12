[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"}]
<div class="container-fluid">
    [{if !empty($error)}]
        <div class="alert alert-danger" role="alert">
            [{$error}]
        </div>
    [{/if}]
    <div class="row">
        <div class="col-lg-12">
            <h2>
                [{$dispute->dispute_id}]: [{oxmultilang ident="OSC_PAYPAL_DISPUTE_REASON_"|cat:$dispute->reason}]
                <small>([{$dispute->create_time|date_format:"%Y-%m-%d %H:%M:%S"}])</small>
            </h2>
            <h4>
                [{oxmultilang ident="OSC_PAYPAL_DISPUTE_STATUS_"|cat:$dispute->status}]
                <small>([{$dispute->update_time|date_format:"%Y-%m-%d %H:%M:%S"}])</small>
            </h4>
        </div>
    </div>
    <br />
    <ul class="nav nav-tabs">
        <li class="active">
            <a href="#" id="history-tab">[{oxmultilang ident="OSC_PAYPAL_HISTORY"}]</a>
        </li>
        [{if $dispute->status !== 'RESOLVED' && $dispute->status !== 'UNDER_REVIEW'}]
            <li class="active">
                <a href="#" id="messages-tab">
                    [{oxmultilang ident="OSC_PAYPAL_MESSAGES"}]
                </a>
            </li>
            <li>
                <a href="#" id="offer-tab">
                    [{oxmultilang ident="OSC_PAYPAL_MAKE_OFFER"}]
                </a>
            </li>
            <li>
                <a href="#" id="escalate-tab">
                    [{oxmultilang ident="OSC_PAYPAL_ESCALATE"}]
                </a>
            </li>
            <li>
                <a href="#" id="evidence-tab">
                    [{oxmultilang ident="OSC_PAYPAL_PROVIDE_EVIDENCE"}]
                </a>
            </li>
        [{/if}]
    </ul>

    <div class="pptab" id="history">
        <div class="row">
            <div class="col-lg-12">
                <h3 class="ppaltmessages">
                    [{oxmultilang ident="OSC_PAYPAL_HISTORY"}]
                </h3>
            </div>
        </div>

        [{if $dispute->dispute_outcome}]
            <div class="row">
                <div class="col-lg-12">
                    <div class="messageRow">
                        <h4>
                            [{oxmultilang ident="OSC_PAYPAL_"|cat:$dispute->dispute_outcome->outcome_code}]
                            <small>
                                [{$dispute->update_time|date_format:"%Y-%m-%d %H:%M:%S"}]
                            </small>
                        </h4>
                        <p>
                            [{$dispute->dispute_outcome->amount_refunded->currency_code}]
                            [{$dispute->dispute_outcome->amount_refunded->value}]
                        </p>
                    </div>
                </div>
                <hr />
            </div>
        [{/if}]

        [{if $dispute->offer->history}]
        <div class="row">
            <div class="col-lg-12">
                [{foreach from=$dispute->offer->history|@array_reverse item="history"}]
                    <div class="messageRow">
                        <h4>
                            [{$history->actor}]
                            <small>[{$history->offer_time|date_format:"%Y-%m-%d %H:%M:%S"}]</small>
                        </h4>
                        <p>
                            [{$history->event_type}]
                            [{$history->offer_type}]
                            [{$history->offer_amount->currency_code}]
                            [{$history->offer_amount->value}]
                        </p>
                    </div>
                [{/foreach}]
            </div>
            <hr />
        </div>
        [{/if}]

        <div class="row">
            <div class="col-lg-12">
                <div class="messageRow">
                    <h4>
                        [{oxmultilang ident="OSC_PAYPAL_CREATED" suffix="COLON"}]
                        <small>
                            [{$dispute->create_time|date_format:"%Y-%m-%d %H:%M:%S"}]
                        </small>
                    </h4>
                </div>
            </div>
            <hr />
        </div>

    </div>
    <div class="pptab" id="messages">
        <div class="row">
            <div class="col-lg-12">
                <h3 class="ppaltmessages">
                    [{oxmultilang ident="OSC_PAYPAL_MESSAGES"}]
                </h3>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 ppmessages">
                <div class="col-lg-4">
                    <form method="post" action="[{$oViewConf->getSelfLink()}]">
                        <input type="hidden" name="oxid" value="[{$dispute->dispute_id}]">
                        <input type="hidden" name="fnc" value="sendMessage">
                        <input type="hidden" name="cl" value="PayPalDisputeDetailsController">
                        <textarea name="message" class="form-control" cols="80" rows="5"></textarea>
                        <br />
                        <button class="btn btn-primary" type="submit">[{oxmultilang ident="OSC_PAYPAL_SEND"}]</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                [{foreach from=$dispute->messages|@array_reverse item="message"}]
                    [{if $message->posted_by == 'SELLER'}]
                        <div class="messageRow">
                            <h4>
                                [{oxmultilang ident="OSC_PAYPAL_ME" suffix="COLON"}]
                                <small>
                                    [{$message->time_posted|date_format:"%Y-%m-%d %H:%M:%S"}]
                                </small>
                            </h4>
                            <p>
                                [{$message->content}]
                            </p>
                        </div>
                    [{else}]
                        <div class="messageRow">
                            <h4>
                                [{$message->posted_by}]
                                <small>
                                    [{$message->time_posted|date_format:"%Y-%m-%d %H:%M:%S"}]
                                </small>
                            </h4>
                            <p>
                                [{$message->content}]
                            </p>
                        </div>
                    [{/if}]
                [{/foreach}]
            </div>
            <hr />
        </div>
    </div>
    <div class="pptab" id="offer">
        <div class="row">
            <div class="col-lg-12">
                <h3 class="ppaltmessages">
                    [{oxmultilang ident="OSC_PAYPAL_MAKE_OFFER"}]
                </h3>
            </div>
        </div>
        <form method="post" action="[{$oViewConf->getSelfLink()}]" class="ppmessages">
            <input type="hidden" name="oxid" value="[{$dispute->dispute_id}]">
            <input type="hidden" name="fnc" value="makeOffer">
            <input type="hidden" name="cl" value="PayPalDisputeDetailsController">
            <div class="row">
                <div class="col-lg-1">
                    <div class="form-group">
                        <label for="offerType">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_OFFER_TYPE"}]</label>
                        <select name="offerType" class="form-control" id="offerType">
                            <option value="REFUND">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_REFUND"}]</option>
                            <option value="REFUND_WITH_RETURN">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_REFUND_WITH_RETURN"}]</option>
                            <option value="REFUND_WITH_REPLACEMENT">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_REFUND_WITH_REPLACEMENT"}]</option>
                            <option value="REPLACEMENT_WITHOUT_REFUND">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_REPLACEMENT_WITHOUT_REFUND"}]</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-1">
                    <div class="form-group">
                        <label for="offerAmount">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_OFFER_AMOUNT"}]</label>
                        <input type="number" class="form-control" id="offerAmount" name="offerAmount[value]">
                        <input type="hidden" name="offerAmount[currency_code]" value="EUR">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-1">
                    <div class="form-group">
                        <label for="note">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_NOTE"}]</label>
                        <input type="text" class="form-control" id="note" name="note">
                    </div>
                </div>
                <div class="col-lg-1">
                    <div class="form-group">
                        <label for="invoiceId">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_INVOICE_ID"}]</label>
                        <input type="text" class="form-control" id="invoiceId" name="invoiceId" maxlength="127">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="shippingAddressLine1">[{oxmultilang ident="OSC_PAYPAL_ADDRESS_LINE_1"}]</label>
                        <input type="text"
                               class="form-control"
                               id="shippingAddressLine1"
                               name="shippingAddress[address][address_line_1]"
                               value="[{$shippingDetails->address->address_line_1}]">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="shippingAddressLine2">[{oxmultilang ident="OSC_PAYPAL_ADDRESS_LINE_2"}]</label>
                        <input type="text"
                               class="form-control"
                               id="shippingAddressLine2"
                               name="shippingAddress[address][address_line_2]"
                               value="[{$shippingDetails->address->address_line_2}]">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="shippingAddressLine3">[{oxmultilang ident="OSC_PAYPAL_ADDRESS_LINE_3"}]</label>
                        <input type="text"
                               class="form-control"
                               id="shippingAddressLine3"
                               name="shippingAddress[address][address_line_3]"
                               value="[{$shippingDetails->address->address_line_3}]">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-1">
                    <div class="form-group">
                        <label for="shippingAddressPostalCode">[{oxmultilang ident="OSC_PAYPAL_POSTAL_CODE"}]</label>
                        <input type="text"
                               class="form-control"
                               id="shippingAddressPostalCode"
                               name="shippingAddress[address][postal_code]"
                               value="[{$shippingDetails->address->postal_code}]">
                    </div>
                </div>
                <div class="col-lg-1">
                    <div class="form-group">
                        <label for="shippingAddressCountryCode">[{oxmultilang ident="OSC_PAYPAL_COUNTRY_CODE"}]</label>
                        <input type="text"
                               class="form-control"
                               id="shippingAddressCountryCode"
                               name="shippingAddress[address][country_code]"
                               value="[{$shippingDetails->address->country_code}]">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-2">
                    <button class="btn btn-primary" type="submit">[{oxmultilang ident="OSC_PAYPAL_APPLY"}]</button>
                </div>
            </div>
        </form>
    </div>
    <div class="pptab" id="escalate">
        <form method="post" action="[{$oViewConf->getSelfLink()}]">
            <input type="hidden" name="oxid" value="[{$dispute->dispute_id}]">
            <input type="hidden" name="fnc" value="escalate">
            <input type="hidden" name="cl" value="PayPalDisputeDetailsController">
            <input type="hidden" name="requestedAmount" value="[{$dispute->dispute_amount->value}]">
            <input type="hidden" name="requestedAmountCurrency" value="[{$dispute->dispute_amount->currency_code}]">

            <div class="row">
                <div class="col-lg-12">
                    <h3 class="ppaltmessages">[{oxmultilang ident="OSC_PAYPAL_ESCALATE"}]</h3>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-3">
                    <div class="form-group">
                        <label for="note">[{oxmultilang ident="OSC_PAYPAL_DISPUTE_NOTE"}]</label>
                        <textarea name="note" class="form-control" cols="80" rows="5"></textarea>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-2">
                    <button class="btn btn-primary" type="submit">[{oxmultilang ident="OSC_PAYPAL_ESCALATE"}]</button>
                </div>
            </div>
        </form>
    </div>
    <div class="pptab" id="evidence">
        <form method="post" action="[{$oViewConf->getSelfLink()}]" enctype="multipart/form-data">
            <input type="hidden" name="oxid" value="[{$dispute->dispute_id}]">
            <input type="hidden" name="fnc" value="provideEvidence">
            <input type="hidden" name="cl" value="PayPalDisputeDetailsController">

            <div class="row">
                <div class="row">
                    <div class="col-lg-12">
                        <h3 class="ppaltmessages">[{oxmultilang ident="OSC_PAYPAL_PROVIDE_EVIDENCE"}]</h3>
                    </div>
                </div>
            </div>

            [{foreach from=$loops item=l}]
                <div class="row ppmessages show[{$l}]">
                    <h4>
                        [{oxmultilang ident="OSC_PAYPAL_EVIDENCE_DOCUMENT"}] [{$l}]
                    </h4>
                </div>
                <div class="row ppaltmessages show[{$l}]">
                    <div class="col-lg-2">
                        <div class="form-group">
                            <label for="evidenceType[{$l}]">[{oxmultilang ident="OSC_PAYPAL_EVIDENCE_TYPE"}]</label>
                            <select name="evidenceType[{$l}]" class="form-control" id="evidenceType[{$l}]">
                                [{foreach from=$evidenceTypes item=i key=k}]
                                    <option value="[{$i|replace:'EVIDENCE_TYPE_':''}]">
                                        [{oxmultilang ident="OSC_PAYPAL_"|cat:$k}]
                                    </option>
                                [{/foreach}]
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label for="note">[{oxmultilang ident="OSC_PAYPAL_UPLOAD_FILE"}]</label>
                            <input type="file" name="evidenceFile[{$l}]" id="evidenceFile[{$l}]">
                        </div>
                    </div>
                </div>
                [{if $l < 5}]
                    <div class="row ppmessages show[{$l}] addMoreButton[{$l}]">
                        <div class="col-lg-2">
                            <button class="btn btn-primary" onCLick="javascript:addFile([{$l}]);" type="button">[{oxmultilang ident="OSC_PAYPAL_MORE"}]</button>
                        </div>
                    </div>
                [{/if}]
            [{/foreach}]

            <div class="row ppaltmessages">
                <div class="col-lg-2">
                    <button class="btn btn-primary" type="submit">[{oxmultilang ident="OSC_PAYPAL_UPLOAD_FILE"}]</button>
                </div>
            </div>
        </form>
    </div>
</div>
[{capture assign="sPayPalDisputeDetailsJS"}]
    [{strip}]
        function addFile(fileNum) {
            jQuery('.show' + (fileNum+1)).show();
            jQuery('.addMoreButton' + (fileNum)).hide();
        }

        jQuery(document).ready(function(){

            jQuery('.show2').hide();
            jQuery('.show3').hide();
            jQuery('.show4').hide();
            jQuery('.show5').hide();

            let showItem = function(divId) {
                jQuery(".nav-tabs li").removeClass('active');
                jQuery(".pptab").hide();
                jQuery("#" + divId).show();
                jQuery("#" + divId + "-tab").parent().addClass('active');
            };

            let listenToTab = function(divId) {
                jQuery("#" + divId + "-tab").click(function(e) {
                    e.preventDefault();
                    showItem(divId);
                });
            }

            showItem("history");
            listenToTab("history");
            listenToTab("messages");
            listenToTab("offer");
            listenToTab("escalate");
            listenToTab("evidence");

            jQuery(".messageRow").filter(':odd').css("background-color", "#f4f4f4");
        });
    [{/strip}]
[{/capture}]
[{oxscript add=$sPayPalDisputeDetailsJS}]
