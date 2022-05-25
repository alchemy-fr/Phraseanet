import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
import pushOrShareIndex from './pushOrShare/index';

const sharebasketModal = (services, datas) => {
    const { configService, localeService, appEvents} = services;
    const url = configService.get('baseUrl');


    const openModal = (datas) => {

        let $dialog = dialog.create(services, {
            size: 'Full',
            title: localeService.t('shareTitle')
        });

        // add classes to the whoe dialog (including title)
        $dialog.getDomElement().closest('.ui-dialog')
               .addClass('whole_dialog_container')
               // .addClass('dialog_container')
               .addClass('Sharebasket');

        $.post(`${url}prod/push/sharebasketform/`, datas, function (data) {
            // data content's javascript can't be fully refactored
            $dialog.setContent(data);
            _onDialogReady();
            return;
        });

        return true;
    };

    const _onDialogReady = () => {
        pushOrShareIndex(services).initialize({
            container: {
                containerId: '#PushBox',
                context: 'Sharebasket'
            },
            listManager: {
                containerId: '#ListManager'
            }
        });
    };

    return {openModal};
};

export default sharebasketModal;
