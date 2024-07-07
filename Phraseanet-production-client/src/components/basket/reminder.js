import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
import merge from "lodash.merge";

const feedbackReminder = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');

    const openModal = (basketId, options = {}) => {
        let dialogOptions = merge({
            size: '558x415',
            loading: false,
            closeButton: true,
        }, options);

        const $dialog = dialog.create(services, dialogOptions);
        $dialog.getDomElement().closest('.ui-dialog').addClass('dialog_container');

        return $.get(`${url}prod/baskets/${basketId}/reminder/`, function (data) {
            $dialog.setContent(data);
            _bindFormEvents();

            return false;
        });
    };

    const _bindFormEvents = () => {
        const $doReminderForm = $('#doReminderForm');

        let $dialog = dialog.get(1);

        $doReminderForm.find(".participant").change(function (e) {
            let allParticipant = $doReminderForm.find("#all-participant").prop('checked');
            if (allParticipant) {
                $doReminderForm.find(".participant[value!=0]").prop('checked', true);
            }
        });

        let  buttons = {};

        buttons[localeService.t('send')] = function () {
            let errorMessage = '';
            let errorContainer = $doReminderForm.find('#reminder-error');

            if (!$doReminderForm.find('input.participant').is(':checked')) {
                errorMessage = '<p>' + localeService.t('reminderParticipantToCheck') + '<p>';
            }

            if ($.trim($doReminderForm.find('#reminder-message').val()) === '') {
                errorMessage += '<p>' + localeService.t('reminderMessageToCheck') + '</p>';
            }

            if (errorMessage !== '') {
                errorContainer.removeClass('hidden');
                errorContainer.empty().append(errorMessage);
            } else {
                $dialog.close();

                $.ajax({
                    type: $doReminderForm.attr('method'),
                    url: $doReminderForm.attr('action'),
                    data: $doReminderForm.serializeArray(),
                    beforeSend: function () {
                    },
                    success: function (datas) {
                        console.log(datas);
                    },
                });
            }

            return false;
        };

        $dialog.setOption('buttons', buttons);
    };

    return {openModal};

};

export default feedbackReminder;
