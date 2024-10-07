[{if $oView->paidWithPayPal()}]
    [{capture name="populateCarrierScript"}]
        [{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
        [{if $phpStorm}]<script>[{/if}]
        function populateCarrier(countryCode) {
            fetch('[{$sSelfLink|cat:"cl=order_main&fnc=getPayPalTrackingCarrierProviderAsJson&countrycode="}]' + countryCode, {
                method: 'post'
            }).then(function (res) {
                return res.json();
            }).then(function (providerObj) {
                let providerHtml = '';
                Object.values(providerObj).forEach(provider => {
                    providerHtml += '<option value="' + provider.id + '">' + provider.title + '</option>';
                });
                document.getElementById("paypaltrackingcarrierprovider").innerHTML = providerHtml;
            });
        }
        [{if $phpStorm}]</script>[{/if}]
    [{/capture}]
    [{oxscript add=$smarty.capture.populateCarrierScript}]
    <tr>
        <td class="edittext" colspan="3">
            [{oxmultilang ident="ORDER_MAIN_SHIPPING_INFORMATION"}]
        </td>
    </tr>

    <tr>
        <td class="edittext">
            [{oxmultilang ident="OSC_PAYPAL_ORDER_MAIN_TRACKCARRIER_COUNTRY"}]&nbsp;&nbsp;
        </td>
        <td class="edittext">
            <select name="paypaltrackingcarriercountry" class="editinput" style="width: 135px;" onchange="populateCarrier(this.value)" [{$readonly}]>
                [{foreach from=$oView->getPayPalTrackingCarrierCountries() item=aCountry}]
                    <option value="[{$aCountry.id}]" [{if $aCountry.selected}]SELECTED[{/if}]>[{$aCountry.title}]</option>
                [{/foreach}]
            </select>
        </td>
    </tr>
    <tr>
        <td class="edittext">
            [{oxmultilang ident="OSC_PAYPAL_ORDER_MAIN_TRACKCARRIER_PROVIDER"}]&nbsp;&nbsp;
        </td>
        <td class="edittext">
            <select id="paypaltrackingcarrierprovider" name="paypaltrackingcarrier" class="editinput" style="width: 135px;" [{$readonly}]>
                [{foreach from=$oView->getPayPalTrackingCarrierProvider() item=aProvider}]
                    <option value="[{$aProvider.id}]" [{if $aProvider.selected}]SELECTED[{/if}]>[{$aProvider.title}]</option>
                [{/foreach}]
            </select>
        </td>
    </tr>
    <tr>
        <td class="edittext">
            [{oxmultilang ident="ORDER_MAIN_TRACKCODE"}]&nbsp;&nbsp;
        </td>
        <td class="edittext">
            <input type="text" class="editinput" size="25" name="paypaltrackingcode" value="[{$oView->getPayPalTrackingCode()}]" [{$readonly}]>
            [{oxinputhelp ident="HELP_ORDER_MAIN_TRACKCODE"}]
        </td>
    </tr>
    <tr >
        <td class="edittext">
            [{oxmultilang ident="GENERAL_DELIVERYCOST"}]
        </td>
        <td class="edittext">
            <input type="text" class="editinput" size="15" maxlength="[{$edit->oxorder__oxdelcost->fldmax_length}]" name="editval[oxorder__oxdelcost]" value="[{$edit->oxorder__oxdelcost->value}]" [{$readonly}]> ([{$edit->oxorder__oxcurrency->value}])
            [{oxinputhelp ident="HELP_GENERAL_DELIVERYCOST"}]
        </td>
    </tr>
    <tr>
        <td class="edittext">[{oxmultilang ident="ORDER_MAIN_DELTYPE"}]:</td>
        <td class="edittext">
            <select name="setDelSet" class="editinput" style="width: 135px;" [{$readonly}]>
                <option value="">----</option>
                [{foreach from=$oShipSet key=sShipSetId item=oShipSet}]
                <option value="[{$sShipSetId}]" [{if $edit->oxorder__oxdeltype->value == $sShipSetId}]SELECTED[{/if}]>[{$oShipSet->oxdeliveryset__oxtitle->value}]</option>
                [{/foreach}]
            </select>
            [{oxinputhelp ident="HELP_ORDER_MAIN_DELTYPE"}]
        </td>
        <td>
            <input type="submit" class="edittext" name="save" id="shippNowButton" onclick="document.myedit.sendorder.value=1;document.myedit.submit();return false;" value="&nbsp;&nbsp;[{oxmultilang ident="GENERAL_NOWSEND"}]&nbsp;&nbsp;" [{$readonly}]>
            <input id='sendmail' class="edittext" type="checkbox" name="sendmail" value='1' [{$readonly}]> [{oxmultilang ident="GENERAL_SENDEMAIL"}]
            <input type="hidden" name="sendorder" value='0'>
        </td>
    </tr>
    <tr>
        <td class="edittext" valign="middle">
            <b>[{oxmultilang ident="GENERAL_SENDON"}]</b>
        </td>
        <td class="edittext" valign="bottom">
            <b>[{$edit->oxorder__oxsenddate->value|oxformdate:'datetime':true}]</b>
        </td>
        <td>
            <input type="submit" class="edittext" name="save" id="resetShippingDateButton" value="[{oxmultilang ident="GENERAL_SETBACKSENDTIME"}]" onclick="document.resetorder.submit();return false;" [{$readonly}]>
        </td>
    </tr>
[{else}]
    [{$smarty.block.parent}]
[{/if}]