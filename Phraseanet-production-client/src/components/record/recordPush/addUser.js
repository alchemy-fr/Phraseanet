/**
 * triggered via workzone > Basket > context menu
 */
import $ from 'jquery';
import * as _ from 'underscore';
import dialog from './../../../phraseanet-common/components/dialog';
import merge from 'lodash.merge';
require('geonames-server-jquery-plugin/jquery.geonames.js');

const pushAddUser = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let searchSelectionSerialized = '';
    appEvents.listenAll({
        'broadcast.searchResultSelection': (selection) => {
            searchSelectionSerialized = selection.serialized;
        }
    });

    const initialize = (options) => {
        let {$container} = options;

        $container.on('click', '.push-add-user', (event) => {
            event.preventDefault();
            const $el = $(event.currentTarget);
            let dialogOptions = {};

            if ($el.attr('title') !== undefined) {
                dialogOptions.title = $el.html;
            }

            if($el.hasClass('validation')) {
                dialogOptions.isValidation = true;
            }

            if($el.hasClass('listmanager-add-user')) {
                dialogOptions.isListManager = true;
            }

            openModal(dialogOptions);
        });
    };

    const openModal = (options = {}) => {
        const url = configService.get('baseUrl');
        let dialogOptions = merge({
            size: '558x305',
            loading: false,
            title: localeService.t('create new user'),
        }, options);
        const $dialog = dialog.create(services, dialogOptions, 2);
        $dialog.getDomElement().closest('.ui-dialog').addClass('dialog_container');

        if(dialogOptions.isValidation) {
            $dialog.getDomElement().closest('.ui-dialog').addClass('validation');
        }

        if(dialogOptions.isListManager) {
            $dialog.getDomElement().closest('.ui-dialog').addClass('push-add-user-listmanager');
        }

        return $.get(`${url}prod/push/add-user/`, function (data) {
            $dialog.setContent(data);
            _onDialogReady(window.addUserConfig);
            return;
        });
    };

    const _onDialogReady = (config) => {
        const $addUserForm = $('#quickAddUser');
        const $addUserFormMessages = $addUserForm.find('.messages');

        var closeModal = function () {
            var $dialog = $addUserForm.closest('.ui-dialog-content');
            if ($dialog.data('ui-dialog')) {
                $dialog.dialog('destroy').remove();
            }
        };

        var submitAddUser = function () {
            $addUserFormMessages.empty();
            var method = $addUserForm.attr('method');

            method = $.inArray(method.toLowerCase(), ['post', 'get']) ? method : 'POST';
            $.ajax({
                type: method,
                url: $addUserForm.attr('action'),
                data: $addUserForm.serializeArray(),
                beforeSend: function () {
                    $addUserForm.addClass('loading');
                },
                success: function (datas) {
                    if (datas.success === true) {
                        appEvents.emit('push.addUser', {
                            $userForm: $addUserForm,
                            callback: closeModal()
                        })
                        //p4.Feedback.addUser($addUserForm, closeModal);
                    } else {
                        if (datas.message !== undefined) {
                            $addUserFormMessages.empty().append('<div class="alert alert-error">' + datas.message + '</div>');
                        }
                    }
                    $addUserForm.removeClass('loading');
                },
                error: function () {
                    $addUserForm.removeClass('loading');
                },
                timeout: function () {
                    $addUserForm.removeClass('loading');
                }
            });
        };
        if (config.geonameServerUrl.length > 0) {
            $addUserForm.find('.geoname_field').geocompleter({
                server: config.geonameServerUrl,
                limit: 40
            });
        }
        $addUserForm.on('submit', function (event) {
            event.preventDefault();
            submitAddUser();
        });

        $addUserForm.on('click', '.valid', function (event) {
            event.preventDefault();
            submitAddUser();
        });

        $addUserForm.on('click', '.cancel', function (event) {
            event.preventDefault();
            closeModal();
            return false;
        });
    };

    return {initialize};
};

export default pushAddUser;
