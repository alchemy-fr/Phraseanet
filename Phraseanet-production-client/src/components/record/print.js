import $ from 'jquery';
const printRecord = (services) => {
    const {configService, localeService, appEvents} = services;
    const url = configService.get('baseUrl');
    let $container = null;

    appEvents.listenAll({
        'record.doPrint': doPrint
    });

    const initialize = () => {
        $container = $('body');
        $container.on('click', '.record-print-action', function (event) {
            event.preventDefault();
            let $el = $(event.currentTarget);
            let key = '';
            let kind = $el.data('kind');
            let idContent = $el.data('id');

            switch (kind) {
                case 'basket':
                    key = 'ssel';
                    break;
                case 'record':
                    key = 'lst';
                    break;
                default:
            }

            doPrint(`${key}=${idContent}`);
        });
    };

    const openModal = (datas) => {
        return doPrint($.param(datas))
    }

    function doPrint(value) {
        if ($('#DIALOG').data('ui-dialog')) {
            $('#DIALOG').dialog('destroy');
        }
        $('#DIALOG').attr('title', localeService.t('print'))
            .empty().addClass('loading')
            .dialog({
                resizable: false,
                closeOnEscape: true,
                modal: true,
                width: '800',
                height: '500',
                open: function (event, ui) {
                    $(this).dialog('widget').css('z-index', '1999');
                },
                close: function (event, ui) {
                    // $(this).dialog('widget').css('z-index', 'auto');
                    $('#DIALOG').dialog('destroy');
                    $('#DIALOG').css('display', 'none');
                }
            })
            .dialog('open');

        $.ajax({
            type: 'POST',
            url: `${url}prod/printer/?${value}`,
            dataType: 'html',
            beforeSend: function () {

            },
            success: function (data) {
                $('#DIALOG').removeClass('loading').empty()
                    .append(data);
                return;
            }
        });
    }

    return {initialize, openModal};
};

export default printRecord;
