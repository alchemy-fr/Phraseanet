$(document).ready(function(){
  if(typeof validator_loaded === 'boolean')
    return;

  $('.agreement_radio').live('mousedown', function(){
    var sselcont_id = $(this).attr('for').split('_').pop();
    var agreement = $('#' + $(this).attr('for')).val() == 'yes' ? '1' : '-1';

    $.mobile.pageLoading();

    $.ajax({
      type: "POST",
      url: "/lightbox/ajax/SET_ELEMENT_AGREEMENT/"+sselcont_id+"/",
      dataType: 'json',
      data: {
        agreement		: agreement
      },
      error: function(datas){
        alert('error');
        $.mobile.pageLoading(true);
      },
      timeout: function(datas){
        alert('error');
        $.mobile.pageLoading(true);
      },
      success: function(datas){
        if(agreement == '1')
          $('.valid_choice_'+sselcont_id).removeClass('disagree').addClass('agree');
        else
          $('.valid_choice_'+sselcont_id).removeClass('agree').addClass('disagree');
        $.mobile.pageLoading(true);
        if(datas.error)
        {
          alert(datas.datas);
          return;
        }
        return;
      }
    });
    return false;

  });
  $('.note_area_validate').live('click', function(){
    var sselcont_id = $(this).closest('form').find('input[name="sselcont_id"]').val();

    $.mobile.pageLoading();
    $.ajax({
      type: "POST",
      url: "/lightbox/ajax/SET_NOTE/"+sselcont_id+"/",
      dataType: 'json',
      data: {
        note			: $('#note_form_'+sselcont_id).find('textarea').val()
      },
      error: function(datas){
        alert('error');
        $.mobile.pageLoading(true);
      },
      timeout: function(datas){
        alert('error');
        $.mobile.pageLoading(true);
      },
      success: function(datas){
        $.mobile.pageLoading(true);
        if(datas.error)
        {
          alert(datas.datas);
          return;
        }

        $('#notes_'+sselcont_id).empty().append(datas.datas);
        window.history.back();
        return;
      }
    });
    return false;
  });

  validator_loaded = true;
});