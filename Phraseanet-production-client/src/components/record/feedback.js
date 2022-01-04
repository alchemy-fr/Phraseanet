import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
import pushRecord from './recordPush/index';

const recordFeedbackModal = (services, datas) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');


    const openModal = (datas) => {
        /* disable push closeonescape as an over dialog may exist (add user) */
        let $dialog = dialog.create(services, {
            size: 'Full',
            title: localeService.t('feedback')
        });

        // add classes to the whoe dialog (including title)
        $dialog.getDomElement().closest('.ui-dialog')
               .addClass('whole_dialog_container')
               .addClass('Feedback');

        $.post(`${url}prod/push/validateform/`, datas, function (data) {
            // data content's javascript can't be fully refactored
            $dialog.setContent(data);
            _onDialogReady();
            return;
        });

        return true;
    };

    const _onDialogReady = () => {
        pushRecord(services).initialize({
            feedback: {
                containerId: '#PushBox',
                context: 'Feedback'
            },
            listManager: {
                containerId: '#ListManager'
            }
        });
    };

    return { openModal };
};

export default recordFeedbackModal;
