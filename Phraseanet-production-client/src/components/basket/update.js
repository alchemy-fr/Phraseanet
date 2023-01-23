/**
 * triggered via workzone > Basket > context menu
 */
import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
import merge from 'lodash.merge';
const humane = require('humane-js');

const basketUpdate = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let basketId = false;
    let searchSelectionSerialized = '';
    appEvents.listenAll({
        'broadcast.searchResultSelection': (selection) => {
            searchSelectionSerialized = selection.serialized;
        }
    });

    const initialize = () => {
        $('body').on('click', '.basket-update-action', (event) => {
            event.preventDefault();
            const $el = $(event.currentTarget);
            let dialogOptions = {};

            if ($el.attr('title') !== undefined) {
                dialogOptions.title = $el.attr('title');
            }
            basketId = $el.data('basket-id');
            openModal(dialogOptions);
        });
    };

    const openModal = (options = {}) => {

        let dialogOptions = merge({
            size: 'Medium',
            loading: false
        }, options);
        const $dialog = dialog.create(services, dialogOptions);

        return $.get(`${url}prod/baskets/${basketId}/update/`, (data) => {
            $dialog.setContent(data);
            _onDialogReady();
            return;
        });
    };

    const _onDialogReady = () => {
        $('form[name="basket-rename-box"]').on('submit', function (event) {
            event.preventDefault();
            onSubmitRenameForm(event);
        });

        $('#basket-rename-box button').on('click', function (event) {
            event.preventDefault();
            onSubmitRenameForm(event);
        });

        var onSubmitRenameForm = function (event) {
            var $form = $(event.currentTarget).closest('form');
            $.ajax({
                type: $form.attr('method'),
                url: $form.attr('action'),
                dataType: 'json',
                data: $form.serializeArray(),
                beforeSend: function () {

                },
                success: function (data) {
                    dialog.get(1).close();
                    if (data.success) {
                        humane.info(data.message);
                        appEvents.emit('workzone.refresh', {
                            basketId: basketId
                        });
                    } else {
                        humane.error(data.message);
                        return false;
                    }
                }
            });

            return false;
        };
    };

    return {initialize};
};

export default basketUpdate;
