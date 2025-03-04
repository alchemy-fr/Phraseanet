/**
 * triggered via workzone > Basket > context menu
 */
import $ from 'jquery';
import * as _ from 'underscore';
import dialog from './../../phraseanet-common/components/dialog';
import Selectable from '../utils/selectable';
import merge from 'lodash.merge';
const basketReorderContent = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let searchSelectionSerialized = '';
    appEvents.listenAll({
        'broadcast.searchResultSelection': (selection) => {
            searchSelectionSerialized = selection.serialized;
        }
    });

    const initialize = () => {
        $('body').on('click', '.basket-reorder-content-action', (event) => {
            event.preventDefault();
            const $el = $(event.currentTarget);
            let dialogOptions = {};

            if ($el.attr('title') !== undefined) {
                dialogOptions.title = $el.attr('title');
            }

            openModal($el.data('basket-id'), dialogOptions);
        });
    };

    const openModal = (basketId, options = {}) => {

        let dialogOptions = merge({
            size: 'Medium',
            loading: true
        }, options);
        const $dialog = dialog.create(services, dialogOptions);

        return $.get(`${url}prod/baskets/${basketId}/reorder/`, function (data) {
            $dialog.setContent(data);
            _onDialogReady();

            return;
        }).fail(function (data) {
            if (data.status === 403 && data.getResponseHeader('x-phraseanet-end-session')) {
                self.location.replace(self.location.href); // refresh will redirect to login
            }
        })
            ;
    };

    const _onDialogReady = () => {
        var container = $('#reorder_box');

        $('button.autoorder', container).bind('click', function () {
            autoorder();
            return false;
        });
        $('button.reverseorder', container).bind('click', function () {
            reverse_order();
            return false;
        });

        function autoorder() {
            var val = $.trim($('#auto_order').val());

            if (val === '') {
                return;
            }

            var diapos = [];
            $('#reorder_box .diapo form').each(function (i, n) {
                diapos.push({
                    title: $('input[name=title]', n).val(),
                    order: parseInt($('input[name=default]', n).val(), 10),
                    id: $('input[name=id]', n).val(),
                    date_created: new Date($('input[name=date_created]', n).val()),
                    date_updated: new Date($('input[name=date_updated]', n).val()),
                });
            });

            var elements = [];
            var sorterCallback;

            if (val === 'default') {
                sorterCallback = function (diapo) {
                    return diapo.order;
                };
                elements = sorting(sorterCallback, diapos, false);
            } else if(val === 'date_updated' || val === 'date_created'){
                sorterCallback = function(diapo) {
                    if(val === 'date_created') {
                        return diapo.date_created;
                    }
                    return diapo.date_updated;
                };
                elements = sorting(sorterCallback, diapos, true);
            } else {
                sorterCallback = function(diapo) {return diapo.title.toLowerCase();};
                elements = sorting(sorterCallback, diapos, false);
            }

            $('#reorder_box .elements').append(elements);
        }

        function sorting(sorterCallback, diapos, reverse) {
            var elements = [];
            if(reverse == true) {
                _.chain(diapos)
                    .sortBy(sorterCallback)
                    .reverse()
                    .each(function(diapo) {
                        elements.push($('#ORDER_'+ diapo.id));
                    });
            }else {
                _.chain(diapos)
                    .sortBy(sorterCallback)
                    .each(function(diapo) {
                        elements.push($('#ORDER_'+ diapo.id));
                    });
            }
            return elements;
        }

        function reverse_order() {
            var $container = $('#reorder_box .elements');
            $('#reorder_box .diapo').each(function () {
                $(this).prependTo($container);
            });
        }

        ('.elements div', container).bind('click', function(){
            $(this).addClass("selected").siblings().removeClass("selected");
            return false;
        });

        $('.elements', container).sortable({
            appendTo: container,
            placeholder: 'diapo ui-sortable-placeholder',
            distance: 20,
            cursorAt: {
                top: 10,
                left: -20
            },
            items: 'div.diapo',
            scroll: true,
            scrollSensitivity: 40,
            scrollSpeed: 30,
            helper: function (e, item) {
                var elements = $('.selected', container).not('.ui-sortable-placeholder').clone();
                var helper = $('<div/>');
                item.siblings('.selected').addClass('hidden');
                return helper.append(elements);
            },
            start: function (event, ui) {
                // var len = ui.helper.children().length;
                // var currentWidth = ui.helper.width();
                // var itemWidth = ui.item.width();
                // ui.helper.width(currentWidth + (len * itemWidth));
                // ui.placeholder.width((len * itemWidth))

                var elementsPrev = ui.item.prevAll('.selected.hidden').not('.ui-sortable-placeholder');
                if (elementsPrev.length > 0) {
                    ui.item.data('itemsPrev', elementsPrev);
                }
                var elementsNext = ui.item.nextAll('.selected.hidden').not('.ui-sortable-placeholder');
                if (elementsNext.length > 0) {
                    ui.item.data('itemsNext', elementsNext);
                }
            },
            stop: function (event, ui) {
                if (ui.item.data('itemsPrev') !== undefined) {
                    var lastBefore = ui.item[0];
                    $(ui.item.data('itemsPrev')).each(function (i, n) {
                        $(lastBefore).before($(n));
                        lastBefore = n;
                    })
                }

                if (ui.item.data('itemsNext') !== undefined) {
                    $(ui.item[0]).after($(ui.item.data('itemsNext')));
                }

                ui.item.siblings('.selected').removeClass('hidden');
            },

        }).disableSelection();

        var OrderSelection = new Selectable(services, $('.elements', container), {
            selector: '.CHIM'
        });

        $('form[name="reorder"] .btn').bind('click', function (event) {

            $(this).attr('disabled', 'disabled');
            $(this).closest('div#reorder_options').find('div.loading').removeClass('hidden');

            var $form = $(this).closest('form');


            $('.elements form', container).each(function (i, el) {
                var id = $('input[name="id"]', $(el)).val();

                $('input[name="element[' + id + ']"]', $form).val(i + 1);
            });

            $.ajax({
                type: $form.attr('method'),
                url: $form.attr('action'),
                data: $form.serializeArray(),
                dataType: 'json',
                beforeSend: function () {

                },
                success: function (data) {
                    if (!data.success) {
                        alert(data.message);
                    }
                    appEvents.emit('workzone.refresh', {
                        basketId: 'current'
                    });
                    dialog.get(1).close();

                    return;
                }
            });

            return false;
        });
    };

    return {initialize};
};

export default basketReorderContent;
