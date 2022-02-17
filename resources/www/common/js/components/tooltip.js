/*
 * jQuery Tooltip plugin 1.3
 *
 * http://bassistance.de/jquery-plugins/jquery-plugin-tooltip/
 * http://docs.jquery.com/Plugins/Tooltip
 *
 * Copyright (c) 2006 - 2008 J�rn Zaefferer
 *
 * $Id: jquery.tooltip.js 5741 2008-06-21 15:22:16Z joern.zaefferer $
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */
/**
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * todo : find where this code is used (it's a close copy of Phraseanet-production-client/src/phraseanet-common/components/tooltip.js)
 */
(function ($) {

    // the tooltip element
    var helper = {},
    // the title of the current element, used for restoring
        title,
    // timeout id for delayed tooltips
        tID,
    // IE 5.5 or 6
        IE = ( navigator.userAgent.match(/msie/i) ) && (/MSIE\s(5\.5|6\.)/).test(navigator.userAgent),
    // flag for mouse tracking
        track = false;

    $.tooltip = {
        blocked: false,
        ajaxTimeout: false,
        ajaxRequest: false,
        ajaxEvent: false,
        current: null,
        visible: false,
        defaults: {
            delay: 700,
            fixable: false,
            fixableIndex: 100,
            fade: true,
            showURL: true,
            outside: true,
            isBrowsable: false,
            extraClass: "",
            top: 15,
            left: 15,
            id: "tooltip"
        },
        block: function () {
            $.tooltip.blocked = !$.tooltip.blocked;
        },

        delayAjax: function (a, b, c) {
            var options_serial = p4.tot_options;
            var query = p4.tot_query;
            var datas = {
                options_serial: options_serial,
                query: query
            };
            $.tooltip.ajaxRequest = $.ajax({
                url: $.tooltip.current.tooltipSrc,
                type: 'post',
                data: datas,
                success: function (data) {
                    title = data;
                    positioning($.tooltip.ajaxEvent);
                },
                "error": function () {
                    return;
                }
            });
        }
    };

    $.fn.extend({
        tooltip: function (settings) {
            settings = $.extend({}, $.tooltip.defaults, settings);
            createHelper(settings);
            return this.each(function () {
                    $.data(this, "tooltip", settings);
                    // copy tooltip into its own expando and remove the title
                    this.tooltipText = $(this).attr('title');
                    this.tooltipSrc = $(this).attr('tooltipsrc');

                    this.ajaxLoad = ($.trim(this.tooltipText) === '' && this.tooltipSrc !== '');
                    this.ajaxTimeout;

                    this.orEl = $(this);
                    $(this).removeAttr("title");
                    // also remove alt attribute to prevent default tooltip in IE
                    this.alt = "";
                })
                .mouseover(save)
                .mouseout(hide)
                .mouseleave(function () {
                    if (settings.isBrowsable) {
                        $.tooltip.currentHover = false;
                        // close caption container after a small delay
                        // (safe travel delay of the mouse between thumbnail and caption / allow user to cross
                        // boundaries without unexpected closing of the catpion)
                        setTimeout(function () {
                            hide();
                        }, 500);
                    }
                })
                .mousedown(fix);
        },
        fixPNG: IE ? function () {
            return this.each(function () {
                var image = $(this).css('backgroundImage');
                if (image.match(/^url\(["']?(.*\.png)["']?\)$/i)) {
                    image = RegExp.$1;
                    $(this).css({
                        'backgroundImage': 'none',
                        'filter': "progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true, sizingMethod=crop, src='" + image + "')"
                    }).each(function () {
                        var position = $(this).css('position');
                        if (position != 'absolute' && position != 'relative')
                            $(this).css('position', 'relative');
                    });
                }
            });
        } : function () {
            return this;
        },
        unfixPNG: IE ? function () {
            return this.each(function () {
                $(this).css({
                    'filter': '',
                    backgroundImage: ''
                });
            });
        } : function () {
            return this;
        },
        hideWhenEmpty: function () {
            return this.each(function () {
                $(this)[$(this).html() ? "show" : "hide"]();
            });
        },
        url: function () {
            return this.attr('href') || this.attr('src');
        }
    });

    function createHelper(settings) {
        // there can be only one tooltip helper
        if (helper.parent)
            return;
        // create the helper, h3 for title, div for url
        helper.parent = $('<div id="' + settings.id + '"><div class="body"></div></div>')
        // add to document
            .appendTo(document.body)
            // hide it at first
            .hide();

        // apply bgiframe if available
        if ($.fn.bgiframe)
            helper.parent.bgiframe();

        // save references to title and url elements
        helper.title = $('h3', helper.parent);
        helper.body = $('div.body', helper.parent);
        helper.url = $('div.url', helper.parent);
    }

    function settings(element) {
        return $.data(element, "tooltip");
    }

    // main event handler to start showing tooltips
    function handle(event) {

        if ($($.tooltip.current).hasClass('SSTT') && $($.tooltip.current).hasClass('ui-state-active'))
            return;

        // show helper, either with timeout or on instant
        if (settings(this).delay)
            tID = setTimeout(visible, settings(this).delay);
        else
            visible();
        show();

        // if selected, update the helper position when the mouse moves
        track = !!settings(this).track;
        $(document.body).bind('mousemove', update);

        // update at least once
        update(event);
    }

    // save elements title before the tooltip is displayed
    function save(event) {
        // if this is the current source, or it has no title (occurs with click event), stop
        if (event.stopPropagation)
            event.stopPropagation();

        event.cancelBubble = true;

        if ($.tooltip.blocked || this == $.tooltip.current || (!this.tooltipText && !this.tooltipSrc && !settings(this).bodyHandler)) {
            return;
        }

        // save current
        $.tooltip.current = this;
        title = this.tooltipText;

        // if element has href or src, add and show it, otherwise hide it
        if (settings(this).showURL && $(this).url())
            helper.url.html($(this).url().replace('http://', '')).show();
        else
            helper.url.hide();

        // add an optional class for this tip
        helper.parent.removeClass();
        helper.parent.addClass(settings(this).extraClass);
        if (this.ajaxLoad) {
            clearTimeout($.tooltip.ajaxTimeout);
            $.tooltip.ajaxTimeout = setTimeout("$.tooltip.delayAjax()", 300);
            $.tooltip.ajaxEvent = event;
        }
        else {
            title = '<div class="popover" style="display:block;position:relative;">' +
                '<div class="arrow"></div>' +
                '<div class="popover-inner" style="width:auto;">' +
                '<div class="popover-content">' +
                title +
                '</div>' +
                '</div>' +
                '</div>';

            positioning.apply(this, arguments);
        }
        return;
    }


    function positioning(event) {
        helper.body.html(title);
        helper.body.show();
        var $this = $.tooltip.current;
        var tooltipSettings = settings($this) ? settings($this) : {};
        var fixedPosition = $.tooltip.blocked;
        // fix PNG background for IE
        if (tooltipSettings.fixPNG)
            helper.parent.fixPNG();
        if (tooltipSettings.outside) {
            var width = 'auto';
            var height = 'auto';
            var tooltipId = tooltipSettings.id;
            var $defaultTips = $('#' + tooltipId);
            var $audioTips = $('#' + tooltipId + ' .audioTips');
            var $imgTips = $('#' + tooltipId + ' .imgTips');
            var $videoTips = $('#' + tooltipId + ' .videoTips');
            var $documentTips = $('#' + tooltipId + ' .documentTips');
            var shouldResize = $('#' + tooltipId + ' .noToolTipResize').length === 0 ? true : false;

            // get image or video original dimensions
            var recordWidth = 260;
            var recordHeight = 0;
            var tooltipVerticalOffset = 75;
            var tooltipHorizontalOffset = 35;
            var maxWidthAllowed = 1024;
            var maxHeightAllowed = 768;
            var tooltipWidth = 0;
            var tooltipHeight = 0;
            var viewportDimensions = viewport();
            var left = 0;
            var top = 0;
            var recordWidthOffset = 0;
            var recordHeightOffset = 0;
            var topOffset = 0;
            var leftOffset = 0;
            var rightOffset = 0;
            var bottomOffset = 0;

            var $selector = $defaultTips;

            if ($imgTips[0] && shouldResize) {
                recordWidth = parseInt($imgTips[0].style.width);
                recordHeight = parseInt($imgTips[0].style.height);
                $imgTips.css({display: 'block', margin: '0 auto'});
                $selector = $imgTips;
            }

            else if ($documentTips[0] && shouldResize) {
                //add min width and height of 400 and 600 respectively
                recordWidth = $documentTips.data('original-width') > 400 ? $documentTips.data('original-width') : 400;
                recordHeight = $documentTips.data('original-width') > 400 ? $documentTips.data('original-height') : 600;
                $documentTips.css({display: 'block', margin: '0 auto'});
                $selector = $documentTips;
            }

            else if ($audioTips[0] && shouldResize) {
                recordWidth = 240;
                recordHeight = 240;
                $audioTips.css({display: 'block', margin: '0 auto'});
                $selector = $audioTips;
            }

            else if ($videoTips[0] && shouldResize) {
                recordWidth = $videoTips.data('original-width');
                recordHeight = $videoTips.data('original-height');
                // limit video to maxWidth:
                /*if( recordWidth > 720 ) {
                 var limitRatio = recordWidth/recordHeight;
                 recordWidth = 720;
                 recordHeight = recordWidth / limitRatio;
                 }*/
                $videoTips.css({display: 'block', margin: '0 auto'});
                $selector = $videoTips;
            }
            else {
                // handle captions
                var contentHeight = $selector.get(0).offsetHeight;
                shouldResize = false;
                tooltipVerticalOffset = 13;
                recordHeight = contentHeight > maxHeightAllowed ? maxHeightAllowed : contentHeight;
                $selector.css({height: 'auto'});
            }

            tooltipWidth = recordWidth + tooltipHorizontalOffset;
            tooltipHeight = recordHeight + tooltipVerticalOffset;

            var rescale = function (containerWidth, containerHeight, resourceWidth, resourceHeight, maxWidthAllowed, maxHeightAllowed, $selector) {
                var resourceRatio = resourceHeight / resourceWidth;
                var resizeW = resourceWidth;
                var resizeH = resourceHeight;

                if (resourceWidth > resourceHeight) {
                    // if width still too large:
                    if (resizeW > containerWidth) {
                        resizeW = containerWidth;
                        resizeH = containerWidth * resourceRatio;
                    }

                    if (resizeH > containerHeight) {
                        resizeW = containerHeight / resourceRatio;
                        resizeH = containerHeight;
                    }
                } else {
                    if (resizeH > containerHeight) {
                        resizeW = containerHeight / resourceRatio;
                        resizeH = containerHeight;
                    }
                }

                if (maxWidthAllowed !== undefined && maxHeightAllowed !== undefined) {
                    if (resizeW > maxWidthAllowed || resizeH > maxHeightAllowed) {
                        return rescale(maxWidthAllowed, maxHeightAllowed, resourceWidth, resourceHeight)
                    }
                }

                if ($selector !== undefined) {
                    $selector.css({width: Math.floor(resizeW), height: Math.floor(resizeH)});
                }

                return {width: Math.floor(resizeW), height: Math.floor(resizeH)};
            };


            if (event) {

                var $origEventTarget = $(event.target);

                // previewTips

                // since event target can have different positionning, try to get common closest parent:
                var $eventTarget = $origEventTarget.closest('.diapo');
                if ($eventTarget.length > 0) {
                    // tooltip from records answer
                    recordWidthOffset = $eventTarget.width()-2; // remove width with margin/2
                    recordHeightOffset = $eventTarget.height()+2; // remove height with margin/2
                    // change offsets:
                    topOffset = 14;
                    leftOffset = 1;
                    rightOffset = 2;
                    bottomOffset = -15;
                } else {
                    // tooltip from workzone (basket)
                    //tooltipVerticalOffset = 0;
                    tooltipHorizontalOffset = 0;
                    topOffset = 50;
                    // fallback on original target if nothing found:
                    $eventTarget = $origEventTarget;
                }

                var recordPosition = $eventTarget.offset();

                var totalViewportWidth = viewportDimensions.x;
                var totalViewportHeight = viewportDimensions.y;

                var leftAvailableSpace = recordPosition.left + leftOffset;
                var topAvailableSpace = recordPosition.top + topOffset;
                var rightAvailableSpace = (totalViewportWidth - leftAvailableSpace - recordWidthOffset) - rightOffset;
                var bottomAvailableSpace = (totalViewportHeight - topAvailableSpace - recordHeightOffset);

                var shouldBeOnTop = false;
                var availableHeight = bottomAvailableSpace;
                var tooltipSize = {width: tooltipWidth, height: tooltipHeight};
                var position = 'top';


                if (topAvailableSpace > bottomAvailableSpace) {
                    shouldBeOnTop = true;
                    availableHeight = topAvailableSpace;
                }

                if (leftAvailableSpace > rightAvailableSpace) {
                    position = 'left';
                } else {
                    position = 'right';
                }


                // prefer bottom position if tooltip is a small caption:
                if (bottomAvailableSpace > leftAvailableSpace && bottomAvailableSpace > rightAvailableSpace) {
                    position = 'bottom';
                } else if (shouldBeOnTop && availableHeight > leftAvailableSpace && availableHeight > rightAvailableSpace) {
                    position = 'top';
                }

                if (fixedPosition === true) {
                    leftAvailableSpace = totalViewportWidth;
                    topAvailableSpace = totalViewportHeight;
                    position = 'top';
                }

                switch (position) {
                    case 'top':
                        tooltipSize = rescale(totalViewportWidth, topAvailableSpace, tooltipWidth, tooltipHeight, maxWidthAllowed, maxHeightAllowed);
                        tooltipWidth = tooltipSize.width;
                        tooltipHeight = tooltipSize.height;
                        left = leftAvailableSpace - (tooltipSize.width / 2) + (recordWidthOffset / 2);
                        top = topAvailableSpace - tooltipSize.height;
                        break;
                    case 'bottom':
                        tooltipSize = rescale(totalViewportWidth, bottomAvailableSpace, tooltipWidth, tooltipHeight, maxWidthAllowed, maxHeightAllowed);
                        tooltipWidth = tooltipSize.width;
                        tooltipHeight = tooltipSize.height;
                        left = leftAvailableSpace - (tooltipSize.width / 2) + (recordWidthOffset / 2);
                        top = totalViewportHeight - bottomAvailableSpace + bottomOffset;
                        break;
                    case 'left':
                        tooltipSize = rescale(leftAvailableSpace, totalViewportHeight, tooltipWidth, tooltipHeight, maxWidthAllowed, maxHeightAllowed);

                        tooltipWidth = tooltipSize.width;
                        tooltipHeight = tooltipSize.height;
                        left = leftAvailableSpace - tooltipSize.width;
                        break;
                    case 'right':
                        tooltipSize = rescale(rightAvailableSpace, totalViewportHeight, tooltipWidth, tooltipHeight, maxWidthAllowed, maxHeightAllowed);
                        tooltipWidth = tooltipSize.width;
                        tooltipHeight = tooltipSize.height;
                        left = leftAvailableSpace + recordWidthOffset + rightOffset;
                        break;

                }

                // tooltipHeight = tooltipHeight + 18;
                // tooltipWidth = tooltipWidth + 28;
                if (fixedPosition === true) {
                    left = totalViewportWidth / 2 - (tooltipWidth / 2);
                    top = totalViewportHeight / 2 - (tooltipHeight / 2);
                } else {


                    // try to vertical center, relative to source:
                    if (position === 'left' || position === 'right') {
                        var verticalSpace = topAvailableSpace + (recordHeightOffset / 2) + (tooltipHeight / 2)
                        if (verticalSpace < totalViewportHeight) {
                            // tooltip can be aligned vertically
                            top = topAvailableSpace + (recordHeightOffset / 2) - (tooltipHeight / 2);
                        } else {
                            top = totalViewportHeight - tooltipHeight;
                        }
                        top = top < 0 ? 0 : top;
                    }

                    // try to horizontal center, relative to source:
                    if (position === 'top' || position === 'bottom') {
                        // push to left
                        // push to right
                        var takeLeftSpace = (tooltipWidth / 2) + leftAvailableSpace;
                        var takeRightSpace = (tooltipWidth / 2) + rightAvailableSpace;
                        // if centering on top or bottom and tooltip is offcanvas
                        if (takeLeftSpace > totalViewportWidth || takeRightSpace > totalViewportWidth) {

                            if (leftAvailableSpace > (totalViewportWidth / 2)) {
                                // push at left
                                left = 0;
                            } else {
                                // push at right
                                left = totalViewportWidth - tooltipWidth;
                            }
                        } else {
                            // center
                            left = leftAvailableSpace - (tooltipWidth / 2) + (recordWidthOffset / 2);
                        }
                    }
                }

                var resizeProperties = {
                    left: left,
                    top: top
                };

                if (shouldResize) {
                    // rescale $selector css:
                    rescale(tooltipWidth - tooltipHorizontalOffset, tooltipHeight - tooltipVerticalOffset, recordWidth, recordHeight, maxWidthAllowed, maxHeightAllowed, $selector);
                    // reset non used css properties:
                    resizeProperties['max-width'] = '';
                    resizeProperties['min-width'] = '';
                } else {
                    // ensure tooltip width match with left position
                    resizeProperties['max-width'] = Math.round(tooltipWidth);
                    resizeProperties['min-width'] = Math.round(tooltipWidth);
                }

                resizeProperties['width'] = shouldResize ? Math.round(tooltipWidth) : 'auto';
                resizeProperties['height'] = shouldResize ? Math.round(tooltipHeight) : 'auto';


                helper.parent.css(resizeProperties);
            }
        }
        handle.apply($this, arguments);
        return;
    }

    // delete timeout and show helper
    function show() {
        tID = null;
        var isBrowsable = false;
        if ($.tooltip.current !== null) {
            isBrowsable = settings($.tooltip.current).isBrowsable;
        }

        if ((!IE || !$.fn.bgiframe) && settings($.tooltip.current).fade) {
            if (helper.parent.is(":animated"))
                helper.parent.stop().show().fadeTo(settings($.tooltip.current).fade, 100);
            else
                helper.parent.is(':visible') ? helper.parent.fadeTo(settings($.tooltip.current).fade, 100) : helper.parent.fadeIn(settings($.tooltip.current).fade);
        } else {
            helper.parent.show();
        }

        $(helper.parent[0])
            .unbind('mouseenter')
            .unbind('mouseleave')
            .mouseenter(function () {
                if (isBrowsable) {
                    $.tooltip.currentHover = true;
                }
            })
            .mouseleave(function () {
                if (isBrowsable) {
                    // if tooltip has scrollable content or selectionnable text - should be closed on mouseleave:
                    $.tooltip.currentHover = false;
                    helper.parent.hide();
                }
            });

        update();
    }

    function fix(event) {
        if (!settings(this).fixable) {
            hide(event);
            return;
        }
        event.cancelBubble = true;
        if (event.stopPropagation)
            event.stopPropagation();
        commonModule.showOverlay('_tooltip', 'body', unfix_tooltip, settings(this).fixableIndex);
        $('#tooltip .tooltip_closer').show().bind('click', unfix_tooltip);
        $.tooltip.blocked = true;
        positioning.apply(this, arguments);
    }

    function visible() {
        $.tooltip.visible = true;
        helper.parent.css({
            visibility: 'visible'
        });
    }

    /**
     * callback for mousemove
     * updates the helper position
     * removes itself when no current element
     */
    function update(event) {

        if ($.tooltip.blocked)
            return;

        if (event && event.target.tagName == "OPTION") {
            return;
        }

        // stop updating when tracking is disabled and the tooltip is visible
        if (!track && helper.parent.is(":visible")) {
            $(document.body).unbind('mousemove', update);
            $.tooltip.currentHover = true;
        }

        // if no current element is available, remove this listener
        if ($.tooltip.current === null) {
            $(document.body).unbind('mousemove', update);
            $.tooltip.currentHover = false;
            return;
        }

        // remove position helper classes
        helper.parent.removeClass("viewport-right").removeClass("viewport-bottom");
        if (!settings($.tooltip.current).outside) {
            var left = helper.parent[0].offsetLeft;
            var top = helper.parent[0].offsetTop;
            helper.parent.width('auto');
            helper.parent.height('auto');
            if (event) {
                // position the helper 15 pixel to bottom right, starting from mouse position
                left = event.pageX + settings($.tooltip.current).left;
                top = event.pageY + settings($.tooltip.current).top;
                var right = 'auto';
                if (settings($.tooltip.current).positionLeft) {
                    right = $(window).width() - left;
                    left = 'auto';
                }
                helper.parent.css({
                    left: left,
                    right: right,
                    top: top
                });
            }

            var v = viewport(),
                h = helper.parent[0];
            // check horizontal position
            if (v.x + v.cx < h.offsetLeft + h.offsetWidth) {
                left -= h.offsetWidth + 20 + settings($.tooltip.current).left;
                helper.parent.css({
                    left: left + 'px'
                }).addClass("viewport-right");
            }
            // check vertical position
            if (v.y + v.cy < h.offsetTop + h.offsetHeight) {
                top -= h.offsetHeight + 20 + settings($.tooltip.current).top;
                helper.parent.css({
                    top: top + 'px'
                }).addClass("viewport-bottom");
            }
        }
    }

    function viewport() {
        return {
            x: $(window).width(),
            y: $(window).height(),

            cx: 0,
            cy: 0
        };
    }

    // hide helper and restore added classes and the title
    function hide(event) {
        var isBrowsable = false;
        if ($.tooltip.current !== null) {
            isBrowsable = settings($.tooltip.current).isBrowsable;
        }
        if ($.tooltip.currentHover && isBrowsable) {
            return;
        }

        if ($.tooltip.blocked || !$.tooltip.current)
            return;

        $(helper.parent[0])
            .unbind('mouseenter')
            .unbind('mouseleave');

        // clear timeout if possible
        if (tID)
            clearTimeout(tID);
        // no more current element
        $.tooltip.visible = false;
        var tsettings = settings($.tooltip.current);
        clearTimeout($.tooltip.ajaxTimeout);
        if ($.tooltip.ajaxRequest && $.tooltip.ajaxRequest.abort) {
            $.tooltip.ajaxRequest.abort();
        }

        helper.body.empty();
        $.tooltip.current = null;
        function complete() {
            helper.parent.removeClass(tsettings.extraClass).hide().css("opacity", "");
        }

        if ((!IE || !$.fn.bgiframe) && tsettings.fade) {
            if (helper.parent.is(':animated'))
                helper.parent.stop().fadeTo(tsettings.fade, 0, complete);
            else
                helper.parent.stop().fadeOut(tsettings.fade, complete);
        } else
            complete();

        if (tsettings.fixPNG)
            helper.parent.unfixPNG();
    }

})(jQuery);

function unfix_tooltip() {
    $.tooltip.blocked = false;
    $.tooltip.visible = false;
    $.tooltip.current = null;
    $('#tooltip').hide();
    $('#tooltip .tooltip_closer').hide();
    commonModule.hideOverlay('_tooltip');
}


$(document).bind('keydown', function (event) {
    if ($.tooltip === undefined) return;

    if (event.keyCode == 27 && $.tooltip.blocked === true) {
        unfix_tooltip();
    }
});
