
$(document).ready(function(){
	initorders();
});
function initorders()
{
	$('.order_launcher').live('click',function(){
		load_orders(false);
	});
	$('body').append('<div id="order_manager"></div>');
}

function load_orders(sort, page)
{
	if(typeof(page) == "undefined")
	{
		page = 1;
	}

	$.ajax({
		type: "GET",
		url: "/prod/order/",
		dataType:'html',
		data: {
			sort:sort,
			page:page
		},
		success: function(data){
			$('#order_manager').empty().append(data);

			$('#order_manager tr.order_row').bind('click', function(e){
				var id = $(this).attr('id').split('_').pop();
				load_order(id);
			}).addClass('clickable').filter(':odd').addClass('odd');

			display_orders();
		}
	});
}

function load_order(id)
{
	$.ajax({
		type: "GET",
		url: "/prod/order/"+id+"/",
		dataType:'html',
		success: function(data){
			display_orders();

			$('#order_manager').empty().append(data);

			$('#order_manager .order_list .selectable').bind('click',function(event){

				var $this = $(this);

				if(is_ctrl_key(event))
				{
					if($(this).hasClass('selected'))
						$(this).removeClass('selected');
					else
						$(this).addClass('selected');
				}
				else
				{
					if(is_shift_key(event))
					{
						var first = false, last = false;
						$('#order_manager .order_list .selectable').each(function(i,n){
							if(last)
								first = last = false;
							if($(n).attr('id') == $this.attr('id') || $(n).hasClass('last_selected'))
							{
								if(first)
									last = true;
								first = true;

							}
							if(first || last)
								$(n).addClass('selected');
						});
					}
					else
					{
						$('#order_manager .order_list .selectable.selected').removeClass('selected');
						$(this).addClass('selected');
					}
				}
				$('#order_manager .order_list .selectable.last_selected').removeClass('last_selected');

				$(this).addClass('last_selected');
			});

			$('#order_manager button.send').bind('click',function(){
				send_documents(id);
			});
			$('#order_manager button.deny').bind('click',function(){
				deny_documents(id);
			});
			$('#order_manager .force_sender').bind('click',function(){
				if(confirm('Forcer l\'envoie du document ?'))
				{
					var element_id = [];
					element_id.push($(this).closest('.order_wrapper').find('input[name=order_element_id]').val());
					do_send_documents(id, element_id, true);
				}
			});
		}
	});
}

function do_send_documents(order_id, elements_ids, force)
{
	var cont = $('#order_manager');
	$('button.deny, button.send', cont).attr('disabled','disabled');
	$('.activity_indicator', cont).show();

	$.ajax({
		type: "POST",
		url: "/prod/order/"+order_id+"/send/",
		dataType:'json',
		data: {
			'elements[]':elements_ids,
			force:(force?1:0)
		},
		success: function(data){
			$('button.deny, button.send', cont).removeAttr('disabled');
			$('.activity_indicator', cont).hide();
			if(data.error)
			{
				alert(data.datas);
				return;
			}
			load_order(order_id);
		},
		error: function(){
			$('button.deny, button.send', cont).removeAttr('disabled');
			$('.activity_indicator', cont).hide();
		},
		timeout: function(){
			$('button.deny, button.send', cont).removeAttr('disabled');
			$('.activity_indicator', cont).hide();
		}
	});
}

function deny_documents(order_id)
{
	var elements = $('#order_manager .order_list .selectable.selected');

	var elements_ids = [];

	elements.each(function(i,n){
		elements_ids.push($(n).find('input[name=order_element_id]').val());
	});

	if(elements_ids.length == 0)
	{
		alert(language.nodocselected);
		return;
	}
	var cont = $('#order_manager');
	$('button.deny, button.send', cont).attr('disabled','disabled');
	$('.activity_indicator', cont).show();

	$.ajax({
		type: "POST",
		url: "/prod/prodFeedBack.php",
		dataType:'json',
		data: {
			action: "DENY_ORDER",
			order_id : order_id,
			'elements[]':elements_ids
		},
		success: function(data){
			$('.activity_indicator', cont).hide();
			$('button.deny, button.send', cont).removeAttr('disabled');
			if(data.error)
			{
				alert(data.datas);
				return;
			}
			load_order(order_id);
		},
		error: function(){
			$('button.deny, button.send', cont).removeAttr('disabled');
			$('.activity_indicator', cont).hide();
		},
		timeout: function(){
			$('button.deny, button.send', cont).removeAttr('disabled');
			$('.activity_indicator', cont).hide();
		}
	});
}


function send_documents(order_id)
{
	var elements = $('#order_manager .order_list .selectable.selected');

	var elements_ids = [];

	elements.each(function(i,n){
		elements_ids.push($(n).find('input[name=order_element_id]').val());
	});

	if(elements_ids.length == 0)
	{
		alert(language.nodocselected);
		return;
	}
	do_send_documents(order_id, elements_ids, false);
}

function display_orders()
{

	$("#order_manager")
		.dialog({
			autoOpen:false,
			closeOnEscape:true,
			resizable:false,
			draggable:false,
			modal:true,
			width:800,
			height:400,
			overlay: {
				backgroundColor: '#000',
				opacity: 0.7
			},
			beforeclose:function(){
			}
		}).dialog('open');
}