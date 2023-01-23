/**
 * triggered via workzone > Basket > context menu
 */
import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
import merge from 'lodash.merge';

const resultInfos = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let searchSelectionSerialized = '0';
    appEvents.listenAll({
        'broadcast.searchResultSelection': (selection) => {
            updateSelectionCounter(selection.asArray.length);
        }
    });

    const initialize = (options) => {
        let {$container} = options;
        updateSelectionCounter(0);
        $container.on('click', '.search-display-info', (event) => {
            event.preventDefault();
            const $el = $(event.currentTarget);
            let dialogOptions = {};

            if ($el.attr('title') !== undefined) {
                dialogOptions.title = $el.html;
            }

            let dialogContent = $el.data('infos');

            openModal(dialogOptions, dialogContent);
        });
    };
    const render = (template, selectionCount) => {
        $('#tool_results').empty().append(template);
        updateSelectionCounter(selectionCount);
    }

    function number_format (number, decimals, dec_point, thousands_sep) {
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function (n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }

    const updateSelectionCounter = (selectionLength) => {
        $('#nbrecsel').empty().append(number_format(selectionLength, null, null, " "));
    }

    const openModal = (options = {}, content) => {
        const url = configService.get('baseUrl');

        let dialogOptions = merge({
            size: '600x600',
            loading: false
        }, options);

        const $dialog = dialog.create(services, dialogOptions, 1);

        $dialog.setContent(content);
    };

    return {initialize, render};
};

export default resultInfos;
