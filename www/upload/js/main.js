$(function() {
  $("#fsUploadProgress").sortable({
    placeholder: 'ui-state-highlight'
  });
  $("#fsUploadProgress").disableSelection();
});

function do_global_action()
{
  var cont = $('#global_operation');
  var action = $('select[name=action]', cont).val();

  var delete_prev = $('input.delete_previous', cont).attr('checked');

  if(action === '')
  {
    alert(language.pleaseselect);
    return;
  }
  var ret = {
    'add':[],
    'substitute':[],
    'delete':[]
  };
  $.each($('.lazaret_item:visible'), function(i,n)
  {

    var manual = false;
    var from, to, id;
    var elem = [];

    if(!manual && action == 'delete')
    {
      elem = $('.lazaret_deleter', n);
      id = $(elem).closest('form').find('input[name=lazaret_id]').val();
    }
    else
    {
      if(!manual && action == 'substitute')
      {
        manual = $('select[name=record_id]',n).length > 0;
        if(!manual)
        {
          elem = $('.lazaret_substituter', n);
          from = $(elem).closest('form').find('input[name=lazaret_id]').val();
          to = $(elem).closest('form').find('[name=record_id]').val();
        }
      }
      if(!manual && (action == 'add' || elem.length === 0))
      {
        elem = $('.lazaret_base_adder', n);
        id = $(elem).closest('form').find('input[name=lazaret_id]').val();
        action = 'add';
      }

    }

    if(elem.length > 0)
    {
      var obj;
      switch (action)
      {
        case 'delete':
          obj = {
            id:id
          };
          break;
        case 'add':
          obj = {
            id:id
          };
          break;
        case 'substitute':
          obj = {
            from:from,
            to:to
          };
          break;
        default:
          break;
      }
      ret[action].push(obj);
    }
    if(delete_prev)
    {
      $.each($(this).closest('td').find('.more_uploads .lazaret_deleter'), function(){
        var obj = {
          id:$(this).closest('form').find('input[name=lazaret_id]').val()
        };
        ret['delete'].push(obj);
      });
    }
  });
  $.ajax({
    type: "POST",
    url: "/upload/uploadFeedback.php",
    dataType:'json',
    data: {
      action: "lazaret_global_operation",
      actions:ret
    },
    success: function(data){
      if(data.error)
        alert(data.message);
      $('.tabs').tabs('load', 1);
      checkQuarantineSize();
      $('#global_operation')
        .dialog('close');
    }
  });

  return;
}

$(document).ready(function(){

  $('#form1').bind('submit', function(event){
    var classic = !!$('#step2classic').is(':visible');
    if(!classic)
    {
      event.stopPropagation();
      return false;
    }
  });

  $.each($('#status_wrapper .slider_status'),function(){
    activeSliders(this);
  });
  showStatus();
  $('.tabs').tabs({
    select: function(event, ui) {
      if($(ui.tab).attr('id') == 'quarantine-tab'){
        if($('#QUEUE li:not(.done)').length > 0)
        {
          alert(language.transfert_active);
          return false;
        }
        if($('#fsUploadProgress li').length > 0)
        {
          if(!confirm(langage.queue_not_empty))
            return false;
        }
        $('#fsUploadProgress li, #QUEUE li').remove();
        return true;
      }
    }
  });
		
		
		
  $('.tooltip').tooltip();

    var buttons = {};
    buttons[language.ok] =
    function(){

      do_global_action();
    };
    buttons[language.annuler] =
    function(){
      $('#global_operation')
      .dialog('close');
    };
    $('#global_operation').dialog({
      width:500,
      height:200,
      resizable:false,
      draggable:false,
      modal:true,
      buttons:buttons
    }).dialog('close');
  $('.global_operation_trigger').live('click', function(event){
      $('#global_operation')
      .dialog('open');

    return false;
  });
  $('.lazaret_base_adder').live('click', function(event){
    var id = $(this).closest('form').find('input[name=lazaret_id]').val();

    $this = $(this).closest('.group');
			
    $.ajax({
      type: "POST",
      url: "/upload/uploadFeedback.php",
      dataType:'html',
      data: {
        action: "lazaret_add_record_to_base",
        id:id
      },
      success: function(data){
        if(data != '1')
          alert(data);
        else
          $('.lazaret_item_'+id).fadeOut('fast', function(){
            $(this).remove();
            clean($this);
          });
      }
    });
  });
  $('.lazaret_deleter').live('click', function(event){
    var id = $(this).closest('form').find('input[name=lazaret_id]').val();

    $this = $(this).closest('.group');
			
    $.ajax({
      type: "POST",
      url: "/upload/uploadFeedback.php",
      dataType:'html',
      data: {
        action: "lazaret_delete_record",
        id:id
      },
      success: function(data){
        if(data != '1')
          alert(data);
        else
          $('.lazaret_item_'+id).fadeOut('fast', function(){
            $(this).remove();
            clean($this);
          });
      }
    });
  });
  $('.lazaret_substituter').live('click', function(event){
    var from_id = $(this).closest('form').find('input[name=lazaret_id]').val();
    var to_id = $(this).closest('form').find('[name=record_id]').val();

    if(to_id === '')
    {
      alert(language.norecordselected);
      return;
    }

    $this = $(this).closest('.group');

    $.ajax({
      type: "POST",
      url: "/upload/uploadFeedback.php",
      dataType:'html',
      data: {
        action: "lazaret_substitute_record",
        from_id:from_id,
        to_id:to_id
      },
      success: function(data){

        if(data != '1')
          alert(data);
        else
          $('.lazaret_item_'+from_id).fadeOut('fast', function(){
            $(this).remove();
            clean($this);
          });
					
      }
    });
  });
  $(window).bind('resize', function(){
    resize();
  }).trigger('resize');
});
	
function checkQuarantineSize()
{
  $.ajax({
    type: "POST",
    url: "/upload/uploadFeedback.php",
    dataType:'json',
    data: {
      action: "get_lazaret_count"
    },
    success: function(data){
      var q_size = $('#quarantine_size');
      q_size.empty();
      if(!data.error)
        q_size.append(data.count);
    }
  });
}
	
function clean(el)
{
  if($('.lazaret_item', el).length === 0)
  {
    el.slideUp('fast',function(){
      el.remove();
    });
    return;
  }
  if($('.main_item', el).length === 0)
  {
    if($('.more_item', el).length > 0)
    {
      var clone = $('.more_item:first', el)
      .clone()
      .removeClass('more_item')
      .addClass('main_item');
      $('.main_container', el).append(clone);
      $('.more_uploads .more_item:first', el).remove();
    }
  }
  if($('.more_uploads .lazaret_item', el).length === 0)
  {
    $('.more_title, .more_uploads', el).remove();
  }
}
	
function resize()
{
  $('.ui-tabs-panel').height(
    $(window).height() - $('.ui-tabs-panel:visible').offset().top - 2
    );
}
function showStatus()
{
  $('#status_wrapper .status_box').hide();
  $("#status_"+$('#coll_selector select option:selected').val()).show();

		
}
function getCurrentStatusValue(slider)
{

  var ret = 0;
  var parent = $(slider).parent().parent();
  var parent_active = $(parent).find('td.active');

  if(parent_active.length === 0)
  {
    parent_active = $(parent).find('td:first');
    parent_active.addClass('active');
  }

  if(parent_active.length >1)
  {
    $(parent).find('td').removeClass('active');
    parent_active = $(parent).find('td:first');
    parent_active.addClass('active');
  }
		
  if(parent_active.length == 1)
  {
    if(parent_active.hasClass('status_on'))
      ret = 1;
    if(parent_active.hasClass('status_off'))
      ret = 0;
  }
		
  return ret;
}

function activeSliders(slider){
  $(slider).slider({
    value:getCurrentStatusValue($(slider)),
    min: 0,
    max: 1,
    step: 1,
    slide: function(event, ui) {
      var el = $(this).parent().parent();
      $(el).find('td.active').removeClass('active');
      if(parseInt(ui.value) === 0)
        $(el).find('td.status_off').addClass('active');
      else
      if(parseInt(ui.value) === 1)
        $(el).find('td.status_on').addClass('active');

    }
  });
}
var swfu;
function startIt(){
		
  var classic = !!$('#step2classic').is(':visible');
  var els;
		
  if(!classic)
  {
    $('#fsUploadProgress a.progressCancel').remove();
    els = $('#fsUploadProgress li');
  }
  else
  {
    $('#classic_parms').empty();
    els = $('input[type=file]');
  }
	
  $.each(els,function(){
    var id = $(this).attr('id');
			
    var val = $('#coll_selector select option:selected').val();

    if(!classic)
      swfu.addFileParam(id, 'coll', val);
    else
      $('#classic_parms').append(
        '<input type="hidden" name="coll" value="'+val+'"/>'
        );
    $.each($('#status_wrapper td.active:visible'),function(){
      if($(this).hasClass('status_on') || $(this).hasClass('status_off'))
      {
        var val = 0;
        if($(this).hasClass('status_on'))
          val = 1;
        if(!classic)
        {
          swfu.addFileParam(
            id,
            'status['+$(this).attr('id').split('_').pop()+']',
            val
            );
        }
        else
        {
          $('#classic_parms').append(
            '<input type="hidden" '+
            'name="status['+$(this).attr('id').split('_').pop()+']" '+
            'value="'+val+'"/>');
        }
      }
    });
  });
  if(classic)
  {
    $('body').append('<iframe style="display:none;" '+
      'name="classic_upload"></iframe>');
    $('#classic_loader').show();
    $('#form1').submit();
  }
  else
  {
    var list = $('#fsUploadProgress').html();
    $('#fsUploadProgress').empty();
    $('#QUEUE').append('<ul>'+list+'</ul>');
			
    swfu.startUpload();
  }
		
		
}
	
function classic_uploaded(message)
{
  $('#classic_loader').hide();
  alert(message);
  $('input[type=file]').val('');
  $('iframe[name=classic_upload]').remove();
}