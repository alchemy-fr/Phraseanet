import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
import userslistsWindow from './userslistswindow';

const userslists = (services, datas) => {
    const {configService, localeService, appEvents} = services;
    const url = configService.get('baseUrl');


    const openModal = (datas) => {
        let $dialog = dialog.create(services, {
            size: 'Full',
            title: localeService.t('Users lists')
        });

        // add classes to the whoe dialog (including title)
        $dialog.getDomElement().closest('.ui-dialog')
               .addClass('whole_dialog_container')
               .addClass('userslists');

        $.post(`${url}prod/push/sendform/`, datas, function (data) {
            $dialog.setContent(data);
            _onDialogReady();
            return;
        });

        return true;
    };

    const _onDialogReady = () => {
        userslistsWindow(services).initialize({
            context: '???',
            containerId: '#ListManager'
        });
    };


    return {openModal};
};

export default userslists;
