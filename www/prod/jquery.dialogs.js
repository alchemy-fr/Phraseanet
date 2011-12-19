/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


$(document).ready(function(){
  
  $('a.dialog').live('click', function(event){
    
    var $this = $(this);
    
    $.ajax({
      type: "GET",
      url: $this.attr('href'),
      dataType: 'html',
      beforeSend:function(){
        
      },
      success: function(data){

        $('#DIALOG').attr('title', $this.attr('title'))
                    .empty()
                    .append(data)
                    .dialog()
                    .dialog('open');
        


        return;
      }
    });
    
    return false;
  });
  
});