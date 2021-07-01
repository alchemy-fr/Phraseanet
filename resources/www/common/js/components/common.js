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

    /**
     * pool notifications on route /user/notifications
     *
     * @param usr_id        // the id of the user originally logged (immutable from twig)
     * @param update        // bool to refresh the counter/dropdown
     * @param recurse       // bool to re-run recursively (used by menubar)
     */
    function pollNotifications(usr_id, update, recurse) {
        var headers = {
            'update-session': recurse ? '0' : '1'       // polling should not maintain the session alive
                                                        // also : use lowercase as recomended / normalized
        };
        if(usr_id !== null) {
            headers['user-id'] = usr_id;
        }
        $.ajax({
            type: "GET",
            url: "/user/notifications/",
            dataType: 'json',
            data: {
                'offset': 0,
                'limit': 10,
                'what': 2,      // 2 : only unread
            },
            headers: headers,
            error: function (data) {
                if(data.getResponseHeader('x-phraseanet-end-session')) {
                    self.location.replace(self.location.href);  // refresh will redirect to login
                }
            },
            timeout: function () {
                if(recurse) {
                    window.setTimeout(function() { pollNotifications(usr_id, update, recurse); }, 10000);
                }
            },
            success: function (data) {
                // there is no notification bar nor a basket notification if not on prod module
                if (update) {
                    updateNotifications(data);
                }
                if(recurse) {
                    window.setTimeout(function() { pollNotifications(usr_id, update, recurse); }, 30000);
                }
            }
        })
    }

    /**
     * mark a notification as read
     *
     * @param notification_id
     * @returns {*}         // ajax promise, so the caller can add his own post-process
     */
    function markNotificationRead(notification_id) {
        return $.ajax({
            type: 'PATCH',
            url: '/user/notifications/' + notification_id + '/',
            data: {
                'read': 1
            },
            success: function () {
                // update the counter & dropdown
                pollNotifications(null, true, false);     // true:update ; false : do not recurse
            }
        });
    }

    function updateNotifications(data)
    {
        // add notification in bar

        // fill the dropdown with pre-formatted notifs (10 unread)
        //
        var $box = $('#notification_box');
        var $box_notifications = $('.notifications', $box);

        $box_notifications.empty();
        if(data.notifications.notifications.length === 0) {
            // no notification
            $('.no_notifications', $box).show();
        }
        else {
            $('.no_notifications', $box).hide();
            for (var n in data.notifications.notifications) {
                var notification = data.notifications.notifications[n];
                // add pre-formatted notif
                var $z = $(notification.html)
                // the "unread" icon is clickable to mark as read
                $('.icon_unread', $z).click(
                    notification.id,
                    function (event) {
                        markNotificationRead(event.data);
                    });
                $box_notifications.append($z);
            }
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

        // display notification about unread baskets
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

    return {
        showOverlay: showOverlay,
        hideOverlay: hideOverlay,
        markNotificationRead: markNotificationRead,
        pollNotifications: pollNotifications,
    }

})(jQuery, p4);

