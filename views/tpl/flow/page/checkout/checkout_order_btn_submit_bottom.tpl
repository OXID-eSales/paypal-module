<input type="hidden" name="pui_required[birthdate][day]" value="" />
<input type="hidden" name="pui_required[birthdate][month]" value="" />
<input type="hidden" name="pui_required[birthdate][year]" value="" />
<input type="hidden" name="pui_required[phonenumber]" value="" />
[{capture name="oscpaypalpui_requiredfields_script"}]
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
[{/capture}]
[{oxscript add=$smarty.capture.oscpaypalpui_requiredfields_script}]