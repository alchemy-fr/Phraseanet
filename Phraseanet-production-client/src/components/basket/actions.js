import $ from 'jquery';
import recordFeedbackModal from "../record/feedback";
import recordShareModal from "../record/sharebasket";

const shareBasket = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let $container = null;

    const initialize = () => {
        $container = $('body');

        // basket general menu : click on "share"
        $container.on('click', '.basket-share-action', function (event) {
            event.preventDefault();
            _triggerModal(event, recordShareModal(services).openModal);
        });

        // basket general menu : click on "feedback"
        $container.on('click', '.basket-feedback-action', function (event) {
            event.preventDefault();
            _triggerModal(event, recordFeedbackModal(services).openModal);
        });
    };

    const _triggerModal = (event, actionFn) => {
        event.preventDefault();
        const $el = $(event.currentTarget);
        const basket_id = $el.attr('data-id');
        // console.log("=== clicked with basket_id = ", basket_id);
        let params = {
            ssel: basket_id,
            feedbackaction: 'adduser'
        };
        // console.log("==== ready to open dlg with params: ", params);
        return actionFn.apply(null, [params]);
    };

    return {initialize};
};

export default shareBasket;
