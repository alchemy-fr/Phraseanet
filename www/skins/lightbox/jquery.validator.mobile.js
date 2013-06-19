$(document).ready(function(){
  if(typeof validator_loaded === 'boolean')
    return;



$('.confirm_report').live('click',function(){
  var $this = $(this);

    $('.loader', $this).css({
        visibility:'visible'
    });

    $.ajax({
        type: "POST",
        url: "/lightbox/ajax/SET_RELEASE/" + $('#basket_validation_id').val() + "/",
        dataType: 'json',
        error: function(data) {
            $('.loader', $this).css({
                visibility: 'hidden'
            });
        },
        timeout: function(data) {
            $('.loader', $this).css({
                visibility: 'hidden'
            });
        },
        success: function(data) {
            $('.loader', $this).css({
                visibility: 'hidden'
            });
            if (data.datas) {
                alert(data.datas);
            }
            if (!data.error) {
               releasable = false;
            }

            return;
        }
    });
});

  $('.agreement_radio').live('vmousedown', function(){
    var sselcont_id = $(this).attr('for').split('_').pop();
    var agreement = $('#' + $(this).attr('for')).val() == 'yes' ? '1' : '-1';

    $.mobile.loading();

    $.ajax({
      type: "POST",
      url: "/lightbox/ajax/SET_ELEMENT_AGREEMENT/"+sselcont_id+"/",
      dataType: 'json',
      data: {
        agreement		: agreement
      },
      error: function(datas){
        alert('error');
        $.mobile.loading();
      },
      timeout: function(datas){
        alert('error');
        $.mobile.loading();
      },
      success: function(datas){
        if(!datas.error)
        {
          if(agreement == '1')
            $('.valid_choice_'+sselcont_id).removeClass('disagree').addClass('agree');
          else
            $('.valid_choice_'+sselcont_id).removeClass('agree').addClass('disagree');
          $.mobile.loading();
          if(datas.error)
          {
            alert(datas.datas);
            return;
          }

          releasable = datas.release;
        }
        else
        {
          alert(datas.datas);
        }
        return;
      }
    });
    return false;

  });
  $('.note_area_validate').live('click', function(){
    var sselcont_id = $(this).closest('form').find('input[name="sselcont_id"]').val();

    $.mobile.loading();
    $.ajax({
      type: "POST",
      url: "/lightbox/ajax/SET_NOTE/"+sselcont_id+"/",
      dataType: 'json',
      data: {
        note			: $('#note_form_'+sselcont_id).find('textarea').val()
      },
      error: function(datas){
        alert('error');
        $.mobile.loading();
      },
      timeout: function(datas){
        alert('error');
        $.mobile.loading();
      },
      success: function(datas){
        $.mobile.loading();
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