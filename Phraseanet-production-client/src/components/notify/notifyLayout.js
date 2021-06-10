import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
// import user from '../user/index.js';


const notifyLayout = (services) => {
    const { configService, localeService, appEvents } = services;
    const $notificationBoxContainer = $('#notification_box');
    const $notificationTrigger = $('.notification_trigger');
    let $notificationDialog = $('#notifications-dialog');
    const initialize = () => {
        $notificationTrigger.on('mousedown', (event) => {
            event.stopPropagation();
            const $target = $(event.currentTarget);
            if ($target.hasClass('open')) {
                $notificationBoxContainer.hide();
                $target.removeClass('open');
                clear_notifications();
            } else {
                $notificationBoxContainer.show();

                setBoxHeight();

                $target.addClass('open');
                read_notifications();
            }
        });

        $(document).on('mousedown', () => {
            if ($notificationTrigger.hasClass('open')) {
                $notificationTrigger.trigger('click');
            }

            if ($notificationTrigger.hasClass('open')) {
                $notificationBoxContainer.hide();
                $notificationTrigger.removeClass('open');
                clear_notifications();
            }
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
            .on('click', '.notification__print-action', (event) => {
                event.preventDefault();
                const $el = $(event.currentTarget);
                const page = $el.data('page');
                print_notifications(page);
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

    const print_notifications = (page) => {

        page = parseInt(page, 10);
        var buttons = {};

        buttons[localeService.t('fermer')] = function () {
            $notificationDialog.dialog('close');
        };

        if ($notificationDialog.length === 0) {
            $('body').append('<div id="notifications-dialog" class="loading"></div>');
            $notificationDialog = $('#notifications-dialog');
        }

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

           // only load on the first time
           if (page === 0 ) {
               $notificationDialog
                   .on('click','.notification_next .notification__print-action', function (event) {
                       event.preventDefault();
                       var $el = $(event.currentTarget);
                       var page = $el.data('page');
                       print_notifications(page);
                   });
           }

        $.ajax({
            type: 'GET',
            url: '/user/notifications/',
            dataType: 'json',
            data: {
                page: page
            },
            error: function (data) {
                $notificationDialog.removeClass('loading');
            },
            timeout: function (data) {
                $notificationDialog.removeClass('loading');
            },
            success: function (data) {
                $notificationDialog.removeClass('loading');


                if (page === 0) {
                    $notificationDialog.empty();
                } else {
                    $('.notification_next', $notificationDialog).remove();
                }

                let i = 0;
                for (i in data.notifications) {
                    var id = 'notif_date_' + i;
                    var date_cont = $('#' + id);
                    if (date_cont.length === 0) {
                        $notificationDialog.append('<div id="' + id + '"><div class="notification_title">' + data.notifications[i].display + '</div></div>');
                        date_cont = $('#' + id);
                    }

                    let j = 0;
                    for (j in data.notifications[i].notifications) {
                        var loc_dat = data.notifications[i].notifications[j];
                        var html = '<div style="position:relative;" id="notification_' + loc_dat.id + '" class="notification">' +
                            '<table style="width:100%;" cellspacing="0" cellpadding="0" border="0"><tr><td style="width:25px;">' +
                            loc_dat.icon +
                            '</td><td>' +
                            '<div style="position:relative;" class="' + loc_dat.classname + '">' +
                            loc_dat.text + ' <span class="time">' + loc_dat.time + '</span></div>' +
                            '</td></tr></table>' +
                            '</div>';
                        date_cont.append(html);
                    }
                }

                var next_ln = $.trim(data.next);

                if (next_ln !== '') {
                    $notificationDialog.append('<div class="notification_next">' + next_ln + '</div>');
                }
            }
        });

    };

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

    return {
        initialize
    };
};

export default notifyLayout;
