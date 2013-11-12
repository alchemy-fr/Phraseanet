var p4 = p4 || {};

(function(p4, window){

    p4.Results = {
        'Selection':new Selectable($('#answers'), {
            selector : '.IMGT',
            limit:800,
            selectStart:function(event, selection){
                $('#answercontextwrap table:visible').hide();
            },
            selectStop:function(event, selection){
                viewNbSelect();
            },
            callbackSelection:function(element){
                var elements = $(element).attr('id').split('_');

                return elements.slice(elements.length - 2 ,elements.length).join('_');
            }
        })
    };

    return;
}(p4, window));
