/**
 * object language, date , and dashboard are init in all_content.twig for translation
 *
 */


//#############START DOCUMENT READY ######################################//
$(document).ready(function () {

    //do tabs and resize window on show
    $('#mainTabs:visible').tabs();

    reportDatePicker();
    bindEvents();

    /**
     * check the first databox on each tab (form), so the rest of the ux (coll list, fields, ...) is updated
     */
    $(".select_one").each(function(){
        $(".sbas-radio", $(this)).first().click();
    });
});
//#############END DOCUMENT READY ######################################//

/**
 *
 * Tous les binds sur le report
 */
function bindEvents() {
    /**
     * "Download" buttons
    **/
    $('.formsubmiter').bind('click', function () {
        var form = $($(this).attr('data-form_selector'));
        var action = form.find("input.sbas-radio:checked");
        if(action.length != 1) {    // should never happen with radios !
            return false;   // prevent button to submit form
        }
        action = $(action[0]).attr("data-action");

        form.attr("action", action);
        form.submit();

        return false;   // prevent button to submit form
    });

    /**
     * "databox" radios
     */
    $('.sbas-radio').bind('click', function () {
        var form = $(this).closest("form");
        var sbas_id = $(this).attr("data-sbasid");

        $(".collist", form).hide();
        $(".collist input", form).prop("disabled", true);

        $(".collist-"+sbas_id, form).show();
        $(".collist-"+sbas_id+" input", form).prop("disabled", false);
    });

    /**
     * "select all" buttons
     */
    $('.select-all').bind('click', function () {
        var form = $(this).closest("form");
        var selector = $(this).attr("data-target_selector");
        $(selector, form).prop('checked', true);

        return false;   // prevent button to submit form
    });

    /**
     * "unselect all" buttons
     */
    $('.unselect-all').bind('click', function () {
        var form = $(this).closest("form");
        var selector = $(this).attr("data-target_selector");
        $(selector, form).prop('checked', false);

        return false;   // prevent button to submit form
    });
}

function reportDatePicker() {
    var dates = $('.dmin, .dmax').datepicker({
        defaultDate: -10,
        changeMonth: true,
        changeYear: true,
        dateFormat: 'yy-mm-dd',
        numberOfMonths: 3,
        maxDate: "-0d",
        onSelect: function (selectedDate, instance) {
            var option = $(this).hasClass("dmin") ? "minDate" : "maxDate";
            var date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
            $(dates).not(':hidden').not(this).datepicker("option", option, date);
        }
    });
}


function pollNotifications() {
    $.ajax({
        type: "POST",
        url: "/session/notifications/",
        dataType: 'json',
        data: {
            module: 10,
            usr: usrId
        },
        error: function () {
            window.setTimeout("pollNotifications();", 10000);
        },
        timeout: function () {
            window.setTimeout("pollNotifications();", 10000);
        },
        success: function (data) {
            if (data) {
                manageSession(data);
            }
            var t = 120000;
            if (data.apps && parseInt(data.apps) > 1) {
                t = Math.round((Math.sqrt(parseInt(data.apps) - 1) * 1.3 * 120000));
            }
            window.setTimeout("pollNotifications();", t);
            return;
        }
    });
};

window.setTimeout("pollNotifications();", 10000);
