(function(){
  
  $(document).ready(function(){
    
    $('a.dialog').live('click', function(event){

      var $this = $(this);
      
      
      $('#DIALOG').dialog('destroy').attr('title', $this.attr('title'))
                  .empty().addClass('loading')
                  .dialog({
                    buttons:{},
                    draggable:false,
                    resizable:false,
                    closeOnEscape:true,
                    modal:true,
                    width:'800',
                    height:'500'
                  })
                  .dialog('open');

      $.ajax({
        type: "GET",
        url: $this.attr('href'),
        dataType: 'html',
        beforeSend:function(){

        },
        success: function(data){
          $('#DIALOG').removeClass('loading').empty()
                      .append(data);
          return;
        }
      });

      return false;
    });
    
  });

}())