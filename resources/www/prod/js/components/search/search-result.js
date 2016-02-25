var p4 = p4 || {};

var searchResultModule = (function (p4, window) {

    p4.Results = {
        'Selection': new Selectable($('#answers'), {
            selector: '.IMGT',
            limit: 800,
            selectStart: function (event, selection) {
                $('#answercontextwrap table:visible').hide();
            },
            selectStop: function (event, selection) {
                searchModule.viewNbSelect();
            },
            callbackSelection: function (element) {
                var elements = $(element).attr('id').split('_');

                return elements.slice(elements.length - 2, elements.length).join('_');
            }
        })
    };

    function gotopage(pag) {
        $('#searchForm input[name="sel"]').val(p4.Results.Selection.serialize());
        $('#formAnswerPage').val(pag);
        $('#searchForm').submit();
    }

    return {
        gotopage: gotopage
    };
}(p4, window));
