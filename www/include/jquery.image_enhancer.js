(function( $ ){

  var methods = {
    init : function( options ) {
      var settings = {
        'zoomable'         : false,
        'display_full_screen' : false
      };
      return this.each(function() {

        var $this = $(this), data = $(this).data('image_enhance');

        if ( ! data )
        {
          if ( options ) {
            $.extend( settings, options );
          }

          var wrapper = $('.thumb_wrapper', $(this));
          var $image =$('img', $this);

          wrapper.css('position','relative');

          reset_position($this);

          if(settings.display_full_screen)
          {
            $image.parent()
              .append('<div class="image_enhance_titlebar" style="display:none;">\n\
                <div class="image_enhance_title_options"><span class="full"><img src="/skins/icons/fullscreen.gif" /></span></div>\n\
                <div class="image_enhance_title_bg"></div></div>');

            var $titlebar = $('.image_enhance_titlebar',$this);

            $('.image_enhance_title_bg',$titlebar).css('opacity',0.5);

            $image.parent()
              .bind('mouseover.image_enhance', function(){
                $titlebar.stop().show().animate({
                  'height':28
                }, 150);
              })
              .bind('mouseout.image_enhance', function(){
                  $titlebar.stop().animate({
                    'height':0
                  }, 150, function(){
                    $titlebar.hide()
                  });
              });

            $('.image_enhance_titlebar .full', wrapper).bind('click.image_enhance', function(){
              $('body').append('<div class="image_enhance_theatre">\n\
                \n\
                <div class="image_enhance_theatre_closer_wrapper"><span class="closer">close</span></div>\n\
                <img style="width:'+image_width+'px;height:'+image_height+'" src="'+$image.attr('src')+'"/>\n\
                </div>');

              var $theatre = $('.image_enhance_theatre');
              var $theatre_img = $('img', $theatre);
              $(window).bind('resize.image_enhance dblclick.image_enhance',function(event){

                if(event.type == 'dblclick')
                {
                  $theatre_img.removeClass('zoomed');
                }
                else
                {
                  if($theatre_img.hasClass('zoomed'))
                    return;
                }
                var datas = calculate_sizes($(this).width(), $(this).height(), image_width, image_height, 80);

                $theatre_img.width(datas.width).height(datas.height).css('top',datas.top).css('left',datas.left);
              })
              $(window).trigger('resize.image_enhance');
              $('.closer', $theatre).bind('click.image_enhance', function(){
                $theatre.remove();
              });

              if(typeof $theatre.disableSelection !== 'function' && window.console)
                console.error('enhanced image require jquery UI\'s disableSelection');
              $('img', $theatre).disableSelection();
            });
          }


          if(settings.zoomable)
          {
            if(typeof $image.draggable !== 'function' && window.console)
              console.error('zoomable require jquery UI\'s draggable');

            if($image.attr('ondragstart'))
            {
              $image.removeAttr('ondragstart');
            }
            $image.draggable();
            $image.css({
               'max-width':'none',
               'max-height':'none'
            });

            var image_width = parseInt($('input[name="width"]', $this).val());
            var image_height = parseInt($('input[name="height"]', $this).val());
            var ratio = image_width / image_height;

            $this.bind('mousewheel',function(event, delta){
              $image.addClass('zoomed');
              if(delta > 0)
              {
                event.stopPropagation();
                zoomPreview(true, ratio, $image, $(this));
              }
              else
              {
                event.stopPropagation();
                zoomPreview(false, ratio, $image, $(this));
              }
              return false;
            }).bind('dblclick', function(event){
              reset_position($this);
            });
          }

          $(this).data('image_enhance', {
            width:image_width,
            height:image_height
          });
        }

      });
    },
    destroy : function( ) {
      return this.each(function() {
        $(this).data('image_enhance', null);
        $('.image_enhance_titlebar, .image_enhance_theatre',this).remove();
      });
    }
  };

  function zoomPreview(bool, ratio, $img, $container)
  {
    if($img.length === 0)
      return;

    var t1 = parseInt($img.css('top'));
    var l1 = parseInt($img.css('left'));
    var w1 = $img.width();
    var h1 = $img.height();

    var w2,t2;

    if(bool)
    {
      if((w1 * 1.08) < 32767) {
        w2 = w1 * 1.08;
      } else {
        w2 = w1;
      }
    }
    else
    {
      if((w1 / 1.08) > 20) {
        w2 = w1 / 1.08;
      } else {
        w2 = w1;
      }
    }

    var datas = $(this).data('image_enhance');

    h2 = Math.round(w2 / ratio);
    w2 = Math.round(w2);

    t2 = Math.round(t1 - (h2 - h1) / 2)+'px';
    var l2 = Math.round(l1 - (w2 - w1) / 2)+'px';

    var wPreview = $container.width()/2;
    var hPreview = $container.height()/2;

    var nt = Math.round((h2 / h1) * (t1 - hPreview) + hPreview);
    var nl = Math.round(((w2 / w1) * (l1 - wPreview)) + wPreview);

    $img.css({
      left: nl,
      top: nt
    }).width(w2).height(h2);
  }

  function calculate_sizes(window_width, window_height,image_width, image_height, border)
  {
    if(typeof border !== 'number')
      border = 0;

    var width, height;
    var ratio_display = window_width / window_height;
    var ratio_image = image_width / image_height;

    if(ratio_image > ratio_display)
    {
      width = window_width - border;
      height = Math.round(width / ratio_image);
    }
    else
    {
      height = window_height - border;
      width = Math.round(height * ratio_image);
    }

    var top = Math.round((window_height - height) / 2);
    var left = Math.round((window_width - width )/2);

    return {
      top:top,
      left:left,
      width:width,
      height:height
    };
  }

  function reset_position($this)
  {
    var display_width = $this.width();
    var display_height = $this.height();
    var image_width = parseInt($('input[name="width"]', $this).val());
    var image_height = parseInt($('input[name="height"]', $this).val());

    var datas = calculate_sizes(display_width, display_height, image_width, image_height);
    var $image =$('img', $this);

    var top = Math.round((display_height - datas.height) / 2)+'px';
    var left = Math.round((display_width - datas.width) / 2)+'px';

    $image.width(datas.width).height(datas.height).css({top:top, left:left});
    return;
  }

  $.fn.image_enhance = function(method) {

    if ( methods[method] ) {
      return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
    } else if ( typeof method === 'object' || ! method ) {
      return methods.init.apply( this, arguments );
    } else {
      $.error( 'Method ' +  method + ' does not exist on jQuery.image_enhance' );
    }


  };
})( jQuery );
