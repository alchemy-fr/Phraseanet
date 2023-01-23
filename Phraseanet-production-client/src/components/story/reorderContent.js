/**
 * triggered via workzone > Basket > context menu
 */
import $ from 'jquery';
import * as _ from 'underscore';
import dialog from './../../phraseanet-common/components/dialog';
import Selectable from '../utils/selectable';
import merge from 'lodash.merge';

const storyReorderContent = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');

    const initialize = () => {
        $('body').on('click', '.story-reorder-content-action', (event) => {
            event.preventDefault();
            const $el = $(event.currentTarget);
            let dialogOptions = {};

            if ($el.attr('title') !== undefined) {
                dialogOptions.title = $el.attr('title');
            }

            openModal($el.data('db-id'), $el.data('record-id'), dialogOptions);
        });
    };

    const openModal = (dbId, recordId, options = {}) => {

        let dialogOptions = merge({
            size: 'Medium',
            loading: false
        }, options);
        const $dialog = dialog.create(services, dialogOptions);
// /prod/story/1/62/reorder/
        return $.get(`${url}prod/story/${dbId}/${recordId}/reorder/`, function (data) {
            $dialog.setContent(data);
            _onDialogReady();
            return;
        });
    };

    const _onDialogReady = () => {
        var optionsContainer = $('#reorder_options');
        var container = $('#reorder_box');

        $('button.autoorder', optionsContainer).bind('click', function () {
            autoorder();

            return false;
        });

        $('button.reverseorder', optionsContainer).bind('click', function () {
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
            start: function (event, ui) {
                var selected = $('.selected', container);

                selected.each(function (i, n) {
                    $(n).attr('position', i);
                });

                var n = selected.length - 1;

                $('.selected:visible', container).hide();

                while (n > 0) {
                    $('<div style="height:130px;" class="diapo ui-sortable-placeholderfollow"></div>').after($('.diapo.ui-sortable-placeholder', container));
                    n--;
                }
            },
            stop: function (event, ui) {

                $('.diapo.ui-sortable-placeholderfollow', container).remove();

                var main_id = $(ui.item[0]).attr('id');

                var selected = $('.selected', container);
                var sorter = [];


                selected.each(function (i, n) {

                    var position = parseInt($(n).attr('position'), 10);

                    if (position !== '') {
                        sorter[position] = $(n);
                    }

                    var id = $(n).attr('id');
                    if (id === main_id) {
                        return;
                    }

                });

                var before = true;
                var last_moved = $(ui.item[0]);
                $(sorter).each(function (i, n) {
                    $(n).show().removeAttr('position');
                    if ($(n).attr('id') === main_id) {
                        before = false;
                    } else {
                        if (before) {
                            $(n).before($(ui.item[0]));
                        } else {
                            $(n).after($(last_moved));
                        }

                    }
                    last_moved = sorter[i];
                });

            },
            change: function () {
                $('.diapo.ui-sortable-placeholderfollow', container).remove();

                var n = OrderSelection.length() - 1;
                while (n > 0) {
                    $('<div style="height:130px;" class="diapo ui-sortable-placeholderfollow"></div>').after($('.diapo.ui-sortable-placeholder', container));
                    n--;
                }
            }

        }).disableSelection();

        var OrderSelection = new Selectable(services, $('.elements', container), {
            selector: '.CHIM'
        });


        $('form[name="reorder"] .btn').bind('click', function (event) {
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
                        basketId: 'current',
                        sort: null,
                        scrolltobottom: false,
                        type: 'story'
                    });
                    dialog.get(1).close();

                    return;
                },
                error: function () {

                },
                timeout: function () {

                }
            });

            return false;
        });
    };

    return {initialize};
};

export default storyReorderContent;
