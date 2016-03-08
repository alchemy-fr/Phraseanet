var p4 = p4 || {};
var baskAjax, baskAjaxrunning;
baskAjaxrunning = false;
var answAjax, answAjaxrunning;
answAjaxrunning = false;
var searchAjax, searchAjaxRunning;
searchAjaxRunning = false;
var bodySize = {
    x: 0,
    y: 0
};

var prodModule = (function (p4, humane) {

    p4.preview = {
        open: false,
        current: false
    };
    p4.sel = [];
    p4.edit = {};
    p4.thesau = {
        tabs: null
    };
    p4.active_zone = false;

    function doSpecialSearch(qry, allbase) {
        if (allbase) {
            prodApp.appEvents.emit('search.doToggleDatabase', true);
        }
        prodApp.appEvents.emit('facets.doResetSelectedFacets');
        $('#EDIT_query').val(decodeURIComponent(qry).replace(/\+/g, " "));
        prodApp.appEvents.emit('search.doNewSearch', qry);
    }

    function addToBasket(sbas_id, record_id, event, singleSelection) {
        var singleSelection = singleSelection || false;
        p4.WorkZone.addElementToBasket(sbas_id, record_id, event, singleSelection);
    }

    function removeFromBasket(el, confirm) {
        var confirm = confirm || false;
        p4.WorkZone.removeElementFromBasket(el, confirm);
    }

    /*function openRecordEditor(type, value) {

        $('#idFrameE').empty().addClass('loading');
        commonModule.showOverlay(2);

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
                recordEditorModule.initialize();
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
    }*/



    function toggleTopic(id) {
        var o = $('#TOPIC_UL' + id);
        if ($('#TOPIC_UL' + id).hasClass('closed'))
            $('#TOPIC_TRI' + id + ' ,#TOPIC_UL' + id).removeClass('closed').addClass('opened');
        else
            $('#TOPIC_TRI' + id + ' ,#TOPIC_UL' + id).removeClass('opened').addClass('closed');
    }

    return {
        doSpecialSearch: doSpecialSearch,
        addToBasket: addToBasket,
        removeFromBasket: removeFromBasket,
        toggleTopic: toggleTopic
    }
})(p4, humane);

//var language = {}; // handled with external prodution module



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
