import $ from 'jquery';

const removeFromBasket = (services) => {
    const { configService, localeService, appEvents } = services;
    let $container = null;
    const initialize = () => {
        $container = $('body');
        $container.on('click', '.record-remove-from-basket-action', (event) => {
            event.preventDefault();
            appEvents.emit('workzone.doRemoveFromBasket', {
                event: event.currentTarget,
                confirm: false
            });
        });
    };

    return { initialize };
};

export default removeFromBasket;
