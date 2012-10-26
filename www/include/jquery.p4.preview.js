var prevAjax,prevAjaxrunning;
prevAjaxrunning = false;
p4.slideShow = false;

$(document).ready(function(){
	$('#PREVIEWIMGDESC').tabs();
});


function getNewVideoToken(lst, obj)
{
	$.ajax({
		type: "POST",
		url: "/prod/records/renew-url/",
		dataType: 'json',
		data: {
			lst: lst
		},
		success: function(data){
			if(!data[lst])
				return;
			obj.unload();
			obj.setClip({url:data[lst]});
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

	if (!p4.preview.open) {
		showOverlay();

		$('#PREVIEWIMGCONT').disableSelection();

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

	$('#PREVIEWIMGCONT').empty();

  var options_serial = p4.tot_options;
  var query = p4.tot_query;

	prevAjax = $.ajax({
		type: "POST",
		url: "/prod/records/",
		dataType: 'json',
		data: {
			env: env,
			pos: pos,
			cont: contId,
			roll: roll,
      options_serial:options_serial,
      query:query
		},
		beforeSend: function(){
			if (prevAjaxrunning)
				prevAjax.abort();
			if(env == 'RESULT')
				$('#current_result_n').empty().append(parseInt(pos)+1);
			prevAjaxrunning = true;
			$('#PREVIEWIMGDESC, #PREVIEWOTHERS').addClass('loading');
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
			posAsk = data.pos - 1;

			$('#PREVIEWIMGCONT').empty().append(data.html_preview);
      $('#PREVIEWIMGCONT .thumb_wrapper')
        .width('100%').height('100%').image_enhance({zoomable:true});

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
			p4.preview.current.width = parseInt($('#PREVIEWIMGCONT input[name=width]').val());
			p4.preview.current.height = parseInt($('#PREVIEWIMGCONT input[name=height]').val());
			p4.preview.current.tot = data.tot;
			p4.preview.current.pos = data.pos;

      if($('#PREVIEWBOX img.record.zoomable').length > 0)
			{
				$('#PREVIEWBOX img.record.zoomable').draggable();
			}

			setTitle(data.title);
			setPreview();

      if(env != 'RESULT')
      {
				setCurrent(data.current);
				viewCurrent($('#PREVIEWCURRENT li.selected'));
			}
			else
			{
				if(!justOpen)
				{
					$('#PREVIEWCURRENT li.selected').removeClass('selected');
					$('#PREVIEWCURRENTCONT li.current'+pos).addClass('selected');
				}
				if(justOpen || ($('#PREVIEWCURRENTCONT li.current'+pos).length === 0) ||  ($('#PREVIEWCURRENTCONT li:last')[0] == $('#PREVIEWCURRENTCONT li.selected')[0]) ||  ($('#PREVIEWCURRENTCONT li:first')[0] == $('#PREVIEWCURRENTCONT li.selected')[0]))
				{
					getAnswerTrain(pos, data.tools, query,options_serial);
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

	var el = $('#PREVIEWIMGCONT img.record');

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

	t2 = Math.round(t1 - (h2 - h1) / 2)+'px';
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

function getAnswerTrain(pos, tools, query,options_serial)
{
	$('#PREVIEWCURRENTCONT').fadeOut('fast');
	$.ajax({
		type: "POST",
		url: "/prod/query/answer-train/",
		dataType: 'json',
		data: {
			pos:pos,
            options_serial:options_serial,
            query:query
		},
		success: function(data){
			setCurrent(data.current);
			viewCurrent($('#PREVIEWCURRENT li.selected'));
			setTools(tools);
			return;
		}
	});
}


function getRegTrain(contId,pos,tools)
{
	$.ajax({
		type: "POST",
		url: "/prod/query/reg-train/",
		dataType: 'json',
		data: {
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
	$('#PREVIEWIMGCONT').empty();
	p4.preview.current = false;
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
	if (p4.preview.mode == 'REG' && parseInt(p4.preview.current.pos) === 0)
		$('#PREVIEWCURRENTCONT li img:first').trigger("click");
	else {
		if (p4.preview.mode == 'RESULT') {
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
	if (p4.preview.mode == 'RESULT')
  {
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
	if (others !== '') {
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
	if (current !== '') {
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
	if (el.length === 0)
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

	var zoomable = $('img.record.zoomable');
	if(zoomable.length > 0 && zoomable.hasClass('zoomed'))
		return;

	var h = parseInt(p4.preview.current.height);
	var w = parseInt(p4.preview.current.width);
//	if(p4.preview.current.type == 'video')
//	{
//		var h = parseInt(p4.preview.current.flashcontent.height);
//		var w = parseInt(p4.preview.current.flashcontent.width);
//	}
	var t=20;
	var de = 0;

	var margX = 0;
	var margY = 0;

	if($('#PREVIEWIMGCONT .record_audio').length > 0)
	{
		margY = 100;
		de = 60;
	}


//	if(p4.preview.current.type != 'flash')
//	{
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
//	}
//	else
//	{

//		h = Math.round(parseInt(p4.preview.height) - margY);
//		w = Math.round(parseInt(p4.preview.width) - margX);
//	}
	t = Math.round((parseInt(p4.preview.height) - h - de) / 2);
	var l = Math.round((parseInt(p4.preview.width) - w) / 2);
	$('#PREVIEWIMGCONT .record').css({
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
