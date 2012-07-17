
var ajaxGeoRunning = false;
var ajaxGeo = false;

					
	
function initialize_geoname_field(box)
{

	
	$(box).bind('keyup',function(event){
				checkCity(event,$(this));
				return false;
			})
			.bind('keydown',function(event){
				goCity(event,$(this));
			})
			.bind('focus',function(event){
				checkCity(event,$(this));return false;
			})
			.bind('blur',function(){
        var city_finder = $(this).parent().find('.geoname_city_finder');
				if($('div.box.selected', city_finder).length > 0)
        {
					selectCity($(this));
        }
				else
        {
          if($('div.box', city_finder).length > 0)
          {
            $(this).val('');
          }
					$(this).parent().find('.geoname_city_finder').empty();
        }

				if (ajaxGeoRunning) 
					ajaxGeo.abort();
				ajaxGeoRunning = false;
				return false
			});
	
	$(box).attr('autocomplete','off').addClass('geoname_initialized');
	var form_name = $(box).attr('name');
	$(box).attr('name',form_name+'_geoname_name');
	$('<div class="geoname_city_finder" style="position:absolute;width:200px;max-height:200px;overflow-y:auto;z-index:99999;"></div>').insertAfter($(box));
	$('<input type="hidden" name="'+form_name+'" value="'+$(box).attr('geonameid')+'"/>').insertAfter($(box));

	var city_finder = $(box).parent().find('.geoname_city_finder');
	
}

function checkCity(event,keybox)
{
	var geoname_id = $(keybox).next().val();
	
	var city_finder = $(keybox).parent().find('.geoname_city_finder');
	
	var badCodes = [9,16,17,18,20,27,33,34,35,36,37,39,45,112,113,114,115,116,117,118,119,120,121,122,123];

	if($.inArray(event.keyCode,badCodes)>=0)
		return false;

	if(event.keyCode == 40)
	{
		el = $('div.box.selected',city_finder); 
		el.removeClass('selected');
		if(el.next(':not(.unselectable)').length == 0)
			el = $('div.box:not(.unselectable):first',city_finder);
		else
			el = el.next(':not(.unselectable)');
		el.addClass('selected');
		city_finder.scrollTop(city_finder.scrollTop()+$(el).position().top-((city_finder.height()-$(el).outerHeight())/2));
		return false;
	}
	else
	{

		if(event.keyCode == 38)
		{
			el =$('div.box.selected',city_finder); 
			el.removeClass('selected');
			if(el.prev(':not(.unselectable)').length == 0)
				el = $('div.box:not(.unselectable):last',city_finder);
			else
				el = el.prev(':not(.unselectable)');
			el.addClass('selected');
			city_finder.scrollTop(city_finder.scrollTop()+$(el).position().top-((city_finder.height()-$(el).outerHeight())/2));
			return false;
		}
		else
		{
			if(event.keyCode == 13)
			{
				event.preventDefault();
				return false;
			}
			else
			{
				$('div.box.selected',city_finder).removeClass('selected'); 
			}
		}
	}

	if($.trim($(keybox).val()) == '')
	{
		$(keybox).next().val('');
		return;
	}

	ajaxGeo = $.ajax({
		type: "POST",
		url: "/include/geonames.feedback.php",
		dataType: 'html',
		data: {
			action: "FIND",
			city : $(keybox).val()
		},
		beforeSend: function(){
			if (ajaxGeoRunning) 
				ajaxGeo.abort();
			ajaxGeoRunning = true;
			city_finder.css({
				top:($(keybox).position().top+$(keybox).outerHeight()),
				left:$(keybox).position().left
			})
			city_finder.empty().append('<div class="box boxI unselectable" style="font-style;italic">Running</div>');
		},
		success: function(data){
			ajaxGeoRunning = false;
			city_finder.empty().append(data);
			if(geoname_id != '')
				$('div:not(.unselectable):first', city_finder).addClass('selected');
			else
			{
				var geo_el = $('#geo_'+geoname_id); 
				if(geo_el.length > 0)
				{
					geo_el.addClass('selected');
					city_finder.scrollTop(city_finder.scrollTop()+geo_el.position().top-((city_finder.height()-geo_el.outerHeight())/2));
				}
			}
			$('div.box:not(.unselectable)', city_finder).bind('mouseover',function(){
				$('div.selected', city_finder).removeClass('selected');
				$(this).addClass('selected');
			}).bind('click',function(){
				selectCity(keybox);
			});
			return false;
		}
    ,error:function(){return;}
    ,timeout:function(){return;}
		
	});
	return false;
}

function goCity(event,keybox)
{
	if(event.keyCode == 13)
	{
		event.preventDefault();
		selectCity(keybox);
		return false;
	}
}
function selectCity(keybox)
{
	var city_finder = $(keybox).parent().find('.geoname_city_finder')
	var val = '',
	id='',
	el = $('div.selected div:first', city_finder);
	if(el.length==0)
		el = false;
	else
	{
		val = el.text();
		id = $('div.selected', city_finder).attr('id').substr(4);
	}
	$(keybox).val(val);
	$(keybox).next().val(id);
	city_finder.empty();
//	$(keybox).trigger('blur');
  
					$(keybox).parent().find('.geoname_city_finder').empty();
}