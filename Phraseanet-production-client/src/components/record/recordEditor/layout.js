import $ from 'jquery';
import * as appCommons from './../../../phraseanet-common';

const recordEditorLayout = services => {
    const { configService, localeService, recordEditorEvents } = services;
    let $container = null;
    let parentOptions = {};

    const initialize = options => {
        let initWith = ({ $container, parentOptions } = options);
        $(window).bind('resize', function () {
            recordEditorEvents.emit('recordEditor.uiResize');
            _setSizeLimits();
        });

        _hsplit1();
        _vsplit2();
        _vsplit1();

        $('#EDIT_TOP', parentOptions.$container).resizable({
            handles: 's',
            minHeight: 100,
            resize: function () {
                _hsplit1();
                recordEditorEvents.emit('recordEditor.uiResize');
            },
            stop: function () {
                _hsplit1();
                appCommons.userModule.setPref(
                    'editing_top_box',
                    Math.floor(
                        $('#EDIT_TOP').height() * 100 / $('#EDIT_ALL').height()
                    )
                );
                _setSizeLimits();
            }
        });

        $('#divS_wrapper', parentOptions.$container).resizable({
            handles: 'e',
            minWidth: 200,
            resize: function () {
                _vsplit1();
                recordEditorEvents.emit('recordEditor.uiResize');
            },
            stop: function () {
                appCommons.userModule.setPref(
                    'editing_right_box',
                    Math.floor(
                        $('#divS').width() * 100 / $('#EDIT_MID_L').width()
                    )
                );
                _vsplit1();
                _setSizeLimits();
            }
        });

        $('#EDIT_MID_R')
            .css(
                'left',
                $('#EDIT_MID_L').position().left + $('#EDIT_MID_L').width() + 15
            )
            .resizable({
                handles: 'w',
                minWidth: 200,
                resize: function () {
                    _vsplit2();
                    recordEditorEvents.emit('recordEditor.uiResize');
                },
                stop: function () {
                    appCommons.userModule.setPref(
                        'editing_left_box',
                        Math.floor(
                            $('#EDIT_MID_R').width() *
                                100 /
                                $('#EDIT_MID').width()
                        )
                    );
                    _vsplit2();
                    _setSizeLimits();
                }
            });

        $('#EDIT_ZOOMSLIDER', parentOptions.$container).slider({
            min: 60,
            max: 300,
            value: parentOptions.recordConfig.diapoSize,
            slide: function (event, ui) {
                var v = $(ui.value)[0];
                $('#EDIT_FILM2 .diapo', parentOptions.$container)
                    .width(v)
                    .height(v);
            },
            change: function (event, ui) {
                parentOptions.recordConfig.diapoSize = $(ui.value)[0];
                appCommons.userModule.setPref(
                    'editing_images_size',
                    parentOptions.recordConfig.diapoSize
                );
            }
        });

        _setSizeLimits();
    };

    function _setSizeLimits() {
        if (!$('#EDITWINDOW').is(':visible')) {
            return;
        }

        if ($('#EDIT_TOP').data('ui-resizable')) {
            $('#EDIT_TOP').resizable(
                'option',
                'maxHeight',
                $('#EDIT_ALL').height() -
                    $('#buttonEditing').height() -
                    10 -
                    160
            );
        }
        if ($('#divS_wrapper').data('ui-resizable')) {
            $('#divS_wrapper').resizable(
                'option',
                'maxWidth',
                $('#EDIT_MID_L').width() - 270
            );
        }
        if ($('#EDIT_MID_R').data('ui-resizable')) {
            $('#EDIT_MID_R').resizable(
                'option',
                'maxWidth',
                $('#EDIT_MID_R').width() + $('#idEditZone').width() - 240
            );
        }
    }

    function _hsplit1() {
        let el = $('#EDIT_TOP');
        if (el.length === 0) {
            return;
        }
        let h = $(el).outerHeight();
        $(el).height(h);
        let t = $(el).offset().top + h;

        $('#EDIT_MID', parentOptions.$container).css('top', t + 'px');
    }

    function _vsplit1() {
        $('#divS_wrapper').height('auto');

        let el = $('#divS_wrapper');
        if (el.length === 0) {
            return;
        }
        let a = $(el).width();
        el.width(a);

        $('#idEditZone', parentOptions.$container).css('left', a + 20);
    }

    function _vsplit2() {
        let el = $('#EDIT_MID_R');
        if (el.length === 0) {
            return;
        }
        let a = $(el).width();
        el.width(a);
        let v = $('#EDIT_ALL').width() - a - 35;

        $('#EDIT_MID_L', parentOptions.$container).width(v);
    }

    return { initialize };
};
export default recordEditorLayout;
