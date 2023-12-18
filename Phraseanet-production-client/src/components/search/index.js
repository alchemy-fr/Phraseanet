import * as Rx from 'rx';
import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
import merge from 'lodash.merge';
import resultInfos from './resultInfos';
import workzoneFacets from '../ui/workzone/facets/index';
import Selectable from '../utils/selectable';
let lazyload = require('jquery-lazyload');
require('./../../phraseanet-common/components/tooltip');
require('./../../phraseanet-common/components/vendors/contextMenu');

import searchForm from './searchForm';

const search = services => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let searchPromise = {};
    let searchResult = {
        selection: false,
        navigation: {
            tot: 0, // p4.tot in record preview
            tot_options: false, // datas.form; // p4.tot_options common/tooltip
            tot_query: false, // datas.query; // p4.tot_query
            perPage: 0,
            page: 0
        }
    };
    let $searchForm = null;
    let $searchResult = null;
    let answAjaxrunning = false;
    let resultInfoView;
    let facets = null;
    var lastFilterResults = [];
    let savedHiddenFacetsList = configService.get('savedHiddenFacetsList') ? JSON.parse(configService.get('savedHiddenFacetsList')) : [];


    const initialize = () => {
        $searchForm = $('#searchForm');
        searchForm(services).initialize({
            $container: $searchForm
        });

        $searchResult = $('#answers');

        resultInfoView = resultInfos(services);
        resultInfoView.initialize({
            $container: $('#answers_status')
        });

        searchResult.selection = new Selectable(services, $searchResult, {
            selector: '.IMGT',
            limit: 800,
            selectStart: function (event, selection) {
                $('#answercontextwrap table:visible').hide();
            },
            selectStop: function (event, selection) {
                appEvents.emit('search.doRefreshSelection');
            },
            callbackSelection: function (element) {
                var elements = $(element).attr('id').split('_');

                return elements
                    .slice(elements.length - 2, elements.length)
                    .join('_');
            }
        });
        // map events to result selection:
        appEvents.listenAll({
            'search.selection.selectAll': () =>
                searchResult.selection.selectAll(),
            'search.selection.unselectAll': () =>
                searchResult.selection.empty(),
            'search.selection.selectByType': dataType =>
                searchResult.selection.select(dataType.type),
            'search.selection.remove': data =>
                searchResult.selection.remove(data.records)
        });

        $searchResult
            .on('click', '.search-navigate-action', event => {
                event.preventDefault();
                let $el = $(event.currentTarget);
                navigate($el.data('page'));
            })
            .on('keypress', '.search-navigate-input-action', event => {
                // event.preventDefault();
                let $el = $(event.currentTarget);
                let inputPage = $el.val();
                let initialPage = $el.data('initial-value');
                let totalPages = $el.data('total-pages');

                if (isNaN(inputPage)) {
                    event.preventDefault();
                }
                if (event.keyCode === 13) {
                    if (inputPage > 0 && inputPage <= totalPages) {
                        navigate(inputPage);
                    } else {
                        navigate(totalPages);
                    }
                }
            });

        window.searchResult = searchResult;
        window.dialog = dialog;
    };

    const getResultSelectionStream = () => searchResult.selection.stream;
    let resultNavigationStream = new Rx.Subject();
    const getResultNavigationStream = () => resultNavigationStream; //Rx.Observable.ofObjectChanges(searchResult.navigation);
    //const getResultNavigationStream = () => Rx.Observable.ofObjectChanges(searchResult.navigation);

    const newSearch = query => {
        searchResult.selection.empty();

        clearAnswers();
        //$('#SENT_query').val(query);
        if (query !== null) {
            var histo = $('#history-queries ul');
            histo.prepend('<li onclick="doSpecialSearch(\'' + query.replace(/\'/g, "\\'") + '\')">' + query + '</li>');
            var lis = $('li', histo);
            if (lis.length > 25) {
                $('li:last', histo).remove();
            }
        }

        $('#idFrameC li.proposals_WZ').removeClass('active');
        appEvents.emit('search.doRefreshState');
        return false;
    };

    /**
     *
     */
    const doRefreshState = () => {

        // get the selectedFacets from the facets module
        let selectedFacets = {};
        appEvents.emit('facets.getSelectedFacets', function(v) {
            selectedFacets = v;
        });

        let data = $searchForm.serializeArray();
        // fix bug : if a sb is dual checked, both values are sent with the SAME name
        //     we can remove those since it means we don't care about this sb

        // /!\ silly fixed bug : in sb[] we will test if a key exists using "_undefined()"
        //     BUT sb["sort”] EXISTS ! it is the array.sort() function !
        //     so the side effect in "_filter()" was that data["sort"] was removed.
        //     quick solution : prefix the key with "k_"

        var sb = [];
        _.each(data, function (v) {
             var name = "k_" + v.name;
                 if (name.substr(0, 9) === "k_status[") {
                    if (_.isUndefined(sb[name])) {
                        sb[name] = 0;
                     }
                 sb[name]++; // so sb["k_x"] is the number of occurences of sb checkbox named "x"
                }
        });
        // now if a sb checkbox appears 2 times, it is removed from data
        data = _.filter(data, function (e) {
            return (_.isUndefined(sb["k_" + e.name])) || (sb["k_" + e.name] === 1);
        });
        // end of sb fix

        var jsonData = serializeJSON(data, selectedFacets);
        var qry = buildQ(jsonData.query);

        data.push({
                name: 'jsQuery',
                value: JSON.stringify(jsonData)
            },
            {
                name: 'qry',
                value: qry
            });
        console.log(jsonData);

        let searchPromise = {};
        searchPromise = $.ajax({
            type: 'POST',
            url: `${url}prod/query/`,
            data: data,
            dataType: 'json',
            beforeSend: function (formData) {
                if (answAjaxrunning && searchPromise.abort !== undefined) {
                    searchPromise.abort();
                }
                beforeSearch();
            },
            error: function (data) {
                answAjaxrunning = false;
                $searchResult.removeClass('loading');
                if (data.status === 403 && data.getResponseHeader('x-phraseanet-end-session')) {
                    self.location.replace(self.location.href);  // refresh will redirect to login
                }
            },
            timeout: function () {
                answAjaxrunning = false;
                $('#answers').removeClass('loading');
            },
            success: function (datas) {
                $searchResult
                    .empty()
                    .append(datas.results)
                    .removeClass('loading');

                $('img.lazyload', $searchResult).lazyload({
                    container: $('#answers')
                });

                //load last result collected or [] if length == 0
                if (!datas.facets) {
                    datas.facets = [];
                }

                facets = datas.facets;

                $searchResult.append(
                    '<div id="paginate"><div class="navigation"><div id="tool_navigate"></div></div></div>'
                );

                resultInfoView.render(
                    datas.infos,
                    searchResult.selection.length()
                );
                $('#tool_navigate').empty().append(datas.navigationTpl);

                // @TODO refactor
                $.each(searchResult.selection.get(), function (i, el) {
                    $('#IMGT_' + el).addClass('selected');
                });

                searchResult.navigation = merge(
                    searchResult.navigation,
                    datas.navigation,
                    {
                        tot: datas.total_answers,
                        tot_options: datas.form,
                        tot_query: datas.query
                    }
                );
                resultNavigationStream.onNext(searchResult.navigation);

                if (datas.next_page) {
                    $('#NEXT_PAGE, #answersNext').bind('click', function () {
                        navigate(datas.next_page);
                    });
                } else {
                    $('#NEXT_PAGE').unbind('click');
                }

                if (datas.prev_page) {
                    $('#PREV_PAGE').bind('click', function () {
                        navigate(datas.prev_page);
                    });
                } else {
                    $('#PREV_PAGE').unbind('click');
                }

                // emptying the facets filter in search zone
                $('#facet_filter_in_search').empty();

                updateHiddenFacetsListInPrefsScreen();
                appEvents.emit('search.doAfterSearch');
                appEvents.emit('search.updateFacetData');
            }
        });
        /*script for pagination*/
        setTimeout(function(){
            if ($( "#tool_navigate").length) {
                $("#tool_navigate .btn-mini").last().addClass("last");
            }
        }, 5000);

    };

    let playFirstQuery = function playFirstQuery() {
        // if defined, play the first query
        //
        try {
            var jsq = $("#FIRST_QUERY_CONTAINER");
            if (jsq.length > 0) {
                // there is a query to play
                if (jsq.data('format') === "json") {
                    // json
                    jsq = JSON.parse(jsq.text());
                    // restoreJsonQuery(jsq, true);
                    appEvents.emit('searchAdvancedForm.restoreJsonQuery', {'jsq':jsq, 'submit':true});
                }
                else {
                    // text : do it the old way : restore only fulltext and submit
                    searchForm.trigger('submit');;
                }
            }
        } catch (e) {
            // malformed jsonquery ?
            // no-op
            // console.error(e);
        }
    }

    const updateHiddenFacetsListInPrefsScreen = () => {
        const $hiddenFacetsContainer = $('.card-body').find('.hiddenFiltersListContainer');
        if (savedHiddenFacetsList.length > 0) {
            $hiddenFacetsContainer.empty();
            _.each(savedHiddenFacetsList, function (value) {
                var $html = $('<span class="hiddenFacetFilter" data-name="' + value.name + '"><span class="hiddenFacetFilter-label" title="'
                    + value.title + '">' + value.title
                    + '<span class="hiddenFacetFilter-gradient">&nbsp;</span></span><a class="remove-btn"></a></span>');

                $hiddenFacetsContainer.append($html);

                $('.remove-btn').on('click', function () {
                    let name = $(this).parent().data('name');
                    savedHiddenFacetsList = _.reject(savedHiddenFacetsList, function (obj) {
                        return (obj.name === name);
                    });
                    $(this).parent().remove();
                    appEvents.emit('searchAdvancedForm.saveHiddenFacetsList', savedHiddenFacetsList);
                    updateFacetData();
                });
            });
        }

    };

    const beforeSearch = () => {
        if (answAjaxrunning) {
            return;
        }
        answAjaxrunning = true;

        clearAnswers();
        $('#tooltip').css({
            display: 'none'
        });
        $searchResult.addClass('loading').empty();
        $('#answercontextwrap').remove();
    };

    const afterSearch = () => {
        if ($('#answercontextwrap').length === 0) {
            $('body').append('<div id="answercontextwrap"></div>');
        }

        $.each($('.contextMenuTrigger', $searchResult), function () {
            var id = $(this)
                .closest('.IMGT')
                .attr('id')
                .split('_')
                .slice(1, 3)
                .join('_');

            $(this).contextMenu('#IMGT_' + id + ' .answercontextmenu', {
                appendTo: '#answercontextwrap',
                openEvt: 'click',
                dropDown: true,
                theme: 'vista',
                showTransition: 'slideDown',
                hideTransition: 'hide',
                shadow: false
            });
        });

        answAjaxrunning = false;
        $searchResult.removeClass('loading');
        $('.captionTips, .captionRolloverTips').tooltip({
            delay: 0,
            delayOptions: {},
            isBrowsable: false,
            extraClass: 'caption-tooltip-container'
        });
        $('.infoTips').tooltip({
            delay: 0
        });
        $('.previewTips').tooltip({
            fixable: true
        });
        $('.thumb .rollovable').hover(
            function () {
                $('.rollover-gif-hover', this).show();
                $('.rollover-gif-out', this).hide();
            },
            function () {
                $('.rollover-gif-hover', this).hide();
                $('.rollover-gif-out', this).show();
            }
        );
        $('div.IMGT', $searchResult).draggable({
            helper: function () {
                $('body').append(
                    '<div id="dragDropCursor" style="position:absolute;z-index:9999;background:red;-moz-border-radius:8px;-webkit-border-radius:8px;"><div style="padding:2px 5px;font-weight:bold;">' +
                        searchResult.selection.length() +
                        '</div></div>'
                );
                return $('#dragDropCursor');
            },
            scope: 'objects',
            distance: 20,
            scroll: false,
            cursorAt: {
                top: -10,
                left: -20
            },
            start: function (event, ui) {
                if (!$(this).hasClass('selected')) {
                    return false;
                }
            }
        });
        appEvents.emit('ui.linearizeUi');
    };

    const clearAnswers = () => {
        $('#formAnswerPage').val('');
        $('#searchForm input[name="nba"]').val('');
        $($searchResult, '#dyn_tool').empty();
    };

    const navigate = page => {
        $('#searchForm input[name="sel"]').val(
            searchResult.selection.serialize()
        );
        $('#formAnswerPage').val(page);
        appEvents.emit('search.doRefreshState');
    };

    const updateFacetData = () => {
        appEvents.emit('facets.doLoadFacets', {
            facets: facets,
            filterFacet: $('.look_box_settings input[name=filter_facet]').prop('checked'),
            facetOrder: $('.look_box_settings select[name=orderFacet]').val(),
            facetValueOrder: $('.look_box_settings select[name=facetValuesOrder]').val(),
            hiddenFacetsList: savedHiddenFacetsList
        });
    };

    const reloadHiddenFacetList = (hiddenFacetsList) => {
        savedHiddenFacetsList = hiddenFacetsList;
        updateHiddenFacetsListInPrefsScreen();
    }

    /**
     * restore the advansearch ux from a json-query
     * elements are restored thank's to custom properties ("_xxx") included in json.
     * nb : for now, _ux_ facets can't be restored _before_sending_the_query_,
     *      but since "selectedFacets" (js) IS restored, sending the query WILL restore facets.
     *
     * @param jsq
     * @param submit
     */
    function serializeJSON(data, selectedFacets) {

        var json = {},
            obj = {},
            bases = [],
            statuses = [],
            fields = [],
            aggregates = [];

        $.each(data, function (i, el) {
            obj[el.name] = el.value;

            var col = parseInt(el.value);

            if (el.name === 'bases[]') {
                bases.push(col);
            }
        });

        var _tmpStat = [];
        $('#ADVSRCH_SB_ZONE INPUT[type=checkbox]:checked').each(function (k, o) {
            o = $(o);
            var b = o.data('sbas_id');
            var i = o.data('sb');
            var v = o.val();
            if (_.isUndefined(_tmpStat[b])) {
                _tmpStat[b] = [];
            }
            if (_.isUndefined(_tmpStat[b][i])) {
                // first check
                _tmpStat[b][i] = v;
            } else {
                // both checked
                _tmpStat[b][i] = -1;
            }
        });
        _.each(_tmpStat, function (v, sbas_id) {
            var status = [];
            _.each(v, function (v, sb_index) {
                if (v !== -1) {
                    // ignore both checked
                    status.push({
                        'index': sb_index,
                        'value': v === '1'
                    });
                }
            });
            statuses.push({
                'databox': sbas_id,
                'status': status
            });
        });

        $('.term_select_field').each(function (i, el) {
            if ($(el).val()) {
                let operator = '';
                let value = '';

                switch ($(el).next().val()) {
                    case "set":
                        operator = "=";
                        value    = "_set_";

                        break;
                    case "unset":
                        operator = "=";
                        value    = "_unset_";

                        break;
                    case "=":
                    case ":":
                    case ">=":
                    case "<=":
                    case ">":
                    case "<":
                        operator = $(el).next().val();
                        value    = $(el).next().next().val();

                        break;
                    default:
                        operator = "=";
                        value    = $(el).next().next().val();

                        break;
                }

                fields.push({
                    'type': 'TEXT-FIELD',
                    'field': $(el).val(),
                    'operator': operator,
                    'value': value,
                    "enabled": true
                });
            }
        });

        _.each(selectedFacets, function(facets) {
            _.each(facets.values, function(facetValue) {
                aggregates.push({
                    'type'   : facetValue.value.type,
                    'field'  : facetValue.value.field,
                    'value'  : facetValue.value.raw_value,
                    'query'  : facetValue.value.query,
                    'negated': facetValue.negated,
                    'enabled': facetValue.enabled
                });
            });
        });

        var date_field = $('#ADVSRCH_DATE_ZONE select[name=date_field]', 'form.phrasea_query .adv_options').val();
        var date_from = $('#ADVSRCH_DATE_ZONE input[name=date_min]', 'form.phrasea_query .adv_options').val();
        var date_to = $('#ADVSRCH_DATE_ZONE input[name=date_max]', 'form.phrasea_query .adv_options').val();

        json['sort'] = {
            'field': obj.sort,
            'order': obj.ord
        };
        json['perpage'] = parseInt($('#nperpage_value').val());
        json['page'] = obj.pag === '' ? 1 : parseInt(obj.pag);
        json['use_truncation'] = obj.truncation === 'on' ? true : false;
        json['phrasea_recordtype'] = obj.search_type == 1 ? 'STORY' : 'RECORD';
        json['phrasea_mediatype'] = obj.record_type.toUpperCase();
        json['bases'] = bases;
        json['statuses'] = statuses;
        json['query'] = {
            '_ux_zone': $('.menu-bar .selectd').text().trim().toUpperCase(),
            'type': 'CLAUSES',
            'must_match': 'ALL',
            'enabled': true,
            'clauses': [{
                '_ux_zone': 'FULLTEXT',
                'type': 'FULLTEXT',
                'value': obj.fake_qry,
                'enabled': obj.fake_qry !== ''
            }, {
                '_ux_zone': 'FIELDS',
                'type': 'CLAUSES',
                'must_match': obj.must_match,
                'enabled': true,
                'clauses': fields
            }, {
                '_ux_zone': 'DATE-FIELD',
                'type': 'DATE-FIELD',
                'field': date_field,
                'from': date_from,
                'to': date_to,
                "enabled": true
            }, {
                '_ux_zone': 'AGGREGATES',
                'type': 'CLAUSES',
                'must_match': 'ALL',
                'enabled': true,
                'clauses': aggregates
            }]
        };
        json['_selectedFacets'] = selectedFacets;

        return json;
    }

    var _ALL_Clause_ = "created_on>0";

    function pjoin(glue, a)
    {
        var r = a.join(glue);
        return a.length===1 ? r : ('('+r+')');
    }

    function buildQ(clause) {
        if (clause.enabled === false) {
            return "";
        }
        switch (clause.type) {
            case "CLAUSES":
                var t_pos = [];
                var t_neg = [];
                for (var i = 0; i < clause.clauses.length; i++) {
                    var _clause = clause.clauses[i];
                    var _sub_q = buildQ(_clause);
                    if (_sub_q !== "()" && _sub_q !== "") {
                        if (_clause.negated === true) {
                            t_neg.push(_sub_q);
                        } else {
                            t_pos.push(_sub_q);
                        }
                    }
                }
                if (t_pos.length > 0) {
                    // some "yes" clauses
                    if (t_neg.length > 0) {
                        // some "yes" and and some "neg" clauses
                        if (clause.must_match === "ONE") {
                            // some "yes" and and some "neg" clauses, one is enough to match
                            var neg = "(" + _ALL_Clause_ + " EXCEPT " + pjoin(" OR ", t_neg) + ")";
                            t_pos.push(neg);
                            return "(" + t_pos.join(" OR ") + ")";
                        } else {
                            // some "yes" and and some "neg" clauses, all must match
                            return "(" + pjoin(" AND ", t_pos) + " EXCEPT " + pjoin(" OR ", t_neg) + ")";
                        }
                    } else {
                        // only "yes" clauses
                        return pjoin(clause.must_match=="ONE" ? " OR " : " AND ", t_pos);
                    }
                } else {
                    // no "yes" clauses
                    if (t_neg.length > 0) {
                        // only "neg" clauses
                        return "(" + _ALL_Clause_ + " EXCEPT " + pjoin(clause.must_match == "ALL" ? " OR " : " AND ", t_neg) + ")";
                    } else {
                        // no clauses at all
                        return "";
                    }
                }
            case "FULLTEXT":
                return clause.value ? "(" + clause.value + ")" : "";

            case "DATE-FIELD":
                var t = "";
                if (clause.from) {
                    t = clause.field + ">=" + clause.from;
                }
                if (clause.to) {
                    t += (t ? " AND " : "") + clause.field + "<=" + clause.to;
                }
                return (clause.from && clause.to) ? ("(" + t + ")") : t;

            case "TEXT-FIELD":
                return clause.field + clause.operator + "\"" + clause.value + "\"";

            case "GEO-DISTANCE":
                return clause.field + "=\"" + clause.lat + " " + clause.lon + " " + clause.distance + "\"";
/*
            case "STRING-AGGREGATE":
                return clause.field + ":\"" + clause.value + "\"";

            case "DATE-AGGREGATE":
                return clause.field + ":\"" + clause.value + "\"";

            case "COLOR-AGGREGATE":
                return clause.field + ":\"" + clause.value + "\"";

            case "NUMBER-AGGREGATE":
                return clause.field + "=" + clause.value;

            case "BOOL-AGGREGATE":
                return clause.field + "=" + (clause.value ? "1" : "0");
*/
            case "STRING-AGGREGATE":
            case "DATE-AGGREGATE":
            case "COLOR-AGGREGATE":
            case "NUMBER-AGGREGATE":
            case "BOOLEAN-AGGREGATE":
                return clause.query;

            default:
                console.error("Unknown clause type \"" + clause.type + "\"");
                return null;
        }
    }

    appEvents.listenAll({
        'search.doRefreshState': doRefreshState,
        'search.doNewSearch': newSearch,
        'search.doAfterSearch': afterSearch,
        'search.doClearSearch': clearAnswers,
        'search.doNavigate': navigate,
        'search.updateFacetData': updateFacetData,
        'search.reloadHiddenFacetList': reloadHiddenFacetList,
        'search.playFirstQuery': playFirstQuery
    });

    return {
        initialize: initialize,
        getResultSelectionStream: getResultSelectionStream,
        getResultNavigationStream: getResultNavigationStream
    };
};


export default search;
