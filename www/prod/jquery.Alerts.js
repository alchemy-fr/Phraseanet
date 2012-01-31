var p4 = p4 || {};

(function(p4){
  
  function create_dialog()
  {
    if($('#p4_alerts').length === 0)
    {
      $('body').append('<div id="p4_alerts"></div>');
    }
    
    return $('#p4_alerts');
  }
  
  function alert(title, message, callback)
  {
    var dialog = create_dialog();
    
    var button = new Object();
    
    button['Ok'] = function(){
      if(typeof callback === 'function')
        callback();
      else
        dialog.dialog('close');
    };

    dialog.dialog('destroy').attr('title',title)
    .empty()
    .append(message)
    .dialog({
      
      autoOpen:false,
      closeOnEscape:true,
      resizable:false,
      draggable:false,
      modal:true,
      buttons : button,
      draggable:false,
      overlay: {
        backgroundColor: '#000',
        opacity: 0.7
      }
    }).dialog('open');
    
    if(typeof callback === 'function')
    {
      dialog.bind( "dialogclose", function(event, ui) {callback();});
    }
    else
    {

    }
    
    return;
  }
  
  p4.Alerts = alert;
        
        
  return;
}(p4))
