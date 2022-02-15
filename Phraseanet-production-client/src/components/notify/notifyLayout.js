import $ from 'jquery';
// import user from '../user/index.js';


const notifyLayout = (services) => {
    const { configService, localeService, appEvents } = services;
    const $notificationBoxContainer = $('#notification_box');
    const $notificationTrigger = $('.notification_trigger');
    let $notificationDialog = $('#notifications-dialog');
    let $notifications = $('.notifications', $notificationDialog);
    let $navigation = $('.navigation', $notificationDialog);

    const initialize = () => {

        //   the dialog MUST be created during print_notifications(), else the first clik on a "read" button
        //   is badly interpreted (no action, but scrolls the content ???
        // $notificationDialog.dialog({});

        /**
         * click on menubar/notifications : drop a box with last 10 notification, and a button "see all"
         * the box content is already set by poll notifications
         */
        $notificationTrigger.on('mousedown', (event) => {
            event.stopPropagation();
            // toggle
            if ($notificationTrigger.hasClass('open')) {
                $notificationBoxContainer.hide();
                $notificationTrigger.removeClass('open');   // revert background in menubar
            }
            else {
                $notificationTrigger.addClass('open');      // highlight background in menubar
                $notificationBoxContainer.show();
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
             * click on "see all notifications"
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

        if(offset == 0) {
            $notifications.empty();
        }

        var buttons = {};

        buttons[localeService.t('fermer')] = function () {
            $notificationDialog.dialog('close');
        };

        let zIndexOverlay = $('.ui-widget-overlay').css("z-index");
        // open the dlg (even if it is already opened when "load more")
        //
        $notificationDialog
            .dialog(
                {
                    title:         $('#notification-title').val(),
                    autoOpen:      false,
                    closeOnEscape: true,
                    resizable:     false,
                    draggable:     false,
                    modal:         true,
                    width:         500,
                    height:        400,
                    dialogClass:  "dialog-notification-box",
                    overlay:       {
                        backgroundColor: '#000',
                        opacity:         0.7
                    },
                    open: function () {
                        $('.ui-widget-overlay').css("z-index", $(".dialog-notification-box").css("z-index"));
                    },
                    close:         function (event, ui) {
                        $('.ui-widget-overlay').css("z-index", zIndexOverlay);
                        // destroy so it will be "fresh" on next open (scrollbar on top)
                        $notificationDialog.dialog('destroy').remove();
                    }
                }
            )
            .dialog('option', 'buttons', buttons)
            .dialog('open');

        // load 10 (more) notifications
        //
        $notificationDialog.addClass('loading');
        $.ajax({
            type: 'GET',
            url: '/user/notifications/',
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
                    $notifications.empty();
                }

                const notifications = data.notifications.notifications;
                let i = 0;
                for (i in notifications) {
                    const notification = notifications[i];

                    // group notifs by day
                    //
                    const date    = notification.created_on_day;
                    const id      = 'notif_date_' + date;
                    let date_cont = $('#' + id, $notifications);

                    // new day ? create the container
                    if (date_cont.length === 0) {
                        $notifications.append('<div id="' + id + '"><div class="notification_title">' + notifications[i].created_on + '</div></div>');
                        date_cont = $('#' + id, $notifications);
                    }

                    // add pre-formatted notif
                    const $z = $(notification.html);
                    // the "unread" icon is clickable to mark as read
                    $('.icon_unread', $z).tooltip().click(
                        {'z':$z, 'id':notification.id},
                        function (event) {
                            markNotificationRead(event.data['id'], $z);
                        });
                    date_cont.append($z);
                    // do not display date in the dialog content beacause it's already grouped by date
                    $(".time", $z).hide();
                    $(".time-in-dialog", $z).show();
                }

                // handle "show more" button
                //
                if(data.notifications.next_offset) {
                    // update the "more" button
                    $navigation
                        .off('click', '.notification__print-action')   // remove previous, else we load 10, 20, 40...
                        .on('click', '.notification__print-action', function (event) {
                            event.preventDefault();
                            print_notifications(data.notifications.next_offset);
                        })
                        .show();
                }
                else {
                    // no more ? no button
                    $navigation.hide();
                }
            }
        });

        $notificationDialog.on('click', '.mark-all-read', function(event) {
            event.preventDefault();
            $.ajax({
                type: 'POST',
                url: '/user/notifications/read-all/',
                success: function (data) {
                    if (data.success == true) {
                        print_notifications(0);
                        commonModule.pollNotifications(null, true, false);
                    }
                }
            });

        });
    };

    const markNotificationRead = (notification_id, $notification) => {
        commonModule.markNotificationRead(notification_id)
            .success(function (data) {
                // xhttp ok : update notif
                $notification.removeClass('unread');
            })
    };

    return {
        initialize
    };
};

export default notifyLayout;
