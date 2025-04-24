/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

jQuery(document).ready(function(){

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
    callConfigControllerAutoConfigurationFromCallback(authCode, sharedId, false);
}

function onboardedCallbackSandbox(authCode, sharedId)
{
    callConfigControllerAutoConfigurationFromCallback(authCode, sharedId, true);
}

function callConfigControllerAutoConfigurationFromCallback(authCode, sharedId, isSandBox)
{
    const sandboxSnippet = isSandBox ? '&XDEBUG_SESSION_START=1' : '';
    fetch(window.selfLink + 'cl=oscpaypalconfig&fnc=autoConfigurationFromCallback' + sandboxSnippet, {
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
                if (response.status === 200) {
                    // Search for the form named "transfer" and submit it
                    document.forms.transfer.submit();
                }
            }
        )
        .catch(function (err) {
        });
}