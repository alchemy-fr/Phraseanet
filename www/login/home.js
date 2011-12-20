jQuery(document).ready(function(){	
	var date = new Date();
	date.setMonth(date.getMonth() + 2);
  jQuery.cookie('screen', screen.width+"x"+screen.height, { path: '/', expires: date });
  
  var test_cookie = date.getTime();
  jQuery.cookie('test_cookie'+test_cookie, 'accepted', { path: '/', expires: date });
  if(!jQuery.cookie('test_cookie'+test_cookie))
  {
    jQuery('.notice.notice_cookie').show();
  }
  else
  {
    date.setMonth(date.getMonth() - 5);
    jQuery.cookie('test_cookie'+test_cookie, '', { path: '/', expires: date });
  }
  
	return false; 
});

function setLanguage()
{
	var date = new Date();
	date.setMonth(date.getMonth() + 2);
	jQuery.cookie('locale', jQuery('#lng-select')[0].value, { path: '/', expires: date });
	window.location.replace(window.location.protocol+"//"+window.location.host+window.location.pathname);
}

function setTab(tab,el)
{
	jQuery('.tab').removeClass('click');
	jQuery(el).addClass('click');
	jQuery('.tab-content').hide();
	jQuery('#id-'+tab).show();
}
