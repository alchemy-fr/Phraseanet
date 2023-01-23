import $ from 'jquery';
import merge from 'lodash.merge';
const humane = require('humane-js');
/**
 * Editor Right tab plugin
 */

// EditReplace

const searchReplace = (services) => {
    const {configService, localeService, recordEditorEvents} = services;
    let $container = null;
    let tRecords = {};
    let parentOptions = {};
    recordEditorEvents.listenAll({
        'recordEditor.plugin.searchReplace.replace': 'replace'
    });

    const initialize = (options) => {
        let initWith = {$container, parentOptions} = options;
        // recordCollection = parentOptions.recordCollection;
        tRecords = parentOptions.recordCollection.getRecords();


        $($container).on('click', '.record-editor-searchReplace-action', (event) => {
            event.preventDefault();

            let ntRecords = replace(tRecords);

            // @TODO - reactivate humane
            // humane.info($.sprintf(localeService.t('nFieldsChanged', n)));
            recordEditorEvents.emit('recordEditor.onUpdateFields', ntRecords);
        });


        $($container).on('change', '.record-editor-toggle-replace-mode-action', (event) => {
            event.preventDefault();
            _toggleReplaceMode(event.currentTarget);
        });
    };

    const replace = (tRecords) => {
        var field = $('#EditSRField', $container).val();
        var search = $('#EditSearch', $container).val();
        var replace = $('#EditReplace', $container).val();

        var where = $('[name=EditSR_Where]:checked', $container).val();
        var commut = '';
        var rgxp = $('#EditSROptionRX', $container).prop('checked') ? true : false;

        var r_search;
        if (rgxp) {
            r_search = search;
            commut = ($('#EditSR_RXG', $container).prop('checked') ? 'g' : '')
                + ($('#EditSR_RXI', $container).prop('checked') ? 'i' : '');
        } else {
            commut = $('#EditSR_case', $container).prop('checked') ? 'g' : 'gi';
            r_search = '';
            for (let i = 0; i < search.length; i++) {
                var c = search.charAt(i);
                if (('^$[]()|.*+?\\').indexOf(c) !== -1) {
                    r_search += '\\';
                }
                r_search += c;
            }
            if (where === 'exact') {
                r_search = '^' + r_search + '$';
            }
        }

        search = new RegExp(r_search, commut);

        let f;
        let n = 0;
        for (let r in tRecords) {
            if (!tRecords[r]._selected) {
                continue;
            }
            for (f in tRecords[r].fields) {
                if (field === '' || field === f) {
                    n += tRecords[r].fields[f].replaceValue(search, replace);
                }
            }
        }

        return merge({}, tRecords);
    };

    function _toggleReplaceMode(ckRegExp) {

        if (ckRegExp.checked) {
            $('#EditSR_TX', $container).hide();
            $('#EditSR_RX', $container).show();
        } else {
            $('#EditSR_RX', $container).hide();
            $('#EditSR_TX', $container).show();
        }
    }

    return {initialize}
};

export default searchReplace;
