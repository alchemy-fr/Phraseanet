import $ from 'jquery';
import * as appCommons from './../../phraseanet-common';
import toolbar from './toolbar';
import mainMenu from '../mainMenu';
import keyboard from './keyboard';
import cgu from '../cgu';
import editRecordService from '../record/edit';
import exportRecord from '../record/export';
import shareRecord from '../record/share';
import recordVideoEditorModal from '../record/videoEditor/index';
import addToBasket from '../record/addToBasket';
import removeFromBasket from '../record/removeFromBasket';
import printRecord from '../record/print';
import preferences from '../preferences';
import order from '../order';
import previewRecordService from '../record/recordPreview';
import Alerts from '../utils/alert';
import uploader from '../uploader';

const ui = services => {
    const { configService, localeService, appEvents } = services;
    let activeZone = false;
    let searchSelection = { asArray: [], serialized: '' };
    let workzoneSelection = { asArray: [], serialized: '' };

    const initialize = options => {
        let { $container } = options;
        // init state navigation
        // records and baskets actions in global interface:
        exportRecord(services).initialize();
        addToBasket(services).initialize();
        removeFromBasket(services).initialize();
        printRecord(services).initialize();
        shareRecord(services).initialize(options);
        recordVideoEditorModal(services).initialize(options);
        cgu(services).initialize(options);
        preferences(services).initialize(options);
        order(services).initialize(options);

        let editRecord = editRecordService(services);
        editRecord.initialize();

        let previewRecord = previewRecordService(services);

        let previewIsOpen = false;
        previewRecord.getPreviewStream().subscribe(function (previewOptions) {
            previewIsOpen = previewOptions.open;
        });
        previewRecord.initialize();

        // add interface components:
        toolbar(services).initialize();
        mainMenu(services).initialize();
        keyboard(services).initialize();
        uploader(services).initialize();

        // main menu > help context menu
        $('.shortcuts-trigger').bind('click', function () {
            keyboard(services).openModal();
        });

        $container.on('keydown', event => {
            let specialKeyState = {
                isCancelKey: false,
                isShortcutKey: false
            };

            if ($('#MODALDL').is(':visible')) {
                switch (event.keyCode) {
                    case 27:
                        // hide download
                        hideOverlay(2);
                        $('#MODALDL').css({
                            display: 'none'
                        });
                        break;
                    default:
                }
            } else {
                if ($('#EDITWINDOW').is(':visible')) {
                    // access to editor instead of edit modal
                     specialKeyState = editRecord.onGlobalKeydown(event, specialKeyState);
                } else if (previewIsOpen) {
                    specialKeyState = previewRecord.onGlobalKeydown(
                        event,
                        specialKeyState
                    );
                } else if ($('#EDIT_query').hasClass('focused')) {
                    // if return true - nothing to do
                } else if ($('.overlay').is(':visible')) {
                    // if return true - nothing to do
                } else if ($('.ui-widget-overlay').is(':visible')) {
                    // if return true - nothing to do
                } else {
                    switch (getActiveZone()) {
                        case 'rightFrame':
                            specialKeyState = _searchResultKeyDownEvent(
                                event,
                                specialKeyState
                            );
                            break;
                        case 'idFrameC':
                            specialKeyState = _workzoneKeyDownEvent(
                                event,
                                specialKeyState
                            );
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

            if (!$('#EDIT_query').hasClass('focused') && event.keyCode !== 17) {
                if (
                    $('#keyboard-dialog.auto').length > 0 &&
                    specialKeyState.isShortcutKey
                ) {
                    keyboard(services).openModal();
                }
            }

            if (specialKeyState.isCancelKey) {
                event.cancelBubble = true;
                if (event.stopPropagation) {
                    event.stopPropagation();
                }
                return false;
            }
            return true;
        });
    };

    // @TODO to be moved
    const _searchResultKeyDownEvent = (event, specialKeyState) => {
        switch (event.keyCode) {
            case 65: // a
                if (appCommons.utilsModule.is_ctrl_key(event)) {
                    appEvents.emit('search.selection.selectAll');
                    specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                    event.cancelBubble = true;
                    if (event.stopPropagation) {
                        event.stopPropagation();
                    }
                }
                break;
            case 80: // P
                if (appCommons.utilsModule.is_ctrl_key(event)) {
                    appEvents.emit(
                        'record.doPrint',
                        'lst=' + searchSelection.serialized
                    );
                    specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                }
                break;
            case 69: // e
                if (appCommons.utilsModule.is_ctrl_key(event)) {
                    // eq to: editRecord.doEdit()
                    appEvents.emit('record.doEdit', {
                        type: 'IMGT',
                        value: searchSelection.serialized
                    });
                    specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                }
                break;
            case 40: // down arrow
                $('#answers').scrollTop($('#answers').scrollTop() + 30);
                specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                break;
            case 38: // down arrow
                $('#answers').scrollTop($('#answers').scrollTop() - 30);
                specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                break;
            case 37: // previous page
                $('#PREV_PAGE').trigger('click');
                specialKeyState.isShortcutKey = true;
                break;
            case 39: // previous page
                $('#NEXT_PAGE').trigger('click');
                specialKeyState.isShortcutKey = true;
                break;
            case 9: // tab
                if (
                    !appCommons.utilsModule.is_ctrl_key(event) &&
                    !$('.ui-widget-overlay').is(':visible') &&
                    !$('.overlay_box').is(':visible')
                ) {
                    document.getElementById('EDIT_query').focus();
                    specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                }
                break;
            default:
        }
        return specialKeyState;
    };

    // @TODO to be moved
    const _workzoneKeyDownEvent = (event, specialKeyState) => {
        switch (event.keyCode) {
            case 65: // a
                if (appCommons.utilsModule.is_ctrl_key(event)) {
                    appEvents.emit('workzone.selection.selectAll');
                    // p4.WorkZone.Selection.selectAll();
                    specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                }
                break;
            case 80: // P
                if (appCommons.utilsModule.is_ctrl_key(event)) {
                    appEvents.emit(
                        'record.doPrint',
                        'lst=' + workzoneSelection.serialized
                    );
                    specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                }
                break;
            case 69: // e
                if (appCommons.utilsModule.is_ctrl_key(event)) {
                    // eq to: editRecord.doEdit()
                    appEvents.emit('record.doEdit', {
                        type: 'IMGT',
                        value: workzoneSelection.serialized
                    });
                    specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                }
                break;
            // 						case 46:// del
            // 								_deleteRecords(searchSelection.serialized);
            // 								specialKeyState.isCancelKey = true;
            // 							break;
            case 40: // down arrow
                $('#baskets div.bloc').scrollTop(
                    $('#baskets div.bloc').scrollTop() + 30
                );
                specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                break;
            case 38: // down arrow
                $('#baskets div.bloc').scrollTop(
                    $('#baskets div.bloc').scrollTop() - 30
                );
                specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                break;
            case 37:// previous page
            	$('#PREV_PAGE').trigger('click');
            	break;
            case 39:// previous page
            	$('#NEXT_PAGE').trigger('click');
            	break;
            case 9: // tab
                if (
                    !appCommons.utilsModule.is_ctrl_key(event) &&
                    !$('.ui-widget-overlay').is(':visible') &&
                    !$('.overlay_box').is(':visible')
                ) {
                    document.getElementById('EDIT_query').focus();
                    specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                }
                break;
            default:
        }
        return specialKeyState;
    };

    const hideOverlay = n => {
        var div = 'OVERLAY';
        if (typeof n !== 'undefined') {
            div += n;
        }
        $('#' + div).hide().remove();
    };

    const showModal = (cas, options) => {
        var content = '';
        var callback = null;
        var button = {
            OK: function (e) {
                hideOverlay(3);
                $(this).dialog('close');
                return;
            }
        };
        var escape = true;
        var onClose = function () {};

        switch (cas) {
            case 'timeout':
                content = localeService.t('serverTimeout');
                break;
            case 'error':
                content = localeService.t('serverError');
                break;
            case 'disconnected':
                content = localeService.t('serverDisconnected');
                escape = false;
                callback = function (e) {
                    self.location.replace(self.location.href);
                };
                break;
            default:
                break;
        }

        if (typeof Alerts === 'undefined') {
            alert(localeService.t('serverDisconnected'));
            self.location.replace(self.location.href);
        } else {
            Alerts(options.title, content, callback);
        }
        return;
    };

    const getActiveZone = () => {
        return activeZone;
    };
    const setActiveZone = zoneId => {
        activeZone = zoneId;
        return activeZone;
    };

    const activeZoning = () => {
        $('#idFrameC, #rightFrame').bind('mousedown', function (event) {
            var old_zone = getActiveZone();
            setActiveZone($(this).attr('id'));
            if (
                getActiveZone() !== old_zone &&
                getActiveZone() !== 'headBlock'
            ) {
                $('.effectiveZone.activeZone').removeClass('activeZone');
                $('.effectiveZone', this).addClass('activeZone'); // .flash('#555555');
            }
            $('#EDIT_query').blur();
        });
        $('#rightFrame').trigger('mousedown');
    };

    const resizeAll = () => {
        var body = $('body');
        window.bodySize.y = body.height();
        window.bodySize.x = body.width();

        var headBlockH = $('#headBlock').outerHeight();
        var bodyY = window.bodySize.y - headBlockH - 2;
        var bodyW = window.bodySize.x - 2;
        // $('#desktop').height(bodyY).width(bodyW);

        appEvents.emit('preview.doResize');

        if ($('#idFrameC').data('ui-resizable')) {
            $('#idFrameC').resizable('option', 'maxWidth', 600);
            $('#idFrameC').resizable('option', 'minWidth', 360);
        }

        answerSizer();
        linearizeUi();
    };
    const answerSizer = () => {
        var el = $('#idFrameC').outerWidth();
        if (!$.support.cssFloat) {
            // $('#idFrameC .insidebloc').width(el - 56);
        }
        var widthA = Math.round(window.bodySize.x - el - 10);
        $('#rightFrame').width(widthA);
        $('#rightFrame').css('left', $('#idFrameC').width());
    };
    const linearizeUi = () => {
        const list = $('#answers .list');
        let fllWidth = $('#answers').innerWidth();
        let n;
        if (list.length > 0) {
            fllWidth -= 16;

            var stdWidth = 567;
            var diff = 28;
            n = Math.round(fllWidth / stdWidth);
            var w = Math.floor(fllWidth / n) - diff;
            if (w < 567 && n > 1) {
                w = Math.floor(fllWidth / (n - 1)) - diff;
            }
            $('#answers .list').width(w);
        } else {
            var minMargin = 5;
            var el = $('#answers .diapo:first');
            var diapoWidth = el.outerWidth() + minMargin * 2;
            fllWidth -= 26;

            n = Math.floor(fllWidth / diapoWidth);

            let margin = Math.floor(fllWidth % diapoWidth / (2 * n));
            margin = margin + minMargin;

            $('#answers .diapo').css('margin', '5px ' + margin + 'px');
            var answerIcons = $('#answers .bottom_actions_holder .fa-stack');
            var answerIconsHolder = $('.bottom_actions_holder');
            if (el.outerWidth() < 180) {
                answerIcons.css('width', '20px');
                answerIcons.css('font-size', '10px');
                answerIconsHolder.addClass('twenty');
            }

            if ((el.outerWidth() >= 180) && (el.outerWidth() < 260)) {
                answerIcons.css('width', '24px');
                answerIcons.css('font-size', '12px');
                answerIconsHolder.addClass('twenty-four');
            }

            if (el.outerWidth() >= 260) {
                answerIcons.css(
                    'width', '30px'
                );
                answerIcons.css(
                    'font-size', '15px'
                );
                answerIcons.closest('td').css('width', '110px');
                answerIconsHolder.css('height', '36px');
                answerIconsHolder.addClass('thirty');
            }
        }
    };

    const saveWindow = () => {
        var key = '';
        var value = '';

        if ($('#idFrameE').is(':visible') && $('#EDITWINDOW').is(':visible')) {
            key = 'edit_window';
            value = $('#idFrameE').outerWidth() / $('#EDITWINDOW').innerWidth();
        } else {
            key = 'search_window';
            value = $('#idFrameC').outerWidth() / window.bodySize.x;
        }
        appCommons.userModule.setPref(key, value);
    };

    appEvents.listenAll({
        'broadcast.searchResultSelection': selection => {
            searchSelection = selection;
        },
        'broadcast.workzoneResultSelection': selection => {
            workzoneSelection = selection;
        },
        'ui.resizeAll': resizeAll,
        'ui.answerSizer': answerSizer,
        'ui.linearizeUi': linearizeUi,
        'ui.saveWindow': saveWindow
    });

    return { initialize, showModal, activeZoning, getActiveZone, resizeAll };
};

export default ui;
