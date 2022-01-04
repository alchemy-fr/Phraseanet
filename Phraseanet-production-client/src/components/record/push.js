import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
import pushRecordWindow from './recordPush/index';

const pushRecord = (services, datas) => {
    const {configService, localeService, appEvents} = services;
    const url = configService.get('baseUrl');


    const openModal = (datas) => {

        let $dialog = dialog.create(services, {
            size: 'Full',
            title: localeService.t('push')
        });

        // add classes to the whoe dialog (including title)
        $dialog.getDomElement().closest('.ui-dialog')
               .addClass('whole_dialog_container')
               .addClass('Push');

        $.post(`${url}prod/push/sendform/`, datas, function (data) {
            $dialog.setContent(data);
            _onDialogReady();
            return;
        });

        return true;
    };

    const _onDialogReady = () => {
        pushRecordWindow(services).initialize({
            feedback: {
                containerId: '#PushBox',
                context: 'Push'
            },
            listManager: {
                containerId: '#ListManager'
            }
        });
    };


    return {openModal};
};

export default pushRecord;
