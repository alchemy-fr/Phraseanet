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

import $ from 'jquery';

let cookie = require('js-cookie');

const initialize = () => {
    // $(document).ready(function () {

    let locale = cookie.get('i18next');
    if (locale === undefined) {
        locale = 'en-GB';
    }

    let cache = $('#mainMenu .helpcontextmenu');
    $('.context-menu-item', cache).hover(function () {
        $(this).addClass('context-menu-item-hover');
    }, function () {
        $(this).removeClass('context-menu-item-hover');
    });
};

// @deprecated
// function manageSession(data, showMessages) {
//     if (typeof (showMessages) === 'undefined')
//         showMessages = false;
//
//     if (data.status === 'disconnected' || data.status === 'session') {
//         disconnected();
//         return false;
//     }
//     if (showMessages) {
//         let box = $('#notification_box');
//         box.empty().append(data.notifications);
//
//         if (box.is(':visible'))
//             fix_notification_height();
//
//         if ($('.notification.unread', box).length > 0) {
//             let trigger = $('#notification_trigger');
//             $('.counter', trigger)
//                 .empty()
//                 .append($('.notification.unread', box).length);
//             $('.counter', trigger).css('visibility', 'visible');
//
//         }
//         else
//             $('#notification_trigger .counter').css('visibility', 'hidden').empty();
//
//         if (data.changed.length > 0) {
//             let current_open = $('.SSTT.ui-state-active');
//             let current_sstt = current_open.length > 0 ? current_open.attr('id').split('_').pop() : false;
//
//             let main_open = false;
//             for (let i = 0; i !== data.changed.length; i++) {
//                 let sstt = $('#SSTT_' + data.changed[i]);
//                 if (sstt.size() === 0) {
//                     if (main_open === false) {
//                         $('#baskets .bloc').animate({'top': 30}, function () {
//                             $('#baskets .alert_datas_changed:first').show();
//                         });
//                         main_open = true;
//                     }
//                 }
//                 else {
//                     if (!sstt.hasClass('active'))
//                         sstt.addClass('unread');
//                     else {
//                         $('.alert_datas_changed', $('#SSTT_content_' + data.changed[i])).show();
//                     }
//                 }
//             }
//         }
//         if ($.trim(data.message) !== '') {
//             if ($('#MESSAGE').length === 0)
//                 $('body').append('<div id="#MESSAGE"></div>');
//             $('#MESSAGE')
//                 .empty()
//                 .append('<div style="margin:30px 10px;"><h4><b>' + data.message + '</b></h4></div><div style="margin:20px 0px 10px;"><label class="checkbox"><input type="checkbox" class="dialog_remove" />' + language.hideMessage + '</label></div>')
//                 .attr('title', 'Global Message')
//                 .dialog({
//                     autoOpen: false,
//                     closeOnEscape: true,
//                     resizable: false,
//                     draggable: false,
//                     modal: true,
//                     close: function () {
//                         if ($('.dialog_remove:checked', $(this)).length > 0) {
//                             // setTemporaryPref
//                             $.ajax({
//                                 type: 'POST',
//                                 url: '/user/preferences/temporary/',
//                                 data: {
//                                     prop: 'message',
//                                     value: 0
//                                 },
//                                 success: function (data) {
//                                     return;
//                                 }
//                             });
//                         }
//                     }
//                 })
//                 .dialog('open');
//         }
//     }
//     return true;
// }

export default {
    initialize
};
