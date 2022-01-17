/**
 * This file is part of OXID eSales PayPal module.
 *
 * OXID eSales PayPal module is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eSales PayPal module is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eSales PayPal module.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2020
 */

jQuery(document).ready(function(){

    jQuery(".boardinglink").click(function() {
        jQuery("#overlay").show();
    });

    if(window.isSandBox) {
        displayByOpMode('sandbox');
    } else {
        displayByOpMode('live');
    }

    jQuery("#opmode").change(function() {
        if(jQuery("#opmode").val() == 'sandbox') {
            window.isSandBox = true;
        } else {
            window.isSandBox = false;
        }

        displayByOpMode(jQuery("#opmode").val());
    });
});

function displayByOpMode(opmode) {
    if(opmode === 'sandbox') {
        jQuery(".live").hide();
        jQuery(".sandbox").show();
    } else {
        jQuery(".sandbox").hide();
        jQuery(".live").show();
    }
}

function onboardedCallbackLive(authCode, sharedId)
{
    callOnboardingControllerAutoConfigurationFromCallback(authCode, sharedId, false);
}

function onboardedCallbackSandbox(authCode, sharedId)
{
    callOnboardingControllerAutoConfigurationFromCallback(authCode, sharedId, true);
}

function callOnboardingControllerAutoConfigurationFromCallback(authCode, sharedId, isSandBox)
{
    fetch(window.selfLink + 'cl=OnboardingController&fnc=autoConfigurationFromCallback', {
        method: 'POST',
        headers: {
            'content-type': 'application/json'
        },
        body: JSON.stringify({
            authCode: authCode,
            sharedId: sharedId,
            isSandBox: isSandBox
        })
    })
    .then(
        function (response) {
            if (response.status !== 200) {
                return;
            }

            response.json().then(function (data) {
                jQuery("#overlay").hide();
                if (window.isSandBox) {
                    jQuery("#client-sandbox-id").val(data.client_id);
                    jQuery("#client-sandbox-secret").val(data.client_secret);
                    jQuery("#webhook-sandbox-id").val(data.webhook_id);
                    jQuery('input[name="conf[sPayPalSandboxClientSecret]"').val(data.client_secret);
                    jQuery('input[name="conf[sPayPalSandboxClientSecret]"').prev('input.password_input').val(data.client_secret);
                } else {
                    jQuery("#client-id").val(data.client_id);
                    jQuery("#client-secret").val(data.client_secret);
                    jQuery("#webhook-id").val(data.webhook_id);
                    jQuery('input[name="conf[sPayPalClientSecret]"').val(data.client_secret);
                    jQuery('input[name="conf[sPayPalClientSecret]"').prev('input.password_input').val(data.client_secret);
                }
                jQuery("#configForm").submit();
            });
        }
    )
    .catch(function (err) {
    });
}
