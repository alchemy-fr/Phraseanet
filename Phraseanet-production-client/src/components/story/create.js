/**
 * triggered via workzone > Basket > context menu
 */
import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
import merge from 'lodash.merge';

const storyCreate = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let searchSelectionSerialized = '';

    appEvents.listenAll({
        'broadcast.searchResultSelection': (selection) => {
            searchSelectionSerialized = selection.serialized;
        }
    });

    const initialize = () => {
        $('body').on('click', '.story-create-action', (event) => {
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

        return $.ajax({
            type: 'GET',
            url: `${url}prod/story/create/`,
            data: {
                lst: searchSelectionSerialized
            },
            success: function (data) {
                $dialog.setContent(data);
                _onDialogReady();

                return;
            }
        });
    };

    const _onDialogReady = () => {
        var $dialog = dialog.get(1);
        var $dialogBox = $dialog.getDomElement();

        $('input[name="lst"]', $dialogBox).val(searchSelectionSerialized);

        var buttons = $dialog.getOption('buttons');

        buttons[localeService.t('create')] = function () {
            $('form', $dialogBox).trigger('submit');
        };

        $dialog.setOption('buttons', buttons);

        $('input[name="lst"]', $dialogBox).change(function() {
            if ($(this).is(":checked")) {
                $('form', $dialogBox).addClass('story-filter-db');
            } else {
                $('form', $dialogBox).removeClass('story-filter-db');
            }
        });

        $('form', $dialogBox).bind('submit', function (event) {
            var $form = $(this);

            if ($('input[name="lst"]', $dialogBox).is(":checked") && $('form #multiple_databox', $dialogBox).val() === '1') {
                alert(localeService.t('warning-multiple-databoxes'));
                event.preventDefault();

                return;
            }

            var $dialog = $dialogBox.closest('.ui-dialog');
            var buttonPanel = $dialog.find('.ui-dialog-buttonpane');

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
                        basketId: data.WorkZone,
                        sort: '',
                        scrolltobottom: true,
                        type: 'story'
                    });
                    dialog.close(1);

                    return;
                },
                error: function () {
                    $(":button:contains('" + localeService.t('create') + "')", buttonPanel)
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

export default storyCreate;
