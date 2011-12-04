
function publicator_reload_publicator()
{
  var options = $('#dialog_publicator form[name="current_datas"]').serializeArray();

  var opts = {
    type : 'POST',
    url : "/prod/bridge/manager/",
    dataType : 'html',
    data : options,
    beforeSend : function(){
      $('#dialog_publicator').empty().addClass("loading");
    },
    success : function(data){
      publicator_load_datas(data);
    },
    error:function(){
      $('#dialog_publicator').removeClass("loading");
    },
    timeout:function(){
      $('#dialog_publicator').removeClass("loading");
    }
  }
  $.ajax(opts);
}

function publicator_load_datas(data)
{
  $('#dialog_publicator').removeClass("loading").empty().append(data);
  $('#ul_main_pub_tabs li:first a').trigger('click');
}

function init_publicator(datas)
{
  $.ajax({
    type : 'POST',
    url : "/prod/bridge/manager/",
    dataType : 'html',
    data : datas,
    success : function(data){
      div_publicator = $('#dialog_publicator');
      publicator_dialog();
      publicator_load_datas(data);
    },
    error:function(){
      
    },
    timeout:function(){
      
    }
  });
}



function publicator_dialog()
{
  var height = Math.max(bodySize.y - 40, 500);
  div_publicator = $('#dialog_publicator');
  div_publicator.dialog({
    width:900,
    height:height,
    modal:true,
    closeOnEscape : true,
    resizable : false,
    overlay: {
      backgroundColor: '#000',
      opacity: 0.7
    }
  }).dialog('open');
}
