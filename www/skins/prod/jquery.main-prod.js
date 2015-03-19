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
            newSearch();
            break;
        case 'PUBLI':
            answAjax = $.ajax({
                type: "GET",
                url: "../prod/feeds/",
                dataType: 'html',
                data: {
                    page: page
                },
                beforeSend: function () {
                    if (answAjaxrunning && answAjax.abort)
                        answAjax.abort();
                    if (page === 0)
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
                    var answers = $('#answers');

                    $('.next_publi_link', answers).remove();

                    answers.append(data);

                    answers.find("img.lazyload").lazyload({
                        container: answers
                    });

                    afterSearch();
                    if (page > 0) {
                        answers.stop().animate({
                            scrollTop: answers.scrollTop() + answers.height()
                        }, 700);
                    }
                    return;
                }

            });
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


function checkBases(bool) {
    $('form.phrasea_query .sbas_list').each(function () {

        var id = $(this).find('input[name=reference]:first').val();
        if (bool)
            $(this).find(':checkbox').attr('checked', 'checked');
        else
            $(this).find(':checkbox').removeAttr('checked');
        infoSbas(false, id, true, false);

    });
    if (bool) {
        $('.sbascont label').addClass('selected');
    }
    else {
        $('.sbascont label').removeClass('selected');
    }
    checkFilters(true);
}

function checkFilters(save) {
    var danger = false;
    var search = {
        bases: {},
        fields: {},
        dates: {},
        status: {}
    };

    var adv_box = $('form.phrasea_query .adv_options');
    var container = $("#sbasfiltercont");
    var fieldsSelect = $('.field_filter select', container);
    var filters = $('.field_filter, .status_filter, .date_filter', adv_box);
    var scroll = fieldsSelect.scrollTop();
    var switches = $('.field_switch', container);

    switches.filter('.was').removeClass('was');
    switches.filter('option:selected, input:checked').addClass('was');

    $('select option:selected:not(".default-selection")', container).removeAttr('selected').selected(false);

    $('select option.field_switch', container).addClass("hidden");

    $('input.field_switch:checked', container).removeAttr('checked');

    filters.removeClass('danger');

    var nbSelectedColls = 0;

    $.each($('.sbascont', adv_box), function () {
        var $this = $(this);

        var sbas_id = $this.parent().find('input[name="reference"]').val();
        search.bases[sbas_id] = new Array();

        var bas_ckbox = $this.find('.checkbas');

        if (bas_ckbox.filter(':not(:checked)').length > 0) {
            danger = 'medium';
        }

        var checked = bas_ckbox.filter(':checked');

        if (checked.length > 0) {
            var sbas_fields = $('.field_' + sbas_id, container).removeClass("hidden");
            sbas_fields.filter('option').show().filter('.was').removeClass('was').attr('selected', 'selected').selected(true);
            sbas_fields.filter(':checkbox').parent().show().find('.was').attr('checked', 'checked').removeClass('was');
        }

        checked.each(function () {
            nbSelectedColls++;
            search.bases[sbas_id].push($(this).val());
        });
    });

    if (nbSelectedColls === 0) {
        filters.addClass("danger");
    }

    search.fields = (search.fields = fieldsSelect.val()) !== null ? search.fields : new Array;

    var reset_field = false;

    $.each(search.fields, function (i, n) {
        if (n === 'phraseanet--all--fields')
            reset_field = true;
    });

    if (reset_field) {
        $('select[name="fields[]"] option:selected', container).removeAttr('selected').selected(false);
        search.fields = new Array;
    }

    if (!reset_field && search.fields.length > 0) {
        danger = true;
        $('.field_filter', adv_box).addClass('danger');
    }

    $('.status_filter :checkbox[checked]').each(function () {

        var n = $(this).attr('n');
        search.status[n] = $(this).val().split('_');
        danger = true;
        $('.status_filter', adv_box).addClass('danger');
    });

    search.dates.minbound = $('.date_filter input[name=date_min]', adv_box).val();
    search.dates.maxbound = $('.date_filter input[name=date_max]', adv_box).val();
    search.dates.field = $('.date_filter select[name=date_field]', adv_box).val();

    if ($.trim(search.dates.minbound) || $.trim(search.dates.maxbound)) {
        danger = true;
        $('.date_filter', adv_box).addClass('danger');
    }

    fieldsSelect.scrollTop(scroll);

    if (save === true)
        setPref('search', JSON.stringify(search));

    if (danger === true || danger === 'medium')
        $('#EDIT_query').addClass('danger');
    else
        $('#EDIT_query').removeClass('danger');
}
function toggleFilter(filter, ele) {
    var el = $('#' + filter);
    if (el.is(':hidden'))
        $(ele).parent().addClass('open');
    else
        $(ele).parent().removeClass('open');
    $('#' + filter).slideToggle('fast');
}


function setVisible(el) {
    el.style.visibility = 'visible';
}

function resize() {
    bodySize.y = $('#mainContainer').height();
    bodySize.x = $('#mainContainer').width();

    if (false)
        $('.overlay').height(bodySize.y).width(bodySize.x);

    var headBlockH = $('#headBlock').outerHeight();
    var bodyY = bodySize.y - headBlockH - 2;
    var bodyW = bodySize.x - 2;
    //$('#desktop').height(bodyY).width(bodyW);

    if (p4.preview.open)
        resizePreview();

    if ($('#idFrameC').data('ui-resizable')) {
        $('#idFrameC').resizable('option', 'maxWidth', (480));
        $('#idFrameC').resizable('option', 'minWidth', 300);
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
    $('#sbasfiltercont select[name="sort"]').val($('#sbasfiltercont select[name="sort"] option.default-selection').attr('value'));
    $('#sbasfiltercont input:checkbox.field_switch').removeAttr('checked');
    $('#sbasfiltercont .datepicker').val('');
    $('form.adv_search_bind input:text').val('');
    checkBases(true);
}

function search_doubles() {
    $('#EDIT_query').val('sha256=sha256');
    newSearch();
}

function newSearch() {
    p4.Results.Selection.empty();

    clearAnswers();
    var val = $('#searchForm input[name="qry"]').val();
    var histo = $('#history-queries ul');

    histo.prepend('<li onclick="doSpecialSearch(\'' + val.replace(/\'/g, "\\'") + '\')">' + val + '</li>');

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
    $('.captionTips, .captionRolloverTips, .infoTips').tooltip({
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
    linearize();
}

function initAnswerForm() {

    var searchForm = $('#searchForm');
    $('button[type="submit"]', searchForm).bind('click', function () {

        newSearch();
        $('searchForm').trigger('submit');
        return false;
    });

    searchForm.unbind('submit').bind('submit', function () {

        var $this = $(this),
            method = $this.attr('method') ? $this.attr('method') : 'POST';

        answAjax = $.ajax({
            type: method,
            url: $this.attr('action'),
            data: $this.serialize(),
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

                $('#answers').empty().append(datas.results).removeClass('loading');

                $("#answers img.lazyload").lazyload({
                    container: $('#answers')
                });

                loadFacets(datas.facets);

                $('#answers').append('<div id="paginate"><div class="navigation"><div id="tool_navigate"></div></div></div>');

                $('#tool_results').empty().append(datas.infos);
                $('#tool_navigate').empty().append(datas.navigation);

                if($('input.btn-mini').length > 0){
                    $('#answers .diapo').last().after('<div id="answersNext" class="IMGT diapo  type-image ui-draggable">Next</div>');
                }


                $.each(p4.Results.Selection.get(), function (i, el) {
                    $('#IMGT_' + el).addClass('selected');
                });

                p4.tot = datas.total_answers;
                p4.tot_options = datas.form;
                p4.tot_query = datas.query;

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
    if (searchForm.hasClass('triggerAfterInit')) {
        searchForm.removeClass('triggerAfterInit').trigger('submit');
    }
}

function loadFacets(facets) {
    // Convert facets data to fancytree source format
    var treeSource = _.map(facets, function(facet) {
        // Values
        var values = _.map(facet.values, function(value) {
            return {
                title: value.value + ' (' + value.count + ')',
                query: value.query
            }
        });
        // Facet
        return {
            title: facet.name,
            folder: true,
            children: values,
            expanded: true
        };
    });
    return getFacetsTree().reload(treeSource);
}

function getFacetsTree() {
    var $facetsTree = $('#proposals');
    if (!$facetsTree.data('ui-fancytree')) {
        $facetsTree.fancytree({
            source: [],
            activate: function(event, data){
                var query = data.node.data.query;
                if (!query) return;
                facetCombinedSearch(query);
            }
        });
    }
    return $facetsTree.fancytree('getTree');
}

var $searchForm;
var $searchInput;
var $facetsBackButton;
var facetsQueriesStack = [];

function facetCombinedSearch(query) {
    var currentQuery = $searchInput.val();
    facetsQueriesStack.push(currentQuery);
    $facetsBackButton.show();
    if (currentQuery) {
        query = '(' + currentQuery + ') AND (' + query + ')';
    }
    facetSearch(query);
}

function facetSearch(query) {
    checkFilters();
    newSearch();
    $searchInput.val(query);
    $searchForm.trigger('submit');
}

function facetsBack() {
    var previousQuery = facetsQueriesStack.pop();
    if (previousQuery != null) {
        facetSearch(previousQuery);
    }
    if (!facetsQueriesStack.length) {
        $facetsBackButton.hide();
    }
}

$(document).ready(function() {
    $searchForm = $('#searchForm');
    $searchInput = $searchForm.find('input[name="qry"]');
    $facetsBackButton = $('#facets-back-btn');
    $facetsBackButton.on('click', facetsBack);
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
        if (w < 360 && n > 1)
            w = Math.floor(fllWidth / (n - 1)) - diff;
        $('#answers .list').width(w);
    }
    else {

        var margin = 0;
        var el = $('#answers .diapo:first');
        var brdrWidth = el.css('border-width');
        var stdWidth = el.outerWidth() + 10;
        var fllWidth = $('#answers').innerWidth();
        fllWidth -= 16;

        var n = Math.floor(fllWidth / (stdWidth));

        margin = Math.floor((fllWidth % stdWidth) / (2 * n));
        $('#answers .diapo').css('margin', '5px ' + (5 + margin) + 'px');
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

$(document).ready(function () {

    $('input[name=search_type]').bind('click', function () {
        var $this = $(this);
        var $record_types = $('#recordtype_sel');
        if ($this.hasClass('mode_type_reg')) {
            $record_types.hide();
            $record_types.prop("selectedIndex", 0);
        } else {
            $record_types.show();
        }
    });

    $('.adv_search_button').live('click', function () {
        var searchForm = $('#searchForm');
        var parent = searchForm.parent();

        var options = {
            size: (bodySize.x - 120)+'x'+(bodySize.y - 120),
            loading: false,
            closeCallback: function (dialog) {

                var datas = dialog.find('form.phrasea_query').appendTo(parent);//.clone();

                $('.adv_trigger', searchForm).show();
                $('.adv_options', searchForm).hide();
            }
        };

        $dialog = p4.Dialog.Create(options);

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

    $('.basket_refresher').live('click', function () {
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

    $('#answers .see_more a').live('click', function (event) {
        $see_more = $(this).closest('.see_more');
        $see_more.addClass('loading');
    });

    $('#answers .feed .entry').live('mouseover', function () {
        $(this).addClass('hover');
    });
    $('#answers .feed .entry').live('mouseout', function () {
        $(this).removeClass('hover');
    });

    $('a.ajax_answers').live('click', function (event) {
        event.stopPropagation();
        var $this = $(this);

        var append = $this.hasClass('append');
        var no_scroll = $this.hasClass('no_scroll');

        $.ajax({
            type: "GET",
            url: $this.attr('href'),
            dataType: 'html',
            success: function (data) {
                var $answers = $('#answers');

                if (!append) {
                    $answers.empty();
                    if (!no_scroll) {
                        $answers.scrollTop(0);
                    }
                    $answers.append(data);

                    $answers.find("img.lazyload").lazyload({
                        container: $answers
                    });
                }
                else {
                    $('.see_more.loading', $answers).remove();
                    $answers.append(data);

                    $answers.find("img.lazyload").lazyload({
                        container: $answers
                    });

                    if (!no_scroll) {
                        $answers.animate({
                            'scrollTop': ($answers.scrollTop() + $answers.innerHeight() - 80)
                        });
                    }
                }
                afterSearch();
            }
        });

        return false;
    });


    $('a.subscribe_rss').live('click', function (event) {

        var $this = $(this);

        if (typeof(renew) === 'undefined')
            renew = 'false';
        else
            renew = renew ? 'true' : 'false';

        var buttons = {};
        buttons[language.renewRss] = function () {
            $this.trigger({
                type: 'click',
                renew: true
            });
        };
        buttons[language.fermer] = function () {
            $('#DIALOG').empty().dialog('destroy');
        };

        event.stopPropagation();
        var $this = $(this);

        $.ajax({
            type: "GET",
            url: $this.attr('href') + (event.renew === true ? '?renew=true' : ''),
            dataType: 'json',
            success: function (data) {
                if (data.texte !== false && data.titre !== false) {
                    if ($("#DIALOG").data("ui-dialog")) {
                        $("#DIALOG").dialog('destroy');
                    }
                    $("#DIALOG").attr('title', data.titre)
                        .empty()
                        .append(data.texte)
                        .dialog({
                            autoOpen: false,
                            closeOnEscape: true,
                            resizable: false,
                            draggable: false,
                            modal: true,
                            buttons: buttons,
                            width: 650,
                            height: 250,
                            overlay: {
                                backgroundColor: '#000',
                                opacity: 0.7
                            }
                        }).dialog('open');

                }
            }
        });

        return false;
    });

    $('#search_submit').live('mousedown', function (event) {
        return false;
    });

    $('#history-queries ul li').live('mouseover',function () {
        $(this).addClass('hover');
    }).live('mouseout', function () {
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
                300,
                'linear',
                function () {
                    answerSizer();
                    linearize();
                    saveWindows();
                });
        }
    });

    $('#idFrameC .escamote').bind('click', function (event) {
        event.stopImmediatePropagation();
        p4.WorkZone.close();
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
                                //								case 37://previous page
                                //									$('#PREV_PAGE').trigger('click');
                                //									break;
                                //								case 39://previous page
                                //									$('#NEXT_PAGE').trigger('click');
                                //									break;
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

    $('input.input_select_copy').live('focus', function () {
        $(this).select();
    });
    $('input.input_select_copy').live('blur', function () {
        $(this).deselect();
    });
    $('input.input_select_copy').live('click', function () {
        $(this).select();
    });

    $('#answers .feed .entry a.options').live('click', function () {
        var $this = $(this);
        $.ajax({
            type: "GET",
            url: $this.attr('href'),
            dataType: 'html',
            success: function (data) {
                return set_up_feed_box(data);
            }
        });
        return false;
    });
    $('#answers .feed .entry a.feed_delete').live('click', function () {
        if (!confirm('etes vous sur de vouloir supprimer cette entree ?'))
            return false;
        var $this = $(this);
        $.ajax({
            type: "POST",
            url: $this.attr('href'),
            dataType: 'json',
            success: function (data) {
                if (data.error === false) {
                    var $entry = $this.closest('.entry');
                    $entry.animate({
                        height: 0,
                        opacity: 0
                    }, function () {
                        $entry.remove();
                    });
                }
                else
                    alert(data.message);
            }
        });
        return false;
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
        size: 'Small',
        title: language.deleteRecords
    });

    $.ajax({
        type: "POST",
        url: "../prod/records/delete/what/",
        dataType: 'html',
        data: {lst: lst},
        success: function (data) {
            $dialog.setContent(data);
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

function toolREFACTOR(datas) {

    var dialog = p4.Dialog.Create({
        size: 'Medium',
        title: language.toolbox,
        loading: true
    });

    $.get("../prod/tools/"
        , datas
        , function (data) {
            dialog.setContent(data);
            return;
        }
    );

}

function activeIcons() {
    $('.TOOL_print_btn').live('click', function () {
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

    $('.TOOL_bridge_btn').live('click', function (e) {
        e.preventDefault();
        var $button = $(this);
        var datas = {};

        if ($button.hasClass('results_window')) {
            if (p4.Results.Selection.length() > 0)
                datas.lst = p4.Results.Selection.serialize();
        }
        else {
            if ($button.hasClass('basket_window')) {
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
            init_publicator($button.attr("href"), datas);
        }
        else {
            alert(language.nodocselected);
        }
    });


    $('.TOOL_trash_btn').live('click', function () {
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

    $('.TOOL_ppen_btn').live('click', function () {
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

    $('.TOOL_publish_btn').live('click', function () {
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
            feedThis(type, value);
        }
        else {
            alert(language.nodocselected);
        }
    });

    function feedThis(type, value) {
        var options = {
            lst: '',
            ssel: '',
            act: ''
        };

        switch (type) {
            case "IMGT":
            case "CHIM":
                options.lst = value;
                break;

            case "STORY":
                options.story = value;
                break;
            case "SSTT":
                options.ssel = value;
                break;
        }

        $.post("../prod/feeds/requestavailable/"
            , options
            , function (data) {

                return set_up_feed_box(data);
            });

        return;
    }

    $('.TOOL_chgcoll_btn').live('click', function () {
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

    $('.TOOL_chgstatus_btn').live('click', function () {
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

    $('.TOOL_pushdoc_btn').live('click', function () {
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


    $('.TOOL_feedback_btn').live('click', function () {
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


    $('.TOOL_imgtools_btn').live('click', function () {
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


    $('.TOOL_disktt_btn').live('click', function () {
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
    $('#EDIT_query').val(decodeURIComponent(qry).replace(/\+/g, " "));
    newSearch();
}

function clktri(id) {
    var o = $('#TOPIC_UL' + id);
    if ($('#TOPIC_UL' + id).hasClass('closed'))
        $('#TOPIC_TRI' + id + ' ,#TOPIC_UL' + id).removeClass('closed').addClass('opened');
    else
        $('#TOPIC_TRI' + id + ' ,#TOPIC_UL' + id).removeClass('opened').addClass('closed');
}


// ---------------------- fcts du thesaurus
function chgProp(path, v, k) {
    var q2;
    if (!k)
        k = "*";
    //if(k!=null)
    v = v + " [" + k + "]";
    $("#thprop_a_" + path).html('"' + v + '"');
    //	q = document.getElementById("thprop_q").innerText;
    //	if(!q )
    //		if(document.getElementById("thprop_q") && document.getElementById("thprop_q").textContent)
    //			q = document.getElementById("thprop_q").textContent;
    q = $("#thprop_q").text();

    q2 = "";
    for (i = 0; i < q.length; i++)
        q2 += q.charCodeAt(i) == 160 ? " " : q.charAt(i);

    $('#EDIT_query').val(q);
    newSearch();

    return(false);
}

function doDelete(lst) {
    var children = '0';
    if (document.getElementById('del_children') && document.getElementById('del_children').checked)
        children = '1';
    $.ajax({
        type: "POST",
        url: "../prod/delete/",
        dataType: 'json',
        data: {
            lst: lst.join(';'),
            del_children: children
        },
        success: function (data) {

            $.each(data, function (i, n) {
                var imgt = $('#IMGT_' + n),
                    chim = $('.CHIM_' + n),
                    stories = $('.STORY_' + n);
                $('.doc_infos', imgt).remove();
                imgt.unbind("click").removeAttr("ondblclick").removeClass("selected").removeClass("IMGT").find("img").unbind();

                if (imgt.data("ui-draggable")) {
                    imgt.draggable("destroy");
                }

                imgt.find(".thumb img").attr("src", "/skins/icons/deleted.png").css({
                    width: '100%',
                    height: 'auto',
                    margin: '0 10px',
                    top: '0'
                });
                chim.parent().slideUp().remove();
                imgt.find(".status,.title,.bottom").empty();

                p4.Results.Selection.remove(n);
                if (stories.length > 0) {
                    p4.WorkZone.refresh();
                }
                else {
                    p4.WorkZone.Selection.remove(n);
                }
            });
            viewNbSelect();
        }
    });
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

function clksbas(num, el) {
    var bool = true;

    if (el.attr('checked')) {
        bool = false;
        $('.sbasChkr_' + num).removeAttr('checked');
    }
    else {
        $('.sbasChkr_' + num).attr('checked', 'checked');
    }

    $.each($('.sbascont_' + num + ' :checkbox'), function () {
        this.checked = bool;
    });
    if (bool) {
        $('.sbascont_' + num + ' label').addClass('selected');
    }
    else {
        $('.sbascont_' + num + ' label').removeClass('selected');
    }

    infoSbas(false, num, false, false);
}
function cancelEvent(event) {
    if (event.stopPropagation)
        event.stopPropagation();
    if (event.preventDefault)
        event.preventDefault();
    event.cancelBubble = true;
    return false;
}

function infoSbas(el, num, donotfilter, event) {
    if (event)
        cancelEvent(event);
    if (el) {
        var item = $('input.ck_' + $(el).val());
        var label = $('label.ck_' + $(el).val());

        if ($(el).attr('checked')) {
            label.removeClass('selected');
            item.removeAttr('checked');
        }
        else {
            label.addClass('selected');
            item.attr('checked', 'checked');
        }
    }
    $('.infos_sbas_' + num).empty().append($('.basChild_' + num + ':first .checkbas:checked').length + '/' + $('.basChild_' + num + ':first .checkbas').length);

    if (donotfilter !== true)
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
        if ($.trim(start_page_query) === '') {
            alert(language.start_page_query_error);
            return;
        }
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

function set_up_feed_box(data) {

    var buttons = {};
    buttons[language.valider] = function () {
        var dialog = p4.Dialog.get(1);
        var error = false;
        var $form = $('form.main_form', dialog.getDomElement());

        $('.required_text', $form).each(function (i, el) {
            if ($.trim($(el).val()) === '') {
                $(el).addClass('error');
                error = true;
            }
        });

        if (error) {
            alert(language.feed_require_fields);
        }

        if ($('input[name="feed_id"]', $form).val() === '') {
            alert(language.feed_require_feed);
            error = true;
        }

        if (error) {
            return false;
        }

        $.ajax({
            type: 'POST',
            url: $form.attr('action'),
            data: $form.serializeArray(),
            dataType: 'json',
            beforeSend: function () {
                $('button', dialog.getDomElement()).attr('disabled', 'disabled');
            },
            error: function () {
                $('button', dialog.getDomElement()).removeAttr('disabled');
            },
            timeout: function () {
                $('button', dialog.getDomElement()).removeAttr('disabled');
            },
            success: function (data) {
                $('button', dialog.getDomElement()).removeAttr('disabled');
                if (data.error === true) {
                    alert(data.message);
                    return;
                }

                if ($('form.main_form', dialog.getDomElement()).hasClass('entry_update')) {
                    var id = $('form input[name="entry_id"]', dialog.getDomElement()).val();
                    var container = $('#entry_' + id);

                    container.replaceWith(data.datas);

                    container.hide().fadeIn();

                    var answers = $('#answers');

                    answers.find("img.lazyload").lazyload({
                        container: answers
                    });
                }

                p4.Dialog.Close(1);
            }
        });
        p4.Dialog.Close(1);
    };

    var dialog = p4.Dialog.Create({
        size: 'Full',
        closeOnEscape: true,
        closeButton: true,
        buttons: buttons
    });

    dialog.setContent(data);

    var $feeds_item = $('.feeds .feed', dialog.getDomElement());
    var $form = $('form.main_form', dialog.getDomElement());

    $feeds_item.bind('click',function () {
        console.log("clcik");
        $feeds_item.removeClass('selected');
        $(this).addClass('selected');
        $('input[name="feed_id"]', $form).val($('input', this).val());
    }).hover(function () {
        $(this).addClass('hover');
    }, function () {
        $(this).removeClass('hover');
    });

    $form.bind('submit', function () {
        return false;
    });

    return;
}

