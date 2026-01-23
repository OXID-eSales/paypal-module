[{assign var="payment" value=$oView->getPayment()}]
[{assign var="paymentId" value=$payment->getId()}]
[{assign var="oConfig" value=$oViewConf->getConfig()}]
[{assign var="PayPalSDKJS" value=$oConfig->getGlobalParameter("PayPalSDKJS")}]
[{if !$PayPalSDKJS}]
    [{capture assign="PayPalSDKJS"}]
        [{assign var="commitFlow" value=false}]
        [{if "oscpaypal" == $paymentId}]
            [{assign var="commitFlow" value=true}]
        [{/if}]
        [{include file="@osc_paypal/frontend/shared/layout/base_js.tpl" commitFlow=$commitFlow}]
    [{/capture}]
    [{$oConfig->setGlobalParameter("PayPalSDKJS", $PayPalSDKJS)}]
[{/if}]
[{if "oscpaypal_acdc" == $paymentId}]
    <button id="[{$paymentId}]" type="submit" class="btn btn-lg btn-primary pull-right submitButton nextStep largeButton">
        <i class="fa fa-check"></i> [{oxmultilang ident="SUBMIT_ORDER"}]
    </button>
[{/if}]

[{if "oscpaypal" == $paymentId}]
    [{if $vaultedPaymentDescription}]
        <button id="[{$paymentId}]" type="submit" class="btn btn-lg btn-primary pull-right submitButton nextStep largeButton">
            <i class="fa fa-check"></i> [{oxmultilang ident="SUBMIT_ORDER"}]
        </button>
    [{else}]
        <div id="[{$paymentId}]" class="paypal-button-container pull-right [{$buttonClass}]"></div>
    [{/if}]
[{/if}]

[{if "oscpaypal_pui" == $paymentId}]
    <input type="hidden" name="pui_required[birthdate][day]" value="" />
    <input type="hidden" name="pui_required[birthdate][month]" value="" />
    <input type="hidden" name="pui_required[birthdate][year]" value="" />
    <input type="hidden" name="pui_required[phonenumber]" value="" />
    [{capture name="oscpaypalpui_requiredfields_script"}]
        [{if $phpstorm}]<script>[{/if}]
        // Function for age calculation
        function calculateAge(birthDate) {
            var today = new Date();
            var birth = new Date(birthDate);
            var age = today.getFullYear() - birth.getFullYear();
            var monthDiff = today.getMonth() - birth.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }

            return age;
        }

        $("#orderConfirmAgbBottom").submit(function(event) {
            var dontStopSubmit = true;

            // Hide all error messages initially
            $('#pui_form .help-block .text-danger').addClass('hidden');

            $('#pui_form [id^=pui_required_]').each(function(index) {
                var help = $('#pui_form .' + $(this).attr("id") + '_help > p.text-danger');
                if (!$(this).val()) {
                    dontStopSubmit = false;
                    help.removeClass('hidden');
                }
                else {
                    help.addClass('hidden');
                }
                $('#orderConfirmAgbBottom input[name="' + $(this).attr("name") + '"]').val($(this).val());
            });

            // Additional age validation for birth date
            var dayInput = $('#pui_required_birthdate_day');
            var monthInput = $('#pui_required_birthdate_month');
            var yearInput = $('#pui_required_birthdate_year');
            var ageErrorElement = $('#age-error-message');

            if (dayInput.val() && monthInput.val() && yearInput.val()) {
                // Construct the birth date
                var birthDateString = yearInput.val() + '-' + monthInput.val().padStart(2, '0') + '-' + dayInput.val().padStart(2, '0');
                var age = calculateAge(birthDateString);

                if (age < 18) {
                    dontStopSubmit = false;
                    ageErrorElement.removeClass('hidden');
                } else {
                    ageErrorElement.addClass('hidden');
                }
            }

            if (!dontStopSubmit) {
                $('html, body').animate({
                    scrollTop: $("#pui_form").offset().top
                }, 1000);
            }
            return dontStopSubmit;
        });
        [{if $phpstorm}]</script>[{/if}]
    [{/capture}]
    [{oxscript add=$smarty.capture.oscpaypalpui_requiredfields_script}]
[{/if}]

[{if "oscpaypal_googlepay" == $paymentId}]
    [{include file="@osc_paypal/frontend/shared/googlepay.tpl" buttonClass="paypal-button-wrapper"}]
[{elseif "oscpaypal_applepay" == $paymentId}]
    [{include file="@osc_paypal/frontend/shared/applepay.tpl" paymentId=$paymentId buttonClass="paypal-button-wrapper"}]
    <div id="applepay-container" class="paypal-button-container paypal-button-wrapper"></div>
[{/if}]