import $ from 'jquery';
require('jquery-ui');
const humane = require('humane-js');
import utils from './../../phraseanet-common/components/utils';
import download from './download';
import pym from 'pym.js';


const lightbox = services => {
    const { configService, localeService, appEvents } = services;
    const downloadService = download(services);
    var _releasable = false;
    var _bodySize = {
        x: 0,
        y: 0
    };
    let $mainContainer = null;
    let activeThumbnailFrame = false;

    const initialize = () => {

        $mainContainer = $('#mainContainer');
        _bodySize.y = $mainContainer.height();
        _bodySize.x = $mainContainer.width();

        $(this).data('slideshow', false);
        $(this).data('slideshow_ctime', false);

        $(window).bind('beforeunload', function () {
            if (_releasable !== false) {
                if (confirm(_releasable)) {
                    $('#basket_options .confirm_report').trigger('click');
                }
            }
        });

        _display_basket();

        //load iframe if type is document
        let $embedFrame = $('.lightbox_container', $('#record_main')).find(
            '#phraseanet-embed-frame'
        );
        let customId = 'phraseanet-embed-lightbox-frame';
        $embedFrame.attr('id', customId);
        let src = $embedFrame.attr('data-src');
        if ($embedFrame.hasClass('documentTips')) {
            activeThumbnailFrame = new pym.Parent(customId, src);
            activeThumbnailFrame.iframe.setAttribute('allowfullscreen', '');
        }

        $(window)
            .bind('mousedown', function () {
                $(this).focus();
            })
            .trigger('mousedown');

        $('.basket_wrapper')
            .hover(
                function () {
                    $(this).addClass('hover');
                },
                function () {
                    $(this).removeClass('hover');
                }
            )
            .bind('click', function () {
                var id = $('input[name=ssel_id]', this).val();
                document.location = '/lightbox/validate/' + id + '/';
                return;
            });

        downloadService.initialize({
            $container: $mainContainer
        });

        if ($('.right_column_wrapper_user').length > 0) {
            $('.right_column_title, #right_column_validation_toggle')
                .bind('click', function () {
                    if (!$('.right_column_wrapper_caption').is(':visible')) {
                        $('.right_column_wrapper_user')
                            .height($('.right_column_wrapper_user').height())
                            .css('top', 'auto')
                            .animate({
                                height: 0
                            });
                        $('.right_column_wrapper_caption').slideDown();
                        $('#right_column_validation_toggle').show();
                    } else {
                        $('.right_column_wrapper_user').height('auto').animate({
                            top: $('.right_column_title').height()
                        });
                        $('.right_column_wrapper_caption').slideUp();
                        $('#right_column_validation_toggle').hide();
                    }
                    var title = $('.right_column_title');
                    title.hasClass('expanded')
                        ? title.removeClass('expanded')
                        : title.addClass('expanded');
                })
                .addClass('clickable');
        }
        var sselcont = $('#sc_container .basket_element:first');
        if (sselcont.length > 0) {
            _display_basket_element(
                false,
                sselcont.attr('id').split('_').pop()
            );
        }

        _setSizeable(
            $(
                '#record_main .lightbox_container, #record_compare .lightbox_container'
            )
        );

        $('#navigation').bind('change', function () {
            window.location.replace(
                window.location.protocol +
                    '//' +
                    window.location.host +
                    '/lightbox/validate/' +
                    $(this).val() +
                    '/'
            );
        });

        $('#left_scroller').bind('click', function () {
            _scrollElements(false);
        });

        $('#right_scroller').bind('click', function () {
            _scrollElements(true);
        });

        $(window).bind('resize', function () {
            _resizeLightbox();
        });
        _bind_keyboard();


    };

    function _resizeLightbox() {
        _bodySize.y = $mainContainer.height();
        _bodySize.x = $mainContainer.width();
        _displayRecord($('#record_compare').css('visibility') !== 'hidden');
    }

    function _display_basket() {
        var sc_wrapper = $('#sc_wrapper');
        var basket_options = $('#basket_options');

        $('.report')
            .on('click', function () {
                _loadReport();
                return false;
            })
            .addClass('clickable');

        $('.confirm_report', basket_options).button().bind('click', function () {
            _getReseaseStatus($(this));
        });

        $('#validate-release').click(function () {
            $("#FeedbackRelease").modal("hide");
            _setRelease($(this));
            console.log('validation is done');
        })

        $('.basket_element', sc_wrapper)
            .parent()
            .bind('click', function (event) {
                _scid_click(event, this);
                _adjust_visibility(this);
                return false;
            });

        $('.agree_button, .disagree_button', sc_wrapper)
            .bind('click', function (event) {
                var sselcont_id = $(this)
                    .closest('.basket_element')
                    .attr('id')
                    .split('_')
                    .pop();

                var agreement = $(this).hasClass('agree_button') ? 1 : -1;

                _setAgreement(event, $(this), sselcont_id, agreement);
                return false;
            })
            .addClass('clickable');

        let n = $('.basket_element', sc_wrapper).length;
        $('#sc_container').width(
            n * $('.basket_element_wrapper:first', sc_wrapper).outerWidth() + 1
        );

        $('.previewTips').tooltip();
    }

    function setReleasable(val) {
        _releasable = val;
    }

    function _bind_keyboard() {
        $(document).bind('keydown', function (event) {
            var stop = false;
            $('.notes_wrapper').each(function (i, n) {
                if (parseInt($(n).css('top'), 10) >= 0) {
                    stop = true;
                }
            });

            if (stop) {
                return true;
            }

            var cancelKey = false;
            var el;
            var id;

            if($('body').hasClass('dialog-open') ==false) {
                switch (event.keyCode) {
                    case 39:
                        _getNext();
                        cancelKey = true;
                        break;
                    case 37:
                        _getPrev();
                        cancelKey = true;
                        break;
                    case 32:
                        var bool = !$(document).data('slideshow');
                        _slideshow(bool);
                        break;
                    case 38:
                        // participants can vote
                        if ($('#basket_infos .user_infos .choices').length === 1) {
                            el = $('#sc_container .basket_element.selected');
                            if (el.length === 1) {
                                id = el.attr('id').split('_').pop();
                                _setAgreement(event, el, id, 1);
                            }
                        }

                        break;
                    case 40:
                        // participants can vote
                        if ($('#basket_infos .user_infos .choices').length === 1) {
                            el = $('#sc_container .basket_element.selected');
                            if (el.length === 1) {
                                id = el.attr('id').split('_').pop();
                                _setAgreement(event, el, id, -1);
                            }
                        }

                        break;
                    default:
                        break;
                }
            }

            if (cancelKey) {
                event.cancelBubble = true;
                if (event.stopPropagation) {
                    event.stopPropagation();
                }
                return false;
            }
            return true;
        });
    }

    function _loadReport() {
        $.ajax({
            type: 'GET',
            url: '/lightbox/ajax/LOAD_REPORT/' + $('#navigation').val() + '/',
            dataType: 'html',
            success: function (data) {
                $('#report').empty().append(data);
                $('#report .reportTips').tooltip({
                    delay: false
                });
                $('#report').dialog({
                    width: 600,
                    modal: true,
                    resizable: false,
                    height: Math.round($(window).height() * 0.8)
                });

                return;
            }
        });
    }

    function _scid_click(event, el) {
        var compare = utils.is_ctrl_key(event);

        if (compare) {
            if ($('.basket_element', el).hasClass('selected')) {
                return;
            }
        } else {
            $('#sc_container .basket_element.selected').removeClass('selected');
            $('.basket_element', el).addClass('selected');
        }

        var sselcont_id = $('.basket_element', el).attr('id').split('_').pop();
        var ssel_id = $('#navigation').val();
        var url = $(el).attr('href');
        var container = $('#sc_container');

        var request = container.data('request');
        if (request && typeof request.abort === 'function') {
            request.abort();
        }

        request = _loadBasketElement(url, compare, sselcont_id);
        container.data('request', request);
    }

    function _loadBasketElement(url, compare, sselcont_id) {
        $.ajax({
            type: 'GET',
            url: url, //'/lightbox/ajax/LOAD_BASKET_ELEMENT/'+sselcont_id+'/',
            dataType: 'json',
            success: function (datas) {
                var container = false;
                var data = datas;

                if (compare) {
                    container = $('#record_compare');
                } else {
                    container = $('#record_main');

                    $('#record_infos .lightbox_container')
                        .empty()
                        .append(data.caption);

                    $('#basket_infos').empty().append(data.agreement_html);
                }

                $('.display_id', container).empty().append(data.number);

                $('.title', container)
                    .empty()
                    .append(data.title)
                    .attr('title', data.title);

                var options_container = $('.options', container);
                options_container.empty().append(data.options_html);

                let customId = 'phraseanet-embed-lightbox-frame';
                let $template = $(data.preview);
                $template.attr('id', customId);
                let src = $template.attr('data-src');

                $('.lightbox_container', container)
                    .empty()
                    .append($template.get(0))
                    .append(data.selector_html)
                    .append(data.note_html);

                if ($('.lightbox_container', container).hasClass('note_editing')) {
                    $('.lightbox_container', container).removeClass('note_editing');
                }

                if ($template.hasClass('documentTips')) {
                    activeThumbnailFrame = new pym.Parent(customId, src);
                    activeThumbnailFrame.iframe.setAttribute(
                        'allowfullscreen',
                        ''
                    );
                }

                // $('.lightbox_container', container).empty()
                //     .append(data.preview + data.selector_html + data.note_html);

                _display_basket_element(compare, sselcont_id);
                $('.report')
                    .on('click', function () {
                        _loadReport();
                        return false;
                    })
                    .addClass('clickable');
                return;
            }
        });
    }

    function _display_basket_element(compare, sselcont_id) {
        var container;
        if (compare) {
            container = $('#record_compare');
        } else {
            container = $('#record_main');
        }
        $('.record_image', container).removeAttr('ondragstart');
        $('.record_image', container).draggable();

        var options_container = $('.options', container);

        $('.download_button', options_container).bind('click', function () {
            //		$(this).blur();
            downloadService.openModal(
                $(this).next('form[name=download_form]').find('input').val()
            );
            // _download($(this).next('form[name=download_form]').find('input').val());
        });

        $('.comment_button').bind('click', function () {
            //				$(this).blur();
            if ($('.lightbox_container', container).hasClass('note_editing')) {
                _hideNotes(container);
            } else {
                _showNotes(container);
            }
        });
        _activateNotes(container);

        $('.previous_button', options_container).bind('click', function () {
            //		$(this).blur();
            _getPrev();
        });

        $('.play_button', options_container).bind('click', function () {
            //		$(this).blur();
            _slideshow(true);
        });

        $('.pause_button', options_container).bind('click', function () {
            //		$(this).blur();
            _slideshow(false);
        });

        if ($(document).data('slideshow')) {
            $(
                '.play_button, .next_button.play, .previous_button.play',
                options_container
            ).hide();
            $(
                '.pause_button, .next_button.pause, .previous_button.pause',
                options_container
            ).show();
        } else {
            $(
                '.play_button, .next_button.play, .previous_button.play',
                options_container
            ).show();
            $(
                '.pause_button, .next_button.pause, .previous_button.pause',
                options_container
            ).hide();
        }

        $('.next_button', options_container).bind('click', function () {
            //		$(this).blur();
            _slideshow(false);
            _getNext();
        });

        $('.lightbox_container', container).bind('dblclick', function (event) {
            _displayRecord();
        });

        $('#record_wrapper .agree_' + sselcont_id + ', .big_box.agree')
            .bind('click', function (event) {
                _setAgreement(event, $(this), sselcont_id, 1);
            })
            .addClass('clickable');

        $('#record_wrapper .disagree_' + sselcont_id + ', .big_box.disagree')
            .bind('click', function (event) {
                _setAgreement(event, $(this), sselcont_id, -1);
            })
            .addClass('clickable');

        if (compare === $('#record_wrapper').hasClass('single')) {
            if (compare) {
                //      $('.agreement_selector').show();
                //			$('#record_wrapper').stop().animate({right:0},100,function(){display_record(compare);});
                $('#record_wrapper').css({
                    right: 0
                });
                _displayRecord(compare);
                $('#right_column').hide();
            } else {
                //      $('.agreement_selector').hide();
                $('#record_wrapper').css({
                    right: 250
                });
                _displayRecord(compare);
                $('#right_column').show();
                $('#record_compare .lightbox_container').empty();
            }
        } else {
            _displayRecord(compare);
        }
    }

    function _getPrev() {
        var current_wrapper = $('#sc_container .basket_element.selected')
            .parent()
            .parent();

        if (current_wrapper.length === 0) {
            return;
        }

        _slideshow(false);

        current_wrapper = current_wrapper.prev();
        if (current_wrapper.length === 0) {
            current_wrapper = $('#sc_container .basket_element_wrapper:last');
        }

        $('.basket_element', current_wrapper).parent().trigger('click');

        _adjust_visibility($('.basket_element', current_wrapper).parent());
    }

    function _getNext() {
        var current_wrapper = $('#sc_container .basket_element.selected')
            .parent()
            .parent();

        if (current_wrapper.length === 0) {
            return;
        }

        current_wrapper = current_wrapper.next();
        if (current_wrapper.length === 0) {
            current_wrapper = $('#sc_container .basket_element_wrapper:first');
        }

        $('.basket_element', current_wrapper).parent().trigger('click');

        _adjust_visibility($('.basket_element', current_wrapper).parent());

        if ($(document).data('slideshow')) {
            var timer = setTimeout(() => _getNext(), 3500);
            $(document).data('slideshow_ctime', timer);
        }
    }

    function _slideshow(boolean_value) {
        if (boolean_value === $(document).data('slideshow')) {
            return;
        }

        if (!boolean_value && $(document).data('slideshow_ctime')) {
            clearTimeout($(document).data('slideshow_ctime'));
            $(document).data('slideshow_ctime', false);
        }

        $(document).data('slideshow', boolean_value);

        var headers = $('#record_wrapper .header');

        if (boolean_value) {
            $(
                '.play_button, .next_button.play, .previous_button.play',
                headers
            ).hide();
            $(
                '.pause_button, .next_button.pause, .previous_button.pause',
                headers
            ).show();
            _getNext();
        } else {
            $(
                '.pause_button, .next_button.pause, .previous_button.pause',
                headers
            ).hide();
            $(
                '.play_button, .next_button.play, .previous_button.play',
                headers
            ).show();
        }
    }

    function _adjust_visibility(el) {
        if (_isViewable(el)) {
            return;
        }

        var sc_wrapper = $('#sc_wrapper');
        var el_parent = $(el).parent();

        var sc_left =
            el_parent.position().left +
            el_parent.outerWidth() -
            sc_wrapper.width() / 2;

        sc_wrapper.stop().animate({
            scrollLeft: sc_left
        });
    }

    function _setAgreement(event, el, sselcont_id, agreeValue) {
        if (event.stopPropagation) {
            event.stopPropagation();
        }
        event.cancelBubble = true;

        var id = $.ajax({
            type: 'POST',
            url: '/lightbox/ajax/SET_ELEMENT_AGREEMENT/' + sselcont_id + '/',
            dataType: 'json',
            data: {
                agreement: agreeValue
            },
            success: function (datas) {
                if (!datas.error) {
                    if (agreeValue === 1) {
                        $('.agree_' + sselcont_id + '').removeClass(
                            'not_decided'
                        );
                        $('.disagree_' + sselcont_id + '').addClass(
                            'not_decided'
                        );
                        $('.userchoice.me')
                            .addClass('agree')
                            .removeClass('disagree');
                    } else {
                        $('.agree_' + sselcont_id + '').addClass('not_decided');
                        $('.disagree_' + sselcont_id + '').removeClass(
                            'not_decided'
                        );
                        $('.userchoice.me')
                            .addClass('disagree')
                            .removeClass('agree');
                    }
                    _releasable = datas.releasable;
                    if (datas.releasable !== false) {
                        if (confirm(datas.releasable)) {
                            $('#basket_options .confirm_report').trigger(
                                'click'
                            );
                        }
                    }
                } else {
                    alert(datas.datas);
                }
                return;
            }
        });
    }

    function _displayRecord(compare) {
        var main_container = $('#record_wrapper');

        if (typeof compare === 'undefined') {
            compare = !main_container.hasClass('single');
        }

        var main_box = $('#record_main');
        var compare_box = $('#record_compare');

        var main_record = $('.lightbox_container .record', main_box);
        var compare_record = $('.lightbox_container .record', compare_box);

        var main_record_width = parseInt(
            main_record.attr('data-original-width'),
            10
        );
        var main_record_height = parseInt(
            main_record.attr('data-original-height'),
            10
        );
        var compare_record_width = parseInt(
            compare_record.attr('data-original-width'),
            10
        );
        var compare_record_height = parseInt(
            compare_record.attr('data-original-height'),
            10
        );

        var main_container_width = main_container.width();
        var main_container_innerwidth = main_container.innerWidth();
        var main_container_height = main_container.height();
        var main_container_innerheight = main_container.innerHeight();
        var smooth_image = false;
        if (compare) {
            $('.agreement_selector').show();
            main_container.addClass('comparison');

            var double_portrait_width = main_container_innerwidth / 2;
            var double_portrait_height =
                main_container_innerheight -
                $('.header', main_box).outerHeight();

            var double_paysage_width = main_container_innerwidth;
            var double_paysage_height =
                main_container_innerheight / 2 -
                $('.header', main_box).outerHeight();

            var main_display_portrait = _calculateDisplay(
                double_portrait_width,
                double_portrait_height,
                main_record_width,
                main_record_height
            );
            var main_display_paysage = _calculateDisplay(
                double_paysage_width,
                double_paysage_height,
                main_record_width,
                main_record_height
            );

            var compare_display_portrait = _calculateDisplay(
                double_portrait_width,
                double_portrait_height,
                compare_record_width,
                compare_record_height
            );
            var compare_display_paysage = _calculateDisplay(
                double_paysage_width,
                double_paysage_height,
                compare_record_width,
                compare_record_height
            );

            var surface_main_portrait =
                main_display_portrait.width * main_display_portrait.height;
            var surface_main_paysage =
                main_display_paysage.width * main_display_paysage.height;
            var surface_compare_portrait =
                compare_display_portrait.width *
                compare_display_portrait.height;
            var surface_compare_paysage =
                compare_display_paysage.width * compare_display_paysage.height;

            var double_portrait_surface =
                (surface_main_portrait + surface_compare_portrait) / 2;
            var double_paysage_surface =
                (surface_main_paysage + surface_compare_paysage) / 2;

            var m_width_image;
            var m_height_image;
            var c_width_image;
            var c_height_image;
            var dim_container;

            if (double_portrait_surface > double_paysage_surface) {
                if (!main_container.hasClass('portrait')) {
                    smooth_image = true;

                    _smoothTransform(main_box, '50%', '100%', function () {
                        _setContainerStatus('portrait');
                    });

                    compare_box.css('visibility', 'hidden');

                    _smoothTransform(compare_box, '50%', '100%', function () {
                        compare_box
                            .css('display', 'none')
                            .css('visibility', 'visible')
                            .fadeIn();
                    });
                }
                m_width_image = main_display_portrait.width;
                m_height_image = main_display_portrait.height;
                c_width_image = compare_display_portrait.width;
                c_height_image = compare_display_portrait.height;
                dim_container = {
                    width: double_portrait_width,
                    height: double_portrait_height
                };
            } else {
                if (!main_container.hasClass('paysage')) {
                    smooth_image = true;

                    _smoothTransform(main_box, '100%', '50%', function () {
                        _setContainerStatus('paysage');
                    });

                    compare_box.css('visibility', 'hidden');

                    _smoothTransform(compare_box, '100%', '50%', function () {
                        compare_box
                            .css('display', 'none')
                            .css('visibility', 'visible')
                            .fadeIn();
                    });
                }
                m_width_image = main_display_paysage.width;
                m_height_image = main_display_paysage.height;
                c_width_image = compare_display_paysage.width;
                c_height_image = compare_display_paysage.height;
                dim_container = {
                    width: double_paysage_width,
                    height: double_paysage_height
                };
            }

            var image_callback = _setImagePosition(
                false,
                compare_record,
                c_width_image,
                c_height_image,
                dim_container,
                function () {}
            );
            _setImagePosition(
                smooth_image,
                main_record,
                m_width_image,
                m_height_image,
                dim_container,
                image_callback
            );
        } else {
            $('.agreement_selector').hide();
            main_container.removeClass('comparison');

            if (compare_box.is(':visible')) {
                compare_box
                    .hide()
                    .css('visibility', 'hidden')
                    .css('display', 'block');
            }

            var main_display = _calculateDisplay(
                main_container_innerwidth,
                main_container_innerheight -
                    $('.header', main_box).outerHeight(),
                main_record_width,
                main_record_height
            );

            if (!main_container.hasClass('single')) {
                main_box.width('100%').height('100%');

                _setContainerStatus('single');
            }
            _setImagePosition(
                smooth_image,
                main_record,
                main_display.width,
                main_display.height,
                {
                    width: main_container_width,
                    height:
                        main_container_height -
                        $('.header', main_box).outerHeight()
                }
            );
        }
    }

    function _calculateDisplay(
        display_width,
        display_height,
        width,
        height,
        margin
    ) {
        if (typeof margin === 'undefined') {
            margin = 10;
        }

        var display_ratio = display_width / display_height;
        var ratio = width / height;
        var w;
        var h;
        // landscape
        if (ratio > display_ratio) {
            w = display_width - 2 * margin;
            if (w > width) {
                w = width;
            }
            h = w / ratio;
        } else {
            h = display_height - 2 * margin;
            if (h > height) {
                h = height;
            }
            w = ratio * h;
        }

        return {
            width: w,
            height: h
        };
    }

    function _setSizeable(container) {
        $(container).bind('mousewheel', function (event, delta) {
            if ($(this).hasClass('note_editing')) {
                return;
            }

            var record = $('.record_image', this);

            if (record.length === 0) {
                return;
            }

            var o_top = parseInt(record.css('top'), 10);
            var o_left = parseInt(record.css('left'), 10);

            var o_width;
            var o_height;
            var width;
            var height;

            if (delta > 0) {
                if (record.width() > 29788 || record.height() >= 29788) {
                    return;
                }
                o_width = record.width();
                o_height = record.height();
                width = Math.round(o_width * 1.1);
                height = Math.round(o_height * 1.1);
            } else {
                if (record.width() < 30 || record.height() < 30) {
                    return;
                }
                o_width = record.width();
                o_height = record.height();
                width = Math.round(o_width / 1.05);
                height = Math.round(o_height / 1.05);
            }

            var top = Math.round(
                height / o_height * (o_top - $(this).height() / 2) +
                    $(this).height() / 2
            );
            var left = Math.round(
                width / o_width * (o_left - $(this).width() / 2) +
                    $(this).width() / 2
            );

            record.width(width).height(height).css({
                top: top,
                left: left
            });
        });
    }

    function _setImagePosition(
        smooth,
        image,
        width,
        height,
        container,
        callback
    ) {
        var dimensions = {};

        if (typeof container !== 'undefined') {
            var c_width = container.width;
            var c_height = container.height;

            dimensions.top = parseInt((c_height - height) / 2, 10);
            dimensions.left = parseInt((c_width - width) / 2, 10);
        }
        if (typeof callback === 'undefined') {
            callback = function () {};
        }

        dimensions.width = width;
        dimensions.height = height;

        if (smooth) {
            $(image).stop().animate(dimensions, 500, callback);
        } else {
            $(image).css(dimensions);
            callback();
        }
    }

    function _scrollElements(boolean_value) {
        var sc_wrapper = $('#sc_wrapper');
        var value;
        if (boolean_value) {
            value = sc_wrapper.scrollLeft() + 400;
        } else {
            value = sc_wrapper.scrollLeft() - 400;
        }

        sc_wrapper.stop().animate({
            scrollLeft: value
        });
        return;
    }

    function _smoothTransform(box, width, height, callback) {
        if (typeof callback === 'undefined') {
            callback = function () {};
        }

        $(box).stop().animate(
            {
                width: width,
                height: height
            },
            500,
            callback
        );
    }

    function _setContainerStatus(status) {
        $('#record_wrapper')
            .removeClass('paysage portrait single')
            .addClass(status);
    }

    function _isViewable(el) {
        var sc_wrapper = $('#sc_wrapper');
        var sc_container = $('#sc_container');

        var el_width = $(el).parent().width();
        var el_position = $(el).parent().offset();
        var sc_scroll_left = sc_wrapper.scrollLeft();

        var boundRight = sc_wrapper.width();
        var boundLeft = 0;
        var placeRight = el_position.left + el_width + sc_scroll_left;
        var placeLeft = el_position.left - sc_scroll_left;

        if (placeRight <= boundRight && placeLeft >= boundLeft) {
            return true;
        }
        return false;
    }

    function _saveNote(container, button) {
        var sselcont_id = $(button).attr('id').split('_').pop();
        var note = $('.notes_wrapper textarea', container).val();

        $.ajax({
            type: 'POST',
            url: '/lightbox/ajax/SET_NOTE/' + sselcont_id + '/',
            dataType: 'json',
            data: {
                note: note
            },
            success: function (datas) {
                _hideNotes(container);
                $('.notes_wrapper', container).remove();
                $('.lightbox_container', container).append(datas.datas);
                _activateNotes(container);
                return;
            }
        });
    }

    function _activateNotes(container) {
        $('.note_closer', container)
            .button({
                text: true
            })
            .bind('click', function () {
                $(this).blur();
                _hideNotes(container);
                return false;
            });

        $('.note_saver', container)
            .button({
                text: true
            })
            .bind('click', function () {
                $(this).blur();
                _saveNote(container, this);
                return false;
            });
    }

    function _showNotes(container) {
        $('.notes_wrapper', container).animate({
            top: 0
        });
        $('.lightbox_container', container).addClass('note_editing');
    }

    function _hideNotes(container) {
        $('.notes_wrapper', container).animate({
            top: '-100%'
        });
        $('.lightbox_container', container).removeClass('note_editing');
    }

    /*Get status before send validation*/
    function _getReseaseStatus(el) {
        $.ajax({
            url: '/lightbox/ajax/GET_ELEMENTS/' + $('#navigation').val() + '/',
            dataType: 'json',
            error: function (data) {
                $('.loader', el).css({
                    visibility: 'hidden'
                });
            },
            timeout: function (data) {
                $('.loader', el).css({
                    visibility: 'hidden'
                });
            },
            success: function (data) {
                $('.loader', el).css({
                    visibility: 'hidden'
                });
                if (data.datas) {
                    if (data.datas) {
                        if (data.datas.counts.nul == 0) {
                            _setRelease($(this));
                        }
                        else {
                            console.log(data.datas.counts);
                            $("#FeedbackRelease .record_accepted").html(data.datas.counts.yes);
                            $("#FeedbackRelease .record_refused").html(data.datas.counts.no);
                            $("#FeedbackRelease .record_null").html(data.datas.counts.nul);
                            $("#FeedbackRelease").modal("show");
                        }
                    }          }
                if (!data.error) {
                    _releasable = false;
                }

                return;
            }
        });
    }

    function _setRelease(el) {
        $('.loader', el).css({
            visibility: 'visible'
        });
        $.ajax({
            type: 'POST',
            url: '/lightbox/ajax/SET_RELEASE/' + $('#navigation').val() + '/',
            dataType: 'json',
            error: function (data) {
                $('.loader', el).css({
                    visibility: 'hidden'
                });
            },
            timeout: function (data) {
                $('.loader', el).css({
                    visibility: 'hidden'
                });
            },
            success: function (data) {
                $('.loader', el).css({
                    visibility: 'hidden'
                });
                if (data.datas) {
                    alert(data.datas);
                }
                if (!data.error) {
                    _releasable = false;
                }

                return;
            }
        });
    }

    return {
        initialize,
        setReleasable
    };
};

export default lightbox;
