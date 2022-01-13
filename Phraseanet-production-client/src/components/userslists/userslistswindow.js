import $ from 'jquery';
import ListManager from './../list/listManager';
import dialog from './../../phraseanet-common/components/dialog';

const recordslists = (services) => {
    const {configService, localeService, appEvents} = services;
    let listManagerInstance = null;

    const initialize = (options) => {
        if ($('#userslistsBox').length > 0) {
            listManagerInstance = new ListManager(services, options);
        } else {
            $('.close-dialog-action').on('click', () => dialog.close('1'))
        }
    };

    function reloadBridge(url) {
        var options = $('#dialog_publicator form[name="current_datas"]').serializeArray();
        var dialog = dialog.get(1);
        dialog.load(url, 'POST', options);
    }

    function createList(listOptions) {
        listManagerInstance.createList(listOptions);
    }

    function setActiveList() {

    }

    function removeList(listObj) {
        var makeDialog = function (box) {

            var buttons = {};

            buttons[localeService.t('valider')] = function () {

                var callbackOK = function () {
                    $('.list-container ul.list').children().each(function() {
                        if($(this).data('list-id') == listObj.list_id) {
                            $(this).remove();
                        }
                    });
                    dialog.get(2).close();
                };

                listManagerInstance.removeList(listObj.list_id, callbackOK);
            };

            var options = {
                title: localeService.t('Delete the list'),
                cancelButton: true,
                buttons: buttons,
                size: 'Alert'
            };

            const $dialog = dialog.create(services, options, 2);
             if(listObj.container === '#ListManager') {
                $dialog.getDomElement().closest('.ui-dialog').addClass('dialog_delete_list_listmanager');
            }
            $dialog.getDomElement().closest('.ui-dialog').addClass('dialog_container dialog_delete_list')
                .find('.ui-dialog-buttonset button')
                .each( function() {
                    var self = $(this).children();
                    if(self.text() === 'Validate') self.text('Yes')
                    else self.text('No');
                });
            $dialog.setContent(box);
        };

        var html = _.template($('#list_editor_dialog_delete_tpl').html());

        makeDialog(html);
    }

    appEvents.listenAll({
        // 'push.doInitialize': initialize,
        'push.setActiveList': setActiveList,
        'push.createList': createList,
        'push.reload': reloadBridge,
        'push.removeList': removeList
    });

    return {
        initialize,
        // Feedback: Feedback,
        // ListManager: ListManager,
        reloadBridge: reloadBridge,
    };

};

export default recordslists;
