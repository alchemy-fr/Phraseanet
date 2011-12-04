/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


(function( $ ){

  $.fn.buttonsetv = function() {
    $.each(this,function(i, $this){
      var default_action = $('.default_action', $this);
      var trigger = $( ".trigger", $this );
      if(default_action.length > 0)
      {
        default_action.parent().buttonset();
        
        trigger
        .button({
          text:false,
          icons: {
            secondary: "ui-icon-triangle-1-s"
          }
        });
        
      }
      else
      {
        trigger
        .button({
          icons: {
            secondary: "ui-icon-triangle-1-s"
          }
        })
      }
  
      var submenu = $('.submenu', $this);
  
  
      trigger.click(function(event) {

        if(event.stopPropagation)
          event.stopPropagation();
        if(event.preventDefault)
          event.preventDefault();
        event.cancelBubble = true;
        
        var todo = submenu.is(':visible');
        
        $(document).trigger('click.menu');

        if(!todo)
        {
          $(document).bind('click.menu', function(){
            submenu.hide();
            $(document).unbind('click.menu');
          });
          default_action.addClass('ui-corner-tl').removeClass('ui-corner-left');
          $(this).addClass('ui-corner-tr').removeClass('ui-corner-right');
          submenu.show();
        }
        else
        {
          $(document).unbind('click.menu');
          default_action.removeClass('ui-corner-tl').addClass('ui-corner-left');
          $(this).removeClass('ui-corner-tr').addClass('ui-corner-right');
          submenu.hide();
        }
      });
  
  
  
      $(':radio, :checkbox, button, a', submenu).wrap('<div>');
      submenu.buttonset()
  
      $('button:first', submenu).removeClass('ui-corner-left');//.css('border-top', 'none');
      $('button:last', submenu).removeClass('ui-corner-right').addClass('ui-corner-bottom');
  
      var mw = 0;
      submenu.css('visibility','hidden').show();
  
      $('button, a', submenu).each(function(index){
        w = $(this).width()+20;
        if (w > mw) mw = w;
      }).addClass('ui-button-vertical');

      submenu.css('visibility','visible').hide();
  
      $('button, a', submenu).each(function(index){
        $(this).width(mw);
      });
    });
  
    return this;
  };
})( jQuery );