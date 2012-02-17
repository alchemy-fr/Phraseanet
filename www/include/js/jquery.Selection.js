/*
 * Selection Object
 *
 *
 */

(function( window ) {

  var Selectable = function($container, options) {

    var defaults = {
          allow_multiple : false,
          selector : '',
          callbackSelection : null,
          selectStart : null,
          selectStop : null,
          limit : null
        },
        options = (typeof options == 'object') ? options : {};

    var $this = this;

    if($container.data('selectionnable'))
    {
      /* this container is already selectionnable */
      if(window.console)
      {
        console.error('Trying to apply new selection to existing one');
      }

      return $container.data('selectionnable');
    }

    this.$container = $container;
    this.options = jQuery.extend(defaults, options);
    this.datas = new Array();

    this.$container.data('selectionnable', this);
    this.$container.addClass('selectionnable');

    jQuery(this.options.selector, this.$container)
    .live('click', function(event){

      if(typeof $this.options.selectStart === 'function')
      {
        $this.options.selectStart(jQuery.extend(jQuery.Event('selectStart'), event), $this);
      }

      var $that = jQuery(this);

      var k = get_value($that, $this);

      if(is_shift_key(event) && jQuery('.last_selected', this.$container).filter($this.options.selector).length != 0)
      {
        var lst = jQuery($this.options.selector, this.$container);

        var index1 = jQuery.inArray( jQuery('.last_selected', this.$container).filter($this.options.selector)[0], lst );
        var index2 = jQuery.inArray( $that[0], lst );

        if(index2<index1)
        {
          var tmp = index1;
          index1 = (index2 - 1) < 0 ? index2 : (index2 - 1);
          index2 = tmp;
        }

        var stopped = false;

        if(index2 != -1 && index1 != -1)
        {
          var exp = $this.options.selector + ':gt(' + index1 + '):lt(' + (index2-index1) + ')';

          $.each(jQuery(exp, this.$container),function(i,n){
            if(!jQuery(n).hasClass('selected') && stopped === false)
            {
              if(!$this.hasReachLimit())
              {
                var k = get_value(jQuery(n), $this);
                $this.push(k);
                jQuery(n).addClass('selected');
              }
              else
              {
                alert(language.max_record_selected);
                stopped = true;
              }
            }
          });
        }

        if($this.has(k) === false && stopped === false)
        {
          if(!$this.hasReachLimit())
          {
            $this.push(k);
            $that.addClass('selected');
          }
          else
          {
            alert(language.max_record_selected);
          }
        }
      }
      else
      {
        if(!is_ctrl_key(event))
        {
          $this.empty().push(k);
          jQuery('.selected', this.$container).filter($this.options.selector).removeClass('selected');
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
            if(!$this.hasReachLimit())
            {
              $this.push(k);
              $that.addClass('selected');
            }
            else
            {
              alert(language.max_record_selected);
            }
          }
        }
      }

      jQuery('.last_selected', this.$container).removeClass('last_selected');
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

  function is_ctrl_key(event)
  {
    if(event.altKey)
      return true;
    if(event.ctrlKey)
      return true;
    if(event.metaKey)	// apple key opera
      return true;
    if(event.keyCode == '17')	// apple key opera
      return true;
    if(event.keyCode == '224')	// apple key mozilla
      return true;
    if(event.keyCode == '91')	// apple key safari
      return true;

    return false;
  }

  function is_shift_key(event)
  {
    if(event.shiftKey)
      return true;
    return false;
  }



  Selectable.prototype = {
    push : function(element){
      if(this.options.allow_multiple === true || !this.has(element))
      {
        this.datas.push(element);
      }

      return this;
    },
    hasReachLimit : function() {
      if(this.options.limit !== null && this.options.limit <= this.datas.length)
      {
        return true;
      }
      return false;
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

      jQuery(this.options.selector, this.$container).filter('.selected:visible').removeClass('selected');

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
    selectAll : function(){
      this.select('*');

      return this;
    },
    select : function(selector){
      var $this = this,
      stopped = false;

      jQuery(this.options.selector, this.$container).filter(selector).not('.selected').filter(':visible').each(function(){
        if(!$this.hasReachLimit())
        {
          $this.push(get_value(this, $this));
          $(this).addClass('selected');
        }
        else
        {
          if(stopped === false)
          {
            alert(language.max_record_selected);
          }
          stopped = true;
        }
      });

      return this;
    }
  };

  window.Selectable = Selectable;
})(window);