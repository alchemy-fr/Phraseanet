document.getElementById('loader_bar').style.width = '30%';

var p4 = p4 || {};

var baskAjax, baskAjaxrunning;
baskAjaxrunning = false;
var answAjax, answAjaxrunning;
answAjaxrunning = false;
var searchAjax, searchAjaxRunning;
searchAjaxRunning = false;

var language = {};
var bodySize = {
    x: 0,
    y: 0
};

var selectedFacets = {};
var facets = null;

var ORDER_BY_BCT = "ORDER_BY_BCT";
var ORDER_ALPHA_ASC = "ORDER_ALPHA_ASC";
var ORDER_BY_HITS = "ORDER_BY_HITS";



function resizePreview() {
    p4.preview.height = $('#PREVIEWIMGCONT').height();
    p4.preview.width = $('#PREVIEWIMGCONT').width();
    setPreview();
}

function getHome(cas, page) {

    if (typeof(page) === 'undefined')
        page = 0;

    switch (cas) {
        case 'QUERY':
            selectedFacets = {};
            newSearch($("#EDIT_query").val());
            break;
        case 'PUBLI':
            publicationModule.fetchPublications(page, answAjax, answAjaxrunning);
            break;
        case 'HELP':
            $.ajax({
                type: "POST",
                url: "/client/home/",
                dataType: 'html',
                data: {
                    type: cas
                },
                beforeSend: function () {
                    if (answAjaxrunning && answAjax.abort)
                        answAjax.abort();
                    clearAnswers();
                    answAjaxrunning = true;
                    $('#answers').addClass('loading');
                },
                error: function () {
                    answAjaxrunning = false;
                    $('#answers').removeClass('loading');
                },
                timeout: function () {
                    answAjaxrunning = false;
                    $('#answers').removeClass('loading');
                },
                success: function (data) {
                    answAjaxrunning = false;
                    $('#answers').append(data);
                    afterSearch();
                    return;
                }

            });
            break;


        default:
            break;
    }
}

function getLanguage() {
    $.ajax({
        type: "GET",
        url: "../prod/language/",
        dataType: 'json',
        success: function (data) {
            language = data;
            return;
        }
    });
}

function is_ctrl_key(event) {
    if (event.altKey)
        return true;
    if (event.ctrlKey)
        return true;
    if (event.metaKey)	// apple key opera
        return true;
    if (event.keyCode === 17)	// apple key opera
        return true;
    if (event.keyCode === 224)	// apple key mozilla
        return true;
    if (event.keyCode === 91)	// apple key safari
        return true;

    return false;
}

function is_shift_key(event) {
    if (event.shiftKey)
        return true;
    return false;
}

/**
 * adv search : check/uncheck all the collections (called by the buttons "all"/"none")
 *
 * @param bool
 */
function checkBases(bool) {
    $('form.phrasea_query .sbas_list').each(function () {

        var sbas_id = $(this).find('input[name=reference]:first').val();
        if (bool)
            $(this).find(':checkbox').prop('checked', true);
        else
            $(this).find(':checkbox').prop('checked', false);
    });

    checkFilters(true);
}

function checkFilters(save) {
    var danger = false;
    var search = {
        bases: {},
        fields: [],
        dates: {},
        status: [],
        elasticSort: {}

    };

    var adv_box = $('form.phrasea_query .adv_options');
    var container = $("#ADVSRCH_OPTIONS_ZONE");
    var fieldsSort = $('#ADVSRCH_SORT_ZONE select[name=sort]', container);
    var fieldsSortOrd = $('#ADVSRCH_SORT_ZONE select[name=ord]', container);
    var fieldsSelectFake = $('#ADVSRCH_FIELDS_ZONE select.term_select_field', container);
    var statusField = $('#ADVSRCH_FIELDS_ZONE .danger_indicator', container);
    var statusFilters = $('#ADVSRCH_SB_ZONE .status-section-title .danger_indicator', container);
    var dateFilterSelect = $('#ADVSRCH_DATE_ZONE select', container);

    // hide all the fields in the "sort by" select, so only the relevant ones will be shown again
    $("option.dbx", fieldsSort).hide().prop("disabled", true);  // dbx is for "field of databases"

    // hide all the fields in the "fields" select, so only the relevant ones will be shown again
    $("option.dbx", fieldsSelectFake).prop("disabled", true);
    // disable the whole select
    $('#ADVSRCH_FIELDS_ZONE .term_select_wrapper select.term_select_field', container).enable(false);
    $('#ADVSRCH_FIELDS_ZONE .term_select_wrapper select.term_select_op', container).enable(false);
    $('#ADVSRCH_FIELDS_ZONE .term_select_wrapper input.term_select_value', container).enable(false);

    // hide all the fields in the "date field" select, so only the relevant ones will be shown again
    $("option.dbx", dateFilterSelect).hide().prop("disabled", true);   // dbx = all "field" entries in the select = all except the firstt

    statusFilters.removeClass('danger');
    $.each($('#ADVSRCH_SB_ZONE .field_switch'), function(index,el){
        if( $(el).prop('checked') === true ) {
            danger = true;
            statusFilters.addClass('danger');
        }
    });

    var nbTotalSelectedColls = 0;
    $.each($('.sbascont', adv_box), function () {
        var $this = $(this);

        var sbas_id = $this.parent().find('input[name="reference"]').val();
        search.bases[sbas_id] = [];

        var nbCols = 0;
        var nbSelectedColls = 0;
        $this.find('.checkbas').each(function (idx, el) {
            nbCols++;
            if($(this).prop('checked')) {
                nbSelectedColls++;
                nbTotalSelectedColls++;
                search.bases[sbas_id].push($(this).val());
            }
        });

        // display the number of selected colls for the databox
        if (nbSelectedColls == nbCols) {
            $('.infos_sbas_' + sbas_id).empty().append(nbCols);
            $(this).siblings(".clksbas").removeClass("danger");
            $(this).siblings(".clksbas").find(".custom_checkbox_label input").prop("checked", "checked");
        }
        else {
            $('.infos_sbas_' + sbas_id).empty().append('<span style="color:#2096F3;font-size: 20px;">' + nbSelectedColls + '</span> / ' + nbCols);
            $(this).siblings(".clksbas").addClass("danger");
            danger = true;
        }

        // if one coll is not checked, show danger
        if(nbSelectedColls != nbCols) {
            $("#ADVSRCH_SBAS_ZONE").addClass("danger");
            danger = true;
        }
        else if (nbSelectedColls == nbCols && danger == false) {
            $("#ADVSRCH_SBAS_ZONE").removeClass("danger");
        }

        if(nbSelectedColls == 0) {
            // no collections checked for this databox
            // hide the status bits
            $("#ADVSRCH_SB_ZONE_"+sbas_id, container).hide();
            // uncheck
            $("#ADVSRCH_SB_ZONE_"+sbas_id+" input:checkbox", container).prop("checked", false);
        }
        else {
            // at least one coll checked for this databox
            // show again the relevant fields in "sort by" select
            $(".db_"+sbas_id, fieldsSort).show().prop("disabled", false);
            // show again the relevant fields in "from fields" select
            $(".db_"+sbas_id, fieldsSelectFake).prop("disabled", false);
            // enable again the whole select, only if the selected option (field name) is relevant for this db
            $("option.db_"+sbas_id+":selected", fieldsSelectFake).closest("select").enable(true);
            // show the sb
            $("#ADVSRCH_SB_ZONE_"+sbas_id, container).show();
            // show again the relevant fields in "date field" select
            $(".db_"+sbas_id, dateFilterSelect).show().prop("disabled", false);
        }
    });

    // enable also the select if the first option ("choose:") was selected
    statusField.removeClass('danger');
    fieldsSelectFake.each(function(e) {
        var $this = $(this);
        var term_ok = $('option:selected:enabled', $this).closest(".term_select_wrapper");
        $("select.term_select_field", term_ok).enable(true);
        if($this.val() !== "") {
            $("select.term_select_op", term_ok).enable(true);
            $("input.term_select_value", term_ok).enable(true);
            danger = true;
            statusField.addClass('danger');
        }
    });

    if (nbTotalSelectedColls == 0) {
        // no collections checked at all
        // hide irrelevant filters
        $("#ADVSRCH_OPTIONS_ZONE").hide();
    }
    else {
        // at least one collection checked
        // show relevant filters
        $("#ADVSRCH_OPTIONS_ZONE").show();
    }

    // --------- sort  --------

    // if no field is selected for sort, select the default option
    if($("option:selected:enabled", fieldsSort).length == 0) {
        $("option.default-selection", fieldsSort).prop("selected", true);
        $("option.default-selection", fieldsSortOrd).prop("selected", true);
    }

    search.elasticSort.by = $("option:selected:enabled", fieldsSort).val();
    search.elasticSort.order = $("option:selected:enabled", fieldsSortOrd).val();

    //--------- status bits filter ---------

    // here only the relevant sb are checked
    for(sbas_id in search.bases) {
        var n_checked = 0;
        var n_unchecked = 0;
        $("#ADVSRCH_SB_ZONE_"+sbas_id+" :checkbox", container).each(function (k, o) {
            var n = $(this).data('sb');
            if($(o).attr('checked')) {
                search.status[n] = $(this).val().split('_');
                n_checked++;
            }
            else {
                n_unchecked++;
            }
        });
        if(n_checked === 0) {
            $("#ADVSRCH_SB_ZONE_"+sbas_id, container).removeClass('danger');
        }
        else {
            $("#ADVSRCH_SB_ZONE_"+sbas_id, container).addClass('danger');
            danger = true;
        }
    }

    //--------- dates filter ---------

    // if no date field is selected for filter, select the first option
    $('#ADVSRCH_DATE_ZONE', adv_box).removeClass('danger');
    if($("option:selected:enabled", dateFilterSelect).length == 0) {
        $("option:eq(0)", dateFilterSelect).prop("selected", true);
    }
    if($("option:selected", dateFilterSelect).val() !== "") {
        $("#ADVSRCH_DATE_SELECTORS", container).show();
        search.dates.minbound = $('#ADVSRCH_DATE_ZONE input[name=date_min]', adv_box).val();
        search.dates.maxbound = $('#ADVSRCH_DATE_ZONE input[name=date_max]', adv_box).val();
        search.dates.field = $('#ADVSRCH_DATE_ZONE select[name=date_field]', adv_box).val();

        if ($.trim(search.dates.minbound) || $.trim(search.dates.maxbound)) {
            danger = true;
            $('#ADVSRCH_DATE_ZONE', adv_box).addClass('danger');
        }
    }
    else {
        $("#ADVSRCH_DATE_SELECTORS", container).hide();
    }

    // if one filter shows danger, show it on the query
    if (danger == true) {
        $('#EDIT_query').addClass('danger');
    }
    else {
        $('#EDIT_query').removeClass('danger');
    }

    if (save === true) {
        setPref('search', JSON.stringify(search));
    }
}

function toggleFilter(filter, ele) {
    var el = $('#' + filter);
    if (el.is(':hidden'))
        $(ele).parent().addClass('open');
    else
        $(ele).parent().removeClass('open');
    el.slideToggle('fast');
}


function setVisible(el) {
    el.style.visibility = 'visible';
}

function resize() {
    var body = $('#mainContainer');
    bodySize.y = body.height();
    bodySize.x = body.width();

    $('.overlay').height(bodySize.y).width(bodySize.x);

    var headBlockH = $('#headBlock').outerHeight();
    var bodyY = bodySize.y - headBlockH - 2;
    var bodyW = bodySize.x - 2;
    //$('#desktop').height(bodyY).width(bodyW);

    if (p4.preview.open)
        resizePreview();

    if ($('#idFrameC').data('ui-resizable')) {
        $('#idFrameC').resizable('option', 'maxWidth', (480));
        $('#idFrameC').resizable('option', 'minWidth', 360);
    }

    answerSizer();
    linearize();
}


function clearAnswers() {
    $('#formAnswerPage').val('');
    $('#searchForm input[name="nba"]').val('');
    $('#answers, #dyn_tool').empty();
}

function reset_adv_search() {
    var jsq = {
        "sort":{
            "field":"created_on",
            "order":"desc"
        },
        "use_truncation":false,
        "phrasea_recordtype":"RECORD",
        "phrasea_mediatype":"",
        "bases":[ ],
        "statuses":[ ],
        "query":{
            "_ux_zone":"PROD",
            "type":"CLAUSES",
            "must_match":"ALL",
            "enabled":true,
            "clauses":[
                {
                    "_ux_zone":"FIELDS",
                    "type":"CLAUSES",
                    "must_match":"ALL",
                    "enabled":false,
                    "clauses":[ ]
                },
                {
                    "_ux_zone":"DATE-FIELD",
                    "type":"DATE-FIELD",
                    "field":"",
                    "from":"",
                    "to":"",
                    "enabled":false
                },
                {
                    "_ux_zone":"AGGREGATES",
                    "type":"CLAUSES",
                    "must_match":"ALL",
                    "enabled":false,
                    "clauses":[ ]
                }
            ]
        },
        "_selectedFacets":{ }
    };

    restoreJsonQuery(jsq, false);
}


function newSearch(query) {
    p4.Results.Selection.empty();

    clearAnswers();
    if(query !== null) {
        // $('#SENT_query').val(query);

        var histo = $('#history-queries ul');

        histo.prepend('<li onclick="doSpecialSearch(\'' + query.replace(/\'/g, "\\'") + '\')">' + query + '</li>');

        var lis = $('li', histo);
        if (lis.length > 25) {
            $('li:last', histo).remove();
        }
    }

    $('#idFrameC li.proposals_WZ').removeClass('active');

    $('#searchForm').submit();
    return false;
}

function beforeSearch() {
    if (answAjaxrunning)
        return;
    answAjaxrunning = true;

    clearAnswers();
    $('#tooltip').css({
        'display': 'none'
    });
    $('#answers').addClass('loading').empty();
    $('#answercontextwrap').remove();
}

function afterSearch() {
    if ($('#answercontextwrap').length === 0)
        $('body').append('<div id="answercontextwrap"></div>');

    $.each($('#answers .contextMenuTrigger'), function () {

        var id = $(this).closest('.IMGT').attr('id').split('_').slice(1, 3).join('_');

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
    $('#answers').removeClass('loading');
    $('.captionTips, .captionRolloverTips').tooltip({
        delay: 0,
        isBrowsable: false,
        extraClass: 'caption-tooltip-container'
    });
    $('.infoTips').tooltip({
        delay: 0
    });
    $('.previewTips').tooltip({
        fixable: true
    });
    $('.captionRolloverTips').tooltip({
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
    viewNbSelect();
    $('#answers div.IMGT').draggable({
        helper: function () {
            $('body').append('<div id="dragDropCursor" style="position:absolute;z-index:9999;background:red;-moz-border-radius:8px;-webkit-border-radius:8px;"><div style="padding:2px 5px;font-weight:bold;">' + p4.Results.Selection.length() + '</div></div>');
            return $('#dragDropCursor');
        },
        scope: "objects",
        distance: 20,
        scroll: false,
        cursorAt: {
            top: -10,
            left: -20
        },
        start: function (event, ui) {
            if (!$(this).hasClass('selected'))
                return false;
        }
    });
    linearize();
}

function initAnswerForm() {

    var searchForm = $('#searchForm');
    $('button[type="submit"]', searchForm).bind('click', function () {
        selectedFacets = {};
        newSearch($("#EDIT_query").val());
        return false;
    });

    searchForm.unbind('submit').bind('submit', function () {
        var $this = $(this),
            method = $this.attr('method') ? $this.attr('method') : 'POST';

        var data = $this.serializeArray();

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


        var jsonData = serializeJSON(data, selectedFacets, facets);
        var qry = buildQ(jsonData.query);

        data.push({
                name: 'jsQuery',
                value: JSON.stringify(jsonData)
            },
            {
                name: 'qry',
                value: qry
            });

        answAjax = $.ajax({
            type: method,
            url: $this.attr('action'),
            data: data,
            dataType: 'json',
            beforeSend: function (formData) {
                if (answAjaxrunning && answAjax.abort)
                    answAjax.abort();
                beforeSearch();
            },
            error: function () {
                answAjaxrunning = false;
                $('#answers').removeClass('loading');
            },
            timeout: function () {
                answAjaxrunning = false;
                $('#answers').removeClass('loading');
            },
            success: function (datas) {
                // DEBUG QUERY PARSER
                try {
                    console.info(JSON.parse(datas.parsed_query));
                }
                catch(e) {}

                if(datas.total_answers > 0) {
                    sessionStorage.setItem('search', JSON.stringify(datas.query));
                }

                $('#answers').empty().append(datas.results).removeClass('loading');

                $("#answers img.lazyload").lazyload({
                    container: $('#answers')
                });

                //load last result collected or [] if length == 0
                if(!datas.facets) {
                    datas.facets = [];
                }
                facets = datas.facets;
                loadFacets(facets);

                $('#answers').append('<div id="paginate"><div class="navigation"><div id="tool_navigate"></div></div></div>');

                $('#tool_results').empty().append(datas.infos);
                $('#tool_navigate').empty().append(datas.navigationTpl);

                $.each(p4.Results.Selection.get(), function (i, el) {
                    $('#IMGT_' + el).addClass('selected');
                });

                p4.tot = datas.total_answers;
                p4.tot_options = datas.form;
                p4.tot_query = datas.query;
                p4.navigation = datas.navigation;

                if (datas.next_page) {
                    $("#NEXT_PAGE, #answersNext").bind('click', function () {
                        gotopage(datas.next_page);
                    });
                }
                else {
                    $("#NEXT_PAGE").unbind('click');
                }

                if (datas.prev_page) {
                    $("#PREV_PAGE").bind('click', function () {
                        gotopage(datas.prev_page);
                    });
                }
                else {
                    $("#PREV_PAGE").unbind('click');
                }

                afterSearch();
            }
        });
        return false;
    });

    // if defined, play the first query
    //
    try {
        var jsq = $("#FIRST_QUERY_CONTAINER");
        if(jsq.length > 0) {
            // there is a query to play
            if(jsq.data('format') === "json") {
                // json
                jsq = JSON.parse(jsq.text());
                restoreJsonQuery(jsq, true);
            }
            else {
                // text : do it the old way : restore only fulltext and submit
                searchForm.trigger('submit');
            }
        }
    }
    catch (e) {
        // malformed jsonquery ?
        // no-op
    }
}

var facetStatus = $.parseJSON(sessionStorage.getItem('facetStatus')) || [];
var tokens = [['[',']']];

/**
 *  add missing selected facets fields into "facets", from "selectedFacets"
 *  why : because if we negates all values for a facet field (all red), the facet will disapear from next query->answers
 *        (not in "facets" anymore, not in ux). So we lose the posibility to delete or invert a facet value.
 *  nb : negating all facets values does not mean there will be 0 results, because the field can be empty for some records.
 */
function facetsAddMissingSelected(_selectedFacets, _facets) {
    _.each(_selectedFacets, function (v, k) {
        // todo : "facets" is an array (no key), change to a k->v (same structure as "selectedFacets")
        //        too bad for now, we must lookup...
        var found = _.find(_facets, function (facet) {
            return (facet.field == k);
        });
        if(!found) {
            var i = _facets.push(_.clone(v)); // add a "fake" facet to facets
            _facets[i-1].values = [];      // with no values
        }
    });
}

function loadFacets(facets) {

    //get facets settings
    var hideSingleValueFacet_setting = $('#look_box_settings input[name=filter_facet]').prop('checked');
    var facetOrder = $('#look_box_settings select[name=orderFacet]').val();
    var facetValueOrder = $('#look_box_settings select[name=facetValuesOrder]').val();

    function sortIteration(i) {
        switch(facetValueOrder) {
            case ORDER_ALPHA_ASC:
                return i.value.toString().toLowerCase();
                break;
            case ORDER_BY_HITS:
                return i.count*-1;
                break;
        }
    }

    facetsAddMissingSelected(selectedFacets, facets); // add missing facets fields into "facets" from "selectedFacets"

    // Convert facets data to fancytree source format
    var treeSource = _.map(facets, function(facet) {
        // Values
        var values = _.map(_.sortBy(facet.values, sortIteration), function (value) {
            var type = facet.type;     // todo : define a new phraseanet "color" type for fields. for now we push a "type" for every value, copied from field type
            // patch "color" type values
            var textLimit = 200;     // cut long values (set to 0 to not cut)
            var text = (value.value).toString();
            var label = title = tooltip = text;
            var match = text.match(/^(.*)\[#([0-9a-fA-F]{6})].*$/);
            if(match && match[2] != null) {
                // text looks like a color !
                var colorCode = '#' + match[2];
                // add color circle and remove color code from text;
                var textWithoutColorCode = text.replace('[' + colorCode + ']', '');
                if (textLimit > 0 && textWithoutColorCode.length > textLimit) {
                    textWithoutColorCode = textWithoutColorCode.substring(0, textLimit) + '…';
                }
                // patch
                type = "COLOR-AGGREGATE";
                label = textWithoutColorCode;
                tooltip = _.escape(textWithoutColorCode);
                title = '<span class="color-dot" style="background-color: ' + colorCode + ';"></span> ' + tooltip;
            }
            else {
                // keep text as it is, just cut if too long
                if (textLimit > 0 && text.length > textLimit) {
                    text = text.substring(0, textLimit) + '…';
                }
                label = text;
                title = tooltip = _.escape(text);
            }

            return {
                // custom data
                query:     value.query,
                field:     facet.field,
                raw_value: value.raw_value,
                value:     value.value,
                label:     label,             // displayed when selected (blue/red), escape is done later (render)
                type:      type,              // todo : define a new phraseanet "color" type for fields. for now we push a "type" for every value
                count:     value.count,
                // jquerytree data
                title:     title + ' (' + value.count + ')',
                tooltip:   tooltip + ' (' + value.count + ')'
            };
        });
        // Facet
        return {
            // custom data
            name:     facet.name,
            field:    facet.field,
            label:    facet.label,
            type:     facet.type,
            // jquerytree data
            title:    facet.label,
            folder:   true,
            children: values,
            expanded: !_.some(facetStatus, function(o) { return _.has(o, facet.name)})
        };
    });

    treeSource.sort(sortFacets('title', true, function(a){return a.toUpperCase()}));

    if(facetOrder == ORDER_BY_BCT) {
        treeSource = sortByPredefinedFacets(treeSource, 'name', ['base_aggregate', 'collection_aggregate', 'doctype_aggregate']);
    }

    if(hideSingleValueFacet_setting == true) {
        treeSource = hideSingleValueFacet(treeSource);
    }

    return getFacetsTree().reload(treeSource)
        .done(function () {
            _.each($('#proposals').find('.fancytree-expanded'), function (element, i) {
                $(element).find('.fancytree-title, .fancytree-expander').css('line-height', $(element)[0].offsetHeight + 'px');

                var li_s = $(element).next().children('li');
                var ul = $(element).next();
                if(li_s.length > 5) {
                    _.each(li_s, function(el, i) {
                        if(i > 4) {
                            $(el).hide();
                        }
                    });

                    ul.append('<button class="see_more_btn">See more</button>');
                }
            });
            $('.see_more_btn').on('click', function() {
                $(this).closest('ul').children().show();
                $(this).hide();
                return false;
            });
        });
}

/**
 * hide facets with only one value (experimental)
 *
 * @param source    treesource
 * @returns {*}
 */
function hideSingleValueFacet(source) {
    var filteredSource = [];
    _.forEach(source, function(facet) {
        if(!_.isUndefined(facet.children) && (facet.children.length > 1 || !_.isUndefined(selectedFacets[facet.field]))) {
            filteredSource.push(facet);
        }
    });
    source = filteredSource;

    return source;
}

function sortByPredefinedFacets(source, field, predefinedFieldOrder) {
    var filteredSource = source,
        ordered = [];

    _.forEach(predefinedFieldOrder, function (fieldValue, index) {
        _.forEach(source, function (facet, facetIndex) {
            if (facet[field] !== undefined) {
                if (facet[field] === fieldValue) {
                    ordered.push(facet);
                    // remove from filtered
                    filteredSource.splice(facetIndex, 1);
                }
            }
        });
    });

    var olen = filteredSource.length;
    // fill predefined facets with non predefined facets
    for (var i = 0; i < olen; i++) {
        ordered.push(filteredSource[i]);
    }
    return ordered;
}

// from stackoverflow
// http://stackoverflow.com/questions/979256/sorting-an-array-of-javascript-objects/979325#979325
function sortFacets(field, reverse, primer) {
    var key = function (x) {return primer ? primer(x[field]) : x[field]};

    return function (a,b) {
        var A = key(a), B = key(b);
        return ( (A < B) ? -1 : ((A > B) ? 1 : 0) ) * [-1,1][+!!reverse];
    }
}

function getFacetsTree() {
    var $facetsTree = $('#proposals');
    if (!$facetsTree.data('ui-fancytree')) {
        $facetsTree.fancytree({
            clickFolderMode: 2, // expand
            icons:false,
            source: [],

            activate: function(event, data){
                //var query = data.node.data.query;
                var eventType = event.originalEvent;
                //if user did not click, then no need to perform any query
                if(eventType == null) {
                    return;
                }
                var facet = data.node.parent;
                var facetData = {
                    value:   data.node.data,
                    enabled: true,
                    negated: event.altKey // ,
                    // mode:    event.altKey ? "EXCEPT" : "AND"
                };

                if (selectedFacets[facet.data.field] == null) {
                    selectedFacets[facet.data.field] = facet.data;
                    selectedFacets[facet.data.field].values = [];
                }
                selectedFacets[facet.data.field].values.push(facetData);

                $('#searchForm').submit();
            },

            collapse: function (event, data) {
                var dict = {};
                dict[data.node.data.name] = "collapse";
                if(_.findWhere(facetStatus, dict) !== undefined ) {
                    facetStatus = _.without(facetStatus, _.findWhere(facetStatus, dict))
                }
                facetStatus.push(dict);
                sessionStorage.setItem('facetStatus', JSON.stringify(facetStatus));
            },

            expand: function (event, data) {
                var dict = {};
                dict[data.node.data.name] = "collapse";
                if (_.findWhere(facetStatus, dict) !== undefined) {
                    facetStatus = _.without(facetStatus, _.findWhere(facetStatus, dict))
                }
                sessionStorage.setItem('facetStatus', JSON.stringify(facetStatus));
            },

            renderNode: function(event, data){
                var label = "";
                var selectedlabel = "";
                var tooltip = "";
                if(data.node.folder) {
                    // here we render a "fieldname" level
                    if (!_.isUndefined(selectedFacets[data.node.data.field])) {
                        // here the field already contains selected facetvalues (to be rendered blue or red)
                        if ($(".fancytree-folder", data.node.li).find('.dataNode').length == 0) {
                            var dataNode = document.createElement('div');
                            dataNode.setAttribute("class", "dataNode");
                            $(".fancytree-folder", data.node.li).append(
                                dataNode
                            );
                        } else {
                            //remove existing facets
                            $(".dataNode", data.node.li).empty();
                        }
                        _.each(selectedFacets[data.node.data.field].values, function (facetValue) {

                            label = facetValue.value.label;
                            selectedlabel = label;
                            tooltip = label;
                            if (selectedlabel.length > 15) {
                                selectedlabel = selectedlabel.substring(0, 15) + '…';
                            }
                            var s_label = document.createElement("SPAN");
                            s_label.setAttribute("class", "facetFilter-label");
                            s_label.setAttribute("title", tooltip);

                            // label is a string ; todo : count may be obsolete when ux is restored ? for now don't print
                            s_label.appendChild(document.createTextNode(selectedlabel)); // + ' (' + facetValue.value.count + ')');

                            var s_gradient = document.createElement("SPAN");
                            s_gradient.setAttribute("class", "facetFilter-gradient");
                            s_gradient.appendChild(document.createTextNode("\u00A0"));

                            s_label.appendChild(s_gradient);
                            var s_facet = document.createElement("SPAN");
                            s_facet.setAttribute("class", "facetFilter_" + (facetValue.negated ? "EXCEPT" : "AND"));
                            s_facet.appendChild(s_label);

                            var s_closer = document.createElement("A");
                            s_closer.setAttribute("class", "facetFilter-closer");
                            s_closer = $(s_facet.appendChild(s_closer));

                            s_closer.click(
                                function (event) {
                                    event.stopPropagation();
                                    var facetField   = $(this).parent().data("facetField");
                                    var facetLabel   = $(this).parent().data("facetLabel");
                                    var facetNegated = $(this).parent().data("facetNegated");
                                    selectedFacets[facetField].values = _.reject(selectedFacets[facetField].values, function (facetValue) {
                                        return (facetValue.value.label == facetLabel && facetValue.negated == facetNegated);
                                    });

                                    $('#searchForm').submit();
                                    return false;
                                }
                            );

                            var newNode = document.createElement('div');
                            newNode.setAttribute("class", "newNode");
                            s_facet = $(newNode.appendChild(s_facet));
                            s_facet.data("facetField",   data.node.data.field);
                            s_facet.data("facetLabel",   label);
                            s_facet.data("facetNegated", facetValue.negated);

                            $(".fancytree-folder .dataNode", data.node.li).append(
                                newNode
                            );

                            s_facet.click(
                                function (event) {
                                    if (event.altKey) {
                                        event.stopPropagation();
                                        var facetField   = $(this).data("facetField");
                                        var facetLabel   = $(this).data("facetLabel");
                                        var facetNegated = $(this).data("facetNegated");
                                        var found = _.find(selectedFacets[facetField].values, function (facetValue) {
                                            return (facetValue.value.label == facetLabel && facetValue.negated == facetNegated);
                                        });
                                        if (found) {
                                            found.negated = !found.negated;
                                            //replace class attr
                                            $(this).replaceClass($(this).attr('class'), "facetFilter_" + (found.negated ? "EXCEPT" : "AND"));
                                            $('#searchForm').submit();
                                        }
                                    }
                                    return false;
                                }
                            );
                        });
                    }
                    else {
                        // here we render a facet value
                    }
                }
            }
        });

    }
    return $facetsTree.fancytree('getTree');
}

function findClauseBy_ux_zone(clause, ux_zone) {
    if(typeof clause._ux_zone != 'undefined' && clause._ux_zone === ux_zone) {
        return clause;
    }
    if(clause.type === "CLAUSES") {
        for(var i=0; i<clause.clauses.length; i++) {
            var r = findClauseBy_ux_zone(clause.clauses[i], ux_zone);
            if(r != null) {
                return r;
            }
        }
    }
    return null;
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
function restoreJsonQuery(jsq, submit) {
    var clause;

    // restore the "fulltext" input-text
    clause = findClauseBy_ux_zone(jsq.query, "FULLTEXT");
    if(clause) {
        $('#EDIT_query').val(clause.value);
    }

    // restore the "records/stories" radios
    if(!_.isUndefined(jsq.phrasea_recordtype)) {
        $('#searchForm INPUT[name=search_type][value="' + ((jsq.phrasea_recordtype == 'STORY') ? '1' : '0') + '"]').prop('checked', true);  // check one radio will uncheck siblings
    }

    // restore the "record type" menu (image, video, audio, ...)
    if(!_.isUndefined(jsq.phrasea_mediatype)) {
        $('#searchForm SELECT[name=record_type] OPTION[value="' + jsq.phrasea_mediatype.toLowerCase() + '"]').prop('selected', true);
    }

    // restore the "use truncation" checkbox
    if(!_.isUndefined(jsq.phrasea_mediatype)) {
        $('#ADVSRCH_USE_TRUNCATION').prop('checked', jsq.phrasea_mediatype);
    }

    // restore the "sort results" menus
    if(!_.isUndefined(jsq.sort)) {
        if(!_.isUndefined(jsq.sort.field)) {
            $('#ADVSRCH_SORT_ZONE SELECT[name=sort] OPTION[value="' + jsq.sort.field + '"]').prop('selected', true);
        }
        if(!_.isUndefined(jsq.sort.order)) {
            $('#ADVSRCH_SORT_ZONE SELECT[name=ord] OPTION[value="' + jsq.sort.order + '"]').prop('selected', true);
        }
    }

    // restore the "bases" checkboxes
    if(!_.isUndefined(jsq.bases)) {
        $('#ADVSRCH_SBAS_ZONE INPUT.checkbas').attr('checked', false);
        if (jsq.bases.length > 0) {
            for (var i = 0; i < jsq.bases.length; i++) {
                $('#ADVSRCH_SBAS_ZONE INPUT.checkbas[value="' + jsq.bases[i] + '"]').attr('checked', true);
            }
        } else {
            // special case : EMPTY array ==> since it's a nonsense, check ALL bases
            $('#ADVSRCH_SBAS_ZONE INPUT.checkbas').attr('checked', true);
        }
    }

    // restore the multiples "fields" (field-menu + op-menu + value-input)
    clause = findClauseBy_ux_zone(jsq.query, "FIELDS");
    if(clause) {
        $('#ADVSRCH_FIELDS_ZONE INPUT[name=must_match][value="' + clause.must_match + '"]').attr('checked', true);
        $('#ADVSRCH_FIELDS_ZONE DIV.term_select_wrapper').remove();
        for (var i = 0; i < clause.clauses.length; i++) {
            var wrapper = AdvSearchAddNewTerm();    // div.term_select_wrapper
            var f = $(".term_select_field", wrapper);
            var o = $(".term_select_op", wrapper);
            var v = $(".term_select_value", wrapper);

            f.data('fieldtype', clause.clauses[i].type);
            $('option[value="' + clause.clauses[i].field + '"]', f).prop('selected', true);
            $('option[value="' + clause.clauses[i].operator + '"]', o).prop('selected', true);
            v.val(clause.clauses[i].value);
        }
        if(i === 0) {
            // if no field, add an empty one to ux
            AdvSearchAddNewTerm();
        }
    }

    // restore the "date field" (field-menu + from + to)
    clause = findClauseBy_ux_zone(jsq.query, "DATE-FIELD");
    if(clause) {
        $("#ADVSRCH_DATE_ZONE SELECT[name=date_field] option[value='" + clause.field + "']").prop('selected', true);
        $("#ADVSRCH_DATE_ZONE INPUT[name=date_min]").val(clause.from);
        $("#ADVSRCH_DATE_ZONE INPUT[name=date_max]").val(clause.to);
    }

    // restore the status-bits (for now dual checked status are restored unchecked)
    if(!_.isUndefined(jsq.statuses)) {
        $("#ADVSRCH_SB_ZONE INPUT:checkbox").prop('checked', false);
        _.each(jsq.statuses, function(db_statuses) {
            var db = db_statuses.databox;
            _.each(db_statuses.status, function(sb) {
                var i = sb.index;
                var v = sb.value ? "1" : "0";
                $("#ADVSRCH_SB_ZONE INPUT[name='status[" + db_statuses.databox + "][" + sb.index + "]'][value=" + v + "]").prop('checked', true);
            });
        });
    }

    // restore the selected facets (whole saved as custom property)
    if(!_.isUndefined(jsq._selectedFacets)) {
        selectedFacets = jsq._selectedFacets;
    }

    // the ux is restored, finish the job (hide unavailable fields/status etc, display "danger" where needed)
    checkFilters(false);
    // loadFacets([]);  // useless, facets will be restored after the query is sent

    if(submit) {
        $('#searchForm').submit();
    }
}

/**
 * advsearch : a status-bit is changed
 * --- NOW BUGGY ---
 * goal : do not allow to uncheck both sb values (1 and 0) since it's nonsense
 * since the "name" is the same for both values, it's buggy to send 2 values in request if both are checked.
 *    so the checkbox are "faked", and the real value is sent by 2 radios
 * @param o
 */
function advSearchChangeStatusBit(o) {
    /*
    * BUGGY : one sb should stay checked, but other js sometimes unselect all.
    * som other js don't know "fakestatus"..
    * todo : to be finished later
    var o_a = $(o);                 // the changed sb (value="0" or value="1")
    var b = o_a.data("sbas_id");
    var i = o_a.data("sb");
    var v = o_a.val();
    var chk = $("#ADVSRCH_SB_"+b+" INPUT[name='fakestatus[" + b + "][" + i + "]']");
    var o_b = chk.filter("[value=" + (v==='1'?'0':'1') + "]");     // the oposite sb (value="1" or value="0")

    // can't uncheck both
    if(!o_a.prop('checked')) {
        o_b.prop('checked', true);  // check the other one
    }

    // transform fakecheckboxes to radio
    var rad = $("#ADVSRCH_SB_"+b+" INPUT[name='status[" + b + "][" + i + "]']");
    rad.prop('checked', false);
    if(!chk.filter("[value=0]").prop('checked') && chk.filter("[value=1]").prop('checked')) {
        rad.filter("[value=1]").prop('checked', true);
    }
    else if (!chk.filter("[value=1]").prop('checked') && chk.filter("[value=0]").prop('checked')) {
        rad.filter("[value=0]").prop('checked', true);
    }
    */
    checkFilters(true);
}

function serializeJSON(data, selectedFacets, facets) {
    var json = {},
        obj = {},
        bases = [],
        statuses = [],
        fields = [],
        aggregates = []
    ;

    $.each(data, function(i, el) {
        obj[el.name] = el.value;

        var col = parseInt(el.value);

        if(el.name === 'bases[]') {
            bases.push(col);
        }

        if(0 && el.name.startsWith('status')) {
            var databoxId = el.name.match(/\d+/g)[0],
                databoxRow = el.name.match(/\d+/g)[1],
                statusMatch = false;

            $.each(statuses, function(i, status) {

                if (status.databox === databoxId) {
                    for (var j = 0; j < status.status.length; j++) {
                        var st = status.status[j].name;
                        var st_id = st.substr(0, st.indexOf(':'));

                        if (st_id === databoxRow) {
                            statusMatch = true;
                        }
                    }
                    statuses.splice((databoxId -1), 1);
                }
            });
            if (!statusMatch) {
                statuses.push({
                    'databox': databoxId,
                    'status': [
                        {
                            'index': databoxRow,
                            'value': !!(parseInt(el.value))
                        }
                    ]
                });
            }
        }
    });

    var _tmpStat = [];
    $("#ADVSRCH_SB_ZONE INPUT[type=checkbox]:checked").each(function(k, o) {
        o = $(o);
        var b = o.data("sbas_id");
        var i = o.data("sb");
        var v = o.val();
        if(_.isUndefined(_tmpStat[b])) {
            _tmpStat[b] = [];
        }
        if(_.isUndefined(_tmpStat[b][i])) {
            // first check
            _tmpStat[b][i] = v;
        }
        else {
            // both checked
            _tmpStat[b][i] = -1;
        }
    });
    _.each(_tmpStat, function(v, sbas_id){
        var status = []
        _.each(v, function(v, sb_index) {
            if( v !== -1) {     // ignore both checked
                status.push({
                    'index': sb_index,
                    'value': (v == "1")
                });
            }
        });
        statuses.push({
            'databox': sbas_id,
            'status': status
        });
    });


    $("#ADVSRCH_FIELDS_ZONE .term_select_wrapper").each(function(i, wrapper) {
        var f = $(".term_select_field option:selected", wrapper);
        var o = $(".term_select_op", wrapper);
        var v = $(".term_select_value", wrapper);
        var type = f.data('fieldtype');
        fields.push({
            'type'    : _.isString(type) ? type.toUpperCase() : "",
            'field'   : f.val(),
            'operator': o.val(),
            'value'   : v.val(),
            "enabled" : !f.prop('disabled') && f.val() !== '' && v.val() !== ''
        });
    });

    _.each(selectedFacets, function(facets) {
        _.each(facets.values, function(facetValue) {
            aggregates.push({
                'type'   : facetValue.value.type,
                'field'  : facetValue.value.field,
                'value'  : facetValue.value.raw_value,
                'negated': facetValue.negated,
                'enabled': facetValue.enabled
            });
        });
    });

    var date_field = $('#ADVSRCH_DATE_ZONE select[name=date_field]', 'form.phrasea_query .adv_options').val();
    var date_from  = $('#ADVSRCH_DATE_ZONE input[name=date_min]', 'form.phrasea_query .adv_options').val();
    var date_to    = $('#ADVSRCH_DATE_ZONE input[name=date_max]', 'form.phrasea_query .adv_options').val();

    json['sort'] = {
        'field': obj.sort,
        'order': obj.ord
    };
    json['perpage'] = parseInt($('#nperpage_value').val());
    json['page'] = obj.pag === "" ? 1 : parseInt(obj.pag);
    json['use_truncation'] = (obj.truncation === "on");
    json['phrasea_recordtype'] = obj.search_type == 1 ? 'STORY' : 'RECORD';
    json['phrasea_mediatype'] = obj.record_type.toUpperCase();
    json['bases'] = bases;
    json['statuses'] = statuses;
    json['query'] = {
        '_ux_zone': $('.menu-bar .selected').text().trim().toUpperCase(),
        'type': 'CLAUSES',
        'must_match': 'ALL',
        'enabled': true,
        'clauses': [
            {
                '_ux_zone': 'FULLTEXT',
                'type': 'FULLTEXT',
                'value': obj.fake_qry,
                'enabled': obj.fake_qry != ""
            },
            {
                '_ux_zone': 'FIELDS',
                'type': 'CLAUSES',
                'must_match': obj.must_match,
                'enabled': fields.length > 0,
                'clauses': fields
            },
            {
                '_ux_zone': "DATE-FIELD",
                "type": "DATE-FIELD",
                "field": date_field,
                "from": date_from,
                "to": date_to,
                "enabled": (date_field != "") && (date_from != "" || date_to != "")
            },
            {
                "_ux_zone": "AGGREGATES",
                "type": "CLAUSES",
                "must_match": "ALL",
                "enabled": aggregates.length > 0,
                "clauses": aggregates
            }
        ]
    };
    json['_selectedFacets'] = selectedFacets;

    return json;
}

var _ALL_Clause_ = "(created_on>1900/01/01)";
function buildQ(clause) {
    if(clause.enabled == false) {
        return "";
    }
    switch(clause.type) {
        case "CLAUSES":
            var t_pos = [];
            var t_neg = [];
            for(var i=0; i<clause.clauses.length; i++) {
                var _clause = clause.clauses[i];
                var _sub_q = buildQ(_clause);
                if(_sub_q !== "()" && _sub_q !== "") {
                    if(_clause.negated == true) {
                        t_neg.push(_sub_q);
                    }
                    else {
                        t_pos.push(_sub_q);
                    }
                }
            }
            if(t_pos.length > 0) {
                // some "yes" clauses
                if(t_neg.length > 0) {
                    // some "yes" and and some "neg" clauses
                    if(clause.must_match=="ONE") {
                        // some "yes" and and some "neg" clauses, one is enough to match
                        var neg = "(" + _ALL_Clause_ + " EXCEPT (" + t_neg.join(" OR ") + "))";
                        t_pos.push(neg);
                        return "(" + t_pos.join(" OR ") + ")";
                    }
                    else {
                        // some "yes" and and some "neg" clauses, all must match
                        return "((" + t_pos.join(" AND ") + ") EXCEPT (" + t_neg.join(" OR ") + "))";
                    }
                }
                else {
                    // only "yes" clauses
                    return "(" + t_pos.join(clause.must_match=="ONE" ? " OR " : " AND ") + ")";
                }
            }
            else {
                // no "yes" clauses
                if(t_neg.length > 0) {
                    // only "neg" clauses
                    return "(" + _ALL_Clause_ + " EXCEPT (" + t_neg.join(clause.must_match == "ALL" ? " OR " : " AND ") + "))";
                }
                else {
                    // no clauses at all
                    return "";
                }
            }

        case "FULLTEXT":
            return clause.value ? ("(" + clause.value + ")") : "";

        case "DATE-FIELD":
            var t="";
            if(clause.from ) {
                t = clause.field + ">=" + clause.from;
            }
            if(clause.to) {
                t += (t?" AND ":"") + clause.field + "<=" + clause.to;
            }
            return t ? ("(" + t + ")") : "";

        case "TEXT-FIELD":
        case "STRING-FIELD":
            return clause.field + clause.operator + "\"" + clause.value + "\"";

        case "GEO-DISTANCE":
            return clause.field + "=\"" + clause.lat + " " + clause.lon + " " + clause.distance + "\"";

        case "STRING-AGGREGATE":
            return clause.field + ":\"" + clause.value + "\"";

        case "COLOR-AGGREGATE":
            return clause.field + ":\"" + clause.value + "\"";

        case "NUMBER-AGGREGATE":
            return clause.field + "=" + clause.value;

        case "BOOLEAN-AGGREGATE":
            return clause.field + "=" + (clause.value ? '1' : '0');

        default :
            console.error("Unknown clause type \"" + clause.type + "\"");
            return null;
    }
}






$(document).ready(function() {
});



function answerSizer() {
    var el = $('#idFrameC').outerWidth();
    if (!$.support.cssFloat) {
       // $('#idFrameC .insidebloc').width(el - 56);
    }
    var widthA = Math.round(bodySize.x - el - 10);
    $('#rightFrame').width(widthA);
    $('#rightFrame').css('left', $('#idFrameC').width());

}

function linearize() {
    var list = $('#answers .list');
    if (list.length > 0) {
        var fllWidth = $('#answers').innerWidth();
        fllWidth -= 16;

        var stdWidth = 460;
        var diff = 28;
        var n = Math.round(fllWidth / (stdWidth));
        var w = Math.floor(fllWidth / n) - diff;
        if (w < 460 && n > 1)
            w = Math.floor(fllWidth / (n - 1)) - diff;
        $('#answers .list').width(w);
    }
    else {
        var minMargin = 5;
        var margin = 0;
        var el = $('#answers .diapo:first');
        var diapoWidth = el.outerWidth() + (minMargin * 2);
        var fllWidth = $('#answers').innerWidth();
        fllWidth -= 26;

        var n = Math.floor(fllWidth / (diapoWidth));

        margin = Math.floor((fllWidth % diapoWidth) / (2 * n));
        margin = margin + minMargin;

        $('#answers .diapo').css('margin', '5px ' + (margin) + 'px');
        
        var answerIcons = $('#answers .bottom_actions_holder .icon-stack');
        var answerIconsHolder = $('.bottom_actions_holder');
        if (el.outerWidth() < 180) {
            answerIcons.css('width', '20px');
        }
        if (el.outerWidth() > 240) {
            answerIcons.css({
                'width': '30px',
                'height': '27px'
            });
            answerIcons.closest('td').css('width', '110px');
            answerIconsHolder.css('height', '36px');
        }
    }

}


function initLook() {
    $('#nperpage_slider').slider({
        value: parseInt($('#nperpage_value').val()),
        min: 10,
        max: 100,
        step: 10,
        slide: function (event, ui) {
            $('#nperpage_value').val(ui.value);
        },
        stop: function (event, ui) {
            setPref('images_per_page', $('#nperpage_value').val());
        }
    });
    $('#sizeAns_slider').slider({
        value: parseInt($('#sizeAns_value').val()),
        min: 90,
        max: 270,
        step: 10,
        slide: function (event, ui) {
            $('#sizeAns_value').val(ui.value);
        },
        stop: function (event, ui) {
            setPref('images_size', $('#sizeAns_value').val());
        }
    });
}

function acceptCgus(name, value) {
    setPref(name, value);
}

function cancelCgus(id) {

    $.ajax({
        type: "POST",
        url: "../prod/TOU/deny/" + id + "/",
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                alert(language.cgusRelog);
                self.location.replace(self.location.href);
            }
            else {
                humane.error(data.message);
            }
        }
    });

}

function activateCgus() {
    var $this = $('.cgu-dialog:first');
    $this.dialog({
        autoOpen: true,
        closeOnEscape: false,
        draggable: false,
        modal: true,
        resizable: false,
        width: 800,
        height: 500,
        open: function () {
            $this.parents(".ui-dialog:first").find(".ui-dialog-titlebar-close").remove();
            $('.cgus-accept', $(this)).bind('click', function () {
                acceptCgus($('.cgus-accept', $this).attr('id'), $('.cgus-accept', $this).attr('date'));
                $this.dialog('close').remove();
                activateCgus();
            });
            $('.cgus-cancel', $(this)).bind('click', function () {
                if (confirm(language.warningDenyCgus)) {
                    cancelCgus($('.cgus-cancel', $this).attr('id').split('_').pop());
                }
            });
        }
    });
}

$(document).ready(function () {
    humane.forceNew = true;
    activateCgus();
});


function triggerShortcuts() {

    $('#keyboard-stop').bind('click', function () {
        var display = $(this).get(0).checked ? '0' : '1';

        setPref('keyboard_infos', display);

    });

    var buttons = {};

    buttons[language.fermer] = function () {
        $("#keyboard-dialog").dialog('close');
    };

    $('#keyboard-dialog').dialog({
        closeOnEscape: false,
        resizable: false,
        draggable: false,
        modal: true,
        width: 600,
        height: 400,
        overlay: {
            backgroundColor: '#000',
            opacity: 0.7
        },
        open: function (event, ui) {
            $(this).dialog("widget").css("z-index", "1400");
        },
        close: function () {
            $(this).dialog("widget").css("z-index", "auto");
            if ($('#keyboard-stop').get(0).checked) {
                var dialog = $('#keyboard-dialog');
                if (dialog.data("ui-dialog")) {
                    dialog.dialog('destroy');
                }
                dialog.remove();
            }
        }
    }).dialog('option', 'buttons', buttons).dialog('open');

    $('#keyboard-dialog').scrollTop(0);

    return false;
}

function activeZoning() {
    $('#idFrameC, #rightFrame').bind('mousedown', function (event) {
        var old_zone = p4.active_zone;
        p4.active_zone = $(this).attr('id');
        if (p4.active_zone !== old_zone && p4.active_zone !== 'headBlock') {
            $('.effectiveZone.activeZone').removeClass('activeZone');
            $('.effectiveZone', this).addClass('activeZone');//.flash('#555555');
        }
        $('#EDIT_query').blur();
    });
    $('#rightFrame').trigger('mousedown');
}

function RGBtoHex(R, G, B) {
    return toHex(R) + toHex(G) + toHex(B);
}
function toHex(N) {
    if (N === null) return "00";
    N = parseInt(N);
    if (N === 0 || isNaN(N)) return "00";
    N = Math.max(0, N);
    N = Math.min(N, 255);
    N = Math.round(N);
    return "0123456789ABCDEF".charAt((N - N % 16) / 16)
        + "0123456789ABCDEF".charAt(N % 16);
}
function hsl2rgb(h, s, l) {
    var m1, m2, hue;
    var r, g, b;
    s /= 100;
    l /= 100;
    if (s === 0)
        r = g = b = (l * 255);
    else {
        if (l <= 0.5)
            m2 = l * (s + 1);
        else
            m2 = l + s - l * s;
        m1 = l * 2 - m2;
        hue = h / 360;
        r = HueToRgb(m1, m2, hue + 1 / 3);
        g = HueToRgb(m1, m2, hue);
        b = HueToRgb(m1, m2, hue - 1 / 3);
    }
    return {
        r: r,
        g: g,
        b: b
    };
}

function HueToRgb(m1, m2, hue) {
    var v;
    if (hue < 0)
        hue += 1;
    else if (hue > 1)
        hue -= 1;

    if (6 * hue < 1)
        v = m1 + (m2 - m1) * hue * 6;
    else if (2 * hue < 1)
        v = m2;
    else if (3 * hue < 2)
        v = m1 + (m2 - m1) * (2 / 3 - hue) * 6;
    else
        v = m1;

    return 255 * v;
}

/**
 * add "field" zone on advsearch
 *
 * @returns {jQuery|HTMLElement}
 * @constructor
 */
function AdvSearchAddNewTerm() {
    var block_template = $('#ADVSRCH_FIELDS_ZONE DIV.term_select_wrapper_template');
    var last_block = $('#ADVSRCH_FIELDS_ZONE DIV.term_select_wrapper:last');
    if(last_block.length === 0) {
        last_block = block_template;
    }
    last_block = block_template.clone(true).insertAfter(last_block);    // true: clone event handlers
    last_block.removeClass('term_select_wrapper_template').addClass('term_select_wrapper').show();
    last_block.css("background-color", "");

    return last_block;
}


$(document).ready(function () {

    var multi_term_select_html = $('.term_select_wrapper').html();

    $('input[name=search_type]').bind('click', function () {
        var $this = $(this);
        var $record_types = $('#recordtype_sel');

        if ($this.hasClass('mode_type_reg')) {
            $record_types.css("visibility", "hidden");  // better than hide because does not change layout
            $record_types.prop("selectedIndex", 0);
        } else {
            $record_types.css("visibility", "visible");
        }
    });

    $(document).on('change', 'select.term_select_field', function () {
        var $this = $(this);
        $this.blur();
        checkFilters(true);
    });

    
    $(document).on('click', '.term_deleter', function (event) {
        event.preventDefault();
        var $this = $(this);
        $this.closest('.term_select_wrapper').remove();
        checkFilters(true);
    });

    $('.add_new_term').on('click', function (event) {
        event.preventDefault();
        AdvSearchAddNewTerm(1);
    });

    $('.adv_search_button').on('click', function () {
        var searchForm = $('#searchForm');
        var parent = searchForm.parent();

        var options = {
            title: 'Advanced search',
            size: (bodySize.x - 120)+'x'+(bodySize.y - 120),
            loading: false,
            closeCallback: function (dialog) {

                var datas = dialog.find('form.phrasea_query').appendTo(parent);//.clone();

                $('.adv_trigger', searchForm).show();
                $('.adv_options', searchForm).hide();
            }
        };

        $dialog = p4.Dialog.Create(options);

        $dialog.getDomElement().closest('.ui-dialog').addClass('advanced_search_dialog_container');
        searchForm.appendTo($dialog.getDomElement());

        $dialog.getDomElement().find('.adv_options').show();
        $dialog.getDomElement().find('.adv_trigger').hide();

        $dialog.getDomElement().find('form').bind('submit.conbo', function () {
            $(this).unbind('submit.conbo');
            $dialog.Close();
            return false;
        });


        return false;
    });

    $(document).bind('contextmenu', function (event) {
        var targ;
        if (event.target)
            targ = event.target;
        else if (event.srcElement)
            targ = event.srcElement;
        if (targ.nodeType === 3)// safari bug
            targ = targ.parentNode;

        var gogo = true;
        var targ_name = targ.nodeName ? targ.nodeName.toLowerCase() : false;

        if (targ_name !== 'input' && targ_name.toLowerCase() !== 'textarea') {
            gogo = false;
        }
        if (targ_name === 'input') {
            if ($(targ).is(':checkbox'))
                gogo = false;
        }

        return gogo;
    });

    $('.basket_refresher').on('click', function () {
        return p4.WorkZone.refresh('current');
        return false;
    });

    $('#loader_bar').stop().animate({
        width: '70%'
    }, 450);
    p4.preview = {
        open: false,
        current: false
    };
    p4.currentViewMode = 'classic';
    p4.nbNoview = 0;
    p4.reg_delete = true;
    p4.sel = [];
    p4.baskSel = [];
    p4.edit = {};
    p4.thesau = {
        tabs: null
    };
    p4.active_zone = false;
    p4.next_bask_scroll = false;


    $('#backcolorpickerHolder').ColorPicker({
        flat: true,
        color: '404040',
        livePreview: false,
        eventName: 'mouseover',
        onSubmit: function (hsb, hex, rgb, el) {
            var back_hex = '';
            var unactive = '';


            if (hsb.b >= 50) {
                back_hex = '000000';

                var sim_b = 0.1 * hsb.b;
            }
            else {
                back_hex = 'FFFFFF';

                var sim_b = 100 - 0.1 * (100 - hsb.b);
            }

            var sim_b = 0.1 * hsb.b;

            var sim_rgb = hsl2rgb(hsb.h, hsb.s, sim_b);
            var sim_hex = RGBtoHex(sim_rgb.r, sim_rgb.g, sim_rgb.b);

            setPref('background-selection', hex);
            setPref('background-selection-disabled', sim_hex);
            setPref('fontcolor-selection', back_hex);

            $('style[title=color_selection]').empty();

            var datas = '.diapo.selected,#reorder_box .diapo.selected, #EDIT_ALL .diapo.selected, .list.selected, .list.selected .diapo' +
                '{' +
                '    COLOR: #' + back_hex + ';' +
                '    BACKGROUND-COLOR: #' + hex + ';' +
                '}';
            $('style[title=color_selection]').empty().text(datas);
        }
    });
    $('#backcolorpickerHolder').find('.colorpicker_submit').append($('#backcolorpickerHolder .submiter')).bind('click', function () {
        $(this).highlight('#CCCCCC');
    });

    $('#search_submit').on('mousedown', function (event) {
        return false;
    });

    $('#history-queries ul li').on('mouseover',function () {
        $(this).addClass('hover');
    }).on('mouseout', function () {
            $(this).removeClass('hover');
        });

    startThesaurus();
    checkFilters();

    activeZoning();

    $('.shortcuts-trigger').bind('click', function () {
        triggerShortcuts();
    });

    $('#idFrameC').resizable({
        handles: 'e',
        resize: function () {
            answerSizer();
            linearize();
        },
        stop: function () {

            var el = $('.SSTT.active').next().find('div:first');
            var w = el.find('div.chim-wrapper:first').outerWidth();
            var iw = el.innerWidth();
            var diff = $('#idFrameC').width() - el.outerWidth();
            var n = Math.floor(iw / w);

            $('#idFrameC').height('auto');

            var nwidth = (n) * w + diff + n;
            if (isNaN(nwidth)) {
                saveWindows();
                return;
            }
            if (nwidth < 265)
                nwidth = 265;
            if (el.find('div.chim-wrapper:first').hasClass('valid') && nwidth < 410)
                nwidth = 410;


            $('#idFrameC').stop().animate({
                    width: nwidth
                },
                360,
                'linear',
                function () {
                    answerSizer();
                    linearize();
                    saveWindows();
                });
        }
    });

    $('#idFrameC .ui-tabs-nav li').on('click', function (event) {
        if($('#idFrameC').attr('data-status') == 'closed'){
            $('#idFrameC').width(360);
            $('#rightFrame').css('left', 360);
            $('#rightFrame').width($(window).width()-360);
            $('#baskets, #proposals, #thesaurus_tab').hide();
            $('.ui-resizable-handle, #basket_menu_trigger').show();
            var IDname = $(this).attr('aria-controls');
            $('#'+IDname).show();
        }

        $('#idFrameC').attr('data-status', 'open');
        $('.WZbasketTab').css('background-position', '9px 21px');
        $('#idFrameC').removeClass('closed');
    });

    var previousTab = "";

    $('#idFrameC #retractableButton').bind('click', function (event) {

        if($('#idFrameC').attr('data-status') != 'closed'){
            $(this).find('i').removeClass('icon-double-angle-left').addClass('icon-double-angle-right')
            $('#idFrameC').width(80);
            $('#rightFrame').css('left', 80);
            $('#rightFrame').width($(window).width()-80);
            $('#idFrameC').attr('data-status', 'closed');
            $('#baskets, #proposals, #thesaurus_tab, .ui-resizable-handle, #basket_menu_trigger').hide();
            $('#idFrameC .ui-tabs-nav li').removeClass('ui-state-active');
            $('.WZbasketTab').css('background-position', '15px 21px');
            $('#idFrameC').addClass('closed');
            previousTab = $('#idFrameC .icon-menu').find('li.ui-tabs-active');
        }else{
            $(this).find('i').removeClass('icon-double-angle-right').addClass('icon-double-angle-left')
            $('#idFrameC').width(360);
            $('#rightFrame').css('left', 360);
            $('#rightFrame').width($(window).width()-360);
            $('#idFrameC').attr('data-status', 'open');
            $('.ui-resizable-handle, #basket_menu_trigger').show();
            $('.WZbasketTab').css('background-position', '9px 21px');
            $('#idFrameC').removeClass('closed');
            $('#idFrameC .icon-menu li').last().find('a').trigger('click');
            $('#idFrameC .icon-menu li').first().find('a').trigger('click');
            $(previousTab).find('a').trigger('click');
        }

        event.stopImmediatePropagation();
        //p4.WorkZone.close();
        return false;
    });

    $('#look_box .tabs').tabs();

    resize();

    $(window).bind('resize', function () {
        resize();
    });
    $('body').append('<iframe id="MODALDL" class="modalbox" src="about:blank;" name="download" style="display:none;border:none;" frameborder="0"></iframe>');

    $('body').append('<iframe id="idHFrameZ" src="about:blank" style="display:none;" name="HFrameZ"></iframe>');

    $('#basket_menu_trigger').contextMenu('#basket_menu', {
        openEvt: 'click',
        dropDown: true,
        theme: 'vista',
        showTransition: 'slideDown',
        hideTransition: 'hide',
        shadow: false
    });

    $('#basket_menu_trigger').trigger("click");
    $('#basket_menu_trigger').trigger("click");

    $('.datepicker').datepicker({
        changeYear: true,
        changeMonth: true,
        dateFormat: 'yy/mm/dd'
    });

    $.ajaxSetup({

        error: function (jqXHR, textStatus, errorThrown) {
            //Request is aborted
            if (errorThrown === 'abort') {
                return false;
            } else {
                showModal('error', {
                    title: language.errorAjaxRequest + ' ' + jqXHR.responseText
                });
            }
        },
        timeout: function () {
            showModal('timeout', {
                title: 'Server not responding'
            });
        }
    });

    $('.tools .answer_selector').bind('click',function () {
        selector($(this));
    }).bind('mouseover',function (event) {
            if (is_ctrl_key(event)) {
                $(this).addClass('add_selector');
            }
            else {
                $(this).removeClass('add_selector');
            }
        }).bind('mouseout', function () {
            $(this).removeClass('add_selector');
        });

    getLanguage();

    activeIcons();

    initAnswerForm();

    initLook();

    setTimeout("pollNotifications();", 10000);

    $(this).bind('keydown', function (event) {
        var cancelKey = false;
        var shortCut = false;

        if ($('#MODALDL').is(':visible')) {
            switch (event.keyCode) {
                case 27:
                    hideDwnl();
                    break;
            }
        }
        else {
            if ($('#EDITWINDOW').is(':visible')) {

                switch (event.keyCode) {
                    case 9:	// tab ou shift-tab
                        edit_chgFld(event, is_shift_key(event) ? -1 : 1);
                        cancelKey = shortCut = true;
                        break;
                    case 27:
                        edit_cancelMultiDesc(event);
                        shortCut = true;
                        break;

                    case 33:	// pg up
                        if (!p4.edit.textareaIsDirty || edit_validField(event, "ask_ok"))
                            skipImage(event, 1);
                        cancelKey = true;
                        break;
                    case 34:	// pg dn
                        if (!p4.edit.textareaIsDirty || edit_validField(event, "ask_ok"))
                            skipImage(event, -1);
                        cancelKey = true;
                        break;
                }

            }
            else {
                if (p4.preview.open) {
                    if (($('#dialog_dwnl:visible').length === 0 && $('#DIALOG1').length === 0 && $('#DIALOG2').length === 0)) {
                        switch (event.keyCode) {
                            case 39:
                                getNext();
                                cancelKey = shortCut = true;
                                break;
                            case 37:
                                getPrevious();
                                cancelKey = shortCut = true;
                                break;
                            case 27://escape
                                closePreview();
                                break;
                            case 32:
                                if (p4.slideShow)
                                    stopSlide();
                                else
                                    startSlide();
                                cancelKey = shortCut = true;
                                break;
                        }
                    }
                }
                else {
                    if ($('#EDIT_query').hasClass('focused'))
                        return true;

                    if ($('.overlay').is(':visible'))
                        return true;

                    if ($('.ui-widget-overlay').is(':visible'))
                        return true;

                    switch (p4.active_zone) {
                        case 'rightFrame':
                            switch (event.keyCode) {
                                case 65:	// a
                                    if (is_ctrl_key(event)) {
                                        $('.tools .answer_selector.all_selector').trigger('click');
                                        cancelKey = shortCut = true;
                                    }
                                    break;
                                case 80://P
                                    if (is_ctrl_key(event)) {
                                        printThis("lst=" + p4.Results.Selection.serialize());
                                        cancelKey = shortCut = true;
                                    }
                                    break;
                                case 69://e
                                    if (is_ctrl_key(event)) {
                                        editThis('IMGT', p4.Results.Selection.serialize());
                                        cancelKey = shortCut = true;
                                    }
                                    break;
                                case 40:	// down arrow
                                    $('#answers').scrollTop($('#answers').scrollTop() + 30);
                                    cancelKey = shortCut = true;
                                    break;
                                case 38:	// down arrow
                                    $('#answers').scrollTop($('#answers').scrollTop() - 30);
                                    cancelKey = shortCut = true;
                                    break;
                                case 37://previous page
                                    $('#PREV_PAGE').trigger('click');
                                    shortCut = true;
                                    break;
                                case 39://previous page
                                    $('#NEXT_PAGE').trigger('click');
                                    shortCut = true;
                                    break;
                                case 9://tab
                                    if (!is_ctrl_key(event) && !$('.ui-widget-overlay').is(':visible') && !$('.overlay_box').is(':visible')) {
                                        document.getElementById('EDIT_query').focus();
                                        cancelKey = shortCut = true;
                                    }
                                    break;
                            }
                            break;


                        case 'idFrameC':
                            switch (event.keyCode) {
                                case 65:	// a
                                    if (is_ctrl_key(event)) {
                                        p4.WorkZone.Selection.selectAll();
                                        cancelKey = shortCut = true;
                                    }
                                    break;
                                case 80://P
                                    if (is_ctrl_key(event)) {
                                        printThis("lst=" + p4.WorkZone.Selection.serialize());
                                        cancelKey = shortCut = true;
                                    }
                                    break;
                                case 69://e
                                    if (is_ctrl_key(event)) {
                                        editThis('IMGT', p4.WorkZone.Selection.serialize());
                                        cancelKey = shortCut = true;
                                    }
                                    break;
                                //						case 46://del
                                //								deleteThis(p4.Results.Selection.serialize());
                                //								cancelKey = true;
                                //							break;
                                case 40:	// down arrow
                                    $('#baskets div.bloc').scrollTop($('#baskets div.bloc').scrollTop() + 30);
                                    cancelKey = shortCut = true;
                                    break;
                                case 38:	// down arrow
                                    $('#baskets div.bloc').scrollTop($('#baskets div.bloc').scrollTop() - 30);
                                    cancelKey = shortCut = true;
                                    break;
                                case 37://previous page
                                    $('#PREV_PAGE').trigger('click');
                                    break;
                                case 39://previous page
                                    $('#NEXT_PAGE').trigger('click');
                                    break;
                                case 9://tab
                                    if (!is_ctrl_key(event) && !$('.ui-widget-overlay').is(':visible') && !$('.overlay_box').is(':visible')) {
                                        document.getElementById('EDIT_query').focus();
                                        cancelKey = shortCut = true;
                                    }
                                    break;
                            }
                            break;


                        case 'mainMenu':
                            break;


                        case 'headBlock':
                            break;

                        default:
                            break;

                    }
                }
            }
        }

        if (!$('#EDIT_query').hasClass('focused') && event.keyCode !== 17) {

            if ($('#keyboard-dialog.auto').length > 0 && shortCut) {
                triggerShortcuts();
            }
        }
        if (cancelKey) {
            event.cancelBubble = true;
            if (event.stopPropagation)
                event.stopPropagation();
            return(false);
        }
        return(true);
    });


    $('#EDIT_query').bind('focus',function () {
        $(this).addClass('focused');
    }).bind('blur', function () {
            $(this).removeClass('focused');
        });

    $('.basketTips').tooltip({
        delay: 200
    });

    $('.basket_title').tooltip({
        extraClass: 'tooltip_flat'
    });

    $('#idFrameC .tabs').tabs({
        activate: function (event, ui) {
            if (ui.newTab.context.hash == "#thesaurus_tab") {
                thesau_show();
            }
            p4.WorkZone.open();
        }
    });

    $('#PREVIEWBOX .gui_vsplitter', p4.edit.editBox).draggable({
        axis: 'x',
        containment: 'parent',
        drag: function (event, ui) {
            var x = $(ui.position.left)[0];
            if (x < 330 || x > (bodySize.x - 400)) {
                return false;
            }
            var v = $(ui.position.left)[0];
            $("#PREVIEWLEFT").width(v);
            $("#PREVIEWRIGHT").css("left", $(ui.position.left)[0]);
            resizePreview();
        }
    });

    $('input.input_select_copy').on('focus', function () {
        $(this).select();
    });
    $('input.input_select_copy').on('blur', function () {
        $(this).deselect();
    });
    $('input.input_select_copy').on('click', function () {
        $(this).select();
    });

    $('#loader_bar').stop().animate({
        width: '100%'
    }, 450, function () {
        $('#loader').parent().fadeOut('slow', function () {
            $(this).remove();
        });
    });

});


function editThis(type, value) {

    $('#idFrameE').empty().addClass('loading');
    showOverlay(2);

    $('#EDITWINDOW').show();

    var options = {
        lst: '',
        ssel: '',
        act: ''
    };

    switch (type) {
        case "IMGT":
            options.lst = value;
            break;

        case "SSTT":
            options.ssel = value;
            break;

        case "STORY":
            options.story = value;
            break;
    }

    $.ajax({
        url: "../prod/records/edit/",
        type: "POST",
        dataType: "html",
        data: options,
        success: function (data) {
            initializeEdit();
            $('#idFrameE').removeClass('loading').empty().html(data);
            $('#tooltip').hide();
            return;
        },
        error: function (XHR, textStatus, errorThrown) {
            if (XHR.status === 0) {
                return false;
            }
        }
    });

    return;
}

(function ($) {
    $.fn.extend({
        highlight: function (color) {
            if ($(this).hasClass('animating')) {
                return;
            }
            color = typeof color !== 'undefined' ? color : 'red';
            var oldColor = $(this).css('backgroundColor');
            return $(this).addClass('animating').stop().animate({
                backgroundColor: color
            }, 50, 'linear', function () {
                $(this).stop().animate({
                    backgroundColor: oldColor
                }, 450, 'linear', function () {
                    $(this).removeClass('animating');
                });
            });
        }
    });
})(jQuery);

(function ($) {
    $.fn.extend({
        flash: function (color) {
            if ($(this).hasClass('animating')) {
                return true;
            }
            color = typeof color !== 'undefined' ? color : 'red';

            var pos = $(this).offset();

            if (!pos) {
                pos = {
                    top: 0,
                    left: 0
                };
            }

            var h = $(this).height();
            var w = $(this).width();
            $('body').append('<div id="flashing" style="border:3px solid ' + color + ';position:absolute;top:' + (pos.top + (h / 2)) + 'px;left:' + (pos.left + (w / 2)) + 'px;width:0px;height:0px"></div>');
            $(this).addClass('animating');
            var el = $(this);

            $('#flashing').stop().animate({
                top: (pos.top + (h / 4)),
                left: (pos.left + (w / 4)),
                opacity: 0,
                width: ($(this).width() / 2),
                height: ($(this).height() / 2)
            }, 700, function () {
                $('#flashing').remove();
                $(el).removeClass('animating');
            });
        }
    });
})(jQuery);


function toggleRemoveReg(el) {
    var state = !el.checked;
    setPref('reg_delete', (state ? '1' : '0'));
    p4.reg_delete = state;
}


function deleteThis(lst) {
    if (lst.split(';').length === 0) {
        alert(language.nodocselected);
        return false;
    }

    var $dialog = p4.Dialog.Create({
        size: '287x153',
        title: language.warning
    });

    $.ajax({
        type: "POST",
        url: "../prod/records/delete/what/",
        dataType: 'html',
        data: {lst: lst},
        success: function (data) {
            var response = JSON.parse(data);
            $dialog.setOption('height', 'auto');

            $dialog.setContent(response.renderView);

            //reset top position of dialog
            $dialog.getDomElement().offsetParent().css('top', ($(window).height() - $dialog.getDomElement()[0].clientHeight)/2);
        }
    });

    return false;
}

function chgCollThis(datas) {
    $dialog = p4.Dialog.Create({
        size: 'Small',
        title: language.move,
        closeButton: true
    });
    $.ajax({
        type: "POST",
        url: "../prod/records/movecollection/",
        data: datas,
        success: function (data) {
            $dialog.setContent(data);
        }
    });
}

function pushThis(sstt_id, lst, story) {
    $dialog = p4.Dialog.Create({
        size: 'Full',
        title: language.push
    });

    $.post("../prod/push/sendform/", {
        lst: lst,
        ssel: sstt_id,
        story: story
    }, function (data) {
        $dialog.setContent(data);
        return;
    });
}

function feedbackThis(sstt_id, lst, story) {
    /* disable push closeonescape as an over dialog may exist (add user) */
    $dialog = p4.Dialog.Create({
        size: 'Full',
        title: language.feedback
    });

    $.post("../prod/push/validateform/", {
        lst: lst,
        ssel: sstt_id,
        story: story
    }, function (data) {
        $dialog.setContent(data);
        return;
    });
}

function toolREFACTOR(datas, activeTab) {

    var dialog = p4.Dialog.Create({
        size: 'Medium',
        title: language.toolbox,
        loading: true
    });

    $.get("../prod/tools/"
        , datas
        , function (data) {
            dialog.setContent(data);
            dialog.setOption('contextArgs', datas);
            var tabs = $('.tabs', dialog.getDomElement()).tabs();

            // activate tab if exists:
            if( activeTab !== undefined ) {
                tabs.tabs('option', 'active', activeTab);
            }
            return;
        }
    );
}

function activeIcons() {
    var $container = $('body');
    $container.on('click', '.TOOL_print_btn', function (e) {
        e.preventDefault();
        var value = "";

        if ($(this).hasClass('results_window')) {
            if (p4.Results.Selection.length() > 0)
                value = "lst=" + p4.Results.Selection.serialize();
        }
        else {
            if ($(this).hasClass('basket_window')) {
                if (p4.WorkZone.Selection.length() > 0)
                    value = "lst=" + p4.WorkZone.Selection.serialize();
                else
                    value = "SSTTID=" + $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
            }
            else {
                if ($(this).hasClass('basket_element')) {
                    value = "SSTTID=" + $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                }
                else {
                    if ($(this).hasClass('story_window')) {
                        if (p4.WorkZone.Selection.length() > 0) {
                            value = "lst=" + p4.WorkZone.Selection.serialize();
                        }
                        else {
                            value = "story=" + $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                        }
                    }
                }
            }
        }

        if (value !== '') {
            printThis(value);
        }
        else {
            alert(language.nodocselected);
        }
    });
    $container.on('click', '.TOOL_bridge_btn', function (e) {
        e.preventDefault();
        var $button = $(this);
        var datas = {};
        var bridgeHref = $button.attr("href");

        if ($button.hasClass('results_window')) {
            if (p4.Results.Selection.length() > 0)
                datas.lst = p4.Results.Selection.serialize();
        }
        else {
            if ($button.hasClass('basket_window')) {
                bridgeHref = $button.attr("data-href");
                if (p4.WorkZone.Selection.length() > 0)
                    datas.lst = p4.WorkZone.Selection.serialize();
                else
                    datas.ssel = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
            }
            else {
                if ($button.hasClass('basket_element')) {
                    datas.ssel = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                }
                else {
                    if ($button.hasClass('story_window')) {
                        bridgeHref = $button.attr("data-href");
                        if (p4.WorkZone.Selection.length() > 0) {
                            datas.lst = p4.WorkZone.Selection.serialize();
                        }
                        else {
                            datas.story = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                        }
                    }
                }
            }
        }

        if (datas.ssel || datas.lst || datas.story) {
            init_publicator(bridgeHref, datas);
        }
        else {
            alert(language.nodocselected);
        }
    });

    $container.on('click', '.TOOL_trash_btn', function () {

        var type = "";
        var el = false;

        if ($(this).hasClass('results_window')) {
            if (p4.Results.Selection.length() > 0)
                type = 'IMGT';
        }
        else {
            if ($(this).hasClass('basket_window')) {
                if (p4.WorkZone.Selection.length() > 0)
                    type = 'CHIM';
                else {
                    type = 'SSTT';
                    el = $('.SSTT.active');
                }
            }
            else {
                if ($(this).hasClass('story_window')) {
                    if (p4.WorkZone.Selection.length() > 0) {
                        type = 'CHIM';
                    }
                    else {
                        type = 'STORY';
                        el = $(this).find('input[name=story_key]');
                    }
                }
            }
        }
        if (type !== '') {
            checkDeleteThis(type, el);
        }
        else {
            alert(language.nodocselected);
        }
    });
    $container.on('click', '.TOOL_ppen_btn', function () {

        var value = "";
        var type = "";

        if ($(this).hasClass('results_window')) {
            if (p4.Results.Selection.length() > 0) {
                type = 'IMGT';
                value = p4.Results.Selection.serialize();
            }
        }
        else {
            if ($(this).hasClass('basket_window')) {
                if (p4.WorkZone.Selection.length() > 0) {
                    type = 'IMGT';
                    value = p4.WorkZone.Selection.serialize();
                }
                else {
                    type = 'SSTT';
                    value = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                }
            }
            else {
                if ($(this).hasClass('basket_element')) {
                    type = 'SSTT';
                    value = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                }
                else {
                    if ($(this).hasClass('story_window')) {
                        if (p4.WorkZone.Selection.length() > 0) {
                            type = 'IMGT';
                            value = p4.WorkZone.Selection.serialize();
                        }
                        else {
                            type = 'STORY';
                            value = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                        }
                    }
                }
            }
        }

        if (value !== '') {
            editThis(type, value);
        }
        else {
            alert(language.nodocselected);
        }
    });
    $container.on('click', '.TOOL_publish_btn', function () {
        var value = "";
        var type = "";

        if ($(this).hasClass('results_window')) {
            if (p4.Results.Selection.length() > 0) {
                type = 'IMGT';
                value = p4.Results.Selection.serialize();
            }
        }
        else {
            if ($(this).hasClass('basket_window')) {
                if (p4.WorkZone.Selection.length() > 0) {
                    type = 'IMGT';
                    value = p4.WorkZone.Selection.serialize();
                }
                else {
                    type = 'SSTT';
                    value = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                }
            }
            else {
                if ($(this).hasClass('basket_element')) {
                    type = 'SSTT';
                    value = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                }
                else {
                    if ($(this).hasClass('story_window')) {
                        if (p4.WorkZone.Selection.length() > 0) {
                            type = 'IMGT';
                            value = p4.WorkZone.Selection.serialize();
                        }
                        else {
                            type = 'STORY';
                            value = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                        }
                    }
                }
            }
        }

        if (value !== '') {
            publicationModule.publishRecords(type, value);
        }
        else {
            alert(language.nodocselected);
        }
    });


    $container.on('click', '.TOOL_chgcoll_btn', function () {
        var value = {};

        if ($(this).hasClass('results_window')) {
            if (p4.Results.Selection.length() > 0)
                value.lst = p4.Results.Selection.serialize();
        }
        else {
            if ($(this).hasClass('basket_window')) {
                if (p4.WorkZone.Selection.length() > 0)
                    value.lst = p4.WorkZone.Selection.serialize();
                else
                    value.ssel = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
            }
            else {
                if ($(this).hasClass('basket_element')) {
                    value.ssel = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                }
                else {
                    if ($(this).hasClass('story_window')) {
                        if (p4.WorkZone.Selection.length() > 0) {
                            value.lst = p4.WorkZone.Selection.serialize();
                        }
                        else {
                            value.story = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                        }
                    }
                }
            }
        }

        /**
         * if works, then the object is not empty
         */
        for (i in value) {
            return chgCollThis(value);
        }

        alert(language.nodocselected);
    });

    $container.on('click', '.TOOL_chgstatus_btn', function () {
        var params = {};
        var $this = $(this);

        if ($this.hasClass('results_window')) {
            if (p4.Results.Selection.length() > 0) {
                params.lst = p4.Results.Selection.serialize();
            }
        } else {
            if ($this.hasClass('basket_window')) {
                if (p4.WorkZone.Selection.length() > 0) {
                    params.lst = p4.WorkZone.Selection.serialize();
                } else {
                    params.ssel = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                }
            } else {
                if ($this.hasClass('basket_element')) {
                    params.ssel = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                } else {
                    if ($this.hasClass('story_window')) {
                        if (p4.WorkZone.Selection.length() > 0) {
                            params.lst = p4.WorkZone.Selection.serialize();
                        } else {
                            params.story = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                        }
                    }
                }
            }
        }

        if (false === $.isEmptyObject(params)) {
            var dialog = p4.Dialog.Create();
            dialog.load('../prod/records/property/', 'GET', params);
        } else {
            alert(language.nodocselected);
        }
    });

    $container.on('click', '.TOOL_pushdoc_btn', function () {
        var value = "", type = "", sstt_id = "", story = "";
        if ($(this).hasClass('results_window')) {
            if (p4.Results.Selection.length() > 0)
                value = p4.Results.Selection.serialize();
        }
        else {
            if ($(this).hasClass('basket_window')) {
                if (p4.WorkZone.Selection.length() > 0)
                    value = p4.WorkZone.Selection.serialize();
                else
                    sstt_id = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
            }
            else {
                if ($(this).hasClass('basket_element')) {
                    sstt_id = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                }
                else {
                    if ($(this).hasClass('story_window')) {
                        if (p4.WorkZone.Selection.length() > 0) {
                            value = p4.WorkZone.Selection.serialize();
                        }
                        else {
                            story = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                        }
                    }
                }
            }
        }
        if (value !== '' || sstt_id !== '' || story !== '') {
            pushThis(sstt_id, value, story);
        }
        else {
            alert(language.nodocselected);
        }
    });

    $container.on('click', '.TOOL_feedback_btn', function () {
        var value = "", type = "", sstt_id = "", story = '';
        if ($(this).hasClass('results_window')) {
            if (p4.Results.Selection.length() > 0)
                value = p4.Results.Selection.serialize();
        }
        else {
            if ($(this).hasClass('basket_window')) {
                if (p4.WorkZone.Selection.length() > 0)
                    value = p4.WorkZone.Selection.serialize();
                else
                    sstt_id = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
            }
            else {
                if ($(this).hasClass('basket_element')) {
                    sstt_id = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                }
                else {
                    if ($(this).hasClass('story_window')) {
                        if (p4.WorkZone.Selection.length() > 0) {
                            value = p4.WorkZone.Selection.serialize();
                        }
                        else {
                            story = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                        }
                    }
                }
            }
        }
        if (value !== '' || sstt_id !== '' || story !== '') {
            feedbackThis(sstt_id, value, story);
        }
        else {
            alert(language.nodocselected);
        }
    });

    $container.on('click', '.TOOL_imgtools_btn', function () {
        var datas = {};

        if ($(this).hasClass('results_window')) {
            if (p4.Results.Selection.length() > 0)
                datas.lst = p4.Results.Selection.serialize();
        }
        else {
            if ($(this).hasClass('basket_window')) {
                if (p4.WorkZone.Selection.length() > 0)
                    datas.lst = p4.WorkZone.Selection.serialize();
                else
                    datas.ssel = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
            }
            else {
                if ($(this).hasClass('basket_element')) {
                    datas.ssel = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                }
                else {
                    if ($(this).hasClass('story_window')) {
                        if (p4.WorkZone.Selection.length() > 0) {
                            datas.lst = p4.WorkZone.Selection.serialize();
                        }
                        else {
                            datas.story = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                        }
                    }
                }
            }
        }

        if (!$.isEmptyObject(datas)) {
            toolREFACTOR(datas);
        }
        else {
            alert(language.nodocselected);
        }
    });

    $container.on('click', '.TOOL_disktt_btn', function () {
        var datas = {};

        if ($(this).hasClass('results_window')) {
            if (p4.Results.Selection.length() > 0) {
                datas.lst = p4.Results.Selection.serialize();
            }
        }
        else {
            if ($(this).hasClass('basket_window')) {
                if (p4.WorkZone.Selection.length() > 0) {
                    datas.lst = p4.WorkZone.Selection.serialize();
                }
                else {
                    datas.ssel = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                }
            }
            else {
                if ($(this).hasClass('basket_element')) {
                    datas.ssel = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                }
                else {
                    if ($(this).hasClass('story_window')) {
                        if (p4.WorkZone.Selection.length() > 0) {
                            datas.lst = p4.WorkZone.Selection.serialize();
                        }
                        else {
                            datas.story = $('.SSTT.active').attr('id').split('_').slice(1, 2).pop();
                        }
                    }
                }
            }
        }

        for (var i in datas) {
            return downloadThis(datas);
        }

        alert(language.nodocselected);
    });


}

function checkDeleteThis(type, el) {
    el = $(el);
    switch (type) {


        case "IMGT":
        case "CHIM":

            var lst = '';

            if (type === 'IMGT')
                lst = p4.Results.Selection.serialize();
            if (type === 'CHIM')
                lst = p4.WorkZone.Selection.serialize();

            deleteThis(lst);

            return;
            break;


        case "SSTT":

            var buttons = {};
            buttons[language.valider] = function (e) {
                deleteBasket(el);
            };

            $('#DIALOG').empty().append(language.confirmDel).attr('title', language.attention).dialog({
                autoOpen: false,
                resizable: false,
                modal: true,
                draggable: false
            }).dialog('open').dialog('option', 'buttons', buttons);
            $('#tooltip').hide();
            return;
            break;
        case "STORY":
            lst = el.val();
            deleteThis(lst);
            break;

    }
}

function shareThis(bas, rec) {
    var dialog = p4.Dialog.Create({
        title: language['share']
    });

    dialog.load("../prod/share/record/" + bas + "/" + rec + "/", "GET");
}

function printThis(value) {
    if ($("#DIALOG").data("ui-dialog")) {
        $("#DIALOG").dialog('destroy');
    }
    $('#DIALOG').attr('title', language.print)
        .empty().addClass('loading')
        .dialog({
            resizable: false,
            closeOnEscape: true,
            modal: true,
            width: '800',
            height: '500',
            open: function (event, ui) {
                $(this).dialog("widget").css("z-index", "1999");
            },
            close: function (event, ui) {
                $(this).dialog("widget").css("z-index", "auto");
            }
        })
        .dialog('open');

    $.ajax({
        type: "POST",
        url: '../prod/printer/?' + value,
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


function downloadThis(datas) {
    var dialog = p4.Dialog.Create({title: language['export']});

    $.post("../prod/export/multi-export/", datas, function (data) {

        dialog.setContent(data);

        $('.tabs', dialog.getDomElement()).tabs();

        $('.close_button', dialog.getDomElement()).bind('click', function () {
            dialog.Close();
        });

        return false;
    });
}


function viewNbSelect() {
    $("#nbrecsel").empty().append(p4.Results.Selection.length());
}

function selector(el) {
    if (el.hasClass('all_selector')) {
        p4.Results.Selection.selectAll();
    }
    else {
        if (el.hasClass('none_selector')) {
            p4.Results.Selection.empty();
        }
        else {
            if (el.hasClass('starred_selector')) {

            }
            else {
                if (el.hasClass('video_selector')) {
                    p4.Results.Selection.empty();
                    p4.Results.Selection.select('.type-video');
                }
                else {
                    if (el.hasClass('image_selector')) {
                        p4.Results.Selection.empty();
                        p4.Results.Selection.select('.type-image');
                    }
                    else {
                        if (el.hasClass('document_selector')) {
                            p4.Results.Selection.empty();
                            p4.Results.Selection.select('.type-document');
                        }
                        else {
                            if (el.hasClass('audio_selector')) {
                                p4.Results.Selection.empty();
                                p4.Results.Selection.select('.type-audio');
                            }
                        }
                    }
                }
            }
        }
    }
}

function evt_dwnl(value) {
    downloadThis("lst=" + value);
}

function evt_print(value) {
    printThis("lst=" + value);
}

function evt_add_in_chutier(sbas_id, record_id, event, singleSelection) {
    var singleSelection = singleSelection || false;
    p4.WorkZone.addElementToBasket(sbas_id, record_id, event, singleSelection);
}

function remove_from_basket(el, confirm) {
    var confirm = confirm || false;
    p4.WorkZone.removeElementFromBasket(el, confirm);
}


function doSpecialSearch(qry, allbase) {
    if (allbase) {
        checkBases(true);
    }
    selectedFacets = {};
    $('#EDIT_query').val(decodeURIComponent(qry).replace(/\+/g, " "));
    newSearch(qry);
}

function clktri(id) {
    var o = $('#TOPIC_UL' + id);
    if ($('#TOPIC_UL' + id).hasClass('closed'))
        $('#TOPIC_TRI' + id + ' ,#TOPIC_UL' + id).removeClass('closed').addClass('opened');
    else
        $('#TOPIC_TRI' + id + ' ,#TOPIC_UL' + id).removeClass('opened').addClass('closed');
}


function archiveBasket(basket_id) {
    $.ajax({
        type: "POST",
        url: "../prod/baskets/" + basket_id + "/archive/?archive=1",
        dataType: 'json',
        beforeSend: function () {

        },
        success: function (data) {
            if (data.success) {
                var basket = $('#SSTT_' + basket_id);
                var next = basket.next();

                if (next.data("ui-droppable")) {
                    next.droppable('destroy');
                }

                next.slideUp().remove();

                if (basket.data("ui-droppable")) {
                    basket.droppable('destroy');
                }

                basket.slideUp().remove();

                if ($('#baskets .SSTT').length === 0) {
                    return p4.WorkZone.refresh(false);
                }
            }
            else {
                alert(data.message);
            }
            return;
        }
    });
}


function deleteBasket(item) {
    if ($("#DIALOG").data("ui-dialog")) {
        $("#DIALOG").dialog('destroy');
    }

    var k = $(item).attr('id').split('_').slice(1, 2).pop();	// id de chutier
    $.ajax({
        type: "POST",
        url: "../prod/baskets/" + k + '/delete/',
        dataType: 'json',
        beforeSend: function () {

        },
        success: function (data) {
            if (data.success) {
                var basket = $('#SSTT_' + k);
                var next = basket.next();

                if (next.data("ui-droppable")) {
                    next.droppable('destroy');
                }

                next.slideUp().remove();

                if (basket.data("ui-droppable")) {
                    basket.droppable('destroy');
                }

                basket.slideUp().remove();

                if ($('#baskets .SSTT').length === 0) {
                    return p4.WorkZone.refresh(false);
                }
            }
            else {
                alert(data.message);
            }
            return;
        }
    });
}

function deploy(deployer, todeploy_selector)
{
    if($(deployer).hasClass("deployer_opened")) {
        $(deployer).removeClass("deployer_opened").addClass("deployer_closed");
        $(todeploy_selector).hide();
    }
    else {
        $(deployer).removeClass("deployer_closed").addClass("deployer_opened");
        $(todeploy_selector).show();
    }
}

function clksbas(el, sbas_id) {
    var bool = $(el).prop('checked');
    $.each($('.sbascont_' + sbas_id + ' :checkbox'), function () {
        this.checked = bool;
    });

    checkFilters(true);
}


function advSearch(event) {
    event.cancelBubble = true;
    //  alternateSearch(false);
    $('#idFrameC .tabs a.adv_search').trigger('click');
}

function start_page_selector() {
    var el = $('#look_box_settings select[name=start_page]');

    switch (el.val()) {
        case "LAST_QUERY":
        case "PUBLI":
        case "HELP":
            $('#look_box_settings input[name=start_page_value]').hide();
            break;
        case "QUERY":
            $('#look_box_settings input[name=start_page_value]').show();
            break;
    }
}

function set_start_page() {
    var el = $('#look_box_settings select[name=start_page]');
    var val = el.val();


    var start_page_query = $('#look_box_settings input[name=start_page_value]').val();

    if (val === 'QUERY') {
        setPref('start_page_query', start_page_query);
    }

    setPref('start_page', val);

}

function basketPrefs() {
    $('#basket_preferences').dialog({
        closeOnEscape: true,
        resizable: false,
        width: 450,
        height: 500,
        modal: true,
        draggable: false,
        overlay: {
            backgroundColor: '#000',
            opacity: 0.7
        }
    }).dialog('open');
}

function lookBox(el, event) {
    $("#look_box").dialog({
        closeOnEscape: true,
        resizable: false,
        width: 450,
        height: 500,
        modal: true,
        draggable: false,
        overlay: {
            backgroundColor: '#000',
            opacity: 0.7
        }
    }).dialog('open');
}

function showAnswer(p) {
    var o;
    if (p === 'Results') {
        // on montre les results
        if (o = document.getElementById("AnswerExplain"))
            o.style.visibility = "hidden";
        if (o = document.getElementById("AnswerResults")) {
            o.style.visibility = "";
            o.style.display = "block";
        }
        // on montre explain
        if (document.getElementById("divpage"))
            document.getElementById("divpage").style.visibility = visibilityDivPage;

        if (document.getElementById("explainResults"))
            document.getElementById("explainResults").style.display = "none";
    }
    else {
        // on montre explain
        if (document.getElementById("divpage")) {
            visibilityDivPage = "visible";
            document.getElementById("divpage").style.visibility = "hidden";
        }
        if (document.getElementById("explainResults"))
            document.getElementById("explainResults").style.display = "block";

        if (o = document.getElementById("AnswerResults")) {
            o.style.visibility = "hidden";
            o.style.display = "none";

        }
        if (o = document.getElementById("AnswerExplain"))
            o.style.visibility = "";
        if (o = document.getElementById("AnswerExplain")) {
            o.style.display = "none";
            setTimeout('document.getElementById("AnswerExplain").style.display = "block";', 200);
        }
    }
}


/**  FROM INDEX.php **/
function saveeditPbar(idesc, ndesc) {
    document.getElementById("saveeditPbarI").innerHTML = idesc;
    document.getElementById("saveeditPbarN").innerHTML = ndesc;
}

function getSelText() {
    var txt = '';
    if (window.getSelection) {
        txt = window.getSelection();
    }
    else if (document.getSelection) {
        txt = document.getSelection();
    }
    else if (document.selection) {
        txt = document.selection.createRange().text;
    }
    else
        return;
    return txt;
}

function getWinPosAsXML() {
    var ret = '<win id="search" ratio="' + ($('#idFrameC').outerWidth() / bodySize.x) + '"/>';

    if ($('#idFrameE').is(':visible') && $('#EDITWINDOW').is(':visible'))
        ret += '<win id="edit" ratio="' + ($('#idFrameE').outerWidth() / $('#EDITWINDOW').innerWidth()) + '"/>';


    return ret;
}

function saveWindows() {
    var key = '';
    var value = '';


    if ($('#idFrameE').is(':visible') && $('#EDITWINDOW').is(':visible')) {
        key = 'edit_window';
        value = $('#idFrameE').outerWidth() / $('#EDITWINDOW').innerWidth();
    }
    else {
        key = 'search_window';
        value = $('#idFrameC').outerWidth() / bodySize.x;
    }
    setPref(key, value);
}

function gotopage(pag) {
    $('#searchForm input[name="sel"]').val(p4.Results.Selection.serialize());
    $('#formAnswerPage').val(pag);
    $('#searchForm').submit();
}

function addFilterMulti(filter, link, sbasid) {
    var clone = $('#filter_multi_' + sbasid + '_' + filter);
    var orig = clone;
    if (!$('#filter_multi_' + sbasid + '_' + filter).is(':visible')) {
        clone = orig.clone(true);
        var par = orig.parent();
        orig.remove();
        par.append(clone);
        clone.slideDown('fast', function () {
            $(this);
        });
        $(link).addClass('filterActive');
    }
    else {
        clone.slideUp();
        $(link).removeClass('filterActive');
    }
    return false;
}

function autoorder() {
    var val = $.trim($('#auto_order').val());

    if (val === '')
        return;

    var sorter = new Array();

    $('#reorder_box .diapo form').each(function (i, n) {

        var id = $('input[name=id]', n).val();

        switch (val) {
            case 'title':
            default:
                var data = $('input[name=title]', n).val();
                break;
            case 'default':
                var data = $('input[name=default]', n).val();
                break;
        }

        sorter[id] = data;
    });

    var data_type = 'string';

    switch (val) {
        case 'default':
            var data_type = 'integer';
            break;
    }

    sorter = arraySortByValue(sorter, data_type);

    var last_moved = false;

    for (i in sorter) {
        var elem = $('#ORDER_' + i);
        if (last_moved) {
            elem.insertAfter(last_moved);
        }
        else {
            $('#reorder_box').prepend(elem);
        }
        last_moved = elem;
    }

}

function setFacet(boolean) {
    setPref("facet", boolean);
    loadFacets(facets);
}

function setFacetOrder(order) {
    setPref("order_facet", order);
    loadFacets(facets);
}

function setFacetValueOrder(valueOrder) {
    setPref("facet_values_order", valueOrder);
    loadFacets(facets);
}


//clear search
$(document).ready(function () {

    $('#thesaurus_tab .input-medium').on('keyup', function(){
        if($('#thesaurus_tab .input-medium').val() != ''){
            $('#thesaurus_tab .th_clear').show();
        }else{
            $('#thesaurus_tab .th_clear').hide();
        }
    });

    $('.th_clear').on('click', function(){
        $('#thesaurus_tab .input-medium').val('');
        $('#thesaurus_tab .gform').submit();
        $('#thesaurus_tab .th_clear').hide();
    });

    $('.treeview>li.expandable>.hitarea').on('click', function(){
        if($(this).css('background-position') == '99% 22px'){
            $(this).css('background-position', '99% -28px');
            $(this).addClass('active');
        }else{
            $(this).css('background-position', '99% 22px');
            $(this).removeClass('active');
        }
    });

});
