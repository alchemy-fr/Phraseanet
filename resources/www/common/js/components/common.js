// import notifyLayout from "../../../../../Phraseanet-production-client/src/components/notify/notifyLayout";

var p4 = p4 || {};
var datepickerLang = [];

var commonModule = (function ($, p4) {
    $(document).ready(function () {
        $('input.input-button').hover(
            function () {
                $(this).addClass('hover');
            },
            function () {
                $(this).removeClass('hover');
            }
        );

        var locale = $.cookie('locale');

        var jq_date = p4.lng = typeof locale !== "undefined" ? locale.split('_').reverse().pop() : $('html').attr('lang');

        if (jq_date == 'en') {
            jq_date = 'en-GB';
        }

        $.datepicker.setDefaults({showMonthAfterYear: false});
        $.datepicker.setDefaults($.datepicker.regional[jq_date]);
        datepickerLang = $.datepicker.regional[jq_date];

        var cache = $('#mainMenu .helpcontextmenu');
        $('.context-menu-item', cache).hover(function () {
            $(this).addClass('context-menu-item-hover');
        }, function () {
            $(this).removeClass('context-menu-item-hover');
        });

        $('body').on('click', '.infoDialog', function (event) {
            infoDialog($(this));
        });
    });


    function showOverlay(n, appendto, callback, zIndex) {

        var div = "OVERLAY";
        if (typeof(n) != "undefined") {
            div += n;
        }
        if ($('#' + div).length === 0) {
            if (typeof(appendto) == 'undefined') {
                appendto = 'body';
            }
            $(appendto).append('<div id="' + div + '" style="display:none;">&nbsp;</div>');
        }

        var css = {
            display: 'block',
            opacity: 0,
            right: 0,
            bottom: 0,
            position: 'absolute',
            top: 0,
            zIndex: zIndex,
            left: 0
        };

        if (parseInt(zIndex) > 0) {
            css['zIndex'] = parseInt(zIndex);
        }

        if (typeof(callback) != 'function') {
            callback = function () {};
        }
        $('#' + div).css(css).addClass('overlay').fadeTo(500, 0.7).bind('click', function () {
            (callback)();
        });
        if (( navigator.userAgent.match(/msie/i) && navigator.userAgent.match(/6/) )) {
            $('select').css({
                visibility: 'hidden'
            });
        }
    }


    function hideOverlay(n) {
        if (( navigator.userAgent.match(/msie/i) && navigator.userAgent.match(/6/) )) {
            $('select').css({
                visibility: 'visible'
            });
        }
        var div = "OVERLAY";
        if (typeof(n) != "undefined") {
            div += n;
        }
        $('#' + div).hide().remove();
    }

    function infoDialog(el) {
        $("#DIALOG").attr('title', '')
            .empty()
            .append(el.attr('infos'))
            .dialog({
                title: 'About',
                autoOpen: false,
                closeOnEscape: true,
                resizable: false,
                draggable: false,
                width: 600,
                height: 400,
                modal: true,
                overlay: {
                    backgroundColor: '#000',
                    opacity: 0.7
                }
            }).dialog('open').css({'overflow-x': 'auto', 'overflow-y': 'hidden', 'padding': '0'});
    }

    function manageSession(data)
    {
        if (data.status == 'disconnected' || data.status == 'session') {
            self.location.replace(self.location.href);
        }

        // add notification in bar

        // fill the pseudo-dropdown with pre-formatted list of notifs (10 unread)
        //
        var box = $('#notification_box');
        box.empty().append(data.notifications_html);

        if (box.is(':visible')) {
            fix_notification_height();  // duplicated, better call  notifyLayout.setBoxHeight();
        }

        // fill the count of uread (red button)
        //
        var trigger = $('.notification_trigger');
        if(data.notifications.unread_count > 0) {
            $('.counter', trigger)
                .empty()
                .append(data.notifications.unread_count).show();
        }
        else {
            $('.counter', trigger)
                .hide()
                .empty();
        }

        // diplay notification about unread baskets
        //
        if (data.unread_basket_ids.length > 0) {
            var current_open = $('.SSTT.ui-state-active');
            var current_sstt = current_open.length > 0 ? current_open.attr('id').split('_').pop() : false;

            var main_open = false;
            for (var i = 0; i != data.unread_basket_ids.length; i++) {
                var sstt = $('#SSTT_' + data.unread_basket_ids[i]);
                if (sstt.size() === 0) {
                    if (main_open === false) {
                        $('#baskets .bloc').animate({'top': 30}, function () {
                            $('#baskets .alert_datas_changed:first').show()
                        });
                        main_open = true;
                    }
                }
                else {
                    if (!sstt.hasClass('active')) {
                        sstt.addClass('unread');
                    }
                    else {
                        $('.alert_datas_changed', $('#SSTT_content_' + data.unread_basket_ids[i])).show();
                    }
                }
            }
        }

        if ('' !== $.trim(data.message)) {
            if ($('#MESSAGE').length === 0) {
                $('body').append('<div id="#MESSAGE"></div>');
            }
            $('#MESSAGE')
                .empty()
                .append('<div style="margin:30px 10px;"><h4><b>' + data.message + '</b></h4></div><div style="margin:20px 0px 10px;"><label class="checkbox"><input type="checkbox" class="dialog_remove" />' + language.hideMessage + '</label></div>')
                .attr('title', 'Global Message')
                .dialog({
                    autoOpen: false,
                    closeOnEscape: true,
                    resizable: false,
                    draggable: false,
                    modal: true,
                    close: function () {
                        if ($('.dialog_remove:checked', $(this)).length > 0) {
                            // setTemporaryPref
                            $.ajax({
                                type: "POST",
                                url: "/user/preferences/temporary/",
                                data: {
                                    prop: 'message',
                                    value: 0
                                },
                                success: function (data) {
                                    return;
                                }
                            });
                        }
                    }
                })
                .dialog('open');
        }

        return true;
    }

    /**
     * duplicated from production_client::notifyLayout.js
     */
    function fix_notification_height() {
        const $notificationBoxContainer = $('#notification_box');
        const not = $('.notification', $notificationBoxContainer);
        const n     = not.length;
        const not_t = $('.notification_title', $notificationBoxContainer);
        const n_t = not_t.length;

        var h = not.outerHeight() * n + not_t.outerHeight() * n_t;
        h = h > 350 ? 350 : h;

        $notificationBoxContainer.stop().animate({height: h});
    }

    return {
        showOverlay: showOverlay,
        hideOverlay: hideOverlay,
        manageSession: manageSession
    }

})(jQuery, p4);

