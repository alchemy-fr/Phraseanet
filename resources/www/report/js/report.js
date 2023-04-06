/**
 * object language, date , and dashboard are init in all_content.twig for translation
 *
 */

//############# START DOCUMENT READY ######################################//
$(document).ready(function () {

    //do tabs and resize window on show
    $('#mainTabs:visible').tabs();

    reportDatePicker();
    bindEvents();

    /**
     * custom select boxes
     */
    $(".select_one").each(function(){
        var $this = $(this),
        numberOfOptions = $(this).children('option').length;
        $this.addClass('select-hidden');
        $this.wrap('<div class="custom_select"></div>');
        $this.after('<div class="select-styled"></div>');
        var $styledSelect = $this.next('div.select-styled');
        $styledSelect.text($this.children('option').eq(0).text());
        var $list = $('<ul />', {
            'class': 'select-options'
        }).insertAfter($styledSelect);
        for (var i = 0; i < numberOfOptions; i++) {
            $('<li />', {
                text: $this.children('option').eq(i).text(),
                rel: $this.children('option').eq(i).val(),
                'data-action': $this.children('option').eq(i).data('action')
            }).appendTo($list);
        }
        var $listItems = $list.children('li');
  
        $styledSelect.click(function(e) {
            e.stopPropagation();
            $('div.select-styled.active').not(this).each(function(){
                $(this).removeClass('active').next('ul.select-options').hide();
            });
            $(this).toggleClass('active').next('ul.select-options').toggle();
        });
      
        $listItems.click(function(e) {
            e.stopPropagation();
            var value = $(this).attr('rel');
            $styledSelect.text($(this).text()).removeClass('active');
            $this.val(value);
            $this.data('action', $(this).attr('data-action'));
            $list.hide();
        });

        $(document).click(function() {
            $styledSelect.removeClass('active');
            $list.hide();
        });
     });        
     
     $(".form2 .select-options li").click(function(e) {
        e.stopPropagation();
        var $this = $(this),
            value = $this.attr('rel'),
            form = $this.closest('form');
        $(".collist", form).hide();
        $(".collist-" + value, form).show();

        // subdef list depends on selected databox
        $(".subdeflist", form).hide();
        $(".subdeflist-" + value, form).show();
    });

    $('.collist').each(function() {
        var $this = $(this),
            form = $this.closest('form'),
            i = $this.closest('form').find('.sbas_select').val()
        ;
        $this.hide();        
        $(".collist-" + i, form).show();
    });

    $('.subdeflist').each(function() {
        var $this = $(this),
            form = $this.closest('form'),
            i = $this.closest('form').find('.sbas_select').val()
        ;
        $this.hide();
        $(".subdeflist-" + i, form).show();
    });

    $('.form2').each(function() {
        if ($(this).html().trim() === '')
            $(this).hide();
    });
});
//############# END DOCUMENT READY ######################################//

/**
 *
 * Tous les binds sur le report
 */
function bindEvents() {
    /**
     * "Download" buttons
    **/
    $('.formsubmiter').bind('click', function () {
        var collectionsArr = [],
            fieldsArr = [],
            form = $($(this).attr('data-form_selector')),
            action = form.find("select.sbas_select")
        ;
        
        if(action.length != 1) {    // should never happen with select !
            return false;   // prevent button to submit form
        }
        
        $(".form2 .collist", form).each(function(i, el) {
            if ($(el).is(':visible') === false) {
                $.each($(el).find('input'), function(i, inputEl) {
                    collectionsArr.push($(inputEl).prop('checked'))
                });
                $(el).find('input').prop('checked', false);
            }
        });
        $(".form3 .collist", form).each(function(i, el) {
            if ($(el).is(':visible') === false) {                
                $.each($(el).find('input'), function(i, inputEl) {
                    fieldsArr.push($(inputEl).prop('checked'))
                });
                $(el).find('input').prop('checked', false);
            }
        });
        action = action.find(':selected').data('action');
        form.attr("action", action);
        form.submit();
        
        $(".form2 .collist", form).each(function(i, el) {            
            if ($(el).is(':visible') === false) {
                
                $.each($(el).find('input'), function(j, inputEl) {                    
                    $(inputEl).prop('checked', collectionsArr[j]);
                });
            }
        });

        $(".form3 .collist", form).each(function(i, el) {            
            if ($(el).is(':visible') === false) {
                
                $.each($(el).find('input'), function(j, inputEl) {                    
                    $(inputEl).prop('checked', fieldsArr[j]);
                });
            }
        });
        collectionsArr = [];
        fieldsArr = [];
        
        return false;   // prevent button to submit form    
    });

    /**
     * disable submit button if no date (dmin or dmax) *
     */
    $('.dmin, .dmax').bind('change', function() {
        var $this = $(this);
        var container = $this.closest('.inside-container');
        
        if ($this.val().length == 0) {
            $('.formsubmiter', container).attr('disabled', true).addClass('disabled');
            $this.siblings('.add-on').addClass('disabled_image');
        }
        else {
            $('.formsubmiter', container).attr('disabled', false).removeClass('disabled');
            $this.siblings('.add-on').removeClass('disabled_image');
        }
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

// poll only from menu bar

// function pollNotifications() {
//     $.ajax({
//         type: "POST",
//         url: "/session/notifications/",
//         dataType: 'json',
//         data: {
//             module: 10,
//             usr: usrId
//         },
//         error: function () {
//             window.setTimeout("pollNotifications();", 10000);
//         },
//         timeout: function () {
//             window.setTimeout("pollNotifications();", 10000);
//         },
//         success: function (data) {
//             if (data) {
//                 commonModule.manageSession(data);
//             }
//             var t = 120000;
//             if (data.apps && parseInt(data.apps) > 1) {
//                 t = Math.round((Math.sqrt(parseInt(data.apps) - 1) * 1.3 * 120000));
//             }
//             window.setTimeout("pollNotifications();", t);
//             return;
//         }
//     });
// };
//
// window.setTimeout("pollNotifications();", 10000);
