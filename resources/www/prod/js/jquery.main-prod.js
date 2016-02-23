document.getElementById('loader_bar').style.width = '30%';

var p4 = p4 || {};

var baskAjax, baskAjaxrunning;
baskAjaxrunning = false;
var answAjax, answAjaxrunning;
answAjaxrunning = false;
var searchAjax, searchAjaxRunning;
searchAjaxRunning = false;

var language = {};
var bodySize = {
    x: 0,
    y: 0
};

function resizePreview() {
    p4.preview.height = $('#PREVIEWIMGCONT').height();
    p4.preview.width = $('#PREVIEWIMGCONT').width();
    setPreview();
}

function getHome(cas, page) {

    if (typeof(page) === 'undefined')
        page = 0;

    switch (cas) {
        case 'QUERY':
            workzoneFacetsModule.resetSelectedFacets();
            searchModule.newSearch($("#EDIT_query").val());
            break;
        case 'PUBLI':
            publicationModule.fetchPublications(page, answAjax, answAjaxrunning);
            break;
        case 'HELP':
            $.ajax({
                type: "POST",
                url: "/client/home/",
                dataType: 'html',
                data: {
                    type: cas
                },
                beforeSend: function () {
                    if (answAjaxrunning && answAjax.abort)
                        answAjax.abort();
                    searchModule.clearAnswers();
                    answAjaxrunning = true;
                    $('#answers').addClass('loading');
                },
                error: function () {
                    answAjaxrunning = false;
                    $('#answers').removeClass('loading');
                },
                timeout: function () {
                    answAjaxrunning = false;
                    $('#answers').removeClass('loading');
                },
                success: function (data) {
                    answAjaxrunning = false;
                    $('#answers').append(data);
                    searchModule.afterSearch();
                    return;
                }

            });
            break;


        default:
            break;
    }
}

function getLanguage() {
    $.ajax({
        type: "GET",
        url: "../prod/language/",
        dataType: 'json',
        success: function (data) {
            language = data;
            return;
        }
    });
}

function is_ctrl_key(event) {
    if (event.altKey)
        return true;
    if (event.ctrlKey)
        return true;
    if (event.metaKey)	// apple key opera
        return true;
    if (event.keyCode === 17)	// apple key opera
        return true;
    if (event.keyCode === 224)	// apple key mozilla
        return true;
    if (event.keyCode === 91)	// apple key safari
        return true;

    return false;
}

function is_shift_key(event) {
    if (event.shiftKey)
        return true;
    return false;
}

/**
 * adv search : check/uncheck all the collections (called by the buttons "all"/"none")
 *
 * @param bool
 */
function checkBases(bool) {
    $('form.phrasea_query .sbas_list').each(function () {

        var sbas_id = $(this).find('input[name=reference]:first').val();
        if (bool)
            $(this).find(':checkbox').prop('checked', true);
        else
            $(this).find(':checkbox').prop('checked', false);
    });

    searchModule.checkFilters(true);
}



/* NOT USED function toggleFilter(filter, ele) {
    var el = $('#' + filter);
    if (el.is(':hidden'))
        $(ele).parent().addClass('open');
    else
        $(ele).parent().removeClass('open');
    el.slideToggle('fast');
}*/


/* NOT USED function setVisible(el) {
    el.style.visibility = 'visible';
}*/

function resize() {
    var body = $('#mainContainer');
    bodySize.y = body.height();
    bodySize.x = body.width();

    $('.overlay').height(bodySize.y).width(bodySize.x);

    var headBlockH = $('#headBlock').outerHeight();
    var bodyY = bodySize.y - headBlockH - 2;
    var bodyW = bodySize.x - 2;
    //$('#desktop').height(bodyY).width(bodyW);

    if (p4.preview.open)
        resizePreview();

    if ($('#idFrameC').data('ui-resizable')) {
        $('#idFrameC').resizable('option', 'maxWidth', (480));
        $('#idFrameC').resizable('option', 'minWidth', 300);
    }

    answerSizer();
    linearize();


}




function reset_adv_search() {
    var container = $("#ADVSRCH_OPTIONS_ZONE");
    var fieldsSort = $('#ADVSRCH_SORT_ZONE select[name=sort]', container);
    var fieldsSortOrd = $('#ADVSRCH_SORT_ZONE select[name=ord]', container);
    var dateFilterSelect = $('#ADVSRCH_DATE_ZONE select', container);

    $("option.default-selection", fieldsSort).prop("selected", true);
    $("option.default-selection", fieldsSortOrd).prop("selected", true);

    $('#ADVSRCH_FIELDS_ZONE option').prop("selected", false);
    $('#ADVSRCH_OPTIONS_ZONE input:checkbox.field_switch').prop("checked", false);

    $("option:eq(0)", dateFilterSelect).prop("selected", true);
    $('#ADVSRCH_OPTIONS_ZONE .datepicker').val('');
    $('form.adv_search_bind input:text').val('');
    checkBases(true);
}

function search_doubles() {
    workzoneFacetsModule.resetSelectedFacets();
    $('#EDIT_query').val('sha256=sha256');
    searchModule.newSearch('sha256=sha256');
}







function initAnswerForm() {

    var searchForm = $('#searchForm');
    $('button[type="submit"]', searchForm).bind('click', function () {
        workzoneFacetsModule.resetSelectedFacets();
        searchModule.newSearch($("#EDIT_query").val());
        return false;
    });

    searchForm.unbind('submit').bind('submit', function () {

        var $this = $(this),
            method = $this.attr('method') ? $this.attr('method') : 'POST';

        var data = $this.serializeArray();

        answAjax = $.ajax({
            type: method,
            url: $this.attr('action'),
            data: data,
            dataType: 'json',
            beforeSend: function (formData) {
                if (answAjaxrunning && answAjax.abort)
                    answAjax.abort();
                searchModule.beforeSearch();
            },
            error: function () {
                answAjaxrunning = false;
                $('#answers').removeClass('loading');
            },
            timeout: function () {
                answAjaxrunning = false;
                $('#answers').removeClass('loading');
            },
            success: function (datas) {

                // DEBUG QUERY PARSER
                try {
                    console.info(JSON.parse(datas.parsed_query));
                }
                catch(e) {}

                $('#answers').empty().append(datas.results).removeClass('loading');

                $("#answers img.lazyload").lazyload({
                    container: $('#answers')
                });

                workzoneFacetsModule.loadFacets(datas.facets);

                $('#answers').append('<div id="paginate"><div class="navigation"><div id="tool_navigate"></div></div></div>');

                $('#tool_results').empty().append(datas.infos);
                $('#tool_navigate').empty().append(datas.navigationTpl);

                $.each(p4.Results.Selection.get(), function (i, el) {
                    $('#IMGT_' + el).addClass('selected');
                });

                p4.tot = datas.total_answers;
                p4.tot_options = datas.form;
                p4.tot_query = datas.query;
                p4.navigation = datas.navigation;

                if (datas.next_page) {
                    $("#NEXT_PAGE, #answersNext").bind('click', function () {
                        gotopage(datas.next_page);
                    });
                }
                else {
                    $("#NEXT_PAGE").unbind('click');
                }

                if (datas.prev_page) {
                    $("#PREV_PAGE").bind('click', function () {
                        gotopage(datas.prev_page);
                    });
                }
                else {
                    $("#PREV_PAGE").unbind('click');
                }

                searchModule.afterSearch();
            }
        });
        return false;
    });
    if (searchForm.hasClass('triggerAfterInit')) {
        searchForm.removeClass('triggerAfterInit').trigger('submit');
    }
}
















$(document).ready(function() {
});



function answerSizer() {
    var el = $('#idFrameC').outerWidth();
    if (!$.support.cssFloat) {
       // $('#idFrameC .insidebloc').width(el - 56);
    }
    var widthA = Math.round(bodySize.x - el - 10);
    $('#rightFrame').width(widthA);
    $('#rightFrame').css('left', $('#idFrameC').width());

}

function linearize() {
    var list = $('#answers .list');
    if (list.length > 0) {
        var fllWidth = $('#answers').innerWidth();
        fllWidth -= 16;

        var stdWidth = 460;
        var diff = 28;
        var n = Math.round(fllWidth / (stdWidth));
        var w = Math.floor(fllWidth / n) - diff;
        if (w < 360 && n > 1)
            w = Math.floor(fllWidth / (n - 1)) - diff;
        $('#answers .list').width(w);
    }
    else {
        var minMargin = 5;
        var margin = 0;
        var el = $('#answers .diapo:first');
        var diapoWidth = el.outerWidth() + (minMargin * 2);
        var fllWidth = $('#answers').innerWidth();
        fllWidth -= 26;

        var n = Math.floor(fllWidth / (diapoWidth));

        margin = Math.floor((fllWidth % diapoWidth) / (2 * n));
        margin = margin + minMargin;

        $('#answers .diapo').css('margin', '5px ' + (margin) + 'px');
    }

}


function initLook() {
    $('#nperpage_slider').slider({
        value: parseInt($('#nperpage_value').val()),
        min: 10,
        max: 100,
        step: 10,
        slide: function (event, ui) {
            $('#nperpage_value').val(ui.value);
        },
        stop: function (event, ui) {
            userModule.setPref('images_per_page', $('#nperpage_value').val());
        }
    });
    $('#sizeAns_slider').slider({
        value: parseInt($('#sizeAns_value').val()),
        min: 90,
        max: 270,
        step: 10,
        slide: function (event, ui) {
            $('#sizeAns_value').val(ui.value);
        },
        stop: function (event, ui) {
            userModule.setPref('images_size', $('#sizeAns_value').val());
        }
    });
}

function acceptCgus(name, value) {
    userModule.setPref(name, value);
}

function cancelCgus(id) {

    $.ajax({
        type: "POST",
        url: "../prod/TOU/deny/" + id + "/",
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                alert(language.cgusRelog);
                self.location.replace(self.location.href);
            }
            else {
                humane.error(data.message);
            }
        }
    });

}

function activateCgus() {
    var $this = $('.cgu-dialog:first');
    $this.dialog({
        autoOpen: true,
        closeOnEscape: false,
        draggable: false,
        modal: true,
        resizable: false,
        width: 800,
        height: 500,
        open: function () {
            $this.parents(".ui-dialog:first").find(".ui-dialog-titlebar-close").remove();
            $('.cgus-accept', $(this)).bind('click', function () {
                acceptCgus($('.cgus-accept', $this).attr('id'), $('.cgus-accept', $this).attr('date'));
                $this.dialog('close').remove();
                activateCgus();
            });
            $('.cgus-cancel', $(this)).bind('click', function () {
                if (confirm(language.warningDenyCgus)) {
                    cancelCgus($('.cgus-cancel', $this).attr('id').split('_').pop());
                }
            });
        }
    });
}

$(document).ready(function () {
    humane.forceNew = true;
    activateCgus();
});


function triggerShortcuts() {

    $('#keyboard-stop').bind('click', function () {
        var display = $(this).get(0).checked ? '0' : '1';

        userModule.setPref('keyboard_infos', display);

    });

    var buttons = {};

    buttons[language.fermer] = function () {
        $("#keyboard-dialog").dialog('close');
    };

    $('#keyboard-dialog').dialog({
        closeOnEscape: false,
        resizable: false,
        draggable: false,
        modal: true,
        width: 600,
        height: 400,
        overlay: {
            backgroundColor: '#000',
            opacity: 0.7
        },
        open: function (event, ui) {
            $(this).dialog("widget").css("z-index", "1400");
        },
        close: function () {
            $(this).dialog("widget").css("z-index", "auto");
            if ($('#keyboard-stop').get(0).checked) {
                var dialog = $('#keyboard-dialog');
                if (dialog.data("ui-dialog")) {
                    dialog.dialog('destroy');
                }
                dialog.remove();
            }
        }
    }).dialog('option', 'buttons', buttons).dialog('open');

    $('#keyboard-dialog').scrollTop(0);

    return false;
}

function activeZoning() {
    $('#idFrameC, #rightFrame').bind('mousedown', function (event) {
        var old_zone = p4.active_zone;
        p4.active_zone = $(this).attr('id');
        if (p4.active_zone !== old_zone && p4.active_zone !== 'headBlock') {
            $('.effectiveZone.activeZone').removeClass('activeZone');
            $('.effectiveZone', this).addClass('activeZone');//.flash('#555555');
        }
        $('#EDIT_query').blur();
    });
    $('#rightFrame').trigger('mousedown');
}




$(document).ready(function () {

    $('input[name=search_type]').bind('click', function () {
        console.log('search bind')
        var $this = $(this);
        var $record_types = $('#recordtype_sel');

        console.log($this.hasClass('mode_type_reg'), $record_types)
        if ($this.hasClass('mode_type_reg')) {
            $record_types.css("visibility", "hidden");  // better than hide because does not change layout
            $record_types.prop("selectedIndex", 0);
        } else {
            $record_types.css("visibility", "visible");
        }
    });

    $('.adv_search_button').on('click', function () {
        var searchForm = $('#searchForm');
        var parent = searchForm.parent();

        var options = {
            size: (bodySize.x - 120)+'x'+(bodySize.y - 120),
            loading: false,
            closeCallback: function (dialog) {

                var datas = dialog.find('form.phrasea_query').appendTo(parent);//.clone();

                $('.adv_trigger', searchForm).show();
                $('.adv_options', searchForm).hide();
            }
        };

        $dialog = p4.Dialog.Create(options);

        searchForm.appendTo($dialog.getDomElement());

        $dialog.getDomElement().find('.adv_options').show();
        $dialog.getDomElement().find('.adv_trigger').hide();

        $dialog.getDomElement().find('form').bind('submit.conbo', function () {
            $(this).unbind('submit.conbo');
            $dialog.Close();
            return false;
        });


        return false;
    });

    $(document).bind('contextmenu', function (event) {
        var targ;
        if (event.target)
            targ = event.target;
        else if (event.srcElement)
            targ = event.srcElement;
        if (targ.nodeType === 3)// safari bug
            targ = targ.parentNode;

        var gogo = true;
        var targ_name = targ.nodeName ? targ.nodeName.toLowerCase() : false;

        if (targ_name !== 'input' && targ_name.toLowerCase() !== 'textarea') {
            gogo = false;
        }
        if (targ_name === 'input') {
            if ($(targ).is(':checkbox'))
                gogo = false;
        }

        return gogo;
    });

    $('.basket_refresher').on('click', function () {
        return p4.WorkZone.refresh('current');
        return false;
    });

    $('#loader_bar').stop().animate({
        width: '70%'
    }, 450);
    p4.preview = {
        open: false,
        current: false
    };
    p4.currentViewMode = 'classic';
    p4.nbNoview = 0;
    p4.reg_delete = true;
    p4.sel = [];
    p4.baskSel = [];
    p4.edit = {};
    p4.thesau = {
        tabs: null
    };
    p4.active_zone = false;
    p4.next_bask_scroll = false;


    $('#backcolorpickerHolder').ColorPicker({
        flat: true,
        color: '404040',
        livePreview: false,
        eventName: 'mouseover',
        onSubmit: function (hsb, hex, rgb, el) {
            var back_hex = '';
            var unactive = '';


            if (hsb.b >= 50) {
                back_hex = '000000';

                var sim_b = 0.1 * hsb.b;
            }
            else {
                back_hex = 'FFFFFF';

                var sim_b = 100 - 0.1 * (100 - hsb.b);
            }

            var sim_b = 0.1 * hsb.b;

            var sim_rgb = utilsModule.hsl2rgb(hsb.h, hsb.s, sim_b);
            var sim_hex = utilsModule.RGBtoHex(sim_rgb.r, sim_rgb.g, sim_rgb.b);

            userModule.setPref('background-selection', hex);
            userModule.setPref('background-selection-disabled', sim_hex);
            userModule.setPref('fontcolor-selection', back_hex);

            $('style[title=color_selection]').empty();

            var datas = '.diapo.selected,#reorder_box .diapo.selected, #EDIT_ALL .diapo.selected, .list.selected, .list.selected .diapo' +
                '{' +
                '    COLOR: #' + back_hex + ';' +
                '    BACKGROUND-COLOR: #' + hex + ';' +
                '}';
            $('style[title=color_selection]').empty().text(datas);
        }
    });
    $('#backcolorpickerHolder').find('.colorpicker_submit').append($('#backcolorpickerHolder .submiter')).bind('click', function () {
        $(this).highlight('#CCCCCC');
    });

    $('#search_submit').on('mousedown', function (event) {
        return false;
    });

    $('#history-queries ul li').on('mouseover',function () {
        $(this).addClass('hover');
    }).on('mouseout', function () {
            $(this).removeClass('hover');
        });

    startThesaurus();
    searchModule.checkFilters();

    activeZoning();

    $('.shortcuts-trigger').bind('click', function () {
        triggerShortcuts();
    });

    $('#idFrameC').resizable({
        handles: 'e',
        resize: function () {
            answerSizer();
            linearize();
        },
        stop: function () {

            var el = $('.SSTT.active').next().find('div:first');
            var w = el.find('div.chim-wrapper:first').outerWidth();
            var iw = el.innerWidth();
            var diff = $('#idFrameC').width() - el.outerWidth();
            var n = Math.floor(iw / w);

            $('#idFrameC').height('auto');

            var nwidth = (n) * w + diff + n;
            if (isNaN(nwidth)) {
                saveWindows();
                return;
            }
            if (nwidth < 265)
                nwidth = 265;
            if (el.find('div.chim-wrapper:first').hasClass('valid') && nwidth < 410)
                nwidth = 410;


            $('#idFrameC').stop().animate({
                    width: nwidth
                },
                300,
                'linear',
                function () {
                    answerSizer();
                    linearize();
                    saveWindows();
                });
        }
    });

    $('#idFrameC .ui-tabs-nav li').on('click', function (event) {
        if($('#idFrameC').attr('data-status') == 'closed'){
            $('#idFrameC').width(300);
            $('#rightFrame').css('left', 300);
            $('#rightFrame').width($(window).width()-300);
            $('#baskets, #proposals, #thesaurus_tab').hide();
            $('.ui-resizable-handle, #basket_menu_trigger').show();
            var IDname = $(this).attr('aria-controls');
            $('#'+IDname).show();
        }

        $('#idFrameC').attr('data-status', 'open');
        $('.WZbasketTab').css('background-position', '9px 21px');
        $('#idFrameC').removeClass('closed');
    });

    var previousTab = "";

    $('#idFrameC #retractableButton').bind('click', function (event) {

        if($('#idFrameC').attr('data-status') != 'closed'){
            $(this).find('i').removeClass('icon-double-angle-left').addClass('icon-double-angle-right')
            $('#idFrameC').width(80);
            $('#rightFrame').css('left', 80);
            $('#rightFrame').width($(window).width()-80);
            $('#idFrameC').attr('data-status', 'closed');
            $('#baskets, #proposals, #thesaurus_tab, .ui-resizable-handle, #basket_menu_trigger').hide();
            $('#idFrameC .ui-tabs-nav li').removeClass('ui-state-active');
            $('.WZbasketTab').css('background-position', '15px 16px');
            $('#idFrameC').addClass('closed');
            previousTab = $('#idFrameC .icon-menu').find('li.ui-tabs-active');
        }else{
            $(this).find('i').removeClass('icon-double-angle-right').addClass('icon-double-angle-left')
            $('#idFrameC').width(300);
            $('#rightFrame').css('left', 300);
            $('#rightFrame').width($(window).width()-300);
            $('#idFrameC').attr('data-status', 'open');
            $('.ui-resizable-handle, #basket_menu_trigger').show();
            $('.WZbasketTab').css('background-position', '9px 16px');
            $('#idFrameC').removeClass('closed');
            $('#idFrameC .icon-menu li').last().find('a').trigger('click');
            $('#idFrameC .icon-menu li').first().find('a').trigger('click');
            $(previousTab).find('a').trigger('click');
        }

        event.stopImmediatePropagation();
        //p4.WorkZone.close();
        return false;
    });

    $('#look_box .tabs').tabs();

    resize();

    $(window).bind('resize', function () {
        resize();
    });
    $('body').append('<iframe id="MODALDL" class="modalbox" src="about:blank;" name="download" style="display:none;border:none;" frameborder="0"></iframe>');

    $('body').append('<iframe id="idHFrameZ" src="about:blank" style="display:none;" name="HFrameZ"></iframe>');

    $('#basket_menu_trigger').contextMenu('#basket_menu', {
        openEvt: 'click',
        dropDown: true,
        theme: 'vista',
        showTransition: 'slideDown',
        hideTransition: 'hide',
        shadow: false
    });

    $('#basket_menu_trigger').trigger("click");
    $('#basket_menu_trigger').trigger("click");

    $('.datepicker').datepicker({
        changeYear: true,
        changeMonth: true,
        dateFormat: 'yy/mm/dd'
    });

    /*$.ajaxSetup({

        error: function (jqXHR, textStatus, errorThrown) {
            //Request is aborted
            if (errorThrown === 'abort') {
                return false;
            } else {
                showModal('error', {
                    title: language.errorAjaxRequest + ' ' + jqXHR.responseText
                });
            }
        },
        timeout: function () {
            showModal('timeout', {
                title: 'Server not responding'
            });
        }
    });*/

    $('.tools .answer_selector').bind('click',function () {
        selector($(this));
    }).bind('mouseover',function (event) {
            if (is_ctrl_key(event)) {
                $(this).addClass('add_selector');
            }
            else {
                $(this).removeClass('add_selector');
            }
        }).bind('mouseout', function () {
            $(this).removeClass('add_selector');
        });

    // getLanguage();

    initAnswerForm();

    initLook();

    // setTimeout("pollNotifications();", 10000);

    $(this).bind('keydown', function (event) {
        var cancelKey = false;
        var shortCut = false;

        if ($('#MODALDL').is(':visible')) {
            switch (event.keyCode) {
                case 27:
                    hideDwnl();
                    break;
            }
        }
        else {
            if ($('#EDITWINDOW').is(':visible')) {

                switch (event.keyCode) {
                    case 9:	// tab ou shift-tab
                        edit_chgFld(event, is_shift_key(event) ? -1 : 1);
                        cancelKey = shortCut = true;
                        break;
                    case 27:
                        edit_cancelMultiDesc(event);
                        shortCut = true;
                        break;

                    case 33:	// pg up
                        if (!p4.edit.textareaIsDirty || edit_validField(event, "ask_ok"))
                            skipImage(event, 1);
                        cancelKey = true;
                        break;
                    case 34:	// pg dn
                        if (!p4.edit.textareaIsDirty || edit_validField(event, "ask_ok"))
                            skipImage(event, -1);
                        cancelKey = true;
                        break;
                }

            }
            else {
                if (p4.preview.open) {
                    if (($('#dialog_dwnl:visible').length === 0 && $('#DIALOG1').length === 0 && $('#DIALOG2').length === 0)) {
                        switch (event.keyCode) {
                            case 39:
                                getNext();
                                cancelKey = shortCut = true;
                                break;
                            case 37:
                                getPrevious();
                                cancelKey = shortCut = true;
                                break;
                            case 27://escape
                                closePreview();
                                break;
                            case 32:
                                if (p4.slideShow)
                                    stopSlide();
                                else
                                    startSlide();
                                cancelKey = shortCut = true;
                                break;
                        }
                    }
                }
                else {
                    if ($('#EDIT_query').hasClass('focused'))
                        return true;

                    if ($('.overlay').is(':visible'))
                        return true;

                    if ($('.ui-widget-overlay').is(':visible'))
                        return true;

                    switch (p4.active_zone) {
                        case 'rightFrame':
                            switch (event.keyCode) {
                                case 65:	// a
                                    if (is_ctrl_key(event)) {
                                        $('.tools .answer_selector.all_selector').trigger('click');
                                        cancelKey = shortCut = true;
                                    }
                                    break;
                                case 80://P
                                    if (is_ctrl_key(event)) {
                                        printThis("lst=" + p4.Results.Selection.serialize());
                                        cancelKey = shortCut = true;
                                    }
                                    break;
                                case 69://e
                                    if (is_ctrl_key(event)) {
                                        editThis('IMGT', p4.Results.Selection.serialize());
                                        cancelKey = shortCut = true;
                                    }
                                    break;
                                case 40:	// down arrow
                                    $('#answers').scrollTop($('#answers').scrollTop() + 30);
                                    cancelKey = shortCut = true;
                                    break;
                                case 38:	// down arrow
                                    $('#answers').scrollTop($('#answers').scrollTop() - 30);
                                    cancelKey = shortCut = true;
                                    break;
                                case 37://previous page
                                    $('#PREV_PAGE').trigger('click');
                                    shortCut = true;
                                    break;
                                case 39://previous page
                                    $('#NEXT_PAGE').trigger('click');
                                    shortCut = true;
                                    break;
                                case 9://tab
                                    if (!is_ctrl_key(event) && !$('.ui-widget-overlay').is(':visible') && !$('.overlay_box').is(':visible')) {
                                        document.getElementById('EDIT_query').focus();
                                        cancelKey = shortCut = true;
                                    }
                                    break;
                            }
                            break;


                        case 'idFrameC':
                            switch (event.keyCode) {
                                case 65:	// a
                                    if (is_ctrl_key(event)) {
                                        p4.WorkZone.Selection.selectAll();
                                        cancelKey = shortCut = true;
                                    }
                                    break;
                                case 80://P
                                    if (is_ctrl_key(event)) {
                                        printThis("lst=" + p4.WorkZone.Selection.serialize());
                                        cancelKey = shortCut = true;
                                    }
                                    break;
                                case 69://e
                                    if (is_ctrl_key(event)) {
                                        editThis('IMGT', p4.WorkZone.Selection.serialize());
                                        cancelKey = shortCut = true;
                                    }
                                    break;
                                //						case 46://del
                                //								deleteThis(p4.Results.Selection.serialize());
                                //								cancelKey = true;
                                //							break;
                                case 40:	// down arrow
                                    $('#baskets div.bloc').scrollTop($('#baskets div.bloc').scrollTop() + 30);
                                    cancelKey = shortCut = true;
                                    break;
                                case 38:	// down arrow
                                    $('#baskets div.bloc').scrollTop($('#baskets div.bloc').scrollTop() - 30);
                                    cancelKey = shortCut = true;
                                    break;
                                //								case 37://previous page
                                //									$('#PREV_PAGE').trigger('click');
                                //									break;
                                //								case 39://previous page
                                //									$('#NEXT_PAGE').trigger('click');
                                //									break;
                                case 9://tab
                                    if (!is_ctrl_key(event) && !$('.ui-widget-overlay').is(':visible') && !$('.overlay_box').is(':visible')) {
                                        document.getElementById('EDIT_query').focus();
                                        cancelKey = shortCut = true;
                                    }
                                    break;
                            }
                            break;


                        case 'mainMenu':
                            break;


                        case 'headBlock':
                            break;

                        default:
                            break;

                    }
                }
            }
        }

        if (!$('#EDIT_query').hasClass('focused') && event.keyCode !== 17) {

            if ($('#keyboard-dialog.auto').length > 0 && shortCut) {
                triggerShortcuts();
            }
        }
        if (cancelKey) {
            event.cancelBubble = true;
            if (event.stopPropagation)
                event.stopPropagation();
            return(false);
        }
        return(true);
    });


    $('#EDIT_query').bind('focus',function () {
        $(this).addClass('focused');
    }).bind('blur', function () {
            $(this).removeClass('focused');
        });

    $('.basketTips').tooltip({
        delay: 200
    });

    $('#idFrameC .tabs').tabs({
        activate: function (event, ui) {
            if (ui.newTab.context.hash == "#thesaurus_tab") {
                thesau_show();
            }
            p4.WorkZone.open();
        }
    });

    $('#PREVIEWBOX .gui_vsplitter', p4.edit.editBox).draggable({
        axis: 'x',
        containment: 'parent',
        drag: function (event, ui) {
            var x = $(ui.position.left)[0];
            if (x < 330 || x > (bodySize.x - 400)) {
                return false;
            }
            var v = $(ui.position.left)[0];
            $("#PREVIEWLEFT").width(v);
            $("#PREVIEWRIGHT").css("left", $(ui.position.left)[0]);
            resizePreview();
        }
    });

    $('input.input_select_copy').on('focus', function () {
        $(this).select();
    });
    $('input.input_select_copy').on('blur', function () {
        $(this).deselect();
    });
    $('input.input_select_copy').on('click', function () {
        $(this).select();
    });

    $('#loader_bar').stop().animate({
        width: '100%'
    }, 450, function () {
        $('#loader').parent().fadeOut('slow', function () {
            $(this).remove();
        });
    });

});

// edit modal - multiusage @todo refactor
function editThis(type, value) {

    $('#idFrameE').empty().addClass('loading');
    showOverlay(2);

    $('#EDITWINDOW').show();

    var options = {
        lst: '',
        ssel: '',
        act: ''
    };

    switch (type) {
        case "IMGT":
            options.lst = value;
            break;

        case "SSTT":
            options.ssel = value;
            break;

        case "STORY":
            options.story = value;
            break;
    }

    $.ajax({
        url: "../prod/records/edit/",
        type: "POST",
        dataType: "html",
        data: options,
        success: function (data) {
            initializeEdit();
            $('#idFrameE').removeClass('loading').empty().html(data);
            $('#tooltip').hide();
            return;
        },
        error: function (XHR, textStatus, errorThrown) {
            if (XHR.status === 0) {
                return false;
            }
        }
    });

    return;
}

(function ($) {
    $.fn.extend({
        highlight: function (color) {
            if ($(this).hasClass('animating')) {
                return;
            }
            color = typeof color !== 'undefined' ? color : 'red';
            var oldColor = $(this).css('backgroundColor');
            return $(this).addClass('animating').stop().animate({
                backgroundColor: color
            }, 50, 'linear', function () {
                $(this).stop().animate({
                    backgroundColor: oldColor
                }, 450, 'linear', function () {
                    $(this).removeClass('animating');
                });
            });
        }
    });
})(jQuery);

(function ($) {
    $.fn.extend({
        flash: function (color) {
            if ($(this).hasClass('animating')) {
                return true;
            }
            color = typeof color !== 'undefined' ? color : 'red';

            var pos = $(this).offset();

            if (!pos) {
                pos = {
                    top: 0,
                    left: 0
                };
            }

            var h = $(this).height();
            var w = $(this).width();
            $('body').append('<div id="flashing" style="border:3px solid ' + color + ';position:absolute;top:' + (pos.top + (h / 2)) + 'px;left:' + (pos.left + (w / 2)) + 'px;width:0px;height:0px"></div>');
            $(this).addClass('animating');
            var el = $(this);

            $('#flashing').stop().animate({
                top: (pos.top + (h / 4)),
                left: (pos.left + (w / 4)),
                opacity: 0,
                width: ($(this).width() / 2),
                height: ($(this).height() / 2)
            }, 700, function () {
                $('#flashing').remove();
                $(el).removeClass('animating');
            });
        }
    });
})(jQuery);


function toggleRemoveReg(el) {
    var state = !el.checked;
    userModule.setPref('reg_delete', (state ? '1' : '0'));
    p4.reg_delete = state;
}


function deleteThis(lst) {
    if (lst.split(';').length === 0) {
        alert(language.nodocselected);
        return false;
    }

    var $dialog = p4.Dialog.Create({
        size: 'Small',
        title: language.deleteRecords
    });

    $.ajax({
        type: "POST",
        url: "../prod/records/delete/what/",
        dataType: 'html',
        data: {lst: lst},
        success: function (data) {
            $dialog.setContent(data);
        }
    });

    return false;
}

// trigger tools modal - multiusage @todo refactor
function toolREFACTOR(datas, activeTab) {

    var dialog = p4.Dialog.Create({
        size: 'Medium',
        title: language.toolbox,
        loading: true
    });

    $.get("../prod/tools/"
        , datas
        , function (data) {
            dialog.setContent(data);
            dialog.setOption('contextArgs', datas);
            var tabs = $('.tabs', dialog.getDomElement()).tabs();

            // activate tab if exists:
            if( activeTab !== undefined ) {
                tabs.tabs('option', 'active', activeTab);
            }
            return;
        }
    );
}

function checkDeleteThis(type, el) {
    el = $(el);
    switch (type) {


        case "IMGT":
        case "CHIM":

            var lst = '';

            if (type === 'IMGT')
                lst = p4.Results.Selection.serialize();
            if (type === 'CHIM')
                lst = p4.WorkZone.Selection.serialize();

            deleteThis(lst);

            return;
            break;


        case "SSTT":

            var buttons = {};
            buttons[language.valider] = function (e) {
                workzoneBasketModule.deleteBasket(el);
            };

            $('#DIALOG').empty().append(language.confirmDel).attr('title', language.attention).dialog({
                autoOpen: false,
                resizable: false,
                modal: true,
                draggable: false
            }).dialog('open').dialog('option', 'buttons', buttons);
            $('#tooltip').hide();
            return;
            break;
        case "STORY":
            lst = el.val();
            deleteThis(lst);
            break;

    }
}

function shareThis(bas, rec) {
    var dialog = p4.Dialog.Create({
        title: language['share']
    });

    dialog.load("../prod/share/record/" + bas + "/" + rec + "/", "GET");
}

function printThis(value) {
    if ($("#DIALOG").data("ui-dialog")) {
        $("#DIALOG").dialog('destroy');
    }
    $('#DIALOG').attr('title', language.print)
        .empty().addClass('loading')
        .dialog({
            resizable: false,
            closeOnEscape: true,
            modal: true,
            width: '800',
            height: '500',
            open: function (event, ui) {
                $(this).dialog("widget").css("z-index", "1999");
            },
            close: function (event, ui) {
                $(this).dialog("widget").css("z-index", "auto");
            }
        })
        .dialog('open');

    $.ajax({
        type: "POST",
        url: '../prod/printer/?' + value,
        dataType: 'html',
        beforeSend: function () {

        },
        success: function (data) {
            $('#DIALOG').removeClass('loading').empty()
                .append(data);
            return;
        }
    });
}




function viewNbSelect() {
    $("#nbrecsel").empty().append(p4.Results.Selection.length());
}

function selector(el) {
    if (el.hasClass('all_selector')) {
        p4.Results.Selection.selectAll();
    }
    else {
        if (el.hasClass('none_selector')) {
            p4.Results.Selection.empty();
        }
        else {
            if (el.hasClass('starred_selector')) {

            }
            else {
                if (el.hasClass('video_selector')) {
                    p4.Results.Selection.empty();
                    p4.Results.Selection.select('.type-video');
                }
                else {
                    if (el.hasClass('image_selector')) {
                        p4.Results.Selection.empty();
                        p4.Results.Selection.select('.type-image');
                    }
                    else {
                        if (el.hasClass('document_selector')) {
                            p4.Results.Selection.empty();
                            p4.Results.Selection.select('.type-document');
                        }
                        else {
                            if (el.hasClass('audio_selector')) {
                                p4.Results.Selection.empty();
                                p4.Results.Selection.select('.type-audio');
                            }
                        }
                    }
                }
            }
        }
    }
}

function evt_dwnl(value) {
    downloadThis("lst=" + value);
}

function evt_print(value) {
    printThis("lst=" + value);
}

function evt_add_in_chutier(sbas_id, record_id, event, singleSelection) {
    var singleSelection = singleSelection || false;
    p4.WorkZone.addElementToBasket(sbas_id, record_id, event, singleSelection);
}

function remove_from_basket(el, confirm) {
    var confirm = confirm || false;
    p4.WorkZone.removeElementFromBasket(el, confirm);
}


function doSpecialSearch(qry, allbase) {
    if (allbase) {
        checkBases(true);
    }
    workzoneFacetsModule.resetSelectedFacets();
    $('#EDIT_query').val(decodeURIComponent(qry).replace(/\+/g, " "));
    searchModule.newSearch(qry);
}

function clktri(id) {
    var o = $('#TOPIC_UL' + id);
    if ($('#TOPIC_UL' + id).hasClass('closed'))
        $('#TOPIC_TRI' + id + ' ,#TOPIC_UL' + id).removeClass('closed').addClass('opened');
    else
        $('#TOPIC_TRI' + id + ' ,#TOPIC_UL' + id).removeClass('opened').addClass('closed');
}


// ---------------------- fcts du thesaurus
/* NOT USED function chgProp(path, v, k) {
    var q2;
    if (!k)
        k = "*";
    //if(k!=null)
    v = v + " [" + k + "]";
    $("#thprop_a_" + path).html('"' + v + '"');
    //	q = document.getElementById("thprop_q").innerText;
    //	if(!q )
    //		if(document.getElementById("thprop_q") && document.getElementById("thprop_q").textContent)
    //			q = document.getElementById("thprop_q").textContent;
    q = $("#thprop_q").text();

    q2 = "";
    for (i = 0; i < q.length; i++)
        q2 += q.charCodeAt(i) == 160 ? " " : q.charAt(i);

    workzoneFacetsModule.resetSelectedFacets();
    $('#EDIT_query').val(q);
    newSearch(q);

    return(false);
}*/

/* NOT USED function doDelete(lst) {
    var children = '0';
    if (document.getElementById('del_children') && document.getElementById('del_children').checked)
        children = '1';
    $.ajax({
        type: "POST",
        url: "../prod/delete/",
        dataType: 'json',
        data: {
            lst: lst.join(';'),
            del_children: children
        },
        success: function (data) {

            $.each(data, function (i, n) {
                var imgt = $('#IMGT_' + n),
                    chim = $('.CHIM_' + n),
                    stories = $('.STORY_' + n);
                $('.doc_infos', imgt).remove();
                imgt.unbind("click").removeAttr("ondblclick").removeClass("selected").removeClass("IMGT").find("img").unbind();

                if (imgt.data("ui-draggable")) {
                    imgt.draggable("destroy");
                }

                imgt.find(".thumb img").attr("src", "/assets/common/images/icons/deleted.png").css({
                    width: '100%',
                    height: 'auto',
                    margin: '0 10px',
                    top: '0'
                });
                chim.parent().slideUp().remove();
                imgt.find(".status,.title,.bottom").empty();

                p4.Results.Selection.remove(n);
                if (stories.length > 0) {
                    p4.WorkZone.refresh();
                }
                else {
                    p4.WorkZone.Selection.remove(n);
                }
            });
            viewNbSelect();
        }
    });
}*/








/* NOT USED function advSearch(event) {
    event.cancelBubble = true;
    //  alternateSearch(false);
    $('#idFrameC .tabs a.adv_search').trigger('click');
}*/

// look_box
function start_page_selector() {
    var el = $('#look_box_settings select[name=start_page]');

    switch (el.val()) {
        case "LAST_QUERY":
        case "PUBLI":
        case "HELP":
            $('#look_box_settings input[name=start_page_value]').hide();
            break;
        case "QUERY":
            $('#look_box_settings input[name=start_page_value]').show();
            break;
    }
}
// look_box
function set_start_page() {
    var el = $('#look_box_settings select[name=start_page]');
    var val = el.val();


    var start_page_query = $('#look_box_settings input[name=start_page_value]').val();

    if (val === 'QUERY') {
        userModule.setPref('start_page_query', start_page_query);
    }

    userModule.setPref('start_page', val);

}


// preferences modal
function lookBox(el, event) {
    $("#look_box").dialog({
        closeOnEscape: true,
        resizable: false,
        width: 450,
        height: 500,
        modal: true,
        draggable: false,
        overlay: {
            backgroundColor: '#000',
            opacity: 0.7
        }
    }).dialog('open');
}

function showAnswer(p) {
    var o;
    if (p === 'Results') {
        // on montre les results
        if (o = document.getElementById("AnswerExplain"))
            o.style.visibility = "hidden";
        if (o = document.getElementById("AnswerResults")) {
            o.style.visibility = "";
            o.style.display = "block";
        }
        // on montre explain
        if (document.getElementById("divpage"))
            document.getElementById("divpage").style.visibility = visibilityDivPage;

        if (document.getElementById("explainResults"))
            document.getElementById("explainResults").style.display = "none";
    }
    else {
        // on montre explain
        if (document.getElementById("divpage")) {
            visibilityDivPage = "visible";
            document.getElementById("divpage").style.visibility = "hidden";
        }
        if (document.getElementById("explainResults"))
            document.getElementById("explainResults").style.display = "block";

        if (o = document.getElementById("AnswerResults")) {
            o.style.visibility = "hidden";
            o.style.display = "none";

        }
        if (o = document.getElementById("AnswerExplain"))
            o.style.visibility = "";
        if (o = document.getElementById("AnswerExplain")) {
            o.style.display = "none";
            setTimeout('document.getElementById("AnswerExplain").style.display = "block";', 200);
        }
    }
}


/**  FROM INDEX.php **/
function saveeditPbar(idesc, ndesc) {
    document.getElementById("saveeditPbarI").innerHTML = idesc;
    document.getElementById("saveeditPbarN").innerHTML = ndesc;
}

/* NOT USED function getSelText() {
    var txt = '';
    if (window.getSelection) {
        txt = window.getSelection();
    }
    else if (document.getSelection) {
        txt = document.getSelection();
    }
    else if (document.selection) {
        txt = document.selection.createRange().text;
    }
    else
        return;
    return txt;
}*/

/* NOT USED function getWinPosAsXML() {
    var ret = '<win id="search" ratio="' + ($('#idFrameC').outerWidth() / bodySize.x) + '"/>';

    if ($('#idFrameE').is(':visible') && $('#EDITWINDOW').is(':visible'))
        ret += '<win id="edit" ratio="' + ($('#idFrameE').outerWidth() / $('#EDITWINDOW').innerWidth()) + '"/>';


    return ret;
}*/

function saveWindows() {
    var key = '';
    var value = '';


    if ($('#idFrameE').is(':visible') && $('#EDITWINDOW').is(':visible')) {
        key = 'edit_window';
        value = $('#idFrameE').outerWidth() / $('#EDITWINDOW').innerWidth();
    }
    else {
        key = 'search_window';
        value = $('#idFrameC').outerWidth() / bodySize.x;
    }
    userModule.setPref(key, value);
}

function gotopage(pag) {
    $('#searchForm input[name="sel"]').val(p4.Results.Selection.serialize());
    $('#formAnswerPage').val(pag);
    $('#searchForm').submit();
}

/* NOT USED function addFilterMulti(filter, link, sbasid) {
    var clone = $('#filter_multi_' + sbasid + '_' + filter);
    var orig = clone;
    if (!$('#filter_multi_' + sbasid + '_' + filter).is(':visible')) {
        clone = orig.clone(true);
        var par = orig.parent();
        orig.remove();
        par.append(clone);
        clone.slideDown('fast', function () {
            $(this);
        });
        $(link).addClass('filterActive');
    }
    else {
        clone.slideUp();
        $(link).removeClass('filterActive');
    }
    return false;
}*/

/* NOT USED function autoorder() {
    var val = $.trim($('#auto_order').val());

    if (val === '')
        return;

    var sorter = new Array();

    $('#reorder_box .diapo form').each(function (i, n) {

        var id = $('input[name=id]', n).val();

        switch (val) {
            case 'title':
            default:
                var data = $('input[name=title]', n).val();
                break;
            case 'default':
                var data = $('input[name=default]', n).val();
                break;
        }

        sorter[id] = data;
    });

    var data_type = 'string';

    switch (val) {
        case 'default':
            var data_type = 'integer';
            break;
    }

    sorter = arraySortByValue(sorter, data_type);

    var last_moved = false;

    for (i in sorter) {
        var elem = $('#ORDER_' + i);
        if (last_moved) {
            elem.insertAfter(last_moved);
        }
        else {
            $('#reorder_box').prepend(elem);
        }
        last_moved = elem;
    }

}*/




//clear search
$(document).ready(function () {

    $('#thesaurus_tab .input-medium').on('keyup', function(){
        if($('#thesaurus_tab .input-medium').val() != ''){
            $('#thesaurus_tab .th_clear').show();
        }else{
            $('#thesaurus_tab .th_clear').hide();
        }
    });

    $('.th_clear').on('click', function(){
        $('#thesaurus_tab .input-medium').val('');
        $('#thesaurus_tab .gform').submit();
        $('#thesaurus_tab .th_clear').hide();
    });

    $('.treeview>li.expandable>.hitarea').on('click', function(){
        if($(this).css('background-position') == '99% 22px'){
            $(this).css('background-position', '99% -28px');
            $(this).addClass('active');
        }else{
            $(this).css('background-position', '99% 22px');
            $(this).removeClass('active');
        }
    });

});
