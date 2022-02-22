<input type="hidden" name="pui_required[birthdate][day]" value="" />
<input type="hidden" name="pui_required[birthdate][month]" value="" />
<input type="hidden" name="pui_required[birthdate][year]" value="" />
<input type="hidden" name="pui_required[phonenumber]" value="" />
[{capture name="oscpaypalpui_requiredfields_script"}]
    $("#orderConfirmAgbBottom").submit(function(event) {
        var dontStopSubmit = true;
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
        if (!dontStopSubmit) {
            $('html, body').animate({
                scrollTop: $("#pui_form").offset().top
            }, 1000);
        }
        return dontStopSubmit;
    });
[{/capture}]
[{oxscript add=$smarty.capture.oscpaypalpui_requiredfields_script}]