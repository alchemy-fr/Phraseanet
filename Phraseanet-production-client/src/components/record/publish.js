import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
import publication from '../publication';

const recordPublishModal = (services, datas) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');

    const openModal = (datas) => {

        $.post(`${url}prod/feeds/requestavailable/`
            , datas
            , function (data) {

                return publication(services).openModal(data);
            }).fail(function (data) {
            if (data.status === 403 && data.getResponseHeader('x-phraseanet-end-session')) {
                self.location.replace(self.location.href); // refresh will redirect to login
            }
        })
        ;

        return true;
    };

    return { openModal };
};

export default recordPublishModal;
