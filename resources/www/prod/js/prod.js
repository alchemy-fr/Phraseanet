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

    /*function _resizeAll() {
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

    }*/

    function doSpecialSearch(qry, allbase) {
        if (allbase) {
            prodApp.appEvents.emit('search.doToggleDatabase', true);
        }
        prodApp.appEvents.emit('facets.doResetSelectedFacets');
        $('#EDIT_query').val(decodeURIComponent(qry).replace(/\+/g, " "));
        prodApp.appEvents.emit('search.doNewSearch', qry);
    }

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
                    prodApp.appEvents.emit('baskets.doDeleteBasket', el);
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

    /*function _saveWindows() {
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
*/
    return {
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
