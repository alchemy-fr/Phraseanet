/* 
 * Selection Object
 * 
 * 
 */

(function( window ) {

  var Selectable = function(options) {
    
    var defaults = {
          allow_multiple : false,
          container : window.document,
          selector : '*',
          callbackSelection : null,
          selectStart : null,
          selectStop : null
        },
        options = (typeof options == 'object') ? options : {};
    
    this.options = jQuery.extend(defaults, options);
    this.datas = new Array();
    
    var $container = jQuery(this.options.container),
        $this = this;
    
    if(jQuery($container).hasClass('selectionnable'))
    {
      /* this container is already selectionnable */
      
      return;
    }
    
    jQuery($container).addClass('selectionnable');
    
    jQuery(this.options.selector, $container)
    .live('click', function(event){
      
      if(typeof $this.options.selectStart === 'function')
      {
        $this.options.selectStart(jQuery.extend(jQuery.Event('selectStart'), event), $this);
      }
      
      var $that = jQuery(this);
      
      var k = get_value($that, $this);

      if(is_shift_key(event) && jQuery('.last_selected', $container).filter($this.options.selector).length != 0)
      {
        var lst = jQuery($this.options.selector, $container);
        
        var index1 = jQuery.inArray( jQuery('.last_selected', $container).filter($this.options.selector)[0], lst );
        var index2 = jQuery.inArray( $that[0], lst );

        if(index2<index1)
        {
          var tmp = index1;
          index1 = (index2 - 1) < 0 ? index2 : (index2 - 1);
          index2 = tmp;
        }

        if(index2 != -1 && index1 != -1)
        {
          var exp = $this.options.selector + ':gt(' + index1 + '):lt(' + (index2-index1) + ')';

          $.each(jQuery(exp, $container),function(i,n){
            if(!jQuery(n).hasClass('selected'))
            {
              var k = get_value(jQuery(n), $this);              
              $this.push(k);
              jQuery(n).addClass('selected');
            }
          });
        }

        if($this.has(k) === false)
        {
          $this.push(k);
          $that.addClass('selected');
        }
      }
      else
      {
        if(!is_ctrl_key(event))
        {
          $this.empty().push(k);
          jQuery('.selected', $container).filter($this.options.selector).removeClass('selected');
          $that.addClass('selected');
        }
        else
        {
          if($this.has(k) === true)
          {
            $this.remove(k);
            $that.removeClass('selected');
          }
          else
          {
            $this.push(k);
            $that.addClass('selected');
          }
        }
      }

      jQuery('.last_selected', $container).removeClass('last_selected');
      $that.addClass('last_selected');
      
      
      if(typeof $this.options.selectStop === 'function')
      {
        $this.options.selectStop(jQuery.extend(jQuery.Event('selectStop'), event), $this);
      }
      
      return false;
      
    })
    
    return;
  };
  
  function get_value(element, Selectable)
  {
    if(typeof Selectable.options.callbackSelection === 'function')
    {
      return Selectable.options.callbackSelection(jQuery(element));
    }
    else
    {
      return jQuery('input[name="id"]', jQuery(element)).val();
    }
  }
  
  
  Selectable.prototype = {
    push : function(element){
      if(window.console)
      {
        window.console.log('pushing ',element);
      }
      if(this.options.allow_multiple === true || !this.has(element))
      {
        this.datas.push(element);
      }
      
      return this;
    },
    remove : function(element){
      this.datas = jQuery.grep(this.datas, function(n){
        return(n !== element);
      });
      
      return this;
    },
    has : function(element){
      
      return jQuery.inArray(element,this.datas) >= 0;
    },
    get : function(){
      
      return this.datas;
    },
    empty : function(){
      this.datas = new Array();
      
      return this;
    },
    length : function(){
      
      return this.datas.length;
    },
    size : function(){
      
      return this.datas.length;
    },
    serialize : function(separator){
      
      separator = separator || ';';
      
      return this.datas.join(separator);
    },
    selectAll : function(separator){
      var $this = this;
      
      jQuery(this.optionsthis.options.selector, $container).not('.selected').filter(':visible').each(function(){
        $this.push(get_value(this, $this));
        $(this).addClass('selected');
      });

      return this;
    }
  };

console.log('SELECTABALE to be declare');

  window.Selectable = Selectable;
console.log('SELECTABALE declared');
})(window);