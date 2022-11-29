require('./recordPreview.scss');

import $ from 'jquery';
import merge from 'lodash.merge';
import * as Rx from 'rx';
import Emitter from '../../core/emitter';
import leafletMap from './../../geolocalisation/providers/mapbox';
import pym from 'pym.js';
let image_enhancer = require('imports-loader?$=jquery!../../utils/jquery-plugins/imageEnhancer/imageEnhancer');
require('./../../../phraseanet-common/components/tooltip');
const previewRecordService = services => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let recordPreviewEvents = new Emitter();
    let $bodyContainer = null;
    let $previewContainer = null;
    let $previewTabContainer;
    let prevAjax;
    let prevAjaxrunning;
    let activeThumbnailFrame = false;
    prevAjaxrunning = false;
    let stream = new Rx.Subject();
    let options = {
        open: false,
        current: false,
        slideShow: false,
        navigation: {
            perPage: 0,
            page: 0
        }
    };
    stream.onNext(options);
    const initialize = () => {
        $bodyContainer = $('body');
        $previewContainer = $('#PREVIEWBOX');
        $previewTabContainer = $('#PREVIEWIMGDESC');
        $previewTabContainer.tabs({
            activate: function (event, ui) {
                recordPreviewEvents.emit('tabChange');
            }
        });

        // if contained in record editor (p4.edit.editBox):
        $('#PREVIEWBOX .gui_vsplitter').draggable({
            axis: 'x',
            containment: 'parent',
            drag: function (event, ui) {
                var x = $(ui.position.left)[0];
                if (x < 330 || x > window.bodySize.x - 400) {
                    return false;
                }
                var v = $(ui.position.left)[0];
                $('#PREVIEWLEFT').width(v);
                $('#PREVIEWRIGHT').css('left', $(ui.position.left)[0]);
                resizePreview();
            }
        });
        leafletMap({
            configService,
            localeService,
            eventEmitter: recordPreviewEvents
        }).initialize({
            $container: $previewContainer,
            parentOptions: options,
            searchable: true,
            tabOptions: {
                /*tabProperties: {
                 classes: 'descBoxes',
                 },*/
                position: 3
            }
        });

        _bindEvents();
    };

    const _bindEvents = () => {
        $bodyContainer
            .on('click', '.close-preview-action', event => {
                event.preventDefault();
                closePreview();
            })
            .on('dblclick', '.open-preview-action', event => {
                let $element = $(event.currentTarget);
                openPreview($element);

            })
            .on('click', '.to-open-preview-action', event => {
                event.preventDefault();
                let $element = $(event.currentTarget);
                openPreview($element);
            })
        ;
        $previewContainer
            .on('click', '.preview-navigate-action', event => {
                event.preventDefault();
                let $el = $(event.currentTarget);
                let dir =
                    $el.data('direction') === 'forward'
                        ? getNext()
                        : getPrevious();
            })
            .on('click', '.preview-start-slideshow-action', event => {
                event.preventDefault();
                startSlide();
            })
            .on('click', '.preview-stop-slideshow-action', event => {
                event.preventDefault();
                stopSlide();
            })
            .on('click', '.edit-record-action', event => {
                if (activeThumbnailFrame !== false) {
                    // tell child iframe to pause:
                    activeThumbnailFrame.sendMessage('pause', 'ok');
                }
                event.preventDefault();
            });
    };

    /**
     * Handle global keydown event if preview is open
     * @param event
     */
    const onGlobalKeydown = (event, specialKeyState) => {
        if (specialKeyState === undefined) {
            let specialKeyState = {
                isCancelKey: false,
                isShortcutKey: false
            };
        }
        if (options.open) {
            if (
                $('#dialog_dwnl:visible').length === 0 &&
                $('#DIALOG1').length === 0 &&
                $('#DIALOG2').length === 0
            ) {
                switch (event.keyCode) {
                    // next
                    case 39:
                        getNext();
                        specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                        break;
                    // previous
                    case 37:
                        getPrevious();
                        specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                        break;
                    case 27: //escape
                        closePreview();
                        break;
                    case 32:
                        var videoElement = $('#PREVIEWIMGCONT iframe').contents().find('video');
                        if (videoElement.length > 0) {
                            if (videoElement.get(0).paused == true) {
                                videoElement.get(0).play();
                            } else {
                                videoElement.get(0).pause();
                            }
                            specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                        }
                        break;
                    default:
                }
            }
        }
        return specialKeyState;
    };

    /**
     * @param source
     * @param env
     * @param pos - relative position in current page
     * @param contId
     * @param reload
     */
    function _openPreview(source, env, pos, contId, reload) {
        if (contId === undefined) {
            contId = '';
        }
        var roll = 0;
        var justOpen = false;

        var options_serial = options.navigation.tot_options;
        var query = options.navigation.tot_query;
        var navigationContext = '';
        // keep relative position for answer train:
        var relativePos = pos;
        var absolutePos = 0;

        if (!options.open) {
            $('#PREVIEWIMGCONT').disableSelection();

            justOpen = true;

            if (!navigator.userAgent.match(/msie/i)) {
                $('#PREVIEWBOX')
                    .css({
                        display: 'block',
                        opacity: 0
                    })
                    .fadeTo(500, 1);
            } else {
                $('#PREVIEWBOX').css({
                    display: 'block',
                    opacity: 1
                });
            }
            options.open = true;
            options.nCurrent = 5;
            $('#PREVIEWCURRENT, #PREVIEWOTHERSINNER, #SPANTITLE').empty();
            resizePreview();

            if (env === 'BASK') {
                roll = 1;
                if (source !== false && source.hasClass('CHIM')) {
                    navigationContext = 'baskFromWorkzone';
                }
            }

            // if comes from story and in workzone
            if (env === 'REG') {
                navigationContext = 'storyFromResults';
                if (source !== false && source.hasClass('CHIM')) {
                    navigationContext = 'storyFromWorkzone';
                }
            }
        }

        if (reload === true) {
            roll = 1;
        }

        $('#tooltip').css({
            display: 'none'
        });

        $('#PREVIEWIMGCONT').empty();

        if (navigationContext === 'storyFromWorkzone' || navigationContext === 'baskFromWorkzone') {
            // if event comes from workzone, set to relative position (CHIM == chutier image)
            absolutePos = relativePos;
        } else if (navigationContext === 'storyFromResults') {
            absolutePos = 0;
        } else {
            // update real absolute position with pagination for records:
            absolutePos =
                parseInt(options.navigation.perPage, 10) *
                    (parseInt(options.navigation.page, 10) - 1) +
                parseInt(pos, 10);
        }

        let posAsk = null;
        prevAjax = $.ajax({
            type: 'POST',
            url: `${url}prod/records/`,
            dataType: 'json',
            data: {
                env: env,
                pos: absolutePos,
                cont: contId,
                roll: roll,
                options_serial: options_serial,
                query: query
            },
            beforeSend: function () {
                if (prevAjaxrunning) {
                    prevAjax.abort();
                }
                if (env === 'RESULT') {
                    $('#current_result_n')
                        .empty()
                        .append(parseInt(pos, 10) + 1);
                }
                prevAjaxrunning = true;
                $('#PREVIEWIMGDESC, #PREVIEWOTHERS').addClass('loading');
            },
            error: function (data) {
                prevAjaxrunning = false;
                $('#PREVIEWIMGDESC, #PREVIEWOTHERS').removeClass('loading');
            },
            timeout: function () {
                prevAjaxrunning = false;
                $('#PREVIEWIMGDESC, #PREVIEWOTHERS').removeClass('loading');
            },
            success: function (data) {
                _cancelPreview();
                prevAjaxrunning = false;

                if (data.error) {
                    $('#PREVIEWIMGDESC, #PREVIEWOTHERS').removeClass('loading');
                    alert(data.error);
                    if (justOpen) {
                        closePreview();
                    }
                    return;
                }
                posAsk = data.pos - 1;

                // transform default embed ID in order to avoid conflicts:
                let customId = 'phraseanet-embed-preview-frame';
                let $template = $(data.html_preview);
                $template.find('#phraseanet-embed-frame').attr('id', customId);

                $('#PREVIEWIMGCONT').empty().append($template.get(0));
                if ($(`#${customId}`).length > 0) {
                    activeThumbnailFrame = new pym.Parent(
                        customId,
                        data.record.preview.url
                    );
                    activeThumbnailFrame.iframe.setAttribute(
                        'allowfullscreen',
                        ''
                    );
                    //set height of iframe to 100%
                    // activeThumbnailFrame.iframe.setAttribute(
                    //     'height',
                    //     '100%'
                    // );
                    /*
                     // warning - if listening events/sendings events,
                     // pym instances should be destroyed when preview is closed
                     activeThumbnailFrame.onMessage('childReady', (child) => {
                     activeThumbnailFrame.sendMessage('parentReady', 'handshake');

                     });*/
                }

                $('#PREVIEWIMGCONT .thumb_wrapper')
                    .width('100%')
                    .height('100%')
                    .image_enhance({ zoomable: true });
                resizeVideoPreview();

                $('#PREVIEWIMGDESCINNER').empty().append(data.desc);
                $('#HISTORICOPS').empty().append(data.history);
                $('#popularity').empty().append(data.popularity);
                $('#NOTICE-INNER').empty().append(data.votingNotice);

                if ($('#popularity .bitly_link').length > 0) {
                    if (
                        window.BitlyCB !== undefined &&
                        window.BitlyClient !== undefined
                    ) {
                        window.BitlyCB.statsResponse = function (data) {
                            var result = data.results;
                            if (
                                $('#popularity .bitly_link_' + result.userHash)
                                    .length > 0
                            ) {
                                $(
                                    '#popularity .bitly_link_' + result.userHash
                                ).append(' (' + result.clicks + ' clicks)');
                            }
                        };
                        window.BitlyClient.stats(
                            $('#popularity .bitly_link').html(),
                            'BitlyCB.statsResponse'
                        );
                    }
                }

                options.current = {};
                options.current.width = parseInt(
                    $('#PREVIEWIMGCONT .thumb_wrapper')
                        .children()
                        .attr('data-original-width'),
                    10
                );
                options.current.height = parseInt(
                    $('#PREVIEWIMGCONT .thumb_wrapper')
                        .children()
                        .attr('data-original-height'),
                    10
                );
                options.current.tot = data.tot;
                options.current.pos = relativePos;
                options.current.captions = data.recordCaptions;

                recordPreviewEvents.emit('recordSelection.changed', {
                    selection: [data.recordCaptions],
                    selectionPos: [relativePos]
                });

                if ($('#PREVIEWBOX img.record.zoomable').length > 0) {
                    $('#PREVIEWBOX img.record.zoomable').draggable();
                }

                let basketIcon = '';


                if (data.containerType !== null ) {
                    switch (data.containerType) {
                        case 'feedback_rec' :
                            basketIcon = '<i class="fa fa-comment vote_rec" style="margin-right: 5px;"></i>';

                            break;
                        case 'share_rec' :
                            basketIcon = '<i class="fa fa-users share_rec" style="margin-right: 5px;"></i>';

                            break;
                        case 'push_rec' :
                            basketIcon = '<i class="fa fa-gift push_rec" style="margin-right: 5px; padding-left: 6px; padding-top: 3px; padding-bottom: 2px;"></i>';

                            break;
                        case 'feedback_sent' :
                            basketIcon = '<i class="fa fa-bullhorn vote_sent" style="margin-right: 5px;"></i>';

                            break;
                        case 'share_sent' :
                            basketIcon = '<i class="fa fa-share share_sent" style="margin-right: 5px;"></i>';

                            break;
                        case 'feedback_push' :
                            basketIcon = '<i class="fa fa-gift push_rec" style="margin-right: 5px; padding-left: 6px; padding-top: 3px; padding-bottom: 2px;"></i>';
                            basketIcon += '<i class="fa fa-bullhorn vote_sent" style="margin-right: 5px;"></i>';

                            break;
                        case 'share_push' :
                            basketIcon = '<i class="fa fa-gift push_rec" style="margin-right: 5px; padding-left: 6px; padding-top: 3px; padding-bottom: 2px;"></i>';
                            basketIcon += '<i class="fa fa-share share_sent" style="margin-right: 5px;"></i>';

                            break;
                        case 'regroup' :
                            basketIcon = '<i class="icomoon icon-stack story" style="margin-right: 5px;"></i>';

                            break;
                        default:
                            basketIcon = '<i class="icomoon icon-stackoverflow basket" style="margin-right: 5px;"></i>';

                            break;
                    }
                }

                $('#SPANTITLE').empty().append(basketIcon + data.title);
                $('#PREVIEWTITLE_COLLLOGO')
                    .empty()
                    .append(data.collection_logo);
                $('#PREVIEWTITLE_COLLNAME')
                    .empty()
                    .append(`${data.databox_name} / ${data.collection_name}`);

                _setPreview();

                if (env !== 'RESULT') {
                    if (justOpen || reload) {
                        _setCurrent(data.current);
                    }
                    _viewCurrent($('#PREVIEWCURRENT li.selected'));
                } else {
                    if (!justOpen) {
                        $('#PREVIEWCURRENT li.selected').removeClass(
                            'selected'
                        );
                        $(
                            '#PREVIEWCURRENTCONT li.current' + absolutePos
                        ).addClass('selected');
                    }
                    if (
                        justOpen ||
                        $('#PREVIEWCURRENTCONT li.current' + absolutePos)
                            .length === 0 ||
                        $('#PREVIEWCURRENTCONT li:last')[0] ===
                            $('#PREVIEWCURRENTCONT li.selected')[0] ||
                        $('#PREVIEWCURRENTCONT li:first')[0] ===
                            $('#PREVIEWCURRENTCONT li.selected')[0]
                    ) {
                        _getAnswerTrain(pos, data.tools, query, options_serial);
                    }

                    _viewCurrent($('#PREVIEWCURRENT li.selected'));
                }
                if (env === 'REG' && $('#PREVIEWCURRENT').html() === '') {
                    _getRegTrain(contId, pos, data.tools);
                }
                _setOthers(data.others);
                _setTools(data.tools);
                $('#tooltip').css({
                    display: 'none'
                });
                $('#PREVIEWIMGDESC, #PREVIEWOTHERS').removeClass('loading');
                if (!justOpen || options.mode !== env) {
                    resizePreview();
                }

                options.mode = env;
                $('#EDIT_query').focus();

                $('#PREVIEWOTHERSINNER .otherBaskToolTip').tooltip();
                stream.onNext(options);
                return;
            }
        });
    }

    function resizeVideoPreview() {

        var $sel = $('#phraseanet-embed-preview-frame.videoTips');
        // V is for "video" ; K is for "container" ; N is for "new"
        var VW = $sel.data('originalWidth');
        var VH = $sel.data('originalHeight');
        var KW = $sel.width();
        var KH = $sel.height();

        var NW, NH;
        if( (NH = (VH / VW) * (NW=KW) ) > KH )  {   // try to fit exact horizontally, adjust vertically
            // too bad... new height overflows container height
            NW = (VW / VH) * (NH=KH);      // so fit exact vertically, adjust horizontally
        }
        $("iframe", $sel).css('width', NW).css('height', NH);

    }

    function openPreview($element) {
        let reload = $element.data('reload') === true ? true : false;
        // env, pos, contId, reload
        _openPreview(
            $element,
            $element.data('kind'),
            $element.data('position'),
            $element.data('id'),
            reload
        );
    }

    function closePreview() {
        options.open = false;
        if (activeThumbnailFrame !== false) {
            // tell child iframe to shutdown:
            activeThumbnailFrame.sendMessage('dispose', 'ok');

            activeThumbnailFrame = false;
        }
        stream.onNext(options);
        $('#PREVIEWBOX').fadeTo(500, 0);
        $('#PREVIEWBOX').queue(function () {
            $(this).css({
                display: 'none'
            });
            _cancelPreview();
            $(this).dequeue();
        });
    }

    function startSlide() {
        if (!options.slideShow) {
            options.slideShow = true;
        }
        if (options.slideShowCancel) {
            options.slideShowCancel = false;
            options.slideShow = false;
            $('#start_slide').show();
            $('#stop_slide').hide();
        }
        if (!options.open) {
            options.slideShowCancel = false;
            options.slideShow = false;
            $('#start_slide').show();
            $('#stop_slide').hide();
        }
        if (options.slideShow) {
            $('#start_slide').hide();
            $('#stop_slide').show();
            getNext();
            setTimeout(
                () => startSlide(),
                configService.get('previewSlideshow.duration')
            );
        }
    }

    function stopSlide() {
        options.slideShowCancel = true;
        $('#start_slide').show();
        $('#stop_slide').hide();
    }

    function getNext() {
        if (options.mode === 'REG' && parseInt(options.current.pos, 10) === 0) {
            $('#PREVIEWCURRENTCONT li img:first').trigger('click');
        } else {
            if (options.mode === 'RESULT') {
                let posAsk = parseInt(options.current.pos, 10) + 1;

                let absolutePos = parseInt(options.navigation.perPage, 10) *
                (parseInt(options.navigation.page, 10) - 1) + parseInt(posAsk, 10);

                if (absolutePos >= parseInt($('#PREVIEWCURRENTCONT').data('records-count'), 10)) {
                    posAsk = posAsk - parseInt($('#PREVIEWCURRENTCONT').data('records-count'), 10);
                }

                _openPreview(false, 'RESULT', posAsk, '', false);
            } else {
                if (!$('#PREVIEWCURRENT li.selected').is(':last-child')) {
                    $('#PREVIEWCURRENT li.selected')
                        .next()
                        .children('img')
                        .trigger('click');
                } else {
                    $('#PREVIEWCURRENT li:first-child')
                        .children('img')
                        .trigger('click');
                }
            }
        }
    }

    function getPrevious() {
        if (options.mode === 'RESULT') {
            let posAsk = parseInt(options.current.pos, 10) - 1;

            let absolutePos = parseInt(options.navigation.perPage, 10) *
                (parseInt(options.navigation.page, 10) - 1) + parseInt(posAsk, 10);

            if ( absolutePos < 0 ) {
                posAsk = parseInt($('#PREVIEWCURRENTCONT').data('records-count'), 10) + posAsk;
            }

            _openPreview(false, 'RESULT', posAsk, '', false);
        } else {
            if (!$('#PREVIEWCURRENT li.selected').is(':first-child')) {
                $('#PREVIEWCURRENT li.selected')
                    .prev()
                    .children('img')
                    .trigger('click');
            } else {
                $('#PREVIEWCURRENT li:last-child')
                    .children('img')
                    .trigger('click');
            }
        }
    }

    function _setPreview() {
        if (!options.current) {
            return;
        }

        var zoomable = $('img.record.zoomable');
        if (zoomable.length > 0 && zoomable.hasClass('zoomed')) {
            return;
        }

        var h = parseInt(options.current.height, 10);
        var w = parseInt(options.current.width, 10);
        var t = 20;
        var de = 0;

        var margX = 0;
        var margY = 0;

        if ($('#PREVIEWIMGCONT .record_audio').length > 0) {
            margY = 100;
            de = 60;
        }

        var ratioP = w / h;
        var ratioD = parseInt(options.width, 10) / parseInt(options.height, 10);

        if (ratioD > ratioP) {
            //je regle la hauteur d'abord
            if (parseInt(h, 10) + margY > parseInt(options.height, 10)) {
                h = Math.round(parseInt(options.height, 10) - margY);
                w = Math.round(h * ratioP);
            }
        } else {
            if (parseInt(w, 10) + margX > parseInt(options.width, 10)) {
                w = Math.round(parseInt(options.width, 10) - margX);
                h = Math.round(w / ratioP);
            }
        }

        t = Math.round((parseInt(options.height, 10) - h - de) / 2);
        var l = Math.round((parseInt(options.width, 10) - w) / 2);
        $('#PREVIEWIMGCONT .record')
            .css({
                width: w,
                height: h,
                top: t,
                left: l
            })
            .attr('width', w)
            .attr('height', h);
    }

    function _setCurrent(current) {
        if (current !== '') {
            var el = $('#PREVIEWCURRENT');
            el.removeClass('loading').empty().append(current);

            $('ul', el).width($('li', el).length * 80);
            $('img.prevRegToolTip', el).tooltip();
            $.each($('img.openPreview'), function (i, el) {
                var jsopt = $(el).attr('jsargs').split('|');
                $(el).removeAttr('jsargs');
                $(el).removeClass('openPreview');
                $(el).bind('click', function () {
                    _viewCurrent($(this).parent());
                    // convert abssolute to relative position
                    var absolutePos = jsopt[1];
                    var relativePos =
                        parseInt(absolutePos, 10) -
                        parseInt(options.navigation.perPage, 10) *
                            (parseInt(options.navigation.page, 10) - 1);
                    // keep relative position for answer train:
                    _openPreview($(this), jsopt[0], relativePos, jsopt[2], false);
                });
            });
        }
    }

    function _viewCurrent(el) {
        if (el.length === 0) {
            return;
        }
        $('#PREVIEWCURRENT li.selected').removeClass('selected');
        el.addClass('selected');
        $('#PREVIEWCURRENTCONT').animate({
            scrollLeft:
                $('#PREVIEWCURRENT li.selected').position().left +
                $('#PREVIEWCURRENT li.selected').width() / 2 -
                $('#PREVIEWCURRENTCONT').width() / 2
        });
        return;
    }

    function reloadPreview() {
        $('#PREVIEWCURRENT li.selected img').trigger('click');
    }

    function _getAnswerTrain(pos, tools, query, options_serial) {
        // keep relative position for answer train:
        var relativePos = pos;
        // update real absolute position with pagination:
        var absolutePos =
            parseInt(options.navigation.perPage, 10) *
                (parseInt(options.navigation.page, 10) - 1) +
            parseInt(pos, 10);

        $('#PREVIEWCURRENTCONT').fadeOut('fast');
        $.ajax({
            type: 'POST',
            url: `${url}prod/query/answer-train/`,
            dataType: 'json',
            data: {
                pos: absolutePos,
                options_serial: options_serial,
                query: query
            },
            success: function (data) {
                _setCurrent(data.current);
                _viewCurrent($('#PREVIEWCURRENT li.selected'));
                _setTools(tools);
                return;
            }
        });
    }

    function _getRegTrain(contId, pos, tools) {
        $.ajax({
            type: 'POST',
            url: `${url}prod/query/reg-train/`,
            dataType: 'json',
            data: {
                cont: contId,
                pos: pos
            },
            success: function (data) {
                _setCurrent(data.current);
                _viewCurrent($('#PREVIEWCURRENT li.selected'));
                if (typeof tools !== 'undefined') {
                    _setTools(tools);
                }
                return;
            }
        });
    }

    function _cancelPreview() {
        $('#PREVIEWIMGDESCINNER').empty();
        $('#PREVIEWIMGCONT').empty();
        options.current = false;
    }

    function _setOthers(others) {
        $('#PREVIEWOTHERSINNER').empty();
        if (others !== '') {
            $('#PREVIEWOTHERSINNER').append(others);

            $('#PREVIEWOTHERS table.otherRegToolTip').tooltip();
        }
    }

    function _setTools(tools) {
        $('#PREVIEWTOOL').empty().append(tools);
        if (!options.slideShowCancel && options.slideShow) {
            $('#start_slide').hide();
            $('#stop_slide').show();
        } else {
            $('#start_slide').show();
            $('#stop_slide').hide();
        }
    }

    function resizePreview() {
        options.height = $('#PREVIEWIMGCONT').height();
        options.width = $('#PREVIEWIMGCONT').width();

        resizeVideoPreview();
        _setPreview();
    }

    const shouldResize = () => {
        if (options.open) {
            resizePreview();
        }
    };

    const shouldReload = () => {
        if (options.open) {
            reloadPreview();
        }
    };

    const onNavigationChanged = (navigation = {}) => {
        options.navigation = merge(options.navigation, navigation);
    };

    const appendTab = params => {
        let { tabProperties, position } = params;
        const $appendAfterTab = $(
            `ul li:eq(${position - 1})`,
            $previewTabContainer
        );

        const newTab = `<li><a href="#${tabProperties.id}">${tabProperties.title}</a></li>`;
        $appendAfterTab.after(newTab);

        const appendAfterTabContent = $(
            ` > div:eq(${position - 1})`,
            $previewTabContainer
        );
        appendAfterTabContent.after(
            `<div id="${tabProperties.id}" class="${tabProperties.classes}"></div>`
        );

        try {
            $previewTabContainer.tabs('refresh');
        } catch (e) {}
        recordPreviewEvents.emit('appendTab.complete', {
            origParams: params,
            selection: []
        });
    };

    appEvents.listenAll({
        'broadcast.searchResultNavigation': onNavigationChanged,
        'preview.doResize': shouldResize,
        'preview.doReload': shouldReload,
        'preview.close': closePreview
    });

    recordPreviewEvents.listenAll({
        /* eslint-disable quote-props */
        appendTab: appendTab
    });

    return {
        initialize,
        onGlobalKeydown,
        getPreviewStream: () => stream,
        //_openPreview,
        startSlide,
        stopSlide,
        getNext,
        getPrevious,
        reloadPreview,
        resizePreview
    };
};
export default previewRecordService;
