import * as Rx from 'rx';
import $ from 'jquery';
import _ from 'underscore';
import user from './../../../phraseanet-common/components/user';

const searchAdvancedForm = (services) => {
    const {configService, localeService, appEvents} = services;
    const url = configService.get('baseUrl');
    let $container = null;
    /**
     * add "field" zone on advsearch
     *
     * @returns {jQuery|HTMLElement}
     * @constructor
     */
    function AdvSearchAddNewTerm() {
        var block_template = $('#ADVSRCH_FIELDS_ZONE DIV.term_select_wrapper_template');
        var last_block = $('#ADVSRCH_FIELDS_ZONE DIV.term_select_wrapper:last');
        if (last_block.length === 0) {
            last_block = block_template;
        }
        last_block = block_template.clone(true).insertAfter(last_block); // true: clone event handlers
        last_block.removeClass('term_select_wrapper_template').addClass('term_select_wrapper').show();
        last_block.css('background-color', '');

        return last_block;
    }

    const initialize = (options) => {
        let initWith = {$container} = options;
        let previousVal;
        const multi_term_select_html = $('.term_select_wrapper').html();


        // advanced
        $container.on('click', '.toggle-collection', (event) => {
            let $el = $(event.currentTarget);
            toggleCollection($el, $($el.data('toggle-content')));
        });

        $container.on('click', '.toggle-database', (event) => {
            let $el = $(event.currentTarget);
            let state = $el.data('state') || false;
            toggleAllDatabase(state);
        });

        $container.on('change', '.select-database', (event) => {
            let $el = $(event.currentTarget);
            let collectionId = $el.data('database');

            selectDatabase($el, collectionId);
        });

        $container.on('change', '.check-filters', (event) => {
            let $el = $(event.currentTarget);
            let shouldSave = $el.data('save') || false;

            checkFilters(shouldSave);
        });

        $('.field_switch').on('change', function (event) {
            checkFilters(true);
        });
        $container.on('click', '.search-reset-action', () => {
            resetSearch();
        });
        $container.on('click', '.reload-search', () => {
            resetSearch();
            $('#searchForm').submit();

        });

        $(document).on('focus', 'select.term_select_field', (event) => {
            previousVal = $(event.currentTarget).val();
        });
        $(document).on('change', 'select.term_select_field', (event) => {
            const $this = $(event.currentTarget);

            // if option is selected
            if ($this.val()) {
                $this.siblings().prop('disabled', false);
                let operatorEl = $this.siblings(".term_select_op");
                let valueEl = $this.siblings(".term_select_value");

                if ($this.find("option:selected").attr("data-fieldtype") == "number-FIELD") {
                    operatorEl.find("option.number-operator").show();
                    operatorEl.find("option.string-operator").hide();
                    operatorEl.val('=');// set default operator
                    valueEl.attr('type', "number");
                    valueEl.attr('placeholder', 'Ex: 249');
                } else {
                    operatorEl.find("option.number-operator").hide();
                    operatorEl.find("option.string-operator").show();
                    operatorEl.val(':');// set default operator
                    valueEl.removeAttr('pattern');
                    valueEl.removeAttr('type');
                    valueEl.attr('placeholder', 'Ex : Paris, bleu, montagne');
                }

                $('.term_select_multiple option').each((index, el) => {
                    let $el = $(el);
                    if ($this.val() === $el.val()) {
                        $el.prop('selected', true);
                    } else if (previousVal === $el.val()) {
                        $el.prop('selected', false);
                    }
                });
            } else {
                $this.siblings().prop('disabled', 'disabled');

                $('.term_select_multiple option').each((index, el) => {
                    let $el = $(el);
                    if (previousVal === $el.val()) {
                        $el.prop('selected', false);
                    }
                });
            }
            $this.blur();
            checkFilters(true);
        });

        $(document).on('change', 'select.term_select_op', (event) => {
            const $this = $(event.currentTarget);
            if ($this.val() === 'set' || $this.val() === 'unset') {
                $this.siblings('.term_select_value').prop('disabled', 'disabled');
            } else {
                $this.siblings('.term_select_value').prop('disabled', false);
            }
        });

        $(document).on('click', '.term_deleter', (event) => {
            event.preventDefault();
            let $this = $(event.currentTarget);
            $this.closest('.term_select_wrapper').remove();
            checkFilters(true);
        });

        $('.add_new_term').on('click', (event) => {
            event.preventDefault();
            AdvSearchAddNewTerm(1);
        });

        // @TODO - check if usefull
        /**
         * inform global app for state
         * @TODO refactor
         */
        $('#EDIT_query').bind('focus', function () {
            $(this).addClass('focused');
        }).bind('blur', function () {
            $(this).removeClass('focused');
        });
    };

    /**
     * adv search : check/uncheck all the collections (called by the buttons "all"/"none")
     *
     * @param bool
     */
    const toggleAllDatabase = (bool) => {
        $('form.phrasea_query .sbas_list').each(function () {

            var sbas_id = $(this).find('input[name=reference]:first').val();
            if (bool) {
                $(this).find(':checkbox').prop('checked', true);
            } else {
                $(this).find(':checkbox').prop('checked', false);
            }
        });

        checkFilters(true);
    };

    const toggleCollection = ($el, $elContent) => {
        if ($el.hasClass('deployer_opened')) {
            $el.removeClass('deployer_opened').addClass('deployer_closed');
            $elContent.hide();
        } else {
            $el.removeClass('deployer_closed').addClass('deployer_opened');
            $elContent.show();
        }
    };

    const selectDatabase = ($el, sbas_id) => {
        var bool = $el.prop('checked');
        $.each($('.sbascont_' + sbas_id + ' :checkbox'), function () {
            this.checked = bool;
        });

        checkFilters(true);
    };
    const activateDatabase = (databaseCollection) => {
        // disable all db,
        toggleAllDatabase(false);
        // then enable only provided
        _.each(databaseCollection, (databaseId) => {
            _.each($('.sbascont_' + databaseId + ' :checkbox'), (checkbox) => {
                $(checkbox).prop('checked', true);
            });
        });

    };

    $('#ADVSRCH_DATE_SELECTORS input').change(function () {
        checkFilters(true);
    });

    const checkFilters = (save) => {
        var danger = false;
        var search = {
            bases: {},
            fields: [],
            dates: {},
            status: [],
            elasticSort: {}

        };

        var adv_box = $('form.phrasea_query .adv_options');
        var container = $('#ADVSRCH_OPTIONS_ZONE');
        var fieldsSort = $('#ADVSRCH_SORT_ZONE select[name=sort]', container);
        var fieldsSortOrd = $('#ADVSRCH_SORT_ZONE select[name=ord]', container);
        var fieldsSelect = $('#ADVSRCH_FIELDS_ZONE select.term_select_multiple', container);
        var fieldsSelectFake = $('#ADVSRCH_FIELDS_ZONE select.term_select_field', container);
        var statusField = $('#ADVSRCH_FIELDS_ZONE .danger_indicator', container);
        var statusFilters = $('#ADVSRCH_SB_ZONE .status-section-title .danger_indicator', container);
        var dateFilterSelect = $('#ADVSRCH_DATE_ZONE select', container);
        var scroll = fieldsSelect.scrollTop();

        // hide all the fields in the "sort by" select, so only the relevant ones will be shown again
        $('option.dbx', fieldsSort).hide().prop('disabled', true);  // dbx is for "field of databases"

        // hide all the fields in the "fields" select, so only the relevant ones will be shown again
        $('option.dbx', fieldsSelect).hide().prop('disabled', true);     // option[0] is "all fields"
        $('option.dbx', fieldsSelectFake).hide().prop('disabled', true);

        // disable the whole select
        $('#ADVSRCH_FIELDS_ZONE .term_select_wrapper select.term_select_field', container).prop('disabled', true);
        $('#ADVSRCH_FIELDS_ZONE .term_select_wrapper select.term_select_op', container).prop('disabled', true);
        $('#ADVSRCH_FIELDS_ZONE .term_select_wrapper input.term_select_value', container).prop('disabled', true);

        // hide all the fields in the "date field" select, so only the relevant ones will be shown again
        $('option.dbx', dateFilterSelect).hide().prop('disabled', true);   // dbx = all "field" entries in the select = all except the firstt

        statusFilters.removeClass('danger');
        $.each($('#ADVSRCH_SB_ZONE .field_switch'), function (index, el) {
            if ($(el).prop('checked') === true) {
                danger = true;
                statusFilters.addClass('danger');
            }
        });
        // enable also the select if the first option ("choose:") was selected
        statusField.removeClass('danger');
        fieldsSelectFake.each(function (e) {
            var $this = $(this);
            if ($this.val() !== '') {
                danger = true;
                statusField.addClass('danger');
            }
        });
        var nbTotalSelectedColls = 0;
        // if one coll is not checked, show danger for ADVSRCH_SBAS_ZONE
        $('#ADVSRCH_SBAS_ZONE').each(function () {
            var $this = (0, $)(this);
            var nbSelectedColls = 0;
            $this.find('.checkbas').each(function (idx, el) {
                if ($(this).prop('checked') === false) {
                    nbSelectedColls++;
                }
            });
            if (nbSelectedColls > 0) {
                $('#ADVSRCH_SBAS_ZONE').addClass('danger');
                danger = true;
            } else {
                $('#ADVSRCH_SBAS_ZONE').removeClass('danger');

            }
        });
        $.each($('.sbascont', adv_box), function () {
            var $this = $(this);

            var sbas_id = $this.parent().find('input[name="reference"]').val();
            search.bases[sbas_id] = [];

            var nbCols = 0;
            var nbSelectedColls = 0;
            $this.find('.checkbas').each(function (idx, el) {
                nbCols++;
                if ($(this).prop('checked')) {
                    nbSelectedColls++;
                    nbTotalSelectedColls++;
                    search.bases[sbas_id].push($(this).val());
                }
            });

            // display the number of selected colls for the databox
            if (nbSelectedColls === nbCols) {
                $('.infos_sbas_' + sbas_id).empty().append(nbCols);
                $(this).siblings('.clksbas').removeClass('danger');
                $(this).siblings('.clksbas').find('.custom_checkbox_label input').prop('checked', 'checked');
            } else {
                $('.infos_sbas_' + sbas_id).empty().append('<span style="color:#2096F3;font-size: 20px;">' + nbSelectedColls + '</span> / ' + nbCols);
                $(this).siblings('.clksbas').addClass('danger');
                danger = true;
            }

            // if one coll is not checked, show danger
            if (nbSelectedColls !== nbCols) {
                $('#ADVSRCH_SBAS_ZONE').addClass('danger');
                danger = true;
            } else if (nbSelectedColls === nbCols && danger === false) {
                $('#ADVSRCH_SBAS_ZONE').removeClass('danger');

            }

            if (nbSelectedColls === 0) {
                // no collections checked for this databox
                // hide the status bits
                $('#ADVSRCH_SB_ZONE_' + sbas_id, container).hide();
                // uncheck
                $('#ADVSRCH_SB_ZONE_' + sbas_id + ' input:checkbox', container).prop('checked', false);
            } else {
                // at least one coll checked for this databox
                // show again the relevant fields in "sort by" select
                $('.db_' + sbas_id, fieldsSort).show().prop('disabled', false);
                // show again the relevant fields in "from fields" select
                $('.db_' + sbas_id, fieldsSelect).show().prop('disabled', false);
                $('.db_' + sbas_id, fieldsSelectFake).show().prop('disabled', false);
                // show the sb
                $('#ADVSRCH_SB_ZONE_' + sbas_id, container).show();
                // show again the relevant fields in "date field" select
                $('.db_' + sbas_id, dateFilterSelect).show().prop('disabled', false);
            }
        });

        // enable also the select if the first option ("choose:") was selected
        statusField.removeClass('danger');
        fieldsSelectFake.each(function(e) {
            var $this = $(this);
            var term_ok = $('option:selected:enabled', $this).closest(".term_select_wrapper");
            $("select.term_select_field", term_ok).prop('disabled', false);
            if($this.val() !== "") {
                $("select.term_select_op", term_ok).prop('disabled', false);
                $("input.term_select_value", term_ok).prop('disabled', false);
                danger = true;
                statusField.addClass('danger');
            }
        });

        if (nbTotalSelectedColls === 0) {
            // no collections checked at all
            // hide irrelevant filters
            $('#ADVSRCH_OPTIONS_ZONE').hide();
        } else {
            // at least one collection checked
            // show relevant filters
            $('#ADVSRCH_OPTIONS_ZONE').show();
        }

        // --------- sort  --------

        // if no field is selected for sort, select the default option
        if ($('option:selected:enabled', fieldsSort).length === 0) {
            $('option.default-selection', fieldsSort).prop('selected', true);
            $('option.default-selection', fieldsSortOrd).prop('selected', true);
        }

        search.elasticSort.by = $('option:selected:enabled', fieldsSort).val();
        search.elasticSort.order = $('option:selected:enabled', fieldsSortOrd).val();

        // --------- from fields filter ---------

        // unselect the unavailable fields (or all fields if "all" is selected)
        var optAllSelected = false;
        $('option', fieldsSelect).each(
            function (idx, opt) {
                if (idx === 0) {
                    // nb: unselect the "all" field, so it acts as a button
                    optAllSelected = $(opt).is(':selected');
                }
                if (idx === 0 || optAllSelected || $(opt).is(':disabled') || $(opt).css('display') === 'none') {
                    $(opt).prop('selected', false);
                }
            }
        );


        // --------- status bits filter ---------

        // here only the relevant sb are checked
        const availableDb = search.bases;
        for (let sbas_id in availableDb) {

            var n_checked = 0;
            var n_unchecked = 0;
            $('#ADVSRCH_SB_ZONE_' + sbas_id + ' :checkbox', container).each(function (k, o) {
                var n = $(this).data('sb');
                if ($(o).attr('checked')) {
                    search.status[n] = $(this).val().split('_');
                    n_checked++;
                } else {
                    n_unchecked++;
                }
            });
            if (n_checked === 0) {
                $('#ADVSRCH_SB_ZONE_' + sbas_id, container).removeClass('danger');
            } else {
                $('#ADVSRCH_SB_ZONE_' + sbas_id, container).addClass('danger');
                danger = true;
            }
        }

        // --------- dates filter ---------

        // if no date field is selected for filter, select the first option
        $('#ADVSRCH_DATE_ZONE', adv_box).removeClass('danger');
        if ($('option:selected:enabled', dateFilterSelect).length === 0) {
            $('option:eq(0)', dateFilterSelect).prop('selected', true);
        }
        if ($('option:selected', dateFilterSelect).val() !== '') {
            $('#ADVSRCH_DATE_SELECTORS', container).show();
            search.dates.minbound = $('#ADVSRCH_DATE_ZONE input[name=date_min]', adv_box).val();
            search.dates.maxbound = $('#ADVSRCH_DATE_ZONE input[name=date_max]', adv_box).val();
            search.dates.field = $('#ADVSRCH_DATE_ZONE select[name=date_field]', adv_box).val();
            if ($.trim(search.dates.minbound) || $.trim(search.dates.maxbound)) {
                danger = true;
                $('#ADVSRCH_DATE_ZONE', adv_box).addClass('danger');
            }
        } else {
            $('#ADVSRCH_DATE_SELECTORS', container).hide();
            $('#ADVSRCH_DATE_ZONE input[name=date_min]').val("");
            $('#ADVSRCH_DATE_ZONE input[name=date_max]').val("");
        }

        fieldsSelect.scrollTop(scroll);


        // if one filter shows danger, show it on the query
       /* if (danger) {*/
        if ($('#ADVSRCH_DATE_ZONE', adv_box).hasClass('danger') || $('#ADVSRCH_SB_ZONE .danger_indicator', adv_box).hasClass('danger') || $('#ADVSRCH_FIELDS_ZONE .danger_indicator', adv_box).hasClass('danger') || $('#ADVSRCH_SBAS_ZONE', adv_box).hasClass('danger')) {
                $('#EDIT_query').addClass('danger');
        } else {
            $('#EDIT_query').removeClass('danger');
        }

        if (save === true) {
            user.setPref('search', JSON.stringify(search));
        }
    };

    const saveHiddenFacetsList = (hiddenFacetsList) => {
        user.setPref('hiddenFacetsList', JSON.stringify(hiddenFacetsList));
    }

    function findClauseBy_ux_zone(clause, ux_zone) {
        //  console.log('find clause' + ux_zone);
        if (typeof clause._ux_zone != 'undefined' && clause._ux_zone === ux_zone) {
            return clause;
        }
        if (clause.type === "CLAUSES") {
            for (var i = 0; i < clause.clauses.length; i++) {
                var r = findClauseBy_ux_zone(clause.clauses[i], ux_zone);
                if (r != null) {
                    return r;
                }
            }
        }
        return null;
    }

    /**
     * add "field" zone on advsearch
     *
     * @returns {jQuery|HTMLElement}
     * @constructor
     */
    function AdvSearchFacetAddNewTerm() {
        var block_template = $('#ADVSRCH_FIELDS_ZONE DIV.term_select_wrapper_template');
        var last_block = $('#ADVSRCH_FIELDS_ZONE DIV.term_select_wrapper:last');
        if (last_block.length === 0) {
            last_block = block_template;
        }
        last_block = block_template.clone(true).insertAfter(last_block); // true: clone event handlers
        last_block.removeClass('term_select_wrapper_template').addClass('term_select_wrapper').show();
        last_block.css('background-color', '');
        return last_block;
    }

    function restoreJsonQuery(args) {
        var jsq = args.jsq;
        var submit = args.submit;

        var clause;

        // restore the "fulltext" input-text
        clause = findClauseBy_ux_zone(jsq.query, "FULLTEXT");
        if (clause) {
            $('#EDIT_query').val(clause.value);
        }

        // restore the "bases" checkboxes
        if(! _.isUndefined(jsq.bases)) {
            $('#ADVSRCH_SBAS_ZONE .sbas_list .checkbas').prop('checked', false);
            if (jsq.bases.length > 0) {
                for (var k = 0; k < jsq.bases.length; k++) {
                    $('#ADVSRCH_SBAS_ZONE .sbas_list .checkbas[value="' + jsq.bases[k] + '"]').prop('checked', true);
                }
            } else {
                // special case : EMPTY array ==> since it's a nonsense, check ALL bases
                $('#ADVSRCH_SBAS_ZONE .sbas_list .checkbas').prop('checked', true);
            }
        }

        // restore the status-bits (for now dual checked status are restored unchecked)
        if(! _.isUndefined(jsq.statuses)) {
            $('#ADVSRCH_SB_ZONE INPUT:checkbox').prop('checked', false);
            _.each(jsq.statuses, function (db_statuses) {
                var db = db_statuses.databox;
                _.each(db_statuses.status, function (sb) {
                    var i = sb.index;
                    var v = sb.value ? '1' : '0';
                    $("#ADVSRCH_SB_ZONE INPUT[name='status[" + db_statuses.databox + '][' + sb.index + "]'][value=" + v + ']').prop('checked', true);
                });
            });
        }

        // restore the "records/stories" radios
        if(! _.isUndefined(jsq.phrasea_recordtype)) {
            $('#searchForm INPUT[name=search_type][value="' + (jsq.phrasea_recordtype == 'STORY' ? '1' : '0') + '"]').prop('checked', true); // check one radio will uncheck siblings
        }

        // restore the "record type" menu (image, video, audio, ...)
        if(! _.isUndefined(jsq.phrasea_mediatype)) {
            $('#searchForm SELECT[name=record_type] OPTION[value="' + jsq.phrasea_mediatype.toLowerCase() + '"]').prop('selected', true);
        }

        // restore the "use truncation" checkbox
        if(! _.isUndefined(jsq.phrasea_mediatype) && jsq.phrasea_mediatype == 'true') {
            $('#ADVSRCH_USE_TRUNCATION').prop('checked', jsq.phrasea_mediatype);
        }

        // restore the "sort results" menus
        if(! _.isUndefined(jsq.sort)) {
            if(! _.isUndefined(jsq.sort.field)) {
                $('#ADVSRCH_SORT_ZONE SELECT[name=sort] OPTION[value="' + jsq.sort.field + '"]').prop('selected', true);
            }
            if(! _.isUndefined(jsq.sort.order)) {
                $('#ADVSRCH_SORT_ZONE SELECT[name=ord] OPTION[value="' + jsq.sort.order + '"]').prop('selected', true);
            }
        }

        // restore the multiples "fields" (field-menu + op-menu + value-input)
        clause = findClauseBy_ux_zone(jsq.query, "FIELDS");
        if (clause) {
            $('#ADVSRCH_FIELDS_ZONE INPUT[name=must_match][value="' + clause.must_match + '"]').attr('checked', true);
            $('#ADVSRCH_FIELDS_ZONE DIV.term_select_wrapper').remove();
            for (var j = 0; j < clause.clauses.length; j++) {
                var wrapper = AdvSearchFacetAddNewTerm(); // div.term_select_wrapper
                var f = $(".term_select_field", wrapper);
                var o = $(".term_select_op", wrapper);
                var v = $(".term_select_value", wrapper);

                f.data('fieldtype', clause.clauses[j].type);
                $('option[value="' + clause.clauses[j].field + '"]', f).prop('selected', true);
                $('option[value="' + clause.clauses[j].operator + '"]', o).prop('selected', true);
                if (clause.clauses[j].operator === ":" || clause.clauses[j].operator === "=") {
                    o.prop('disabled', false);
                    v.val(clause.clauses[j].value).prop('disabled', false);
                }
            }
        }

        // restore the "date field" (field-menu + from + to)
        clause = findClauseBy_ux_zone(jsq.query, "DATE-FIELD");
        if (clause) {
            $("#ADVSRCH_DATE_ZONE SELECT[name=date_field] option[value='" + clause.field + "']").prop('selected', true);
            $("#ADVSRCH_DATE_ZONE INPUT[name=date_min]").val(clause.from);
            $("#ADVSRCH_DATE_ZONE INPUT[name=date_max]").val(clause.to);
            if ($("#ADVSRCH_DATE_ZONE SELECT[name=date_field]").val() !== '') {
                $("#ADVSRCH_DATE_SELECTORS").show();
                // $('#ADVSRCH_DATE_ZONE').addClass('danger');
            }
        }

        // restore the selected facets (whole saved as custom property)
        if(! _.isUndefined(jsq._selectedFacets)) {
            appEvents.emit('facets.setSelectedFacets', jsq._selectedFacets);
            //(0, _index2.default)(services).setSelectedFacets(jsq._selectedFacets);
            // selectedFacets = jsq._selectedFacets;
        }

        // the ux is restored, finish the job (hide unavailable fields/status etc, display "danger" where needed)
        appEvents.emit('searchAdvancedForm.checkFilters');
        //loadFacets([]);  // useless, facets will be restored after the query is sent

        if(submit) {
            appEvents.emit('search.doRefreshState');
        }
    }

    var resetSearch = function resetSearch() {
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

        restoreJsonQuery({'jsq':jsq, 'submit':false});
    };

    appEvents.listenAll({
        'searchAdvancedForm.checkFilters':         checkFilters,
        'searchAdvancedForm.selectDatabase':       selectDatabase,
        'searchAdvancedForm.activateDatabase':     function (params) {
            return activateDatabase(params.databases);
        },
        'searchAdvancedForm.toggleCollection':     toggleCollection,
        'searchAdvancedForm.saveHiddenFacetsList': saveHiddenFacetsList,
        'searchAdvancedForm.restoreJsonQuery':     restoreJsonQuery
    });

    return { initialize: initialize };
};

export default searchAdvancedForm;
