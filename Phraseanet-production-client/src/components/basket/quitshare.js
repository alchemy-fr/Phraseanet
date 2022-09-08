import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';

const quitshareBasket = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let $container = null;
    const initialize = () => {
        $container = $('body');
        $container.on('click', '.basket-quitshare-action', function (event) {
            event.preventDefault();
            let $el = $(event.currentTarget);
            quitshareConfirmation($el, $el.data('context'));
        });
    };

    const quitshareConfirmation = ($el, type) => {
        switch (type) {
            case 'SSTT':

                var buttons = {};

                buttons[localeService.t('quitshareTitle')] = function (e) {
                    _quitshareBasket($el);
                };

                let dialogWindow = dialog.create(services, {
                    size: 'Medium',
                    title: localeService.t('attention'),
                    closeButton: true,
                });

                //Add custom class to dialog wrapper
                dialogWindow.getDomElement().closest('.ui-dialog').addClass('black-dialog-wrap');

                let content = '<div class="well-small">' + localeService.t('confirmQuitshare') + '</div>';
                dialogWindow.setContent(content);

                dialogWindow.setOption('buttons', buttons);

                $('#tooltip').hide();
                break;
            default:
        }
    };

    const _quitshareBasket = (item) => {
        let dialogWindow = dialog.get(1);
        dialogWindow.close();

        // id de chutier
        var k = $(item).attr('id').split('_').slice(1, 2).pop();
        $.ajax({
            type: 'POST',
            url: `${url}prod/share/quitshare/${k}/`,
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    var basket = $('#SSTT_' + k);
                    var next = basket.next();

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
    };

    return {initialize, quitshareConfirmation};
};

export default quitshareBasket;
