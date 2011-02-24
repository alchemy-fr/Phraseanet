var prevAjax,prevAjaxrunning;
prevAjaxrunning = false;
p4.slideShow = false;

$(document).ready(function(){
	$('#PREVIEWIMGDESC').tabs();
});


function getNewVideoToken(base_id, record_id, obj)
{
	$.ajax({
		type: "POST",
		url: "/prod/prodFeedBack.php",
		dataType: 'json',
		data: {
			action:'VIDEOTOKEN',
			base_id : base_id,
			record_id : record_id
		},
		success: function(data){
			if(!data.url)
				return;
			obj.unload();
			obj.setClip(data);
			obj.play();
			return;
		}

	});
}

function openPreview(env, pos, contId, reload){

	if (contId == undefined)
		contId = '';
	var roll = 0;
	var justOpen = false;
//	if (env == 'RESULT')
//		$('#PREVIEWCURRENT').height(40);
//	else
//		$('#PREVIEWCURRENT').height(116);

	if (!p4.preview.open) {
		showOverlay();

		$('#PREVIEWIMGCONT').disableSelection();

		$('#PREVIEWIMGCONT.dblclick').bind('dblclick',function(event){
			$(this).find('.zoomable').removeClass('zoomed');
			setPreview();
		}).bind('mousewheel',function(event, delta){
			$(this).find('.zoomable').addClass('zoomed');
			if(delta > 0)
			{
				event.stopPropagation();
				zoomPreview(true);
			}
			else
			{
				event.stopPropagation();
				zoomPreview(false);
			}
		}).removeClass('dblclick');

		justOpen = true;

		if (!$.browser.msie) {
			$('#PREVIEWBOX').css({
				'display': 'block',
				'opacity': 0
			}).fadeTo(500, 1);
		}else
		{
			$('#PREVIEWBOX').css({
				'display': 'block',
				'opacity': 1
			});
		}
		p4.preview.open = true;
		p4.preview.nCurrent = 5;
		$('#PREVIEWCURRENT, #PREVIEWOTHERSINNER, #SPANTITLE').empty();
		resizePreview();
		if(env == 'BASK')
			roll = 1;

	}

	if(reload === true)
		roll = 1;


	$('#tooltip').css({
		'display': 'none'
	});

	empty_preview();

	prevAjax = $.ajax({
		type: "POST",
		url: "/client/clientFeedBack.php",
		dataType: 'json',
		data: {
			action: "PREVIEW",
			env: env,
			pos: pos,
			cont: contId,
			roll: roll
		},
		beforeSend: function(){
			if (prevAjaxrunning)
				prevAjax.abort();
			if(env == 'RESULT')
				$('#current_result_n').empty().append(parseInt(pos)+1);
			prevAjaxrunning = true;
			$('#PREVIEWIMGDESC, #PREVIEWOTHERS').addClass('loading');
      empty_preview();
		},
		error: function(data){
			prevAjaxrunning = false;
			$('#PREVIEWIMGDESC, #PREVIEWOTHERS').removeClass('loading');
			posAsk = null;
		},
		timeout: function(){
			prevAjaxrunning = false;
			$('#PREVIEWIMGDESC, #PREVIEWOTHERS').removeClass('loading');
			posAsk = null;
		},
		success: function(data){
			cancelPreview();
			prevAjaxrunning = false;
			posAsk = null;

			if(data.error)
			{
				$('#PREVIEWIMGDESC, #PREVIEWOTHERS').removeClass('loading');
				alert(data.error);
				if(justOpen)
					closePreview();
				return;
			}

      if((data.type == 'video'))
      {
    		$('#PREVIEWIMGCONT').html(data.prev_html);
      }
      else
      {
    		$('#PREVIEWIMGCONT').html(data.prev);
      }

      $('#PREVIEWIMGDESCINNER').empty().append(data.desc);
			$('#HISTORICOPS').empty().append(data.history);
			$('#popularity').empty().append(data.popularity);

			if($('#popularity .bitly_link').length>0)
			{

				BitlyCB.statsResponse = function(data) {
				    var result = data.results;
				    if( $( '#popularity .bitly_link_' + result.userHash ).length > 0 )
				    {
				    	$( '#popularity .bitly_link_' + result.userHash ).append( ' (' + result.clicks + ' clicks)');
				    }
				};
				BitlyClient.stats($('#popularity .bitly_link').html(), 'BitlyCB.statsResponse');
			}


			p4.preview.current = {};
			p4.preview.current.width = data.width;
			p4.preview.current.height = data.height;
			p4.preview.current.prev = data.prev;
			p4.preview.current.tot = data.tot;
			p4.preview.current.hd = data.hd;
			p4.preview.current.hdH = data.hdH;
			p4.preview.current.hdW = data.hdW;
			p4.preview.current.type = data.type;
			p4.preview.current.pos = data.pos;
			p4.preview.current.flashcontent = data.flashcontent;

			if ((data.type == 'video' || data.type == 'audio' || data.type == 'flash'))
      {
				if(data.type != 'video' && p4.preview.current.flashcontent.url)
				{
					var flashvars = false;
					var params = {
						menu: "false",
						flashvars: p4.preview.current.flashcontent.flashVars,
						movie: p4.preview.current.flashcontent.url,
						allowFullScreen :"true",
						wmode: "transparent"
					};
					var attributes = false;
					if (data.type != 'audio') {
						attributes = {
							styleclass: "PREVIEW_PIC"
						};
					}
					swfobject.embedSWF(p4.preview.current.flashcontent.url, "FLASHPREVIEW", p4.preview.current.flashcontent.width, p4.preview.current.flashcontent.height, "9.0.0", false, flashvars, params, attributes);
				}
				else
				{
//          alert(data.flashcontent.flv);
//          try
//          {
//					flowplayer("FLASHPREVIEW",  {src:"/include/flowplayer/flowplayer-3.2.6.swf", wmode: "transparent"}, {
//							clip: {
//								autoPlay: true,
//								autoBuffering:true,
//								provider: 'h264streaming',
//								metadata: false,
//								scaling:'fit',
//							    url: data.flashcontent.flv
//							},
//							onError:function(code,message){getNewVideoToken(data.base_id, data.record_id, this);},
//							plugins: {
//								h264streaming: {
//								url: '/include/flowplayer/flowplayer.pseudostreaming-3.2.6.swf'
//							}
//						}
//					});
//          $('#PREVIEWIMGDESCINNER').empty().append('<textarea></textarea>').find('textarea').val($('#FLASHPREVIEW').html());
//
//          }
//          catch(err)
//          {
//            alert(err);
//          }
				}
			}

			if($('img.PREVIEW_PIC.zoomable').length > 0)
			{
				$('img.PREVIEW_PIC.zoomable').draggable();
			}

			setTitle(data.title);
			setPreview();

			if (env == 'REG' || (env == 'BASK' && data.current !== '')) {
				setCurrent(data.current);
				viewCurrent($('#PREVIEWCURRENT li.selected'));
			}
			if(env == 'RESULT')
			{
				if(!justOpen)
				{
					$('#PREVIEWCURRENT li.selected').removeClass('selected');
					$('#PREVIEWCURRENTCONT li.current'+pos).addClass('selected');
				}
				if(justOpen || ($('#PREVIEWCURRENTCONT li.current'+pos).length === 0) ||  ($('#PREVIEWCURRENTCONT li:last')[0] == $('#PREVIEWCURRENTCONT li.selected')[0]) ||  ($('#PREVIEWCURRENTCONT li:first')[0] == $('#PREVIEWCURRENTCONT li.selected')[0]))
				{
					getAnswerTrain(pos, data.tools);
				}

				viewCurrent($('#PREVIEWCURRENT li.selected'));
			}
			if(env == 'REG' && $('#PREVIEWCURRENT').html() === '')
			{
				getRegTrain(contId,pos,data.tools);
			}
			setOthers(data.others);
			setTools(data.tools);
			$('#tooltip').css({
				'display': 'none'
			});
			$('#PREVIEWIMGDESC, #PREVIEWOTHERS').removeClass('loading');
			if(!justOpen || (p4.preview.mode != env))
				resizePreview();

			p4.preview.mode = env;
			$('#EDIT_query').focus();

			$('#PREVIEWOTHERSINNER .otherBaskToolTip').tooltip();

			return;
		}

	});

}

function zoomPreview(bool){

	var el = $('img.PREVIEW_PIC');

	if(el.length === 0)
		return;

	var t1 = parseInt(el.css('top'));
	var l1 = parseInt(el.css('left'));
	var w1 = el.width();
	var h1 = el.height();

	var w2,t2;

	if(bool)
	{
		if(w1 * 1.08 < 32767)
			w2 = w1 * 1.08;
		else
			w2 = w1;
	}
	else
	{
		if(w1 / 1.08 > 20)
			w2 = w1 / 1.08;
		else
			w2 = w1;
	}

	var ratio = p4.preview.current.width / p4.preview.current.height;
	h2 = Math.round(w2 / ratio);
	w2 = Math.round(w2);

	var t2 = Math.round(t1 - (h2 - h1) / 2)+'px';
	var l2 = Math.round(l1 - (w2 - w1) / 2)+'px';

	var wPreview = $('#PREVIEWIMGCONT').width()/2;
	var hPreview = $('#PREVIEWIMGCONT').height()/2;

	var nt = Math.round((h2 / h1) * (t1 - hPreview) + hPreview);
	var nl = Math.round(((w2 / w1) * (l1 - wPreview)) + wPreview);

	el.css({
		left: nl,
		top: nt
	}).width(w2).height(h2);
}

function getAnswerTrain(pos, tools)
{
	$('#PREVIEWCURRENTCONT').fadeOut('fast');
	$.ajax({
		type: "POST",
		url: "/prod/prodFeedBack.php",
		dataType: 'json',
		data: {
			action: "ANSWERTRAIN",
			pos:pos

		},
		success: function(data){
			setCurrent(data.current);
			viewCurrent($('#PREVIEWCURRENT li.selected'));
			setTools(tools);
//			setTools(tools);
			return;
		}
	});
}


function getRegTrain(contId,pos,tools)
{
	$.ajax({
		type: "POST",
		url: "./prodFeedBack.php",
		dataType: 'json',
		data: {
			action: "REGTRAIN",
			cont:contId,
			pos:pos
		},
		success: function(data){
			setCurrent(data.current);
			viewCurrent($('#PREVIEWCURRENT li.selected'));
			if(typeof(tools) != 'undefined')
				setTools(tools);
			return;
		}
	});
}

function bounce(sbid, term, field){
	doThesSearch('T', sbid, term, field);
	closePreview();
}

function setTitle(title){
	$('#SPANTITLE').empty().append(title);
}

function cancelPreview(){
	$('#PREVIEWIMGDESCINNER').empty();
	empty_preview();
	p4.preview.current = false;
}

function empty_preview()
{
  var player_cont = $('#PREVIEWIMGCONT object').parent();
  if(player_cont.attr('id') != 'PREVIEWIMGCONT')
  {
    player_cont.empty();
    player_cont.remove();
  }
  $('#PREVIEWIMGCONT').empty();
}

function startSlide(){
	if (!p4.slideShow) {
		p4.slideShow = true;
	}
	if (p4.slideShowCancel) {
		p4.slideShowCancel = false;
		p4.slideShow = false;
		$('#start_slide').show();
		$('#stop_slide').hide();
	}
	if(!p4.preview.open)
	{
		p4.slideShowCancel = false;
		p4.slideShow = false;
		$('#start_slide').show();
		$('#stop_slide').hide();
	}
	if (p4.slideShow) {
		$('#start_slide').hide();
		$('#stop_slide').show();
		getNext();
		setTimeout("startSlide()", 3000);
	}
}

function stopSlide(){
	p4.slideShowCancel = true;
		$('#start_slide').show();
		$('#stop_slide').hide();
}

//var posAsk = null;

function getNext(){
	if (p4.preview.mode == 'REG' && parseInt(p4.preview.current.pos) == 0)
		$('#PREVIEWCURRENTCONT li img:first').trigger("click");
	else {
		if (p4.preview.mode == 'RESULT') {
			if(posAsk != null)
				posAsk += 1;
			else
				posAsk = parseInt(p4.preview.current.pos) + 1;
			posAsk = (posAsk > parseInt(p4.tot) || isNaN(posAsk)) ? 0 : posAsk;
			openPreview('RESULT', posAsk);
		}
		else
		{
			if(!$('#PREVIEWCURRENT li.selected').is(':last-child'))
				$('#PREVIEWCURRENT li.selected').next().children('img').trigger("click");
			else
				$('#PREVIEWCURRENT li:first-child').children('img').trigger("click");
		}

	}
}
function reloadPreview(){
	$('#PREVIEWCURRENT li.selected img').trigger("click");
}

function getPrevious(){
	if (p4.preview.mode == 'RESULT') {
		if(posAsk != null)
			posAsk -= 1;
		else
			posAsk = parseInt(p4.preview.current.pos) - 1;
		posAsk = (posAsk < 0) ? ((parseInt(p4.tot) - 1)) : posAsk;
		openPreview('RESULT', posAsk);
	}
	else
	{
		if(!$('#PREVIEWCURRENT li.selected').is(':first-child'))
			$('#PREVIEWCURRENT li.selected').prev().children('img').trigger("click");
		else
			$('#PREVIEWCURRENT li:last-child').children('img').trigger("click");
	}
}

function setOthers(others){

	$('#PREVIEWOTHERSINNER').empty();
	if (others != '') {
		$('#PREVIEWOTHERSINNER').append(others);

		$('#PREVIEWOTHERS table.otherRegToolTip').tooltip();
	}
}

function setTools(tools){
	$('#PREVIEWTOOL').empty().append(tools);
	if(!p4.slideShowCancel && p4.slideShow)
	{
		$('#start_slide').hide();
		$('#stop_slide').show();
	}else
	{
		$('#start_slide').show();
		$('#stop_slide').hide();
	}
}

function setCurrent(current){
	if (current != '') {
		var el = $('#PREVIEWCURRENT');
		el.removeClass('loading').empty().append(current);

		$('ul',el).width($('li',el).length * 80);
		$('img.prevRegToolTip',el).tooltip();
		$.each($('img.openPreview'), function(i, el){
			var jsopt = $(el).attr('jsargs').split('|');
			$(el).removeAttr('jsargs');
			$(el).removeClass('openPreview');
			$(el).bind('click', function(){
				viewCurrent($(this).parent());
				openPreview(jsopt[0], jsopt[1], jsopt[2]);
			});
		});
	}
}

function viewCurrent(el){
	if (el.length == 0)
	{
		return;
	}
	$('#PREVIEWCURRENT li.selected').removeClass('selected');
	el.addClass('selected');
	$('#PREVIEWCURRENTCONT').animate({'scrollLeft':($('#PREVIEWCURRENT li.selected').position().left + $('#PREVIEWCURRENT li.selected').width()/2 - ($('#PREVIEWCURRENTCONT').width() / 2 ))});
	return;
}

function setPreview(){
	if (!p4.preview.current)
		return;

	var zoomable = $('img.PREVIEW_PIC.zoomable');
	if(zoomable.length > 0 && zoomable.hasClass('zoomed'))
		return;

	var h = parseInt(p4.preview.current.height);
	var w = parseInt(p4.preview.current.width);
	if(p4.preview.current.type == 'video')
	{
		var h = parseInt(p4.preview.current.flashcontent.height);
		var w = parseInt(p4.preview.current.flashcontent.width);
	}
	var t=20;
	var de = 0;

	var margX = 0;
	var margY = 0;

	if(p4.preview.current.type == 'audio')
	{
		var margY = 100;
		de = 60;
	}


	if(p4.preview.current.type != 'flash')
	{
		var ratioP = w / h;
		var ratioD = parseInt(p4.preview.width) / parseInt(p4.preview.height);

		if (ratioD > ratioP) {
			//je regle la hauteur d'abord
			if ((parseInt(h) + margY) > parseInt(p4.preview.height)) {
				h = Math.round(parseInt(p4.preview.height) - margY);
				w = Math.round(h * ratioP);
			}
		}
		else {
			if ((parseInt(w) + margX) > parseInt(p4.preview.width)) {
				w = Math.round(parseInt(p4.preview.width) - margX);
				h = Math.round(w / ratioP);
			}
		}
	}
	else
	{

		h = Math.round(parseInt(p4.preview.height) - margY);
		w = Math.round(parseInt(p4.preview.width) - margX);
	}
	t = Math.round((parseInt(p4.preview.height) - h - de) / 2);
	var l = Math.round((parseInt(p4.preview.width) - w) / 2);
	$('#PREVIEWIMGCONT .PREVIEW_PIC').css({
		width: w,
		height: h,
		top: t,
		left: l
	}).attr('width',w).attr('height',h);
}

function classicMode(){
	$('#PREVIEWCURRENTCONT').animate({'scrollLeft' : ($('#PREVIEWCURRENT li.selected').position().left - 160)});
	p4.currentViewMode = 'classic';
}

function doudouMode(){
	$('#PREVIEWCURRENT li').removeClass('see-all');
	$('#PREVIEWCURRENT ul').width('auto');
	$('#PREVIEWCURRENTCONT').css({
		'overflow-x': 'hidden'
	});
	p4.currentViewMode = 'enhance';
	viewCurrent($('#PREVIEWCURRENT li.selected'));
}

function closePreview(){
	p4.preview.open = false;
  empty_preview();
	hideOverlay();

	$('#PREVIEWBOX').fadeTo(500, 0);
	$('#PREVIEWBOX').queue(function(){
		$(this).css({
			'display': 'none'
		});
		cancelPreview();
		$(this).dequeue();
	});
}


function fullScreen(){
	var url = '';
	var dHd = false;
	$('#PREVIEWHD').empty();
	$('#PREVIEWHD').show();

	if ((p4.preview.current.width / bodySize.x) < 0.6 && (p4.preview.current.height / bodySize.y) < 0.6) {
		//je charge la HD
		dHd = true;
		if (!p4.preview.current.hd)
			dHd = false;
	}
	if (dHd) {
		$('#PREVIEWHD').append(p4.preview.current.hd);
	}
	else {
		$('#PREVIEWHD').append(p4.preview.current.prev);
	}
	$('#PREVIEWHD .PREVIEW_PIC').removeClass('PREVIEW_PIC').addClass('PREVIEW_HD');

	var h = $('.PREVIEW_HD').height();
	var w = $('.PREVIEW_HD').width();
	var t = 0;
	var ratio = w / h;

	if (h > bodySize.y) {
		h = bodySize.y - 40;
		w = h * ratio;
	}
	if (w > bodySize.x) {
		w = bodySize.x - 40;
		h = w / ratio;
	}


	var t = (bodySize.y - h) / 2;
	$('.PREVIEW_HD').css({
		width: w,
		height: h,
		top: t,
		position: 'relative'
	});

}
