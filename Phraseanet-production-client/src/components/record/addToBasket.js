import $ from 'jquery';

const addToBasket = (services) => {
    const { configService, localeService, appEvents } = services;
    let $container = null;
    const initialize = () => {
        $container = $('body');
        $container.on('click', '.record-add-to-basket-action', (event) => {
            event.preventDefault();
            let $el = $(event.currentTarget);
            let dbId = $el.data('db-id');
            let recordId = $el.data('record-id');
            appEvents.emit('workzone.doAddToBasket', {
                dbId, recordId, event: event.currentTarget, singleSelection: true
            });
        });
    };

    return { initialize };
};

export default addToBasket;
