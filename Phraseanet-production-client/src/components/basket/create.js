/**
 * triggered via workzone > Basket > context menu
 */
import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
import merge from 'lodash.merge';
const basketCreate = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let searchSelectionSerialized = '';
    appEvents.listenAll({
        'broadcast.searchResultSelection': (selection) => {
            searchSelectionSerialized = selection.serialized;
        }
    });

    const initialize = () => {
        $('body').on('click', '.basket-create-action', (event) => {
            event.preventDefault();
            const $el = $(event.currentTarget);
            let dialogOptions = {};

            if ($el.attr('title') !== undefined) {
                dialogOptions.title = $el.attr('title');
            }

            openModal(dialogOptions);
        });
    };

    const openModal = (options = {}) => {

        let dialogOptions = merge({
            size: 'Small',
            loading: false
        }, options);
        const $dialog = dialog.create(services, dialogOptions);

        return $.get(`${url}prod/baskets/create/`, function (data) {
            $dialog.setContent(data);
            _onDialogReady();
            return;
        });
    };

    const _onDialogReady = () => {
        // recordBridge(services).initialize();
        var $dialog = dialog.get(1);
        var $dialogBox = $dialog.getDomElement();

        $('input[name="lst"]', $dialogBox).val(searchSelectionSerialized);

        var buttons = $dialog.getOption('buttons');

        buttons[localeService.t('create')] = function () {
            $('form', $dialogBox).trigger('submit');
        };

        $dialog.setOption('buttons', buttons);

        $('form', $dialogBox).bind('submit', function (event) {

            var $form = $(this);
            var $dialog = $dialogBox.closest('.ui-dialog');
            var buttonPanel = $dialog.find('.ui-dialog-buttonpane');

            // @TODO should be in a service:
            $.ajax({
                type: $form.attr('method'),
                url: $form.attr('action'),
                data: $form.serializeArray(),
                dataType: 'json',
                beforeSend: function () {
                    $(":button:contains('" + localeService.t('create') + "')", buttonPanel)
                        .attr('disabled', true).addClass('ui-state-disabled');
                },
                success: function (data) {
                    appEvents.emit('workzone.refresh', {
                        basketId: data.basket.id
                    });
                    dialog.close(1);

                    return;
                },
                error: function () {
                    $(":button:contains('" + localeService.t('create ') + "')", buttonPanel)
                        .attr('disabled', false).removeClass('ui-state-disabled');
                },
                timeout: function () {

                }
            });

            return false;
        });
    };

    return {initialize};
};

export default basketCreate;
