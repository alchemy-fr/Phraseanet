import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
import * as _ from 'underscore';
const humane = require('humane-js');

const listShare = (services, options) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let $dialog = null;


    const initialize = () => {
    };


    const openModal = (options) => {
        let { listId, modalOptions, modalLevel } = options;

        $dialog = dialog.create(services, modalOptions, modalLevel);
        $dialog.getDomElement().closest('.ui-dialog').addClass('dialog_container dialog_share_list');

        return $.get(`${url}prod/lists/list/${listId}/share/`, function (data) {
            $dialog.setContent(data);
            onModalReady(listId);
        });
    };

    const onModalReady = (listId) => {
        let $container = $('#ListShare');
        let $completer_form = $('form[name="list_share_user"]', $container);
        let $owners_form = $('form[name="owners"]', $container);
        let $autocompleter = $('input[name="user"]', $completer_form);
        let $dialog = dialog.get(2);

        $completer_form.bind('submit', function () {
            return false;
        });

        $('select[name="role"]', $owners_form).bind('change', function (event) {
            event.preventDefault();
            let $el = $(event.currentTarget);
            const userId = $el.data('user-id');

            shareWith(userId, $el.val());

            return false;
        });
        $container.on('click', '.listmanager-share-delete-user-action', (event) => {
            event.preventDefault();
            let $el = $(event.currentTarget);
            const userId = $el.data('user-id');

            let $owner = $el.closest('.owner');

            unShareWith(userId, function (data) {
                $owner.remove();
            });

            return false;
        });


        function shareWith(userId, role) {
            role = typeof role === 'undefined' ? 1 : role;

            $.ajax({
                type: 'POST',
                url: `${url}prod/lists/list/${listId}/share/${userId}/`,
                dataType: 'json',
                data: {role: role},
                beforeSend: function () {
                },
                success: function (data) {
                    if (data.success) {
                        humane.info(data.message);
                    } else {
                        humane.error(data.message);
                    }

                    $('.push-list-share-action').trigger('click');
                    $dialog.refresh();

                    return;
                }
            });
        }

        function unShareWith(userId, callback) {
            $.ajax({
                type: 'POST',
                url: `${url}prod/lists/list/${listId}/unshare/${userId}/`,
                dataType: 'json',
                data: {},
                beforeSend: function () {
                },
                success: function (data) {
                    if (data.success) {
                        humane.info(data.message);
                        callback(data);
                    } else {
                        humane.error(data.message);
                    }
                    $dialog.refresh();

                    return;
                }
            });
        }

        $autocompleter.autocomplete({
                minLength: 2,
                source: function (request, response) {
                    $.ajax({
                        url: `${url}prod/push/search-user/`,
                        dataType: 'json',
                        data: {
                            query: request.term
                        },
                        success: function (data) {
                            response(data);
                        }
                    });
                },
                select: function (event, ui) {
                    if (ui.item.type === 'USER') {
                        shareWith(ui.item.usr_id);
                    }

                    return false;
                }
            })
            .data('ui-autocomplete')._renderItem = function (ul, item) {
            if (item.type === 'USER') {
                var html = _.template($('#list_user_tpl').html())({
                    item: item
                });

                return $(html).data('ui-autocomplete-item', item).appendTo(ul);
            }
        };
    };


    return {
        openModal
    };
};

export default listShare;
