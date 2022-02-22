<input type="hidden" name="pui_required[birthdate][day]" value="" />
<input type="hidden" name="pui_required[birthdate][month]" value="" />
<input type="hidden" name="pui_required[birthdate][year]" value="" />
<input type="hidden" name="pui_required[phonenumber]" value="" />
[{capture name="oscpaypalpui_requiredfields_script"}]
    $("#orderConfirmAgbBottom").submit(function(event) {
        var dontStopSubmit = true;
        $('#pui_form [id^=pui_required_]').each(function(index) {
            if (!$(this).val()) {
                dontStopSubmit = false;
            }
            $('#orderConfirmAgbBottom input[name="' + $(this).attr("name") + '"]').val($(this).val());
        });
        return dontStopSubmit;
    });
[{/capture}]
[{oxscript add=$smarty.capture.oscpaypalpui_requiredfields_script}]