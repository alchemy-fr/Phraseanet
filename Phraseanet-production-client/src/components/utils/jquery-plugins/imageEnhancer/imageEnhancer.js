require('./imageEnhancer.scss');

import $ from 'jquery';
(function ($) {

    const methods = {
        init: function (options) {
            let settings = {
                zoomable: false,
                display_full_screen: false
            };
            return this.each(function () {

                let $this = $(this);
                let data = $(this).data('image_enhance');

                if (!data) {
                    if (options) {
                        $.extend(settings, options);
                    }

                    let wrapper = $('.thumb_wrapper', $(this));
                    let $image = $('img', $this);
                    let image_width = parseInt($('input[name="width"]', $this).val(), 10);
                    let image_height = parseInt($('input[name="height"]', $this).val(), 10);
                    let ratio = image_width / image_height;

                    wrapper.css('position', 'relative');

                    reset_position($this);

                    if (settings.display_full_screen) {
                        $image.parent()
                            .append('<div class="image_enhance_titlebar" style="display:none;">\n\
                <div class="image_enhance_title_options"><span class="full"><img src="/assets/common/images/icons/fullscreen.gif" /></span></div>\n\
                <div class="image_enhance_title_bg"></div></div>');

                        let $titlebar = $('.image_enhance_titlebar', $this);

                        $('.image_enhance_title_bg', $titlebar).css('opacity', 0.5);

                        $image.parent()
                            .bind('mouseover.image_enhance', function () {
                                $titlebar.stop().show().animate({
                                    height: 28
                                }, 150);
                            })
                            .bind('mouseout.image_enhance', function () {
                                $titlebar.stop().animate({
                                    height: 0
                                }, 150, function () {
                                    $titlebar.hide();
                                });
                            });

                        $('.image_enhance_titlebar .full', wrapper).bind('click.image_enhance', function () {
                            $('body').append('<div class="image_enhance_theatre">\n\
                \n\
                <div class="image_enhance_theatre_closer_wrapper"><span class="closer">close</span></div>\n\
                <img style="width:' + image_width + 'px;height:' + image_height + '" src="' + $image.attr('src') + '"/>\n\
                </div>');

                            let $theatre = $('.image_enhance_theatre');
                            let $theatre_img = $('img', $theatre);
                            $(window).bind('resize.image_enhance dblclick.image_enhance', function (event) {

                                if (event.type === 'dblclick') {
                                    $theatre_img.removeClass('zoomed');
                                } else {
                                    if ($theatre_img.hasClass('zoomed')) {
                                        return;
                                    }
                                }
                                let datas = calculate_sizes($(this).width(), $(this).height(), image_width, image_height, 80);

                                $theatre_img.width(datas.width).height(datas.height).css('top', datas.top).css('left', datas.left);
                            });
                            $(window).trigger('resize.image_enhance');
                            $('.closer', $theatre).bind('click.image_enhance', function () {
                                $theatre.remove();
                            });

                            if (typeof $theatre.disableSelection !== 'function' && window.console) {
                                console.error('enhanced image require jquery UI\'s disableSelection');
                            }
                            $('img', $theatre).disableSelection();
                        });
                    }


                    if (settings.zoomable) {
                        if (typeof $image.draggable !== 'function' && window.console) {
                            console.error('zoomable require jquery UI\'s draggable');
                        }

                        if ($image.attr('ondragstart')) {
                            $image.removeAttr('ondragstart');
                        }
                        $image.draggable();
                        $image.css({
                            'max-width': 'none',
                            'max-height': 'none'
                        });

                        $this.bind('mousewheel', function (event, delta) {
                            $image.addClass('zoomed');
                            if (delta > 0) {
                                event.stopPropagation();
                                zoomPreview(true, ratio, $image, $(this));
                            } else {
                                event.stopPropagation();
                                zoomPreview(false, ratio, $image, $(this));
                            }
                            return false;
                        }).bind('dblclick', function (event) {
                            reset_position($this);
                        });
                    }

                    $(this).data('image_enhance', {
                        width: image_width,
                        height: image_height
                    });
                }

            });
        },
        destroy: function () {
            return this.each(function () {
                $(this).data('image_enhance', null);
                $('.image_enhance_titlebar, .image_enhance_theatre', this).remove();
            });
        }
    };

    function zoomPreview(bool, ratio, $img, $container) {
        if ($img.length === 0) {
            return;
        }

        let t1 = parseInt($img.css('top'), 10);
        let l1 = parseInt($img.css('left'), 10);
        let w1 = $img.width();
        let h1 = $img.height();

        let w2;

        if (bool) {
            if ((w1 * 1.08) < 32767) {
                w2 = w1 * 1.08;
            } else {
                w2 = w1;
            }
        } else {
            if ((w1 / 1.08) > 20) {
                w2 = w1 / 1.08;
            } else {
                w2 = w1;
            }
        }

        let h2 = Math.round(w2 / ratio);
        w2 = Math.round(w2);

        let wPreview = $container.width() / 2;
        let hPreview = $container.height() / 2;

        let nt = Math.round((h2 / h1) * (t1 - hPreview) + hPreview);
        let nl = Math.round(((w2 / w1) * (l1 - wPreview)) + wPreview);

        $img.css({
            left: nl,
            top: nt
        }).width(w2).height(h2);
    }

    function calculate_sizes(window_width, window_height, image_width, image_height, border) {
        if (typeof border !== 'number') {
            border = 0;
        }

        let width;
        let height;
        let ratio_display = window_width / window_height;
        let ratio_image = image_width / image_height;

        if (ratio_image > ratio_display) {
            width = window_width - border;
            height = Math.round(width / ratio_image);
        } else {
            height = window_height - border;
            width = Math.round(height * ratio_image);
        }

        let top = Math.round((window_height - height) / 2);
        let left = Math.round((window_width - width) / 2);

        return {
            top: top,
            left: left,
            width: width,
            height: height
        };
    }

    function reset_position($this) {
        let display_width = $this.width();
        let display_height = $this.height();
        let image_width = parseInt($('input[name="width"]', $this).val(), 10);
        let image_height = parseInt($('input[name="height"]', $this).val(), 10);

        let datas = calculate_sizes(display_width, display_height, image_width, image_height);
        let $image = $('img', $this);

        let top = Math.round((display_height - datas.height) / 2) + 'px';
        let left = Math.round((display_width - datas.width) / 2) + 'px';

        $image.width(datas.width).height(datas.height).css({top: top, left: left});
        return;
    }

    $.fn.image_enhance = function (method) {

        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on jQuery.image_enhance');
        }


    };
})($);
