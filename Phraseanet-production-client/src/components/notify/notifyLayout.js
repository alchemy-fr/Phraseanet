import $ from 'jquery';
// import user from '../user/index.js';


const notifyLayout = (services) => {
    const { configService, localeService, appEvents } = services;
    const $notificationBoxContainer = $('#notification_box');
    const $notificationTrigger = $('.notification_trigger');
    let $notificationDialog = $('#notifications-dialog');
    let $notificationsContent = null;
    let $notificationsNavigation = null;

    const initialize = () => {
        /**
         * click on menubar/notifications : drop a box with last 10 notification, and a button "see all"
         * the box content is already set by poll notifications
         */
        $notificationTrigger.on('mousedown', (event) => {
            event.stopPropagation();
            // toggle
            if ($notificationTrigger.hasClass('open')) {
                $notificationBoxContainer.hide();
                $notificationTrigger.removeClass('open');
            }
            else {
                $notificationTrigger.addClass('open');
                $notificationBoxContainer.show();
                setBoxHeight();
            }
        });

        /**
         * close on every mousedown
         */
        $(document).on('mousedown', () => {
            $notificationBoxContainer.hide();
            $notificationTrigger.removeClass('open');
        });

        $notificationBoxContainer
            .on('mousedown', (event) => {
                event.stopPropagation();
            })
            .on('mouseover', '.notification', (event) => {
                $(event.currentTarget).addClass('hover');
            })
            .on('mouseout', '.notification', (event) => {
                $(event.currentTarget).removeClass('hover');
            })
            /**
             * click on "see all notification"
             */
            .on('click', '.notification__print-action', (event) => {
                event.preventDefault();
                $notificationBoxContainer.hide();
                $notificationTrigger.removeClass('open');
                print_notifications(0);
            });

        $(window).bind('resize', function () {
            setBoxPosition();
        });
        setBoxPosition();

    };

    // const addNotifications = (notificationContent) => {
    //     // var box = $('#notification_box');
    //     $notificationBoxContainer.empty().append(notificationContent);
    //
    //     if ($notificationBoxContainer.is(':visible')) {
    //         setBoxHeight();
    //     }
    //
    //     if ($('.notification.unread', $notificationBoxContainer).length > 0) {
    //         $('.counter', $notificationTrigger)
    //             .empty()
    //             .append($('.notification.unread', $notificationBoxContainer).length);
    //         $('.counter', $notificationTrigger).css('visibility', 'visible');
    //
    //     } else {
    //         $('.notification_trigger .counter').css('visibility', 'hidden').empty();
    //     }
    // };


    const setBoxHeight = () => {
        //var box = $('#notification_box');
        var not = $('.notification', $notificationBoxContainer);
        var n = not.length;
        var not_t = $('.notification_title', $notificationBoxContainer);
        var n_t = not_t.length;

        var h = not.outerHeight() * n + not_t.outerHeight() * n_t;
        h = h > 350 ? 350 : h;

        $notificationBoxContainer.stop().animate({height: h});
    };

    const setBoxPosition = () => {
        if ($notificationTrigger.length > 0) {
            var leftOffset = Math.round($notificationTrigger.offset().left);
            if(leftOffset == 0) {
                $notificationBoxContainer.css({
                    left: 20
                });
            }else {
                $notificationBoxContainer.css({
                    left: Math.round($notificationTrigger.offset().left - 1)
                });
            }
        }
    };

    /**
     * add 10 notifications into the dlgbox
     * display the button "load more" while relevant
     *
     * @param offset
     */
    const print_notifications = (offset) => {

        offset = parseInt(offset, 10);
        var buttons = {};

        buttons[localeService.t('fermer')] = function () {
            $notificationDialog.dialog('close');
        };

        // create the dlg div if it does not exists
        //
        if ($notificationDialog.length === 0) {
            $('body').append('<div id="notifications-dialog"><div class="content"></div><div class="navigation"></div></div>');
            $notificationDialog = $('#notifications-dialog');
            $notificationsContent = $('.content', $notificationDialog);
            $notificationsNavigation = $('.navigation', $notificationDialog);
        }

        // open the dlg (even if it is already opened when "load more")
        //
        $notificationDialog
            .dialog({
                title: $('#notification-title').val(),
                autoOpen: false,
                closeOnEscape: true,
                resizable: false,
                draggable: false,
                modal: true,
                width: 500,
                height: 400,
                overlay: {
                    backgroundColor: '#000',
                    opacity: 0.7
                },
                close: function (event, ui) {
                    $notificationDialog.dialog('destroy').remove();
                }
            })
            .dialog('option', 'buttons', buttons)
            .dialog('open');

        // load 10 (more) notifications
        //
        $notificationDialog.addClass('loading');
        $.ajax({
            type: 'POST',
            // url: '/user/notifications/',
            url: '/session/notifications/',
            dataType: 'json',
            data: {
                'offset': offset,
                'limit': 10,
                'what': 3,          // 3 = read | unread
            },
            error: function (data) {
                $notificationDialog.removeClass('loading');
            },
            timeout: function (data) {
                $notificationDialog.removeClass('loading');
            },
            success: function (data) {
                $notificationDialog.removeClass('loading');

                if (offset === 0) {
                    $notificationsContent.empty();
                }

                const notifications = data.notifications.notifications;
                let i = 0;
                for (i in notifications) {
                    const notification = notifications[i];

                    // group notifs by day
                    //
                    const date    = notification.created_on_day;
                    const id      = 'notif_date_' + date;
                    let date_cont = $('#' + id, $notificationsContent);
                    if (date_cont.length === 0) {
                        $notificationsContent.append('<div id="' + id + '"><div class="notification_title">' + notifications[i].created_on + '</div></div>');
                        date_cont = $('#' + id, $notificationsContent);
                    }
                    // write notif
                    let html = '<div style="position:relative;" id="notification_' + notification.id + '" class="notification">' +
                        '<table style="width:100%;" cellspacing="0" cellpadding="0" border="0"><tr style="border-top: 1px grey solid"><td style="width:25px; vertical-align: top;">' +
                        '<img src="' + notification.icon + '" style="vertical-align:middle;width:16px;margin:2px;" />' +
                        '</td><td style="vertical-align: top;">' +
                        '<div style="position:relative;" class="' + notification.classname + '">' +
                        notification.text + ' <span class="time">' + notification.time + '</span></div>' +
                        '</td></tr></table>' +
                        '</div>';
                    date_cont.append(html);
                }

                if (data.notifications.next_page_html) {
                    $notificationsNavigation
                        .off('click', '.notification__print-action');
                    $notificationsNavigation.empty().show().append(data.notifications.next_page_html);
                    $notificationsNavigation
                        .on('click', '.notification__print-action', function (event) {
                            event.preventDefault();
                            let $el  = $(event.currentTarget);
                            let offset = $el.data('offset');
                            print_notifications(offset);
                        });
                }
                else {
                    $notificationsNavigation.empty().hide();
                }
            }
        });
    };
    /* remove in favor of existing /session/ route
    const read_notifications = () => {
        var notifications = [];

        $('#notification_box .unread').each(function () {
            notifications.push($(this).attr('id').split('_').pop());
        });

        $.ajax({
            type: 'POST',
            url: '/user/notifications/read/',
            data: {
                notifications: notifications.join('_')
            },
            success: function (data) {
                $('.notification_trigger .counter').css('visibility', 'hidden').empty();
            }
        });
    };

    const clear_notifications = () => {
        var unread = $('#notification_box .unread');

        if (unread.length === 0) {
            return;
        }

        unread.removeClass('unread');
        $('.notification_trigger .counter').css('visibility', 'hidden').empty();
    };

     */

    return {
        initialize
    };
};

export default notifyLayout;
