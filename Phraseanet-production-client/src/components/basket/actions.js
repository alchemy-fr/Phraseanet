import $ from 'jquery';
import sharebasketModal from "../record/sharebasketModal";

const shareBasket = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let $container = null;

    const initialize = () => {
        $container = $('body');

        // basket general menu : click on "share"
        $container.on('click', '.basket-share-action', function (event) {
            event.preventDefault();
            _triggerModal(event, sharebasketModal(services).openModal);
        });
    };

    const _triggerModal = (event, actionFn) => {
        event.preventDefault();
        const $el = $(event.currentTarget);
        const basket_id = $el.attr('data-id');

        let params = {
            ssel: basket_id,
            feedbackaction: 'adduser'
        };

        return actionFn.apply(null, [params]);
    };

    return {initialize};
};

export default shareBasket;
