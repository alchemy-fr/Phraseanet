import ui from '../ui';

const user = (services) => {
    const { configService, localeService, appEvents } = services;

    const initialize = () => {

    };

    const onUserDisconnect = (...data) => {
        // @TODO refactor - display modal in here
        ui(services).showModal('disconnected', {title: localeService.t('serverDisconnected')});
    };

    appEvents.listenAll({
        'user.disconnected': onUserDisconnect
    });

    // const manageSession = (...params) => {
    //     let [data, showMessages] = params;
    //
    //     if (typeof (showMessages) === 'undefined') {
    //         showMessages = false;
    //     }
    //
    //     if (showMessages) {
    //         // @todo: to be moved
    //         if ($.trim(data.message) !== '') {
    //             if ($('#MESSAGE').length === 0) {
    //                 $('body').append('<div id="#MESSAGE"></div>');
    //             }
    //             $('#MESSAGE')
    //                 .empty()
    //                 .append(data.message + '<div style="margin:20px;"><input type="checkbox" class="dialog_remove" />' + localeService.t('hideMessage') + '</div>')
    //                 .attr('title', 'Global Message')
    //                 .dialog({
    //                     autoOpen: false,
    //                     closeOnEscape: true,
    //                     resizable: false,
    //                     draggable: false,
    //                     modal: true,
    //                     close: function () {
    //                         if ($('.dialog_remove:checked', $(this)).length > 0) {
    //                             // @TODO get from module
    //                             appCommons.userModule.setTemporaryPref('message', 0);
    //                         }
    //                     }
    //                 })
    //                 .dialog('open');
    //         }
    //     }
    //     return true;
    // };

    return {initialize};
};

export default user;
