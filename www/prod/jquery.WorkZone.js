var p4 = p4 || {};

(function(p4){





  function refreshBaskets(baskId, sort, scrolltobottom, type)
  {

    type = typeof type == 'undefined' ? 'basket' : type;

    var active = $('#baskets .SSTT.ui-state-active');
    if(baskId == 'current' && active.length>0)
      baskId = active.attr('id').split('_').slice(1,2).pop();
    sort = ($.inArray(sort, ['date', 'name'])>=0) ? sort : '';

    scrolltobottom = typeof scrolltobottom == 'undefined' ? false : scrolltobottom;

    $.ajax({
      type: "GET",
      url: "/prod/WorkZone/",
      data: {
        type:'basket',
        id:baskId,
        sort:sort,
        type:type
      },
      beforeSend:function(){
        $('#basketcontextwrap').remove();
      },
      success: function(data){

        var cache = $("#idFrameC #baskets");
        $(".SSTT",cache).droppable('destroy');

        $('.bloc',cache).droppable('destroy');

        cache.accordion('destroy')
          .empty()
          .append(data);

        activeBaskets();
        $('.basketTips').tooltip({
          delay: 200
        });
        cache.disableSelection();

        if(!scrolltobottom)
          return;

        p4.next_bask_scroll = true;
        return;
      }
    });
  }



  function setTemporaryPref(name,value)
  {

    $.ajax({
      type: "POST",
      url: "/prod/prodFeedBack.php",
      data: {
        action: "SAVETEMPPREF",
        prop:name,
        value:value
      },
      success: function(data){
        return;
      }
    });
  }

  $("#baskets div.content select[name=valid_ord]").live('change',function(){

    var active = $('#baskets .SSTT.ui-state-active');
    if(active.length==0)
    {
      return;
    }

    var order = $(this).val();

    getContent(active, order);
  });



  function WorkZoneElementRemover(el, confirm)
  {

    if(confirm !== true && $(el).hasClass('groupings') && p4.reg_delete == true)
    {
      var buttons = {};


      buttons[language.valider] = function() {
        $("#DIALOG-baskets").dialog('close').remove();
        WorkZoneElementRemover(el,true);
      };

      buttons[language.annuler] = function(){
        $("#DIALOG-baskets").dialog('close').remove();
      };

      var texte = '<p>'+language.confirmRemoveReg+'</p><div><input type="checkbox" onchange="toggleRemoveReg(this);"/>'+language.hideMessage+'</div>';
      $('body').append('<div id="DIALOG-baskets"></div>');
      $("#DIALOG-baskets").attr('title',language.removeTitle)
      .empty()
      .append(texte)
      .dialog({

        autoOpen:false,
        closeOnEscape:true,
        resizable:false,
        draggable:false,
        modal:true,
        buttons:buttons,
        draggable:false,
        overlay: {
          backgroundColor: '#000',
          opacity: 0.7
        }
      }).dialog('open');
      return;
    }

    var id = $(el).attr('id').split('_').slice(2,4).join('_');

    $.ajax({
      type: "POST",
      url: $(el).attr('href'),
      dataType:'json',
      beforeSend : function(){
        $('.wrapCHIM_'+id).find('.CHIM').fadeOut();
      },
      success: function(data){

        if(data.success)
        {
          humane.info(data.message);
          p4.WorkZone.Selection.remove(id);

          $('.wrapCHIM_'+id).find('.CHIM').draggable('destroy');
          $('.wrapCHIM_'+id).remove();
        }
        else
        {
          humane.error(data.message);
          $('.wrapCHIM_'+id).find('.CHIM').fadeIn();
        }
        return;
      }
    });
    return false;
  }


  function activeBaskets()
  {
    var cache = $("#idFrameC #baskets");

    cache.accordion({
      active:'active',
      autoHeight: false,
      collapsible:true,
      header:'div.header',
      change:function(event,ui){

        var b_active = $('#baskets .SSTT.active');
        if(p4.next_bask_scroll)
        {
          p4.next_bask_scroll = false;


          if(!b_active.next().is(':visible'))
            return;

          var t = $('#baskets .SSTT.active').position().top + b_active.next().height() -200;

          t = t < 0 ? 0 : t;

          $('#baskets .bloc').stop().animate({
            scrollTop:t
          });
        }


        var uiactive = $(this).find('.ui-state-active');
        b_active.not('.ui-state-active').removeClass('active');

        if(uiactive.length === 0)
        {
          return; /* everything is closed */
        }

        var clicked = uiactive.attr('id').split('_').pop();
        var href = $('a', uiactive).attr('href');

        uiactive.addClass('ui-state-focus active');

        p4.WorkZone.Selection.empty();

        getContent(uiactive);

      },
      changestart:function(event,ui){
        ui.newHeader.addClass('active');
        $('#basketcontextwrap .basketcontextmenu').hide();
      }
    });
    $('.bloc',cache).droppable({
      accept:function(elem){
        if($(elem).hasClass('grouping') && !$(elem).hasClass('SSTT'))
          return true;
        return false;
      },
      scope:'objects',
      hoverClass:'groupDrop',
      tolerance:'pointer',
      drop:function(){
        fix();
      }
    });

    if($('.SSTT.active',cache).length>0)
    {
      var el = $('.SSTT.active',cache)[0];
      $(el).trigger('click');
    }


    $(".SSTT, .content",cache)
    .droppable({
      scope:'objects',
      hoverClass:'baskDrop',
      tolerance:'pointer',
      accept:function(elem){
        if($(elem).hasClass('CHIM'))
        {
          if($(elem).closest('.content').prev()[0] == $(this)[0])
          {
            return false;
          }
        }
        if($(elem).hasClass('grouping') || $(elem).parent()[0]==$(this)[0])
          return false;
        return true;
      },
      drop:function(event,ui){
        dropOnBask(event,ui.draggable,$(this));
      }
    });

    if($('#basketcontextwrap').length === 0)
      $('body').append('<div id="basketcontextwrap"></div>');

    $('.context-menu-item',cache).hover(function(){
      $(this).addClass('context-menu-item-hover');
    },function(){
      $(this).removeClass('context-menu-item-hover');
    });
    $.each($(".SSTT",cache),function(){
      var el = $(this);
      $(this).find('.contextMenuTrigger').contextMenu('#'+$(this).attr('id')+' .contextMenu',{
        'appendTo':'#basketcontextwrap',
        openEvt:'click',
        theme:'vista',
        dropDown:true,
        showTransition:'slideDown',
        hideTransition:'hide',
        shadow:false
      });
    });

  }



  function getContent(header, order)
  {
    if(window.console)
    {
      console.log('Reload content for ', header);
    }

    var url = $('a', header).attr('href');

    if(typeof order !== 'undefined')
    {
      url += '?order=' + order;
    }

    $.ajax({
      type: "GET",
      url: url,
      dataType:'html',
      beforeSend:function(){
        $('#tooltip').hide();
        header.next().addClass('loading');
      },
      success: function(data){
        header.removeClass('unread');

        var dest = header.next();

        dest.droppable('destroy').empty().removeClass('loading');

        dest.append(data)

        $('a.WorkZoneElementRemover', dest)
          .bind('mousedown', function(event){return false;})
          .bind('click', function(event){
            return WorkZoneElementRemover($(this), false);
          });

        dest.droppable({
          accept:function(elem){
            if($(elem).hasClass('CHIM'))
            {
              if($(elem).closest('.content')[0] == $(this)[0])
              {
                return false;
              }
            }
            if($(elem).hasClass('grouping') || $(elem).parent()[0]==$(this)[0])
              return false;
            return true;
          },
          hoverClass:'baskDrop',
          scope:'objects',
          drop:function(event, ui){
            dropOnBask(event,ui.draggable,$(this).prev());
          },
          tolerance:'pointer'
        });

        $('.noteTips, .captionRolloverTips', dest).tooltip();

        dest.find('.CHIM').draggable({
          helper : function(){
            $('body').append('<div id="dragDropCursor" '+
              'style="position:absolute;z-index:9999;background:red;'+
              '-moz-border-radius:8px;-webkit-border-radius:8px;">'+
              '<div style="padding:2px 5px;font-weight:bold;">'+
              p4.WorkZone.Selection.length() + '</div></div>');
            return $('#dragDropCursor');
          },
          scope:"objects",
          distance : 20,
          scroll : false,
          refreshPositions:true,
          cursorAt: {
            top:10,
            left:-20
          },
          start:function(event, ui){
            var baskets = $('#baskets');
            baskets.append('<div class="top-scroller"></div>'+
              '<div class="bottom-scroller"></div>');
            $('.bottom-scroller',baskets).bind('mousemove',function(){
              $('#baskets .bloc').scrollTop($('#baskets .bloc').scrollTop()+30);
            });
            $('.top-scroller',baskets).bind('mousemove',function(){
              $('#baskets .bloc').scrollTop($('#baskets .bloc').scrollTop()-30);
            });
          },
          stop:function(){
            $('#baskets').find('.top-scroller, .bottom-scroller')
            .unbind()
            .remove();
          },
          drag:function(event,ui){
            if(is_ctrl_key(event) || $(this).closest('.content').hasClass('grouping'))
              $('#dragDropCursor div').empty().append('+ '+p4.WorkZone.Selection.length());
            else
              $('#dragDropCursor div').empty().append(p4.WorkZone.Selection.length());

          }
        });
        answerSizer();
        return;
      }
    });
  }


  function dropOnBask(event,from,destKey, singleSelection)
  {
    var action = "",
    from = $(from), dest_uri = '', lstbr = [],
        sselcont = [], act = "ADD";

    if(from.hasClass("CHIM"))
    {
      /* Element(s) come from an open object in the workzone */
      action = $(' #baskets .ui-state-active').hasClass('grouping') ? 'REG2' : 'CHU2';
    }
    else
    {
      /* Element(s) come from result */
      action = 'IMGT2';
    }

    action += destKey.hasClass('grouping') ? 'REG' : 'CHU';

    if(destKey.hasClass('content'))
    {
      /* I dropped on content */
      dest_uri = $('a', destKey.prev()).attr('href');
    }
    else
    {
      /* I dropped on Title */
      dest_uri = $('a', destKey).attr('href');
    }

    if(window.console)
    {
      window.console.log('Requested action is ', action, ' and act on ', dest_uri);
    }

    if(action=="IMGT2CHU" || action=="IMGT2REG")
    {
      if($(from).hasClass('.baskAdder') )
      {
        lstbr = [$(from).attr('id').split('_').slice(2,4).join('_')];
      }
      else if(singleSelection)
      {
          if(from.length == 1) {
            lstbr = [$(from).attr('id').split('_').slice(1,3).join('_') ];
          } else {
            lstbr = [$(from).selector.split('_').slice(1,3).join('_') ]
          }
      }
      else
      {
        lstbr = p4.Results.Selection.get();
      }
    }
    else
    {
      sselcont = $.map(p4.WorkZone.Selection.get(), function(n,i){
        return $('.CHIM_'+n, $('#baskets .content:visible')).attr('id').split('_').slice(1,2).pop();
      });
      lstbr = p4.WorkZone.Selection.get();
    }

    switch(action)
    {
      case "CHU2CHU" :
        if(!is_ctrl_key(event))
          act = "MOV";
        break;

      case "IMGT2REG":
      case "CHU2REG" :
      case "REG2REG":
        var sameSbas = true,
            sbas_reg = destKey.attr('sbas');

        for (var i=0; i<lstbr.length && sameSbas ; i++)
        {
          if(lstbr[i].split('_').shift() != sbas_reg)
          {
            sameSbas = false;
            break;
          }
        }

        if(sameSbas === false)
        {
          return p4.Alerts('', language.reg_wrong_sbas);
        }

        break;
    }

    switch(act+action)
    {
      case 'MOVCHU2CHU':
          var url = dest_uri + "stealElements/";
          var data = {
            elements:sselcont
          };
          break;
      case 'ADDCHU2REG':
      case 'ADDREG2REG':
      case 'ADDIMGT2REG':
      case 'ADDCHU2CHU':
      case 'ADDREG2CHU':
      case 'ADDIMGT2CHU':
          var url = dest_uri + "addElements/";
          var data = {
            lst:lstbr.join(';')
          };
          break;
      default:
          if(window.console)
          {
            console.log('Should not happen');
          }
          return;
        break;
    }

    if(window.console)
    {
      window.console.log('About to execute ajax POST on ',url,' with datas ', data );
    }

    $.ajax({
      type: "POST",
      url: url,
      data: data,
      dataType:'json',
      beforeSend:function(){

      },
      success: function(data){
        if(!data.success)
        {
          humane.error(data.message);
        }
        else
        {
          humane.info(data.message);
        }
        if(act == 'MOV' || $(destKey).next().is(':visible') === true || $(destKey).hasClass('content') === true)
        {
          $('.CHIM.selected:visible').fadeOut();
          p4.WorkZone.Selection.empty();
          return p4.WorkZone.reloadCurrent();
        }

        return;
      }
    });
  }



  function fix()
  {
    $.ajax({
      type: "POST",
      url: "/prod/WorkZone/attachStories/",
      data:{stories:p4.Results.Selection.get()},
      dataType: "json",
      success: function(data){
        humane.info(data.message);
        p4.WorkZone.refresh();
      }
    });
  }

  function unfix(link)
  {
    $.ajax({
      type: "POST",
      url: link,
      dataType: "json",
      success: function(data){
        humane.info(data.message);
        p4.WorkZone.refresh();
      }
    });
  }

  $(document).ready(function(){
    activeBaskets();

    $('a.story_unfix').live('click', function(){
      unfix($(this).attr('href'));

      return false;
    });

    p4.WorkZone = {
      'Selection':new Selectable($('#baskets'), {selector : '.CHIM'}),
      'refresh':refreshBaskets,
      'addElementToBasket': function(sbas_id, record_id, event , singleSelection) {
        singleSelection = !!singleSelection || false;

        if($('#baskets .SSTT.active').length == 1)
        {
            return dropOnBask(event, $('#IMGT_'+ sbas_id +'_'+ record_id), $('#baskets .SSTT.active'), singleSelection);
        }
      },
      'reloadCurrent':function(){
        var sstt = $('#baskets .content:visible');
        if(sstt.length === 0)
          return;
        getContent(sstt.prev());
      },
      'close':function(){

        var frame = $('#idFrameC'),
        that = this;

        if(!frame.hasClass('closed'))
        {
          frame.data('openwidth', frame.width());
          frame.animate({width:100},
            300,
            'linear',
            function(){
              answerSizer();
              linearize();
            });
          frame.addClass('closed');
          $('.escamote', frame).hide();
          $('li.ui-tabs-selected', frame).removeClass('ui-tabs-selected');
          frame.unbind('click.escamote').bind('click.escamote', function(){
            that.open();
          })
        }
      },
      'open':function(){

        var frame = $('#idFrameC');

        if(frame.hasClass('closed'))
        {
          var width = frame.data('openwidth') ? frame.data('openwidth') : 300;
          frame.css({width:width});
              answerSizer();
              linearize();
          frame.removeClass('closed');
          $('.escamote', frame).show();
          frame.unbind('click.escamote');
        }
      }
    };
  });

  return;
}(p4))
