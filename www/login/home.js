
$(document).ready(function(){	
	var date = new Date();
	date.setMonth(date.getMonth() + 2);
  $.cookie('screen', screen.width+"x"+screen.height, { path: '/', expires: date });
  
  var test_cookie = date.getTime();
  $.cookie('test_cookie'+test_cookie, 'accepted', { path: '/', expires: date });
  if(!$.cookie('test_cookie'+test_cookie))
  {
    $('.notice.notice_cookie').show();
  }
  else
  {
    date.setMonth(date.getMonth() - 5);
    $.cookie('test_cookie'+test_cookie, '', { path: '/', expires: date });
  }
  
	return false; 
});

function setLanguage()
{
	var date = new Date();
	date.setMonth(date.getMonth() + 2);
	$.cookie('locale', $('#lng-select')[0].value, { path: '/', expires: date });
	window.location.replace(window.location.protocol+"//"+window.location.host+window.location.pathname);
}

function setTab(tab,el)
{
	$('.tab').removeClass('click');
	$(el).addClass('click');
	$('.tab-content').hide();
	$('#id-'+tab).show();
}
				
