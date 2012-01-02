var p4 = p4 || {};

(function(p4, window){
  
      p4.Results = {
        'Selection':new Selectable({
          selector : '.IMGT', 
          container:$('#answers'),
          selectStart:function(event, selection){
            $('#answercontextwrap table:visible').hide();
          },
          selectStop:function(event, selection){
            viewNbSelect();
          },
          callbackSelection:function(element){
            return $(element).attr('id').split('_').slice(1,3).join('_');
          }
        })
      };
  
  return;
}(p4, window))
