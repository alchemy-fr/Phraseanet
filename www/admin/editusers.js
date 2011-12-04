function ini_edit_usrs(){

  initialize_geoname_field($('#user_infos_tab input.geoname_field'));
        
  $('.users_col.options').bind('click', function(event){
    $('#users_check_uncheck').remove();
    event.stopPropagation();
    event.preventDefault();
    check_uncheck_menu($('input[name="right"]', $(this)).val(),$('input[name="sbas_id"]', $(this)).val(), $(this));
    return false;
  });

  $(document).unbind('click.usersoptions').bind('click.usersoptions', function(event){
    $('#users_check_uncheck').remove();
  });

  $('table.hoverable tr').hover(
    function(){
      $(this).addClass('hovered');
    },
    function(){
      $(this).removeClass('hovered');
    }
    );

  $('table.hoverable td').hover(
    function(){
      var attr = $('input:first', this).attr('name');
      var right = attr ? attr.split('_').shift() : false;
      if(right)
        $('td.case_right_'+right).addClass('hovered');
    },
    function(){
      var attr = $('input:first', this).attr('name');
      var right = attr ? attr.split('_').shift() : false;
      if(right)
        $('td.hovered').removeClass('hovered');
    }
    );

  function user_click_box(event, element, status)
  {
    var newclass, newvalue, boxes;

    var $element = $(element);

    if($element.hasClass('right_access'))
    {
      var base_id = $element.find('input').attr('name').split('_').pop();
      boxes = $('div.base_'+base_id+':not(:first)');
    }

    if((typeof status !== 'undefined' && status == 'checked') || (typeof status === 'undefined' && (($element.hasClass('mixed') === true) || ($element.hasClass('unchecked') === true))))
    {
      newclass = 'checked';
      newvalue = '1';

      if(boxes)
        boxes.show();
    }
    else
    {
      newclass = 'unchecked';
      newvalue = '0';

      if(boxes)
        boxes.hide();
    }

    $element.find('input').val(newvalue);
    $element.removeClass('mixed checked unchecked').addClass(newclass);
  }

  $('#users_rights_form div.switch_right').bind('click', function(event){
    user_click_box(event, $(this));
  });
  $('#right-ajax button.users_rights_valid').bind('click', function(){
    var datas = {
      users:$('#users_rights_form input[name="users"]').val(),
      values:$('#users_rights_form').serialize(),
      user_infos:$('#user_infos_form').serialize()
      };
    $.ajax({
      type: 'POST',
      url: '/admin/users/rights/apply/',
      dataType:'json',
      data: datas,
      success: function(data){
        if(!data.error)
          $('a.zone_editusers').trigger('click');
        else
          alert(data.message);
      }
    });
    return false;
  });

  var time_buttons = {
    'Ok':function(){
      save_time();
    },
    'Cancel':function(){
      $('#time_dialog').dialog('close');
    }
  };
  $('#time_dialog').dialog({
    resizable:false,
    autoOpen:false,
    draggable:false,
    buttons:time_buttons,
    modal:true
  });
  var quota_buttons = {
    'Ok':function(){
      save_quotas();
    },
    'Cancel':function(){
      $('#quotas_dialog').dialog('close');
    }
  };
  $('#quotas_dialog').dialog({
    resizable:false,
    autoOpen:false,
    draggable:false,
    buttons:quota_buttons,
    modal:true
  });

  var masks_buttons = {
    'Ok':function(){
      save_masks();
    },
    'Cancel':function(){
      $('#masks_dialog').dialog('close');
    }
  };
  $('#masks_dialog').dialog({
    resizable:false,
    autoOpen:false,
    draggable:false,
    buttons:masks_buttons,
    width:900,
    height:300,
    modal:true
  });
  $('#users_rights_form .time_trigger').bind('click', function(){
    var base_id = $(this).find('input[name="time_base_id"]').val();
    $.ajax({
      type: 'POST',
      url: '/admin/users/rights/time/',
      data: {
        users:$('#users_rights_form input[name="users"]').val(),
        base_id:base_id
      },
      success: function(data){   
        var dialog = $('#time_dialog');   
        
        dialog.html(data).dialog('open');   
        
        $('div.switch_time', dialog).bind('click', function(event){
          var newclass, boxes;

          boxes = $(this).closest('form').find('input.datepicker');

          if(($(this).hasClass('mixed') === true) || ($(this).hasClass('unchecked') === true))
          {
            newclass = 'checked';
            boxes.removeAttr('readonly');
          }
          else
          {
            newclass = 'unchecked';
            boxes.attr('readonly','readonly');
          }

          $(this).removeClass('mixed checked unchecked').addClass(newclass);
        });   
      }
    });
  });
  $('#users_rights_form .quota_trigger').bind('click', function(){
    var base_id = $(this).find('input[name="quota_base_id"]').val();
    $.ajax({
      type: 'POST',
      url: '/admin/users/rights/quotas/',
      data: {
        users:$('#users_rights_form input[name="users"]').val(),
        base_id:base_id
      },
      success: function(data){
        
        var dialog = $('#quotas_dialog');
        dialog.html(data).dialog('open');
        
        $('div.switch_quota', dialog).bind('click', function(event){
          var newclass, boxes;

          boxes = $(this).closest('form').find('input:text');

          if(($(this).hasClass('mixed')===true) || ($(this).hasClass('unchecked') === true))
          {
            newclass = 'checked';
            boxes.removeAttr('readonly');
          }
          else
          {
            newclass = 'unchecked';
            boxes.attr('readonly','readonly');
          }

          $(this).removeClass('mixed checked unchecked').addClass(newclass);
        });
      }
    });
  });

  $('#users_rights_form .masks_trigger').bind('click', function(){
    var base_id = $(this).find('input[name="masks_base_id"]').val();
    $.ajax({
      type: 'POST',
      url: '/admin/users/rights/masks/',
      data: {
        users:$('#users_rights_form input[name="users"]').val(),
        base_id:base_id
      },
      success: function(data){
        $('#masks_dialog').html(data).dialog('open');

        $('.switch_masks').bind('click', function(){
          var currentclass, newclass;

          var bit = $(this).find('input[name="bit"]').val();
          currentclass = 'unchecked';
          if($(this).hasClass('mixed'))
          {
            currentclass = 'mixed';
          }
          if($(this).hasClass('checked'))
          {
            currentclass = 'checked';
          }
          switch(currentclass)
          {
            case 'mixed':
            default:
              $('.bitnum_'+bit).removeClass('mixed checked unchecked').addClass('checked')
              break;
            case 'unchecked':
              $(this).removeClass('mixed checked unchecked').addClass('checked')
              break;
            case 'checked':
              if($('.checked.bitnum_'+bit).length == 2)
              {
                $(this).removeClass('mixed checked unchecked').addClass('unchecked')
              }
              else
              {
                alert("admin::user:mask: vous devez cocher au moins une case pour chaque status");
                return;
              }
              break;
          }

          var left = $('.bitnum_'+bit+'.bit_left');
          var right = $('.bitnum_'+bit+'.bit_right');

          var maskform = $('#masks_dialog form');
          var vand_and = $('input[name="vand_and"]', maskform);
          var vand_or  = $('input[name="vand_or"]', maskform);
          var vxor_and = $('input[name="vxor_and"]', maskform);
          var vxor_or  = $('input[name="vxor_or"]', maskform);

          var newbit_vand_and = newbit_vand_or = newbit_vxor_and = newbit_vxor_or = 0;

          if( left.length === 1 && right.length === 1 )
          {
            if(left.hasClass('checked') && right.hasClass('unchecked') )
            {
              newbit_vand_and = "1";
              newbit_vand_or  = "1";
            }
            else if( left.hasClass('unchecked') && right.hasClass('checked') )
            {
              newbit_vand_and = "1";
              newbit_vand_or  = "1";
              newbit_vxor_and = "1";
              newbit_vxor_or  = "1";
            }
            vand_and.val( vand_and.val().substr(0, 63 - bit) + newbit_vand_and + vand_and.val().substr(63 + 1 - bit) );
            vand_or.val ( vand_or.val().substr( 0, 63 - bit) + newbit_vand_or  + vand_or.val().substr( 63 + 1 - bit) );
            vxor_and.val( vxor_and.val().substr(0, 63 - bit) + newbit_vxor_and + vxor_and.val().substr(63 + 1 - bit) );
            vxor_or.val ( vxor_or.val().substr( 0, 63 - bit) + newbit_vxor_or  + vxor_and.val().substr(63 + 1 - bit) );
          }
        });
      }
    });
  });


  function save_masks()
  {
    var cont = $('#masks_dialog');

    var maskform = $('#masks_dialog form');
    var base_id  = $('input[name="base_id"]', maskform).val();
    var users    = $('input[name="users"]', maskform).val();
    var vand_and = $('input[name="vand_and"]', maskform).val();
    var vand_or  = $('input[name="vand_or"]', maskform).val();
    var vxor_and = $('input[name="vxor_and"]', maskform).val();
    var vxor_or  = $('input[name="vxor_or"]', maskform).val();


    $.ajax({
      type: 'POST',
      url: '/admin/users/rights/masks/apply/',
      data: {
        users:users,
        base_id:base_id,
        vand_and:vand_and,
        vand_or:vand_or,
        vxor_and:vxor_and,
        vxor_or:vxor_or
      },
      success: function(data){
        $('#masks_dialog').dialog('close');
      }
    });
  }


  function save_quotas()
  {
    var cont = $('#quotas_dialog');
    var base_id = $('input[name="base_id"]', cont).val();
    var users = $('input[name="users"]', cont).val();
    var droits = $('input[name="droits"]', cont).val();
    var restes = $('input[name="restes"]', cont).val();

    var switch_quota = $('.switch_quota', cont);

    if(switch_quota.hasClass('mixed'))
      return;

    var quota = 0;

    if(switch_quota.hasClass('checked'))
      quota = 1;

    $.ajax({
      type: 'POST',
      url: '/admin/users/rights/quotas/apply/',
      data: {
        act:"APPLYQUOTAS",
        users:users,
        base_id:base_id,
        quota:quota,
        droits:droits,
        restes:restes
      },
      success: function(data){
        $('#quotas_dialog').dialog('close');
      }
    });
  }

  function save_time()
  {
    var cont = $('#time_dialog');
    var dmin = $('input[name="dmin"]', cont).val();
    var dmax = $('input[name="dmax"]', cont).val();
    var users = $('input[name="users"]', cont).val();
    var base_id = $('input[name="base_id"]', cont).val();

    var switch_time = $('.switch_time', cont);

    if(switch_time.hasClass('mixed'))
      return;

    var limit = 0;

    if(switch_time.hasClass('checked'))
      limit = 1;
      
    $.ajax({
      type: 'POST',
      url: '/admin/users/rights/time/apply/',
      data: {
        users:users,
        base_id:base_id,
        limit:limit,
        dmin:dmin,
        dmax:dmax
      },
      success: function(data){
        $('#time_dialog').dialog('close');
      }
    });
  }

  function check_uncheck_menu(right, sbas_id, el)
  {
    var top = el.offset().top ;
    var left = el.offset().left + 16;
    $('body').append('<div id="users_check_uncheck" style="position:absolute;top:'+top+'px;left:'+left+'px;">'+
      '<div class="checker" >'+
      language.check_all+
      '<input type="hidden" name="sbas_id" value="'+sbas_id+'"/>'+
      '<input type="hidden" name="right" value="'+right+'"/>'+
      '</div>'+
      '<div class="unchecker">'+
      language.uncheck_all+
      '<input type="hidden" name="sbas_id" value="'+sbas_id+'"/>'+
      '<input type="hidden" name="right" value="'+right+'"/>'+
      '</div></div>');

    $('#users_check_uncheck div').hover(
      function(){
        $(this).addClass('hovered');
      },
      function(){
        $(this).removeClass('hovered');
      }
      );
    $('#users_check_uncheck div.checker').bind('click',function(event){
      event.stopPropagation();
      event.preventDefault();
      users_check(true, $('input[name="sbas_id"]',$(this)).val(), $('input[name="right"]',$(this)).val());
      $('#users_check_uncheck').remove();
    });
    $('#users_check_uncheck div.unchecker').bind('click',function(event){
      event.stopPropagation();
      event.preventDefault();
      users_check(false, $('input[name="sbas_id"]',$(this)).val(), $('input[name="right"]',$(this)).val());
      $('#users_check_uncheck').remove();
    });

  }
  function users_check(bool, sbas_id, right)
  {
    var newclass;
    if(bool)
    {
      newclass="checked";
    }
    else
    {
      newclass="unchecked";
    }
    $('.inside_sbas_'+sbas_id+'.'+right+':visible').each(function(i,n){
      user_click_box(null, $(n), newclass);
    });
  }
}

