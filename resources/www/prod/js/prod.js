var p4 = p4 || {};
var baskAjax, baskAjaxrunning;
baskAjaxrunning = false;
var answAjax, answAjaxrunning;
answAjaxrunning = false;
var searchAjax, searchAjaxRunning;
searchAjaxRunning = false;
var bodySize = {
    x: 0,
    y: 0
};

var prodModule = (function (p4, humane) {

    document.getElementById('loader_bar').style.width = '30%';
    $(document).ready(function () {
        humane.info = humane.spawn({addnCls: 'humane-libnotify-info', timeout: 1000});
        humane.error = humane.spawn({addnCls: 'humane-libnotify-error', timeout: 1000});
        humane.forceNew = true;
        // cguModule.activateCgus();
        $('body').on('click', 'a.dialog', function (event) {
            var $this = $(this), size = 'Medium';

            if ($this.hasClass('small-dialog')) {
                size = 'Small';
            } else if ($this.hasClass('full-dialog')) {
                size = 'Full';
            }

            var options = {
                size: size,
                loading: true,
                title: $this.attr('title'),
                closeOnEscape: true
            };

            $dialog = dialogModule.dialog.create(options);

            $.ajax({
                type: "GET",
                url: $this.attr('href'),
                dataType: 'html',
                success: function (data) {
                    $dialog.setContent(data);
                    return;
                }
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



        $('#loader_bar').stop().animate({
            width: '70%'
        }, 450);

        p4.preview = {
            open: false,
            current: false
        };
        p4.sel = [];
        p4.edit = {};
        p4.thesau = {
            tabs: null
        };
        p4.active_zone = false;

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

        _activeZoning();

        $('.shortcuts-trigger').bind('click', function () {
            _triggerShortcuts();
        });





        _resizeAll();

        $(window).bind('resize', function () {
            _resizeAll();
        });
        $('body').append('<iframe id="MODALDL" class="modalbox" src="about:blank;" name="download" style="display:none;border:none;" frameborder="0"></iframe>');

        $('body').append('<iframe id="idHFrameZ" src="about:blank" style="display:none;" name="HFrameZ"></iframe>');


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
            _answerSelector($(this));

        }).bind('mouseover',function (event) {
            if (utilsModule.is_ctrl_key(event)) {
                $(this).addClass('add_selector');
            }
            else {
                $(this).removeClass('add_selector');
            }
        }).bind('mouseout', function () {
            $(this).removeClass('add_selector');
        });

        // getLanguage();

        _initAnswerForm();

        // setTimeout("pollNotifications();", 10000);

        $(this).bind('keydown', function (event) {
            var cancelKey = false;
            var shortCut = false;

            if ($('#MODALDL').is(':visible')) {
                switch (event.keyCode) {
                    case 27:
                        // hide download
                        commonModule.hideOverlay(2);
                        $('#MODALDL').css({
                            'display': 'none'
                        });
                        break;
                }
            }
            else {
                if ($('#EDITWINDOW').is(':visible')) {

                    switch (event.keyCode) {
                        case 9:	// tab ou shift-tab
                            recordEditorModule.edit_chgFld(event, utilsModule.is_shift_key(event) ? -1 : 1);
                            cancelKey = shortCut = true;
                            break;
                        case 27:
                            recordEditorModule.edit_cancelMultiDesc(event);
                            shortCut = true;
                            break;

                        case 33:	// pg up
                            if (!p4.edit.textareaIsDirty || recordEditorModule.edit_validField(event, "ask_ok"))
                                recordEditorModule.skipImage(event, 1);
                            cancelKey = true;
                            break;
                        case 34:	// pg dn
                            if (!p4.edit.textareaIsDirty || recordEditorModule.edit_validField(event, "ask_ok"))
                                recordEditorModule.skipImage(event, -1);
                            cancelKey = true;
                            break;
                    }

                }
                else {
                    if (p4.preview.open) {
                        if (($('#dialog_dwnl:visible').length === 0 && $('#DIALOG1').length === 0 && $('#DIALOG2').length === 0)) {
                            switch (event.keyCode) {
                                case 39:
                                    recordPreviewModule.getNext();
                                    cancelKey = shortCut = true;
                                    break;
                                case 37:
                                    recordPreviewModule.getPrevious();
                                    cancelKey = shortCut = true;
                                    break;
                                case 27://escape
                                    recordPreviewModule.closePreview();
                                    break;
                                case 32:
                                    if (p4.slideShow)
                                        recordPreviewModule.stopSlide();
                                    else
                                        recordPreviewModule.startSlide();
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
                                        if (utilsModule.is_ctrl_key(event)) {
                                            $('.tools .answer_selector.all_selector').trigger('click');
                                            cancelKey = shortCut = true;
                                        }
                                        break;
                                    case 80://P
                                        if (utilsModule.is_ctrl_key(event)) {
                                            _onOpenPrintModal("lst=" + p4.Results.Selection.serialize());
                                            cancelKey = shortCut = true;
                                        }
                                        break;
                                    case 69://e
                                        if (utilsModule.is_ctrl_key(event)) {
                                            openRecordEditor('IMGT', p4.Results.Selection.serialize());
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
                                        if (!utilsModule.is_ctrl_key(event) && !$('.ui-widget-overlay').is(':visible') && !$('.overlay_box').is(':visible')) {
                                            document.getElementById('EDIT_query').focus();
                                            cancelKey = shortCut = true;
                                        }
                                        break;
                                }
                                break;


                            case 'idFrameC':
                                switch (event.keyCode) {
                                    case 65:	// a
                                        if (utilsModule.is_ctrl_key(event)) {
                                            p4.WorkZone.Selection.selectAll();
                                            cancelKey = shortCut = true;
                                        }
                                        break;
                                    case 80://P
                                        if (utilsModule.is_ctrl_key(event)) {
                                            _onOpenPrintModal("lst=" + p4.WorkZone.Selection.serialize());
                                            cancelKey = shortCut = true;
                                        }
                                        break;
                                    case 69://e
                                        if (utilsModule.is_ctrl_key(event)) {
                                            openRecordEditor('IMGT', p4.WorkZone.Selection.serialize());
                                            cancelKey = shortCut = true;
                                        }
                                        break;
                                    //						case 46://del
                                    //								_deleteRecords(p4.Results.Selection.serialize());
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
                                        if (!utilsModule.is_ctrl_key(event) && !$('.ui-widget-overlay').is(':visible') && !$('.overlay_box').is(':visible')) {
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
                    _triggerShortcuts();
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

    function _resizeAll() {
        var body = $('body');
        bodySize.y = body.height();
        bodySize.x = body.width();

        var headBlockH = $('#headBlock').outerHeight();
        var bodyY = bodySize.y - headBlockH - 2;
        var bodyW = bodySize.x - 2;
        //$('#desktop').height(bodyY).width(bodyW);

        if (p4.preview.open)
            recordPreviewModule.resizePreview();

        if ($('#idFrameC').data('ui-resizable')) {
            $('#idFrameC').resizable('option', 'maxWidth', (480));
            $('#idFrameC').resizable('option', 'minWidth', 300);
        }

        answerSizer();
        linearizeUi();


    }
    function answerSizer() {
        var el = $('#idFrameC').outerWidth();
        if (!$.support.cssFloat) {
            // $('#idFrameC .insidebloc').width(el - 56);
        }
        var widthA = Math.round(bodySize.x - el - 10);
        $('#rightFrame').width(widthA);
        $('#rightFrame').css('left', $('#idFrameC').width());

    }
    function linearizeUi() {
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

    /*function doSpecialSearch(qry, allbase) {
        if (allbase) {
            searchModule.toggleDatabase(true);
        }
        workzoneFacetsModule.resetSelectedFacets();
        $('#EDIT_query').val(decodeURIComponent(qry).replace(/\+/g, " "));
        searchModule.newSearch(qry);
    }*/

    function addToBasket(sbas_id, record_id, event, singleSelection) {
        var singleSelection = singleSelection || false;
        p4.WorkZone.addElementToBasket(sbas_id, record_id, event, singleSelection);
    }

    function removeFromBasket(el, confirm) {
        var confirm = confirm || false;
        p4.WorkZone.removeElementFromBasket(el, confirm);
    }

    function openRecordEditor(type, value) {

        $('#idFrameE').empty().addClass('loading');
        commonModule.showOverlay(2);

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
                recordEditorModule.initialize();
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

    function openShareModal(bas, rec) {
        var dialog = dialogModule.dialog.create({
            title: language['share']
        });

        dialog.load("../prod/share/record/" + bas + "/" + rec + "/", "GET");
    }
    function openPrintModal(value) {
        _onOpenPrintModal("lst=" + value);
    }

    function _onOpenPrintModal(value) {
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

    function openToolModal(datas, activeTab) {

        var dialog = dialogModule.dialog.create({
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

    function openDownloadModal(value, key) {
        if( key !== undefined ) {
            value = key+'='+value
        }
        _onOpenDownloadModal(value);
    }
    // @TODO duplicate with external module
    function _onOpenDownloadModal(datas) {
        var dialog = dialogModule.dialog.create({title: language['export']});

        $.post("../prod/export/multi-export/", datas, function (data) {

            dialog.setContent(data);

            $('.tabs', dialog.getDomElement()).tabs();

            $('.close_button', dialog.getDomElement()).bind('click', function () {
                dialog.close();
            });

            return false;
        });
    }


    function deleteConfirmation(type, el) {
        el = $(el);
        switch (type) {


            case "IMGT":
            case "CHIM":

                var lst = '';

                if (type === 'IMGT')
                    lst = p4.Results.Selection.serialize();
                if (type === 'CHIM')
                    lst = p4.WorkZone.Selection.serialize();

                _deleteRecords(lst);

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
                _deleteRecords(lst);
                break;

        }
    }
    function _deleteRecords(lst) {
        if (lst.split(';').length === 0) {
            alert(language.nodocselected);
            return false;
        }

        var $dialog = dialogModule.dialog.create({
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

    function toggleTopic(id) {
        var o = $('#TOPIC_UL' + id);
        if ($('#TOPIC_UL' + id).hasClass('closed'))
            $('#TOPIC_TRI' + id + ' ,#TOPIC_UL' + id).removeClass('closed').addClass('opened');
        else
            $('#TOPIC_TRI' + id + ' ,#TOPIC_UL' + id).removeClass('opened').addClass('closed');
    }

    function _initAnswerForm() {
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
                            searchResultModule.gotopage(datas.next_page);
                        });
                    }
                    else {
                        $("#NEXT_PAGE").unbind('click');
                    }

                    if (datas.prev_page) {
                        $("#PREV_PAGE").bind('click', function () {
                            searchResultModule.gotopage(datas.prev_page);
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
    }

    function _triggerShortcuts() {

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

    function _activeZoning() {
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

    function _answerSelector(el) {
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

    function _saveWindows() {
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

    return {
        linearizeUi: linearizeUi,
        answerSizer: answerSizer,
        openRecordEditor: openRecordEditor,
        openPrintModal: openPrintModal,
        openShareModal: openShareModal,
        openToolModal: openToolModal,
        openDownloadModal: openDownloadModal,
        deleteConfirmation: deleteConfirmation,
        doSpecialSearch: doSpecialSearch,
        addToBasket: addToBasket,
        removeFromBasket: removeFromBasket,
        toggleTopic: toggleTopic
    }
})(p4, humane);

//var language = {}; // handled with external prodution module



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
