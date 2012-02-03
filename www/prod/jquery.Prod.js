(function(){
  
  $(document).ready(function(){
    
    $('a.dialog').live('click', function(event){

      var $this = $(this), size = 'Medium';
      
      if($this.hasClass('small-dialog'))
      {
        size = 'Small';
      }
      else if($this.hasClass('full-dialog'))
      {
        size = 'Full';
      }
      
      var options = {
        size : size,
        loading : true,
        title : $this.attr('title'),
        closeOnEscape : true
      };

      $dialog = p4.Dialog.Create(options);
      
      $.ajax({
        type: "GET",
        url: $this.attr('href'),
        dataType: 'html',
        beforeSend:function(){

        },
        success: function(data){
          p4.Dialog.setContent(data);
          return;
        }
      });

      return false;
    });
    
  });

}())