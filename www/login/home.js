
$(document).ready(function(){	
	var date = new Date();
	date.setMonth(date.getMonth() + 2);
	$.cookie('screen', screen.width+"x"+screen.height, { path: '/', expires: date });
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
				
