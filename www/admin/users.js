/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


$(document).ready(function(){

  function users_select_this(n,k)
  {

    if(p4.users.sel.length >= 800)
    {
      alert(language.max_record_selected);
      return false;
    }
    p4.users.sel.push(k);
    $(n).addClass('selected');
    return true;
  }

  var buttons = {};
  buttons[language.create_user] = function(){
    check_new_user(false);
  };
  buttons[language.annuler] = function(){
    $('#user_add_dialog').dialog('close')
  };
  
  $('#user_add_dialog').dialog({
    buttons:buttons,
    modal:true,
    resizable:false,
    draggable:false,
    width:500
    
  }).dialog('close');

  var buttons = {};
  buttons[language.create_template] = function(){
    check_new_user(true);
  };
  buttons[language.annuler] = function(){
    $('#template_add_dialog').dialog('close')
  };
  
  $('#template_add_dialog').dialog({
    buttons:buttons,
    modal:true,
    resizable:false,
    draggable:false,
    width:500
    
  }).dialog('close');


  function check_new_user(is_template)
  {
    var container = is_template ? $('#template_add_dialog') : $('#user_add_dialog');
    $('#new_user_loader').show();
    $.ajax({
      type: 'POST',
      url: '/admin/users/create/',
      dataType : 'json',
      data: {
        act:'CREATENEWUSER',
        value:$('input[name="value"]', container).val(),
        template:is_template ? '1':'0'
      },
      success: function(data){
        $('.new_user_loader', container).hide();
        if(!data.error)
        {
          container.dialog('close');
          $('input[name="value"]', container).val('');
          $('#right-ajax').empty().addClass('loading');
          p4.users.sel = [];
          $.ajax({
            type: 'POST',
            url: '/admin/users/rights/',
            data: {
              users : data.data
            },
            success: function(data){
              $('#right-ajax').removeClass('loading').html(data);
            }
          });
        }
        else
        {
          alert(data.message);
        }
          
      },
      error:function()
      {
        alert(language.serverError);
      },
      timeout:function()
      {
        alert(language.serverTimeout);
      }
    });
  }


  $('#users_page .user_adder').live('click', function(){

    $('#user_add_dialog').dialog('open');

  });
  $('#users_page .template_adder').live('click', function(){

    $('#template_add_dialog').dialog('open');

  });

  $('#users_page_form').live('submit', function(){
    var datas = $('#users_page_form').serializeArray();
    $('#right-ajax').empty().addClass('loading');
    $.ajax({
      type: 'POST',
      url: '/admin/users/search/',
      data: datas,
      success: function(data){
        $('#right-ajax').removeClass('loading').empty().html(data);
      }
    });

    return false;
  });

  $('#users_page_search').live('submit', function(){
    var datas = $('#users_page_search').serializeArray();
    $('#right-ajax').empty().addClass('loading');
    $.ajax({
      type: 'POST',
      url: '/admin/users/search/',
      data: datas,
      success: function(data){
        $('#right-ajax').removeClass('loading').empty().html(data);
      }
    });

    return false;
  });

  $('#users_page_form .pager').live('click', function(){
    var form = $('#users_page_form');

    var current_page = parseInt($('input[name="page"]', form).val());
    var perPage = parseInt($('select[name="per_page"]', form).val());
    current_page = isNaN(current_page) ? 1 : current_page;

    var offset_start = 0;

    if($(this).hasClass('prev'))
    {
      offset_start = (current_page-2) * perPage;
    }
    if($(this).hasClass('first'))
    {
      offset_start = 0;
    }
    if($(this).hasClass('next'))
    {
      offset_start = current_page * perPage;
    }
    if($(this).hasClass('last'))
    {
      offset_start = Math.floor(parseInt($('input[name=total_results]').val()) / perPage);
    }
    $('input[name="offset_start"]', form).val(offset_start);
  });
  
  $('#users tbody tr, #users tbody td').live('dblclick', function(evt){
    $('#users_page_form .user_modifier').trigger('click');
  });
  
  $('#users tbody tr, #users tbody td').live('click', function(evt){
    if(evt.stopPropagation)
      evt.stopPropagation();
    evt.cancelBubble = true;
    evt.preventDefault();
    var el = $(this).closest('tr');
    var cont = $('#users');

    var k = el.attr('id').split('_').pop();

    if(is_shift_key(evt) && $('tr.last_selected', cont).length!=0)
    {
      var lst = $('tr', cont);
      var index1 = $.inArray($('tr.last_selected', cont)[0],lst);
      var index2 = $.inArray($(el)[0],lst);
      if(index2<index1)
      {
        var tmp = index1;
        index1=(index2-1)<0?index2:(index2-1);
        index2=tmp;
      }

      var stopped = false;
      if(index2 != -1 && index1 != -1)
      {
        var exp = 'tr:gt('+index1+'):lt('+(index2-index1)+')';
        $.each($(exp, cont),function(i,n){

          if(!$(n).hasClass('selected'))
          {

            if(!users_select_this(n,$(n).attr('id').split('_').pop()))
            {
              stopped = true;
              return false;
            }
          }
        });
      }

      if(!stopped && $.inArray(k,p4.users.sel)<0)
      {
        if(!users_select_this(el,k))
          return false;
      }
    }
    else
    {
      if(!is_ctrl_key(evt))
      {
        if($.inArray(k,p4.users.sel)<0)
        {
          p4.users.sel = new Array();
          $('tr', cont).removeClass('selected');

          if(!users_select_this(el,k))
            return false;
        }
      }
      else
      {
        if($.inArray(k,p4.users.sel)>=0)
        {
          p4.users.sel = $.grep(p4.users.sel,function(n){
            return(n!=k);
          });
          $(el).removeClass('selected');
        }
        else
        {
          if(!users_select_this(el,k))
            return false;
        }
      }
    }
    $('.last_selected', cont).removeClass('last_selected');
    $(el).addClass('last_selected');
  }).live('mousedown', function(evt){

    if(evt.stopPropagation)
      evt.stopPropagation();
    evt.cancelBubble = true;
    evt.preventDefault();
  });
  $('#users_page_form .user_modifier').live('click', function(){
    var users = p4.users.sel.join(';');
    if(users === '')
    {
      return false;
    }

    $('#right-ajax').empty().addClass('loading');
    p4.users.sel = [];
    $.ajax({
      type: 'POST',
      url: '/admin/users/rights/',
      data: {
        users : users
      },
      success: function(data){
        $('#right-ajax').removeClass('loading').html(data);
      }
    });
    return false;
  });
  
  
  $('#users_page_form .user_deleter').live('click', function(){
    var users = p4.users.sel.join(';');
    if(users === '')
    {
      return false;
    }

    $('#right-ajax').empty().addClass('loading');
    p4.users.sel = [];
    $.ajax({
      type: 'POST',
      url: '/admin/users/delete/',
      data: {
        users : users
      },
      success: function(data){
        $('#right-ajax').removeClass('loading').html(data);
      }
    });
    return false;
  });
  $('#users_page .invite_modifier').live('click', function(){
    var users = $(this).next('input').val();

    if($.trim(users) === '')
    {
      return false;
    }

    $('#right-ajax').empty().addClass('loading');
    p4.users.sel = [];
    $.ajax({
      type: 'POST',
      url: '/admin/users/rights/',
      data: {
        users : users
      },
      success: function(data){
        $('#right-ajax').removeClass('loading').html(data);
      }
    });
    return false;
  });
})