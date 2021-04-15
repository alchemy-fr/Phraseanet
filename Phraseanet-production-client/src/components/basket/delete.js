import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';

const deleteBasket = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let $container = null;
    const initialize = () => {
        $container = $('body');
        $container.on('click', '.basket-delete-action', function (event) {
            event.preventDefault();
            let $el = $(event.currentTarget);
            deleteConfirmation($el, $el.data('context'));
        });
    };

    const deleteConfirmation = ($el, type) => {
        switch (type) {
            /*case 'IMGT':
            case 'CHIM':

                var lst = '';

                if (type === 'IMGT') {
                    lst = p4.Results.Selection.serialize();
                }
                if (type === 'CHIM') {
                    lst = p4.WorkZone.Selection.serialize();
                }

                _deleteRecords(lst);
                break;
            */
            case 'SSTT':

                var buttons = {};

                buttons[localeService.t('archive')] = function (e) {
                    _archiveBasket($el);
                };

                buttons[localeService.t('deleteTitle')] = function (e) {
                    _deleteBasket($el);
                };

                let dialogWindow = dialog.create(services, {
                    size: 'Medium',
                    title: localeService.t('attention'),
                    closeButton: true,
                });

                //Add custom class to dialog wrapper
                dialogWindow.getDomElement().closest('.ui-dialog').addClass('black-dialog-wrap');

                let content = '<div class="well-small">' + localeService.t('confirmDel') + '</div>';
                dialogWindow.setContent(content);

                dialogWindow.setOption('buttons', buttons);

                $('#tooltip').hide();
                break;
            /*case 'STORY':
                lst = $el.val();
                _deleteRecords(lst);
                break;*/
            default:
        }
    };

    const _deleteBasket = (item) => {
        let dialogWindow = dialog.get(1);
        dialogWindow.close();

        // id de chutier
        var k = $(item).attr('id').split('_').slice(1, 2).pop();
        $.ajax({
            type: 'POST',
            url: `${url}prod/baskets/${k}/delete/`,
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

    const _archiveBasket = (item) => {
        let dialogWindow = dialog.get(1);
        dialogWindow.close();

        let basketId = $(item).attr('id').split('_').slice(1, 2).pop();
        $.ajax({
            type: 'POST',
            url: `${url}prod/baskets/${basketId}/archive/?archive=1`,
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    const basket = $('#SSTT_' + basketId);
                    const basketNext = basket.next();

                    if (basketNext.data('ui-droppable')) {
                        basketNext.droppable('destroy');
                    }

                    basketNext.slideUp().remove();

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

                return false;
            }
        });
    };

    /*const _deleteRecords = (lst) => {
        if (lst.split(';').length === 0) {
            alert(localeService.t('nodocselected'));
            return false;
        }
        let $dialog = dialog.create(services, {
            size: 'Small',
            title: localeService.t('deleteRecords')
        });


        $.ajax({
            type: 'POST',
            url: '../prod/records/delete/what/',
            dataType: 'html',
            data: {lst: lst},
            success: function (data) {
                $dialog.setContent(data);
            }
        });

        return false;
    };*/

    return {initialize, deleteConfirmation};
};

export default deleteBasket;
