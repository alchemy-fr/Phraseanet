import $ from 'jquery';
import orderItem from './orderItem';
import dialog from './../../phraseanet-common/components/dialog';

const order = services => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');

    const initialize = (options = {}) => {
        const { $container } = options;
        $container.on('click', '.order-open-action', function (event) {
            event.preventDefault();
            orderModal(event);
        });
        $container.on('click', '.order-notif', function (event) {
            event.preventDefault();
            $('#notification_box').hide();
            orderModal(event, this);
        });

    };

    const orderModal = (event, id) => {
        var $dialog = dialog.create(services, {
            size: 'Full',
            title: $(event).attr('title')
        });

        $.ajax({
            type: 'GET',
            url: `${url}prod/order/`,
            success: function (data) {
                $dialog.setContent(data);
                _onOrderReady($dialog);
                if ($(id).length) {
                    $('#order_manager').css('opacity',0).hide(function () {
                        console.log($(id).data("id"));
                        $('#order_' + $(id).data("id")).trigger('click');
                    });
                }
            }
        });

        return true;
    };

    const _onOrderReady = $dialog => {
        let filterDateSelected = false;
        let perPage = window.orderData.perPage;
        let date = window.orderData.date;
        let dateSelection = window.orderData.dateSelection;
        let dateSelectionText = window.orderData.dateSelectionText;
        let info = window.orderData.info;
        let tabSelection = window.orderData.tabSelection;

        const FilterTodo = {
            TODO: 'pending',
            PROCESSED: 'processed'
        };
        const FilterDate = {
            NO_FILTER: 'no_filter',
            CURRENT_WEEK: 'current_week',
            PAST_WEEK: 'past_week',
            PAST_MONTH: 'past_month',
            BEFORE: 'before',
            AFTER: 'after'
        };

        /******** functions *********/
        const setDateInPicker = date => {
            if (date !== '') {
                $('#datepicker').val(date);
            }
        };

        const setDateSelection = element => {
            var selection = $(element).attr('name');
            dateSelection = FilterDate[selection];
            setSelectionText(dateSelection);
            clearAll();
            $(element).addClass('active');
        };

        const setSelectionText = dateSelection => {
            if (dateSelection === undefined) {
                return;
            }
            $('.reset-btn').hide();
            if (dateSelection !== FilterDate.NO_FILTER) {
                if (
                    dateSelection === FilterDate.BEFORE ||
                    dateSelection === FilterDate.AFTER
                ) {
                    dateSelectionText =
                        $(
                            `#filter_box tbody tr td button[name=${dateSelection.toUpperCase()}]`
                        ).html() +
                        ' ' +
                        $('#datepicker').val();
                    var obj = {};
                    obj.date = $('#datepicker').val();
                    obj.type = dateSelection;
                    info.limit = obj;
                } else {
                    dateSelectionText = $(
                        `#filter_box tbody tr td button[name=${dateSelection.toUpperCase()}]`
                    ).html();
                    info.limit = null;
                }
                $('#filter-text').text(dateSelectionText);
                $('.reset-btn').show();
            } else {
                $('#filter-text').text(window.orderData.noFilterText);
                info.limit = null;
            }
            info.todo = tabSelection;
            info.start = dateSelection;
        };

        const clearAll = () => {
            $('#filter_box tbody tr td').children('button').each(function () {
                $(this).removeClass('active');
            });
        };

        const performRequest = () => {
            $.ajax({
                type: 'GET',
                url: '../prod/order/',
                data: info,
                success: function (data) {
                    $dialog.setContent(data);
                    _onOrderReady($dialog);
                }
            });
        };

        const toggleFilterDate = () => {
            if (!filterDateSelected) {
                filterDateSelected = true;
                $('#filter-date').addClass('active');
                $('#filter_box').css('display', 'block');
            } else {
                filterDateSelected = false;
                $('#filter-date').removeClass('active');
                $('#filter_box').css('display', 'none');
            }
        };

        $('#datepicker').datepicker({
            beforeShow: () => {
                setTimeout(() => {
                    $('.ui-datepicker').css('z-index', 999999);
                }, 0);
            },
            changeYear: true,
            changeMonth: true,
            dateFormat: 'yy/mm/dd',
            onSelect: value => {
                date = value;
                setSelectionText(dateSelection);
            }
        });

        $('.reset-btn', $dialog.getDomElement()).bind('click', function (e) {
            info.start = FilterDate.NO_FILTER;
            setSelectionText(FilterDate.NO_FILTER);
            $('#date-form').trigger('submit');
        });

        $('#ORDERPREVIEW').tabs({
            activate: function (event, ui) {
                if (ui.newPanel.selector === '#PROCESSED-ORDER') {
                    $('.pager-processed').show();
                    $('.pager-todo').hide();
                    tabSelection = FilterTodo.PROCESSED;
                } else {
                    $('.pager-processed').hide();
                    $('.pager-todo').show();
                    tabSelection = FilterTodo.TODO;
                }
            }
        });

        $('#ORDERPREVIEW').tabs(
            'option',
            'active',
            tabSelection === FilterTodo.PROCESSED ? 1 : 0
        );
        $('.pager-processed').hide();

        $('a.self-ajax', $dialog.getDomElement()).bind('click', function (e) {
            e.preventDefault();
            var url = $(this).attr('href');
            dialog.load(url);
        });

        $(
            '#date-form .toggle-button-text',
            $dialog.getDomElement()
        ).bind('click', function (e) {
            setDateSelection(this);
            //trigger request immediately for this week, past week, past month
            if (
                $(this).attr('name') !== 'BEFORE' &&
                $(this).attr('name') !== 'AFTER'
            ) {
                $('#date-form').trigger('submit');
            }
        });

        $('.pager li', $dialog.getDomElement()).bind('click', function (e) {
            info.todo = tabSelection;
            info.start = dateSelection;
            info['per-page'] = perPage;
            info.page = $(this).find('a').attr('data-page');

            performRequest();
        });

        $('tr.order_row', $dialog.getDomElement())
            .bind('click', function (event) {
                event.preventDefault();
                let orderId = $(this).attr('id').split('_').pop();

                orderItem(services).openModal(orderId);
            })
            .addClass('clickable');

        $('#filter-date a').click(function () {
            toggleFilterDate();
        });

        $('#date-form').submit(function (e) {
            e.preventDefault();
            performRequest();
            toggleFilterDate();
        });

        setDateInPicker(date);
        setSelectionText(dateSelection);

        if (dateSelection !== FilterDate.NO_FILTER) {
            let element = $(
                `#filter_box tbody tr td button[name='${dateSelection.toUpperCase()}]`
            );
            setDateSelection(element);
        }
    };

    return {
        initialize,
        orderModal
    };
};

export default order;
