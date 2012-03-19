var p4 = {
  releasable:false
};

$(window).bind('beforeunload', function(){
  if(p4.releasable !== false)
  {
    if(confirm(p4.releasable))
    {
      $('#basket_options .confirm_report').trigger('click');
    }
  }
});

$(document).ready(function(){


	$(window).bind('resize',function(){
		resize();
	}).trigger('resize');

	function resize()
	{
		var h = $(window).height();
		var w = $(window).width();

		$('body').width(w).height(h);
		$('#mainContent').width(w).height(h - $('#mainMenu').outerHeight());
		$('#innerWrapper').width(w - 20).height(h - $('#mainMenu').outerHeight() - 20);
		$('#innerTop').height(h - $('#mainMenu').outerHeight() - 20 - 186);
		$('#record_wrapper').width($('#innerWrapper').innerWidth() - $('#right_column').outerWidth() - 2);
//		var right_column_wrapper_height = $('#right_column').innerHeight() - $('.right_column_title').outerHeight() - 10;
//		$('.right_column_wrapper').height(right_column_wrapper_height);

		$('.record_display_box').each(function(i,n){
			$('.container',n).height($(n).innerHeight() - $('.header').outerHeight());
		});
		display_record($('#record_compare').css('visibility') != 'hidden');
	}


	$(this).data('slideshow',false);
	$(this).data('slideshow_ctime', false);

	$('#mainMenu, .unselectable').disableSelection();

	display_basket();

	$(window).bind('mousedown', function(){
		$(this).focus();
	}).trigger('mousedown');

	$('.basket_downloader').bind('click', function(){
		download_basket();
	});

	$('.basket_wrapper').hover(
		function(){
			$(this).addClass('hover');
		},
		function(){
			$(this).removeClass('hover');
		}
	).bind('click', function(){
		var id = $('input[name=ssel_id]',this).val();
		document.location = '/lightbox/validate/'+id+'/';
		return;
	});
	$('.right_column_title').bind('click', function(){
		var title = $('.right_column_title');
		if(!$('.right_column_wrapper_caption').is(':visible'))
		{
			$('.right_column_wrapper_user').height(0);
			$('.right_column_wrapper_caption').show();
			$('.caption', title).addClass('highlight');
			$('.validate', title).removeClass('highlight');
		}
		else
		{
			$('.right_column_wrapper_user').height('auto');
			$('.right_column_wrapper_caption').hide();
			$('.caption', title).removeClass('highlight');
			$('.validate', title).addClass('highlight');
		}
	}).addClass('clickable');

	var sselcont = $('#sc_container .basket_element:first');
	if(sselcont.length > 0)
	{
		display_basket_element(false, sselcont.attr('id').split('_').pop());
	}


	set_sizeable($('#record_main .container, #record_compare .container'));

	$('#navigation')
			.bind('change',
					function()
					{
						window.location.replace(window.location.protocol+"//"+window.location.host+'/lightbox/validate/'+$(this).val()+'/');
					}
			);
	bind_keyboard();
});


function bind_keyboard()
{
	$(document).bind('keydown', function(event){

		var stop = false;
		$('.notes_wrapper').each(function(i,n){
			if(parseInt($(n).css('top')) >= 0)
				stop = true;
		});

		if(stop)
			return true;

		var cancelKey = false;

    var el, id;

		switch(event.keyCode)
		{
			case 39:
				get_next();
				cancelKey = true;
				break;
			case 37:
				get_prev();
				cancelKey = true;
				break;
			case 32:
				var bool = !$(document).data('slideshow');
				slideshow(bool);
				break;
			case 38:
				el = $('#sc_container .basket_element.selected');
				if(el.length === 1)
				{
					id = el.attr('id').split('_').pop();
					set_agreement(event, el, id, 1);
				}
				break;
			case 40:
				el = $('#sc_container .basket_element.selected');
				if(el.length === 1)
				{
					id = el.attr('id').split('_').pop();
					set_agreement(event, el, id, -1);
				}
				break;
			default:
				break;
		}

		if(cancelKey)
		{
			event.cancelBubble = true;
			if(event.stopPropagation)
				event.stopPropagation();
			return(false);
		}
    return true;
	});
}

function set_release(el)
{
  $('.loader', el).css({visibility:'visible'});
	$.ajax({
		type: "POST",
    url: "/lightbox/ajax/SET_RELEASE/"+$('#navigation').val()+"/",
		dataType: 'json',
    error: function(data){
      $('.loader', el).css({visibility:'hidden'});
    },
    timeout: function(data){
      $('.loader', el).css({visibility:'hidden'});
    },
		success: function(data){
      $('.loader', el).css({
        visibility:'hidden'
      });
      if(data.datas)
  		{
      	alert(data.datas);
      }
      if(!data.error)
      {
        p4.releasable = false;
      }

			return;
		}
	});
}

function load_report()
{
	$.ajax({
    type: "GET",
    url: "/lightbox/ajax/LOAD_REPORT/"+$('#navigation').val()+"/",
		dataType: 'html',
		success: function(data){
			$('#report').empty().append(data);
			$('#report .reportTips').tooltip({delay:false});
			$('#report').dialog({
				width	: 600,
				height	: Math.round($(window).height() * 0.8)
			}).dialog('open').show();
			return;
		}
	});
}


function set_sizeable(container)
{

	$(container).bind('mousewheel',function(event,delta){

		if($(this).hasClass('note_editing'))
			return;

		var record = $('.record', this);

		if(record.length === 0)
			return;

		var o_top = parseInt(record.css('top'));
		var o_left = parseInt(record.css('left'));

    var o_width, o_height, width, height;

		if(delta > 0)
		{
			if (record.width() > 29788 || record.height() >= 29788)
				return;
			o_width = record.width();
			o_height = record.height();
			width = Math.round(o_width * 1.1);
			height = Math.round(o_height * 1.1);
		}
		else
		{
			if (record.width() < 30 || record.height() < 30)
				return;
			o_width = record.width();
			o_height = record.height();
			width = Math.round(o_width / 1.05);
			height = Math.round(o_height / 1.05);
		}

		var top = Math.round((height / o_height) * (o_top - $(this).height() / 2) + $(this).height() / 2);
		var left = Math.round((width / o_width) * (o_left - $(this).width() / 2) + $(this).width() / 2);

		record.width(width).height(height).css({top:top, left:left});
	});

}



function display_basket()
{
	var sc_wrapper = $('#sc_wrapper');
	var basket_options = $('#basket_options');

	$('.report').bind('click', function(){
		load_report();
	}).addClass('clickable');
	$('.confirm_report', basket_options).button({
	}).bind('click',function(){
		set_release($(this));
	});

	$('.basket_element',sc_wrapper).parent()
		.bind('click',function(event){
			scid_click(event, this);
      return false;
		})
		.addClass('clickable');

	$('.agree_button, .disagree_button',sc_wrapper).bind('click',function(event){

		var sselcont_id = $(this).closest('.basket_element').attr('id').split('_').pop();

		var agreement = $(this).hasClass('agree_button') ? '1' : '-1';

		set_agreement(event, $(this), sselcont_id, agreement);
    return false;
	}).addClass('clickable');

	n = $('.basket_element',sc_wrapper).length;
	$('#sc_container').width(n * $('.basket_element_wrapper:first',sc_wrapper).outerWidth() + 1);

	$('.previewTips').tooltip();
}














function display_basket_element(compare, sselcont_id)
{
	var container;
	if(compare)
	{
		container = $('#record_compare');
	}
	else
	{
		container = $('#record_main');
	}
	$('.record_image', container).draggable();

	var options_container = $('.options',container);

	$('.download_button', options_container).button({
				text	: false
	}).bind('click',function(){
		download($(this).next('form[name=download_form]').find('input').val());
	});

	$('.comment_button', options_container).button({
		text	: true
	}).bind('click',function()
			{
				if($('.container', container).hasClass('note_editing'))
				{
					hide_notes(container);
				}
				else
				{
					show_notes(container);
				}
			}
		);

	activate_notes(container);

	$('.previous_button', options_container).button({
		text	: false
	}).bind('click',function(){
		get_prev();
	});

	$('.play_button', options_container).button({
		text	: false
	}).bind('click',function(){
		slideshow(true);
	});

	$('.pause_button', options_container).button({
		text	: false
	}).bind('click',function(){
		slideshow(false);
	});

	if($(document).data('slideshow'))
	{
		$('.play_button, .next_button.play, .previous_button.play', options_container).hide();
	}
	else
	{
		$('.pause_button, .next_button.pause, .previous_button.pause', options_container).hide();
	}

	$('.next_button', options_container).button({
		text	: false
	}).bind('click',function(){
		get_next();
	});

	$('.container', container).bind('dblclick',function(event){
								display_record();
							});

	$('#record_wrapper .agree_'+sselcont_id+', .big_box.agree')
			.bind('click',
					function(event)
					{
						set_agreement(event, $(this), sselcont_id, '1');
					}
				)
			.addClass('clickable');

	$('#record_wrapper .disagree_'+sselcont_id+', .big_box.disagree')
			.bind('click',
					function(event)
					{
						set_agreement(event, $(this), sselcont_id, '-1');
					}
				)
			.addClass('clickable');

	if(compare == $('#record_wrapper').hasClass('single'))
	{
		if(compare)
		{
			$('#record_infos, #right_column').hide();
			$('#record_wrapper').stop().css({width:'100%'});
			display_record(compare);
		}
		else
		{
			$('#record_wrapper').css({
        width:($('#innerWrapper').width() - $('#record_infos').outerWidth() - $('#right_column').outerWidth() - 2)
      });
			display_record(compare);
			$('#record_infos, #right_column').show();
			$('#record_compare .container').empty();
		}

	}
	else
	{
		display_record(compare);
	}

}


function set_container_status(status)
{
	$('#record_wrapper').removeClass('paysage portrait single').addClass(status);
}

function show_notes(container)
{
	$('.notes_wrapper', container).animate({top:0});
	$('.container', container).addClass('note_editing');
}

function hide_notes(container)
{
	$('.notes_wrapper', container).animate({top:'-100%'});
	$('.container', container).removeClass('note_editing');
}

function activate_notes(container)
{
	$('.note_closer', container).button({
		text	: true
	}).bind('click',function()
			{
//				$(this).blur();
				hide_notes(container);
				return false;
			}
		);

	$('.note_saver', container).button({
		text	: true
	}).bind('click',function()
			{
//				$(this).blur();
				save_note(container, this);
				return false;
			}
		);
}




function download(value)
{
	var dialog_box = $('#dialog_dwnl');

	dialog_box = $('#dialog_dwnl');

	dialog_box.empty().addClass('loading').dialog({
		width:800,
		height:600,
		modal:true,
		closeOnEscape : false,
		resizable : false,
		overlay: {
			backgroundColor: '#000',
			opacity: 0.7
		},
		beforeclose:function(){
			tinyMCE.execCommand('mceRemoveControl',true,'sendmail_message');
			tinyMCE.execCommand('mceRemoveControl',true,'order_usage');
		}
	}).dialog('open');

	$.post("/include/multiexports.php", "lst="+value, function(data) {

		dialog_box.removeClass('loading').empty().append(data);
		$('.tabs', dialog_box).tabs();
		tinyMCE.execCommand('mceAddControl',true,'sendmail_message');
		tinyMCE.execCommand('mceAddControl',true,'order_usage');

		$('.close_button', dialog_box).bind('click',function(){
			dialog_box.dialog('close').dialog('destroy');
		});
		return false;
	});

}


function display_record(compare)
{
	var main_container 				= $('#record_wrapper');

  main_container.width($('#innerWrapper').innerWidth() - $('#right_column').outerWidth() - 2);

	if(typeof compare == 'undefined')
		compare = !main_container.hasClass('single');

	var main_box					= $('#record_main');
	var compare_box					= $('#record_compare');

	var main_record 				= $('.container .record', main_box);
	var compare_record 				= $('.container .record', compare_box);

	var main_record_width			= parseInt($('input[name=width]', main_box).val());
	var main_record_height			= parseInt($('input[name=height]', main_box).val());
	var compare_record_width		= parseInt($('input[name=width]', compare_box).val());
	var compare_record_height		= parseInt($('input[name=height]', compare_box).val());

	var main_container_width 		= main_container.width();
	var main_container_innerwidth 	= main_container.innerWidth();
	var main_container_height 		= main_container.height();
	var main_container_innerheight 	= main_container.innerHeight();


	if(compare)
	{
    $('.agreement_selector').show();
		main_container.addClass('comparison');

		var double_portrait_width		= main_container_innerwidth / 2;
		var double_portrait_height		= main_container_innerheight - $('.header', main_box).outerHeight();

		var double_paysage_width		= main_container_innerwidth;
		var double_paysage_height		= main_container_innerheight / 2  - $('.header', main_box).outerHeight();

		var main_display_portrait		= calculate_display(
											double_portrait_width, double_portrait_height,
											main_record_width, main_record_height
											);
		var main_display_paysage		= calculate_display(
											double_paysage_width, double_paysage_height,
											main_record_width, main_record_height
											);

		var compare_display_portrait	= calculate_display(
											double_portrait_width, double_portrait_height,
											compare_record_width, compare_record_height
											);
		var compare_display_paysage		= calculate_display(
											double_paysage_width, double_paysage_height,
											compare_record_width, compare_record_height
											);

		var surface_main_portrait		= main_display_portrait.width * main_display_portrait.height;
		var surface_main_paysage		= main_display_paysage.width * main_display_paysage.height;
		var surface_compare_portrait	= compare_display_portrait.width * compare_display_portrait.height;
		var surface_compare_paysage		= compare_display_paysage.width * compare_display_paysage.height;

		var double_portrait_surface		= (surface_main_portrait + surface_compare_portrait) / 2;
		var double_paysage_surface		= (surface_main_paysage + surface_compare_paysage) / 2;

		var smooth_image = false;

		var m_width_image, m_height_image, c_width_image, c_height_image, dim_container;

		if(double_portrait_surface > double_paysage_surface)
		{
			if(!main_container.hasClass('portrait'))
			{
				smooth_image = true;

				smooth_transform(
						main_box,
						parseInt($('#innerWrapper').width() / 2 - 13),
						$('#innerWrapper').height(),
						function()
						{
							set_container_status('portrait');
						}
					);

				compare_box.css('visibility','hidden');

				smooth_transform(
							compare_box,
							parseInt($('#innerWrapper').width() / 2 - 13),
							$('#innerWrapper').height(),
							function()
							{
								compare_box.css('display','none')
													.css('visibility','visible').show();
							}
						);
			}
			m_width_image	= main_display_portrait.width;
			m_height_image	= main_display_portrait.height;
			c_width_image	= compare_display_portrait.width;
			c_height_image	= compare_display_portrait.height;
			dim_container 	= {width:double_portrait_width,height:double_portrait_height};
		}
		else
		{
			if(!main_container.hasClass('paysage'))
			{
				smooth_image = true;

				smooth_transform(
							main_box,
							$('#innerWrapper').width(),
							parseInt($('#innerWrapper').height() / 2),
							function()
							{
								set_container_status('paysage');
							}
						);

				compare_box.css('visibility','hidden');

				smooth_transform(
							compare_box,
							$('#innerWrapper').width(),
							parseInt($('#innerWrapper').height() / 2),
							function()
							{
								compare_box.css('display','none')
													.css('visibility','visible')
													.show();
							}
						);
			}
			m_width_image	= main_display_paysage.width;
			m_height_image	= main_display_paysage.height;
			c_width_image	= compare_display_paysage.width;
			c_height_image	= compare_display_paysage.height;
			dim_container	= {width:double_paysage_width,height:double_paysage_height};
		}

		var image_callback = set_image_position(false, compare_record, c_width_image, c_height_image, dim_container, function(){});
		set_image_position(smooth_image, main_record, m_width_image, m_height_image, dim_container, image_callback);
	}
	else
	{
    $('.agreement_selector').hide();
		main_container.removeClass('comparison');

		if(compare_box.is(':visible'))
		{
			compare_box.hide().css('visibility','hidden').css('display','block');
		}

		var main_display	= calculate_display(
											main_container_innerwidth
                      , (main_container_innerheight - $('.header', main_box).outerHeight())
                      , main_record_width
                      , main_record_height
										);

		if(!main_container.hasClass('single'))
		{
			main_box.width('100%')
					.height('100%');

			set_container_status('single');
		}

		set_image_position(
      smooth_image
      , main_record
      , main_display.width
      , main_display.height
      , {
          width : main_container_width
          ,height : (main_container_height - $('.header', main_box).outerHeight())
        }
    );
	}
}


function set_agreement(event, el, sselcont_id, boolean_value)
{
	if(event.stopPropagation)
		event.stopPropagation();
	event.cancelBubble = true;

	var id =

	$.ajax({
    type: "POST",
    url: "/lightbox/ajax/SET_ELEMENT_AGREEMENT/"+sselcont_id+"/",
		dataType: 'json',
		data: {
			agreement		: boolean_value
		},
		success: function(datas){
      if(!datas.error)
      {
        if(boolean_value == '1')
        {
          $('.agree_'+sselcont_id+'').removeClass('not_decided');
          $('.disagree_'+sselcont_id+'').addClass('not_decided');
          $('.userchoice.me').addClass('agree').removeClass('disagree');
        }
        else
        {
          $('.agree_'+sselcont_id+'').addClass('not_decided');
          $('.disagree_'+sselcont_id+'').removeClass('not_decided');
          $('.userchoice.me').addClass('disagree').removeClass('agree');
        }
        p4.releasable = datas.releasable;
        if(datas.releasable !== false)
        {
          if(confirm(datas.releasable))
            $('#basket_options .confirm_report').trigger('click');
        }
      }
      else
      {
        alert(datas.datas);
      }
			return;
		}
	});
}



function get_next()
{
	var current_wrapper = $('#sc_container .basket_element.selected').parent();

	if(current_wrapper.length === 0)
		return;

	current_wrapper = current_wrapper.next();
	if(current_wrapper.length === 0)
		current_wrapper = $('#sc_container .basket_element_wrapper:first');

	$('.basket_element', current_wrapper).trigger('click');

	adjust_visibility($('.basket_element', current_wrapper));

	if($(document).data('slideshow'))
	{
		var timer = setTimeout('get_next();', 3500);
		$(document).data('slideshow_ctime', timer);
	}
}

function get_prev()
{
	var current_wrapper = $('#sc_container .basket_element.selected').parent();

	if(current_wrapper.length === 0)
		return;

	slideshow(false);

	current_wrapper = current_wrapper.prev();
	if(current_wrapper.length === 0)
		current_wrapper = $('#sc_container .basket_element_wrapper:last');

	$('.basket_element', current_wrapper).trigger('click');

	adjust_visibility($('.basket_element', current_wrapper));
}
function is_viewable(el)
{
	var sc_wrapper = $('#sc_wrapper');
	var sc_container = $('#sc_container');

	var el_width = $(el).parent().outerWidth();
	var el_position = $(el).parent().position();
	var sc_scroll_left = sc_wrapper.scrollLeft();

	var boundup = sc_wrapper.width(),
		bounddown = 0,
		placeup = el_position.left + el_width - sc_scroll_left,
		placedown = el_position.left - sc_scroll_left;

	if(placeup <= boundup && placedown >= bounddown)
		return true;
	return false;
}

function adjust_visibility(el)
{
	if(is_viewable(el))
		return;

	var sc_wrapper = $('#sc_wrapper');
	var el_parent = $(el).parent();

	var sc_left = el_parent.position().left + el_parent.outerWidth() / 2 - sc_wrapper.width() / 2;

	sc_wrapper.stop().animate({'scrollLeft':sc_left});
}


function slideshow(boolean_value)
{
	if(boolean_value == $(document).data('slideshow'))
		return;

	if(!boolean_value && $(document).data('slideshow_ctime'))
	{
		clearTimeout($(document).data('slideshow_ctime'));
		$(document).data('slideshow_ctime', false);
	}

	$(document).data('slideshow', boolean_value);

	var headers = $('#record_wrapper .header');

	if(boolean_value)
	{
		$('.play_button, .next_button.play, .previous_button.play').hide();
		$('.pause_button, .next_button.pause, .previous_button.pause').show();
		get_next();
	}
	else
	{
		$('.pause_button, .next_button.pause, .previous_button.pause').hide();
		$('.play_button, .next_button.play, .previous_button.play').show();
	}
}


function smooth_transform(box, width, height, callback)
{
	if(typeof callback == 'undefined')
		callback = function(){};

	$(box).stop()
		.css(
			{
				width	: width,
				height	: height
			}
//				,
//				500,
//				callback
		);
	callback();
}

function save_note(container, button)
{
	var sselcont_id = $(button).attr('id').split('_').pop();
	var note = $('.notes_wrapper textarea', container).val();

	$.ajax({
    type: "POST",
    url: "/lightbox/ajax/SET_NOTE/"+sselcont_id+"/",
		dataType: 'json',
		data: {
			note			: note
		},
		success: function(datas){
				hide_notes(container);
				$('.notes_wrapper', container).remove();
				$('.container', container).append(datas.datas);
				activate_notes(container);
			return;
		}
	});

}
function calculate_display(display_width, display_height, width, height, margin)
{
	if(typeof margin == 'undefined')
		margin = 10;

	var display_ratio = display_width / display_height;
	var ratio = width / height;
	var w,h;

	if(ratio > display_ratio)//landscape
	{
		w = display_width - 2 * margin;
		if(w > width)
			w = width;
		h = w / ratio;
	}
	else
	{
		h = display_height - 2 * margin;
		if(h > height)
			h = height;
		w = ratio * h;
	}

	return {width:w,height:h};
}


function set_image_position(smooth, image, width, height, container, callback)
{
	var dimensions = {};

	if(typeof container !== 'undefined')
	{
		var c_width		= container.width;
		var c_height	= container.height;

		dimensions.top	= parseInt((c_height - height) / 2);
		dimensions.left	= parseInt((c_width - width) / 2);
	}
	if(typeof callback == 'undefined')
	{
		callback = function(){};
	}

	dimensions.width	= parseInt(width);
	dimensions.height	= parseInt(height);
//		if(smooth)
//		{
//			$(image).stop().animate(dimensions,500,callback);
//		}
//		else
//		{
		$(image).css(dimensions);
		callback;
//		}
}


function scid_click(event, el)
{
	var compare = is_ctrl_key(event);

  if(compare)
  {
    if($('.basket_element', el).hasClass('selected'))
      return;
  }
  else
  {
    $('#sc_container .basket_element.selected').removeClass('selected');
    $('.basket_element', el).addClass('selected');
  }

	var sselcont_id = $('.basket_element', el).attr('id').split('_').pop();
	var ssel_id = $('#navigation').val();

	var container = $('#sc_container');

	var request = container.data('request');
	if(request && typeof(request.abort) == 'function')
	{
		request.abort();
	}

	request = $.ajax({
    type: "GET",
    url: $(el).attr('href'),//"/lightbox/ajax/LOAD_BASKET_ELEMENT/"+sselcont_id+'/',
		dataType: 'json',
		success: function(data){
			var container = false;

			if(compare)
			{
				container = $('#record_compare');
			}
			else
			{
				container = $('#record_main');

				$('#record_infos .container')
						.empty()
						.append(data.caption);

				$('#basket_infos')
						.empty()
						.append(data.agreement_html);
			}

			$('.display_id',container)
					.empty()
					.append(data.number);

			$('.title',container)
					.empty()
					.append(data.title)
					.attr('title', data.title);

			var options_container = $('.options',container);
			options_container
					.empty()
					.append(data.options_html);

			$('.container', container).empty()
									.append(data.preview+data.selector_html+data.note_html);


			display_basket_element(compare, sselcont_id);


			return;
		}
	});
	container.data('request', request);
}



function download_basket()
{
	var ids = $.map($('#sc_container .download_form').toArray(), function(el, i){
		return $('input[name="basrec"]',$(el)).val();
	});
	download(ids.join(';'));
}

function is_ctrl_key(event)
{
	if(event.altKey)
		return true;
	if(event.ctrlKey)
		return true;
	if(event.metaKey)	// apple key opera
		return true;
	if(event.keyCode == '17')	// apple key opera
		return true;
	if(event.keyCode == '224')	// apple key mozilla
		return true;
	if(event.keyCode == '91')	// apple key safari
		return true;

	return false;
}

function is_shift_key(event)
{
	if(event.shiftKey)
		return true;
	return false;
}


