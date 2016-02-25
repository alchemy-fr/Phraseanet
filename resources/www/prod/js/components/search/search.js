var p4 = p4 || {};

var searchModule = (function (p4) {
    function toggleCollection(deployer, todeploy_selector)
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
    /**
     * adv search : check/uncheck all the collections (called by the buttons "all"/"none")
     *
     * @param bool
     */
    function toggleDatabase(bool) {
        $('form.phrasea_query .sbas_list').each(function () {

            var sbas_id = $(this).find('input[name=reference]:first').val();
            if (bool)
                $(this).find(':checkbox').prop('checked', true);
            else
                $(this).find(':checkbox').prop('checked', false);
        });

        checkFilters(true);
    }

    function resetSearch() {
        var container = $("#ADVSRCH_OPTIONS_ZONE");
        var fieldsSort = $('#ADVSRCH_SORT_ZONE select[name=sort]', container);
        var fieldsSortOrd = $('#ADVSRCH_SORT_ZONE select[name=ord]', container);
        var dateFilterSelect = $('#ADVSRCH_DATE_ZONE select', container);

        $("option.default-selection", fieldsSort).prop("selected", true);
        $("option.default-selection", fieldsSortOrd).prop("selected", true);

        $('#ADVSRCH_FIELDS_ZONE option').prop("selected", false);
        $('#ADVSRCH_OPTIONS_ZONE input:checkbox.field_switch').prop("checked", false);

        $("option:eq(0)", dateFilterSelect).prop("selected", true);
        $('#ADVSRCH_OPTIONS_ZONE .datepicker').val('');
        $('form.adv_search_bind input:text').val('');
        toggleDatabase(true);
    }

    function selectDatabase(el, sbas_id) {
        console.log('ok select')
        var bool = $(el).prop('checked');
        $.each($('.sbascont_' + sbas_id + ' :checkbox'), function () {
            this.checked = bool;
        });

        checkFilters(true);
    }

    function clearAnswers() {
        $('#formAnswerPage').val('');
        $('#searchForm input[name="nba"]').val('');
        $('#answers, #dyn_tool').empty();
    }

    function newSearch(query) {
        p4.Results.Selection.empty();

        searchModule.clearAnswers();
        $('#SENT_query').val(query);
        var histo = $('#history-queries ul');

        histo.prepend('<li onclick="prodModule.doSpecialSearch(\'' + query.replace(/\'/g, "\\'") + '\')">' + query + '</li>');

        var lis = $('li', histo);
        if (lis.length > 25) {
            $('li:last', histo).remove();
        }

        $('#idFrameC li.proposals_WZ').removeClass('active');

        $('#searchForm').submit();
        return false;
    }

    function beforeSearch() {
        if (answAjaxrunning)
            return;
        answAjaxrunning = true;

        searchModule.clearAnswers();
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
        prodModule.linearizeUi();
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
        var fieldsSelect = $('#ADVSRCH_FIELDS_ZONE select', container);
        var dateFilterSelect = $('#ADVSRCH_DATE_ZONE select', container);
        var scroll = fieldsSelect.scrollTop();

        // hide all the fields in the "sort by" select, so only the relevant ones will be shown again
        $("option.dbx", fieldsSort).hide().prop("disabled", true);  // dbx is for "field of databases"

        // hide all the fields in the "fields" select, so only the relevant ones will be shown again
        $("option.dbx", fieldsSelect).hide().prop("disabled", true);     // option[0] is "all fields"

        // hide all the fields in the "date field" select, so only the relevant ones will be shown again
        $("option.dbx", dateFilterSelect).hide().prop("disabled", true);   // dbx = all "field" entries in the select = all except the firstt

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
            $('.infos_sbas_' + sbas_id).empty().append(nbSelectedColls + '/' + nbCols);

            // if one coll is not checked, show danger
            if(nbSelectedColls != nbCols) {
                $("#ADVSRCH_SBAS_LABEL_" + sbas_id).addClass("danger");
                danger = true;
            }
            else {
                $("#ADVSRCH_SBAS_LABEL_" + sbas_id).removeClass("danger");
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
                $(".db_"+sbas_id, fieldsSelect).show().prop("disabled", false);
                // show the sb
                $("#ADVSRCH_SB_ZONE_"+sbas_id, container).show();
                // show again the relevant fields in "date field" select
                $(".db_"+sbas_id, dateFilterSelect).show().prop("disabled", false);
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

        //--------- from fields filter ---------

        // unselect the unavailable fields (or all fields if "all" is selected)
        var optAllSelected = false;
        $("option", fieldsSelect).each(
            function(idx, opt) {
                if(idx == 0) {
                    // nb: unselect the "all" field, so it acts as a button
                    optAllSelected = $(opt).is(":selected");
                }
                if(idx == 0 || optAllSelected || $(opt).is(":disabled") || !$(opt).is(":visible") ) {
                    $(opt).prop("selected", false);
                }
            }
        );

        // here only the relevant fields are selected
        search.fields = fieldsSelect.val();
        if(search.fields == null || search.fields.length == 0) {
            $('#ADVSRCH_FIELDS_ZONE', container).removeClass('danger');
            search.fields = [];
        }
        else {
            $('#ADVSRCH_FIELDS_ZONE', container).addClass('danger');
            danger = true;
        }

        //--------- status bits filter ---------

        // here only the relevant sb are checked
        for(sbas_id in search.bases) {
            var nchecked = 0;
            $("#ADVSRCH_SB_ZONE_"+sbas_id+" :checkbox[checked]", container).each(function () {
                var n = $(this).attr('n');
                search.status[n] = $(this).val().split('_');
                nchecked++;
            });
            if(nchecked == 0) {
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
        if($("option.dbx:selected:enabled", dateFilterSelect).length == 0) {
            $("option:eq(0)", dateFilterSelect).prop("selected", true);
            $("#ADVSRCH_DATE_SELECTORS", container).hide();
        }
        else {
            $("#ADVSRCH_DATE_SELECTORS", container).show();
            search.dates.minbound = $('#ADVSRCH_DATE_ZONE input[name=date_min]', adv_box).val();
            search.dates.maxbound = $('#ADVSRCH_DATE_ZONE input[name=date_max]', adv_box).val();
            search.dates.field = $('#ADVSRCH_DATE_ZONE select[name=date_field]', adv_box).val();
            console.log(search.dates.minbound, search.dates.maxbound, search.dates.field)
            if ($.trim(search.dates.minbound) || $.trim(search.dates.maxbound)) {
                danger = true;
                $('#ADVSRCH_DATE_ZONE', adv_box).addClass('danger');
            }
        }

        fieldsSelect.scrollTop(scroll);

        // if one filter shows danger, show it on the query
        if (danger) {
            $('#EDIT_query').addClass('danger');
        }
        else {
            $('#EDIT_query').removeClass('danger');
        }

        if (save === true) {
            userModule.setPref('search', JSON.stringify(search));
        }
    }

    function viewNbSelect() {
        $("#nbrecsel").empty().append(p4.Results.Selection.length());
    }


    return {
        checkFilters: checkFilters,
        toggleDatabase: toggleDatabase,
        toggleCollection: toggleCollection,
        selectDatabase: selectDatabase,
        beforeSearch: beforeSearch,
        afterSearch: afterSearch,
        clearAnswers: clearAnswers,
        newSearch: newSearch,
        resetSearch: resetSearch,
        viewNbSelect: viewNbSelect
    };
}(p4));
