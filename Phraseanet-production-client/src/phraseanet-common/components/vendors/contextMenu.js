// @TODO enable lints
/* eslint-disable max-len*/
/* eslint-disable object-shorthand*/
/* eslint-disable dot-notation*/
/* eslint-disable vars-on-top*/
/* eslint-disable prefer-template*/
/* eslint-disable prefer-const*/
/* eslint-disable spaced-comment*/
/* eslint-disable curly*/
/* eslint-disable object-curly-spacing*/
/* eslint-disable spaced-comment*/
/* eslint-disable prefer-arrow-callback*/
/* eslint-disable one-var*/
/* eslint-disable space-in-parens*/
/* eslint-disable camelcase*/
/* eslint-disable no-undef*/
/* eslint-disable quote-props*/
/* eslint-disable no-shadow*/
/* eslint-disable no-param-reassign*/
/* eslint-disable no-unused-expressions*/
/* eslint-disable no-shadow*/
/* eslint-disable no-implied-eval*/
/* eslint-disable brace-style*/
/* eslint-disable no-unused-vars*/
/* eslint-disable brace-style*/
/* eslint-disable no-lonely-if*/
/* eslint-disable no-inline-comments*/
/* eslint-disable default-case*/
/* eslint-disable one-var*/
/* eslint-disable semi*/
/* eslint-disable no-throw-literal*/
/* eslint-disable no-sequences*/
/* eslint-disable consistent-this*/
/* eslint-disable no-dupe-keys*/
/* eslint-disable semi*/
/* eslint-disable no-loop-func*/
/* eslint-disable space-after-keywords*/
/* eslint-disable no-var*/
/* eslint-disable quotes*/


/**
 * Copyright (c)2005-2009 Matt Kruse (javascripttoolbox.com)
 *
 * Dual licensed under the MIT and GPL licenses.
 * This basically means you can use this code however you want for
 * free, but don't claim to have written it yourself!
 * Donations always accepted: http://www.JavascriptToolbox.com/donate/
 *
 * Please do not link to the .js files on javascripttoolbox.com from
 * your site. Copy the files locally to your server instead.
 *
 */
/**
 * jquery.contextmenu.js
 * jQuery Plugin for Context Menus
 * http://www.JavascriptToolbox.com/lib/contextmenu/
 *
 * Copyright (c) 2008 Matt Kruse (javascripttoolbox.com)
 * Dual licensed under the MIT and GPL licenses.
 *
 * @version 1.0
 * @history 1.0 2008-10-20 Initial Release
 * @todo slideUp doesn't work in IE - because of iframe?
 * @todo Hide all other menus when contextmenu is shown?
 * @todo More themes
 * @todo Nested context menus
 */

(function ($) {
    $.contextMenu = {
        // props add by Alchemy
        _showEvent: null,	// the original event the caused the menu to show (useful to find the original element clicked)
        _div: null,
        //
        openEvt: "contextmenu",	// ouverture sur right-click
        closeTimer: null,		// fermer le menu apres 100ms de mouseout

        shadow: true,
        dropDown: false,
        shadowOffset: 0,
        shadowOffsetX: 5,
        shadowOffsetY: 5,
        shadowWidthAdjust: -3,
        shadowHeightAdjust: -3,
        shadowOpacity: 0.2,
        shadowClass: 'context-menu-shadow',
        shadowColor: 'black',

        offsetX: 0,
        offsetY: 0,
        appendTo: 'body',
        direction: 'down',
        constrainToScreen: true,

        showTransition: 'show',
        hideTransition: 'hide',
        showSpeed: '',
        hideSpeed: '',
        showCallback: null,
        hideCallback: null,

        className: 'context-menu',
        itemClassName: 'context-menu-item',
        itemHoverClassName: 'context-menu-item-hover',
        disabledItemClassName: 'context-menu-item-disabled',
        disabledItemHoverClassName: 'context-menu-item-disabled-hover',
        separatorClassName: 'context-menu-separator',
        innerDivClassName: 'context-menu-item-inner',
        themePrefix: 'context-menu-theme-',
        theme: 'default',

        separator: 'context-menu-separator', // A specific key to identify a separator
        target: null, // The target of the context click, to be populated when triggered
        menu: null, // The jQuery object containing the HTML object that is the menu itself
        shadowObj: null, // Shadow object
        bgiframe: null, // The iframe object for IE6
        shown: false, // Currently being shown?
        useIframe: /*@cc_on @*//*@if (@_win32) true, @else @*/false, /*@end @*/ // This is a better check than looking at userAgent!

        _originalPlace: null,
        _hovered: false,

        _hover_in: function(cm) {
            if (cm.closeTimer) {
                clearTimeout(cm.closeTimer);
                cm.closeTimer = null;
            }
        },

        _hover_out: function(cm, tms) {
            cm.closeTimer = setTimeout(
                function() {
                    cm.hide();
                },
                tms
            );
        },

        // Create the menu instance
        create: function (menu, opts) {
            const cmenu = $.extend({}, this, opts); // Clone all default properties to created object

            // If a selector has been passed in, then use that as the menu
            if (typeof menu === "string") {
                cmenu.menu = $(menu);
                cmenu._originalPlace = cmenu.menu.parent();
                cmenu.menu.hover(
                    function() { cmenu._hover_in(cmenu); },
                    function() { cmenu._hover_out(cmenu, 500); }
                );
            }
            // If a function has been passed in, call it each time the menu is shown to create the menu
            else if (typeof menu === "function") {
                cmenu.menuFunction = menu;
            }
            // Otherwise parse the Array passed in
            else {
                cmenu.menu = cmenu.createMenu(menu, cmenu);
            }
            if (cmenu.menu) {
                cmenu.menu.css({display: 'none'});
                $(cmenu.appendTo).append(cmenu.menu);
            }

            // Create the shadow object if shadow is enabled
            if (cmenu.shadow) {
                cmenu.createShadow(cmenu); // Extracted to method for extensibility
                if (cmenu.shadowOffset) {
                    cmenu.shadowOffsetX = cmenu.shadowOffsetY = cmenu.shadowOffset;
                }
            }
            $('body').bind(cmenu.openEvt, function () {
                cmenu.hide();
            }); // If right-clicked somewhere else in the document, hide this menu

            cmenu.onCreated(cmenu);
            return cmenu;
        },

        // Create an iframe object to go behind the menu
        createIframe: function () {
            return $('<iframe tabindex="-1" src="javascript:false" style="display:block;position:absolute;z-index:-1;filter:Alpha(Opacity=0);"/>');
        },

        // Accept an Array representing a menu structure and turn it into HTML
        createMenu: function (menu, cmenu) {
            let className = cmenu.className;
            $.each(cmenu.theme.split(","), function (i, n) {
                className += ' ' + cmenu.themePrefix + n;
            });
            const $t = $('<table style=""></table>').click(function () {
                cmenu.hide();
                return false;
            }); // We wrap a table around it so width can be flexible
            const $tr = $('<tr></tr>');
            const $td = $('<td></td>');
            const $div = cmenu._div = $('<div class="' + className + '"></div>');

            cmenu._div.hover(
                function() { cmenu._hover_in(cmenu); },
                function() { cmenu._hover_out(cmenu, 500); }
            );

            // Each menu item is specified as either:
            //     title:function
            // or  title: { property:value ... }
            /*
             for (var i=0; i<menu.length; i++) {
             var m = menu[i];
             if (m==$.contextMenu.separator) {
             $div.append(cmenu.createSeparator());
             }
             else {
             for (var opt in menu[i]) {
             $div.append(cmenu.createMenuItem(opt,menu[i][opt])); // Extracted to method for extensibility
             }
             }
             }
             */
            for (let i = 0; i < menu.length; i++) {
                const m = menu[i];
                if (m === $.contextMenu.separator) {
                    $div.append(cmenu.createSeparator());
                }
                else {
                    $div.append(cmenu.createMenuItem(m)); // Extracted to method for extensibility
                }
            }
            if (cmenu.useIframe) {
                $td.append(cmenu.createIframe());
            }
            $t.append($tr.append($td.append($div)));

            return $t;
        },

        // Create an individual menu item
        createMenuItem: function (obj) {
            const cmenu = this;
            const label = obj.label;
            if (typeof obj === "function") {
                obj = {onclick: obj};
            } // If passed a simple function, turn it into a property of an object
            // Default properties, extended in case properties are passed
            const o = $.extend({
                onclick: function () {
                },
                className: '',
                hoverClassName: cmenu.itemHoverClassName,
                icon: '',
                disabled: false,
                title: '',
                hoverItem: cmenu.hoverItem,
                hoverItemOut: cmenu.hoverItemOut
            }, obj);
            // If an icon is specified, hard-code the background-image style. Themes that don't show images should take this into account in their CSS
            const iconStyle = (o.icon) ? 'background-image:url(' + o.icon + ');' : '';
            const $div = $('<div class="' + cmenu.itemClassName + ' ' + o.className + ((o.disabled) ? ' ' + cmenu.disabledItemClassName : '') + '" title="' + o.title + '"></div>')
            // If the item is disabled, don't do anything when it is clicked
                .click(
                    function (e) {
                        if (cmenu.isItemDisabled(this)) {
                            return false;
                        }
                        else {
                            return o.onclick.call(cmenu.target, this, cmenu, e, label);
                        }
                    }
                )
                // Change the class of the item when hovered over
                .hover(
                    function () {
                        o.hoverItem.call(this, (cmenu.isItemDisabled(this)) ? cmenu.disabledItemHoverClassName : o.hoverClassName);
                    }
                    , function () {
                        o.hoverItemOut.call(this, (cmenu.isItemDisabled(this)) ? cmenu.disabledItemHoverClassName : o.hoverClassName);
                    }
                );
            const $idiv = $('<div class="' + cmenu.innerDivClassName + '" style="' + iconStyle + '">' + label + '</div>');
            $div.append($idiv);

            return $div;
        },

        // Create a separator row
        createSeparator: function () {
            return $('<div class="' + this.separatorClassName + '"></div>');
        },

        // Determine if an individual item is currently disabled. This is called each time the item is hovered or clicked because the disabled status may change at any time
        isItemDisabled: function (item) {
            return $(item).is('.' + this.disabledItemClassName);
        },

        // Functions to fire on hover. Extracted to methods for extensibility
        hoverItem: function (c) {
            $(this).addClass(c);
        },
        hoverItemOut: function (c) {
            $(this).removeClass(c);
        },

        // Create the shadow object
        createShadow: function (cmenu) {
            cmenu.shadowObj = $('<div class="' + cmenu.shadowClass + '"></div>').css({
                display: 'none',
                position: "absolute",
                zIndex: 9998,
                opacity: cmenu.shadowOpacity,
                backgroundColor: cmenu.shadowColor
            });
            $(cmenu.appendTo).append(cmenu.shadowObj);
        },

        // Display the shadow object, given the position of the menu itself
        showShadow: function (x, y, e) {
            const cmenu = this;
            if (cmenu.shadow) {
                cmenu.shadowObj.css({
                    width: (cmenu.menu.width() + cmenu.shadowWidthAdjust) + "px",
                    height: (cmenu.menu.height() + cmenu.shadowHeightAdjust) + "px",
                    top: (y + cmenu.shadowOffsetY) + "px",
                    left: (x + cmenu.shadowOffsetX) + "px"
                }).addClass(cmenu.shadowClass)[cmenu.showTransition](cmenu.showSpeed);
            }
        },

        // A hook to call before the menu is shown, in case special processing needs to be done.
        // Return false to cancel the default show operation
        beforeShow: function () {
            return true;
        },

        onCreated: function (cmenu) {},

        // Show the context menu
        show: function (t, e) {
            const cmenu = this;
            let x = e.pageX, y = e.pageY;

            if (cmenu._div) {
                cmenu._div.css('height', 'auto').css('overflow-y', 'auto');
            }

            cmenu.target = t; // Preserve the object that triggered this context menu so menu item click methods can see it
            cmenu._showEvent = e; // Preserve the event that triggered this context menu so menu item click methods can see it
            if (cmenu.beforeShow() !== false) {
                const $t = $(t);
                $t.off("mouseleave").on("mouseleave", function () {
                    cmenu._hover_out(cmenu, 100);
                });

                // If the menu content is a function, call it to populate the menu each time it is displayed
                if (cmenu.menuFunction) {
                    if (cmenu.menu) {
                        if(cmenu._originalPlace) {
                            cmenu._originalPlace.append(cmenu.menu);
                        }
                        else {
                            $(cmenu.menu).remove();
                        }
                    }
                    let r = cmenu.menuFunction(cmenu, t);
                    if(Array.isArray(r)) {
                        cmenu.menu = cmenu.createMenu(r, cmenu);
                    }
                    else {
                        cmenu.menu = r;
                    }
                    cmenu.menu.css({display: 'none'});
                    $(cmenu.appendTo).append(cmenu.menu);
                }
                const $c = cmenu.menu;
                x += cmenu.offsetX;
                y += cmenu.offsetY;
                const pos = cmenu.getPosition(x, y, cmenu, e); // Extracted to method for extensibility
                cmenu.showShadow(pos.x, pos.y, e);
                // Resize the iframe if needed
                if (cmenu.useIframe) {
                    $c.find('iframe').css({
                        width: $c.width() + cmenu.shadowOffsetX + cmenu.shadowWidthAdjust,
                        height: $c.height() + cmenu.shadowOffsetY + cmenu.shadowHeightAdjust
                    });
                }
                if (cmenu.dropDown) {
                    $c.css('visibility', 'hidden').show();

                    let bodySize = {x: $(window).width(), y: $(window).height()};

                    if ($t.offset().top + $t.outerHeight() + $c.height() > bodySize.y) {
                        if ($t.offset().left + $t.outerWidth() + $c.width() > bodySize.x)
                            $c.css({
                                top: ($t.offset().top - $c.outerHeight()) + "px",
                                left: ($t.offset().left - $c.outerWidth()) + "px",
                                position: "absolute",
                                zIndex: 9999
                            })[cmenu.showTransition](cmenu.showSpeed, ((cmenu.showCallback) ? function () {
                                cmenu.showCallback.call(cmenu);
                            } : null));
                        else
                            $c.css({
                                top: ($t.offset().top - $c.outerHeight()) + "px",
                                left: ($t.offset().left) + "px",
                                position: "absolute",
                                zIndex: 9999
                            })[cmenu.showTransition](cmenu.showSpeed, ((cmenu.showCallback) ? function () {
                                cmenu.showCallback.call(cmenu);
                            } : null));
                    }
                    else {

                        if ($t.offset().left + $t.outerWidth() + $c.width() > bodySize.x)
                            $c.css({
                                top: ($t.offset().top + $t.outerHeight()) + "px",
                                left: ($t.offset().left - $c.outerWidth()) + "px",
                                position: "absolute",
                                zIndex: 9999
                            })[cmenu.showTransition](cmenu.showSpeed, ((cmenu.showCallback) ? function () {
                                cmenu.showCallback.call(cmenu);
                            } : null));
                        else
                            $c.css({
                                top: ($t.offset().top + $t.outerHeight()) + "px",
                                left: ($t.offset().left) + "px",
                                position: "absolute",
                                zIndex: 9999
                            })[cmenu.showTransition](cmenu.showSpeed, ((cmenu.showCallback) ? function () {
                                cmenu.showCallback.call(cmenu);
                            } : null));

                    }
                    $c.css('visibility', 'visible');
                }
                else {
                    $c.css({
                        top:      pos.y + "px",
                        left:     pos.x + "px",
                        position: "absolute",
                        zIndex:   9999
                    })[cmenu.showTransition](cmenu.showSpeed, ((cmenu.showCallback) ? function () {
                        cmenu.showCallback.call(cmenu);
                    } : null));
                }
                cmenu.shown = true;
                $(document).one('click', null, function () {
                    cmenu.hide();
                }); // Handle a single click to the document to hide the menu
            }
        },

        // Find the position where the menu should appear, given an x,y of the click event
        getPosition: function (clickX, clickY, cmenu, e) {
            let x = clickX + cmenu.offsetX;
            let y = clickY + cmenu.offsetY;
            let h = $(cmenu.menu).height();
            let w = $(cmenu.menu).width();
            const dir = cmenu.direction;
            if (cmenu.constrainToScreen) {
                const $w = $(window);
                const wh = $w.height();
                const ww = $w.width();
                const st = $w.scrollTop();
                const maxTop = y - st - 5;
                const maxBottom = wh + st - y - 5;
                if (h > maxBottom) {
                    if (h > maxTop) {
                        if (maxTop > maxBottom) {
                            // scrollable en haut
                            h = maxTop;
                            cmenu._div.css('height', h + 'px').css('overflow-y', 'scroll');
                            y -= h;
                        }
                        else {
                            // scrollable en bas
                            h = maxBottom;
                            cmenu._div.css('height', h + 'px').css('overflow-y', 'scroll');
                        }
                    }
                    else {
                        // menu ok en haut
                        y -= h;
                    }
                }
                else {
                    // menu ok en bas
                }

                const maxRight = x + w - $w.scrollLeft();
                if (maxRight > ww) {
                    x -= (maxRight - ww);
                }
            }
            return {'x': x, 'y': y};
        },

        // Hide the menu, of course
        hide: function () {
            const cmenu = this;
            if (cmenu.shown) {
                if (cmenu.iframe) {
                    $(cmenu.iframe).hide();
                }
                if (cmenu.menu) {
                    cmenu.menu[cmenu.hideTransition](cmenu.hideSpeed, ((cmenu.hideCallback) ? function () {
                        cmenu.hideCallback.call(cmenu);
                    } : null));
                }
                if (cmenu.shadow) {
                    cmenu.shadowObj[cmenu.hideTransition](cmenu.hideSpeed);
                }
            }
            cmenu.shown = false;
        }
    };

    // This actually adds the .contextMenu() function to the jQuery namespace
    $.fn.contextMenu = function (menu, options) {
        const cmenu = $.contextMenu.create(menu, options);
        return this.each(function () {
            $(this).bind(cmenu.openEvt, function (e) {
                if (cmenu.menu.is(':visible'))
                    cmenu.hide();
                else {
                    $('body').trigger(cmenu.openEvt);
                    cmenu.show(this, e);
                }
                return false;
            }).bind('mouseover', function () {
                return false;
            });
        });
    };
})(jQuery);
