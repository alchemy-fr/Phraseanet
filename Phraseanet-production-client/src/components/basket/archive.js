import $ from 'jquery';

const archiveBasket = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let $container = null;
    const initialize = () => {
        $container = $('body');
        $container.on('click', '.basket-archive-action', function (event) {
            event.preventDefault();
            let $el = $(event.currentTarget);
            doArchive($el.data('basket-id'));
        });
    };

    function doArchive(basketId) {
        $.ajax({
            type: 'POST',
            url: `${url}prod/baskets/${basketId}/archive/?archive=1`,
            dataType: 'json',
            beforeSend: function () {

            },
            success: function (data) {
                if (data.success) {
                    const basket = $('#SSTT_' + basketId);
                    const next = basket.next();

                    if (next.data('ui-droppable')) {
                        next.droppable('destroy');
                    }

                    next.slideUp().remove();

                    if (basket.data('ui-droppable')) {
                        basket.droppable('destroy');
                    }

                    basket.slideUp().remove();

                    if ($('#baskets .SSTT').length === 0) {
                        appEvents.emit('workzone.refresh');
                    }
                } else {
                    alert(data.message);
                }
                return;
            }
        });
    }

    return {initialize};
};

export default archiveBasket;
