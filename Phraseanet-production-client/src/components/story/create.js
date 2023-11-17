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
            size: 'Medium',
            loading: false
        }, options);
        const $dialog = dialog.create(services, dialogOptions);
        $dialog.getDomElement().closest('.ui-dialog').addClass('create-story');

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
            },
            error: function (data) {
                if (data.status === 403 && data.getResponseHeader('x-phraseanet-end-session')) {
                    self.location.replace(self.location.href);  // refresh will redirect to login
                }
            }
        });
    };

    const _onDialogReady = () => {
        var $dialog = dialog.get(1);
        var $dialogBox = $dialog.getDomElement();
        var $selectCollection = $('select[name="base_id"]', $dialogBox);

        $('input[name="lst"]', $dialogBox).val(searchSelectionSerialized);

        if ($('input[name="lst"]', $dialogBox).val() !== '') {
            $('.new_story_add_sel', $dialogBox).removeClass('hidden');

            $('form', $dialogBox).addClass('story-filter-db');

            if ($('form #multiple_databox', $dialogBox).val() === '1') {
                $('input[name="lst"]', $dialogBox).prop('checked', false);
            } else {
                $('input[name="lst"]', $dialogBox).prop('checked', true);
            }
        }

        var buttons = $dialog.getOption('buttons');

        buttons[localeService.t('create')] = function () {
            $('form', $dialogBox).trigger('submit');
        };

        $dialog.setOption('buttons', buttons);

        if ($selectCollection.val() == '') {
            $('.create-story-name', $dialogBox).hide();
            $('.create-story-name input', $dialogBox).prop('disabled', true);
        }

        $selectCollection.change(function () {
            let that = $(this);
            if (that.val() != '') {
                // first hide all input and show only the corresponding field for the selected db
                $('.create-story-name', $dialogBox).hide();
                // mark as disabled to no process the hidden field when submit
                $('.create-story-name input', $dialogBox).prop('disabled', true);
                let sbasId = that.find('option:selected').data('sbas');
                $('.sbas-' + sbasId, $dialogBox).show();
                $('.sbas-' + sbasId + ' input', $dialogBox).prop('disabled', false);
                $('.create-story-name-title', $dialogBox).show();
            } else {
                $('.create-story-name-title', $dialogBox).hide();
                $('.create-story-name', $dialogBox).hide();
                $('.create-story-name input', $dialogBox).prop('disabled', true);
            }
        });

        $('input[name="lst"]', $dialogBox).change(function () {
            let that = this;
            if ($(that).is(":checked")) {
                $('form', $dialogBox).addClass('story-filter-db');
                // unselected if needed
                $('.story-filter-db .not-selected-db').prop('selected', false);

                if ($('form #multiple_databox', $dialogBox).val() === '1') {
                    alert(localeService.t('warning-multiple-databoxes'));

                    $(that).prop('checked', false);
                }
            } else {
                $('form', $dialogBox).removeClass('story-filter-db');
                if ($selectCollection.val() != '') {
                    $('.create-story-name', $dialogBox).hide();
                    $('.create-story-name input', $dialogBox).prop('disabled', true);
                    let sbasId = $selectCollection.find('option:selected').data('sbas');
                    $('.sbas-' + sbasId, $dialogBox).show();
                    $('.sbas-' + sbasId + ' input', $dialogBox).prop('disabled', false);
                    $('.create-story-name-title', $dialogBox).show();
                }
            }
        });

        $('form', $dialogBox).bind('submit', function (event) {
            var $form = $(this);

            if ($('input[name="lst"]', $dialogBox).is(":checked") && $('form #multiple_databox', $dialogBox).val() === '1') {
                alert(localeService.t('warning-multiple-databoxes'));
                event.preventDefault();

                return;
            }

            if ($selectCollection.val() == '') {
                alert(localeService.t('choose-collection'));
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
