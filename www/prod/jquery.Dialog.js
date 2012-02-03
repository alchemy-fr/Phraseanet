;
var p4 = p4 || {};

;
(function(p4, $){
  
  
  function createDialog() {
    var $dialog = $('#DIALOG');
    
    if($dialog.length === 0)
    {
      $dialog = $('<div style="display:none;" id="DIALOG"></div>');
      $('body').append($dialog);
    }
    
    return $dialog;
  };
  
  
  var Create = function(options) {
    
    var defaults = { 
      size : 'Medium',
      buttons : {},
      loading : true,
      title : '',
      closeOnEscape : true,
      confirmExit:false,
      closeButton:false,
      cancelButton:false
    },
    options = typeof options === 'object' ? options : {},
    width,
    height,
    $dialog;
    
    this.options = $.extend(defaults, options);
    
    if(this.options.closeButton === true)
    {
      this.options.buttons[language.fermer] = Close;
    }
    if(this.options.cancelButton === true)
    {
      this.options.buttons[language.annuler] = Close;
    }
    
    switch(this.options.size)
    {
      case 'Full':
        height = bodySize.y - 30;
        width = bodySize.x - 30 ;
        break;
      case 'Medium':
        width = 730;
        height = 520;
        break;
      default:
      case 'Small':
        width = 420;
        height = 300;
        break;
    }
    
    /*
       * 3 avaailable dimensions :
       * 
       *  - Full   | Full size ()
       *  - Medium | 420 x 450
       *  - Small  | 730 x 480
       *  
       **/
    var $dialog = createDialog();
    
    $dialog.dialog('destroy').attr('title', this.options.title)
      .empty()
      .dialog({
        buttons:this.options.buttons,
        draggable:false,
        resizable:false,
        closeOnEscape:this.options.closeOnEscape,
        modal:true,
        width:width,
        height:height,
        close:Close
      })
      .dialog('open').addClass('dialog-' + this.options.size);
    
    if(this.options.loading === true)
    {
      $dialog.addClass('loading');
    }
    
    if(this.options.size === 'Full')
    {
      $(window).unbind('resize.DIALOG').bind('resize.DIALOG', function(){
        $dialog.dialog('option', { width : bodySize.x - 30, height : bodySize.y - 30 });
      });
    }
    
    return $dialog;
  };

  var Close = function () {
    $(window).unbind('resize.DIALOG');
    createDialog().dialog('destroy').remove();
  };
  
  var setContent = function (content) {
    createDialog().removeClass('loading').empty().append(content);
  };
  
  var getDialog = function () {
    return $('#DIALOG');
  };
  
  p4.Dialog = {
    Create : Create,
    getDialog : getDialog,
    Close : Close,
    setContent : setContent
  };
  
}(p4, jQuery));