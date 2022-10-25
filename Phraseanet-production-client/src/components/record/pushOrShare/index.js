import $ from 'jquery';
import pushOrShare from './pushOrShare';
import ListManager from './../../list/listManager';
import dialog from './../../../phraseanet-common/components/dialog';

const pushOrShareIndex = (services) => {
    const {configService, localeService, appEvents} = services;
    let pushOrShareInstance = null;
    let listManagerInstance = null;

    const initialize = (options) => {
        let {container, listManager} = options;
        let $container = $('#PushBox');
        if ($container.length > 0) {
            pushOrShareInstance = new pushOrShare(services, container);
            listManagerInstance = new ListManager(services, listManager);
        }
        else {
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

    function addUser(userOptions) {
        pushOrShareInstance.addUser(userOptions);
    }

    function setActiveList() {

    }

    function removeList(listObj) {
        var makeDialog = function (box) {

            var buttons = {};

            buttons[localeService.t('buttonYes')] = function () {

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

            buttons[localeService.t('buttonNo')] = function () {
                dialog.get(2).close();
            };

            var options = {
                title: localeService.t('DeleteList'),
                buttons: buttons,
                size: 'Alert'
            };

            const $dialog = dialog.create(services, options, 2);
            if(listObj.container === '#ListManager') {
                $dialog.getDomElement().closest('.ui-dialog').addClass('dialog_delete_list_listmanager');
            }

            $dialog.getDomElement().closest('.ui-dialog').addClass('dialog_container dialog_delete_list');

            $dialog.setContent(box);
        };

        var html = _.template($('#list_editor_dialog_delete_tpl').html());

        makeDialog(html);
    }

    appEvents.listenAll({
        // 'push.doInitialize': initialize,
        'push.addUser': addUser,
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

export default pushOrShareIndex;
