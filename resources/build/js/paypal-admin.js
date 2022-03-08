/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

jQuery(document).ready(function(){

    jQuery(".popuplink").click(function() {
        jQuery("#overlay").show();
        localStorage.reloadoscpaypalconfig = "0";
        window.setInterval(function(){
            if(localStorage.reloadoscpaypalconfig == "1"){
                localStorage.reloadoscpaypalconfig = "0";
                jQuery("#overlay").hide();
                location.reload();
            }
        }, 500);
    });

    jQuery(".boardinglink").click(function() {
//        jQuery("#overlay").show();
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
    fetch(window.selfLink + 'cl=oscpaypalonboarding&fnc=autoConfigurationFromCallback', {
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
/*
            response.json().then(function (data) {
                jQuery("#overlay").hide();
                if (window.isSandBox) {
                    jQuery("#client-sandbox-id").val(data.client_id);
                    jQuery("#client-sandbox-secret").val(data.client_secret);
                    jQuery("#webhook-sandbox-id").val(data.webhook_id);
                    jQuery('input[name="conf[oscPayPalSandboxClientSecret]"').val(data.client_secret);
                    jQuery('input[name="conf[oscPayPalSandboxClientSecret]"').prev('input.password_input').val(data.client_secret);
                } else {
                    jQuery("#client-id").val(data.client_id);
                    jQuery("#client-secret").val(data.client_secret);
                    jQuery("#webhook-id").val(data.webhook_id);
                    jQuery('input[name="conf[oscPayPalClientSecret]"').val(data.client_secret);
                    jQuery('input[name="conf[oscPayPalClientSecret]"').prev('input.password_input').val(data.client_secret);
                }
                jQuery("#configForm").submit();
            });
*/
        }
    )
    .catch(function (err) {
    });
}
