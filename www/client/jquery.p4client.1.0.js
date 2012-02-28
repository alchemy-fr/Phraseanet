/*************
 * GLOBALES
 **************/

var p4 = {
	tot:0,
	preview :{open:false,current:false},
	currentViewMode:'classic',
	nbNoview:0
	};

var baskAjax,baskAjaxrunning;
baskAjaxrunning = false;
var answAjax,answAjaxrunning;
answAjaxrunning = false;

var wCompare = null;
var language = {};
var bodySize = {x:0,y:0};

/*************
 * INITIALISATION
 **************/


function acceptCgus(name,value)
{
	setPref(name,value);
}

function cancelCgus(id)
{

	$.ajax({
		type: "POST",
		url: "/prod/prodFeedBack.php",
		data: {
			sbas_id:id,
			action:'DENY_CGU'
		},
		success: function(data){
			if(data == '1')
			{
				alert(language.cgusRelog);
				self.location.replace(self.location.href);
			}
		}
	});

}

function activateCgus()
{

	$('.cgu-dialog:first').dialog({
			autoOpen : true,
			closeOnEscape:false,
			draggable:false,
			modal:true,
			resizable:false,
			width:800,
			height:600,
			open:function() {
			    $(this).parents(".ui-dialog:first").find(".ui-dialog-titlebar-close").remove();
			    var currentdialog = $(this);
			    $('.cgus-accept',$(this)).bind('click',function(){
			    	acceptCgus($('.cgus-accept',currentdialog).attr('id'),$('.cgus-accept',currentdialog).attr('date'));
			    	$('.cgu-dialog').dialog('close');
			    });
			    $('.cgus-cancel',$(this)).bind('click',function(){
			    	if(confirm(language.warningDenyCgus))
			    	{
				    	cancelCgus($('.cgus-cancel',currentdialog).attr('id').split('_').pop());
			    	}
			    });
			  },
			  close:function(){
				  activateCgus();
			  }
	});
}

$(document).ready(function(){
	activateCgus();
});

$(document).ready(function(){
	$.ajaxSetup({

		error: function(){
			showModal('error',{title:'Server error'});
		},
		timeout: function(){
			showModal('timeout',{title:'Server not responding'});
		}
		});

	getLanguage();
	$('.datepicker').datepicker({
		firstDay: 1,
		changeYear: true, changeMonth:true,
		showOn: 'button', buttonImage:'/skins/icons/cal.png', buttonImageOnly: true
	});

	checkFilters();
	window.setTimeout("checkBaskets();", 5000);

	$('.actives').hover(function(){
		$(this).addClass("hover");
	},function(){
		$(this).removeClass("hover");
	});


//	if (!$.browser.msie || ($.browser.msie && $.browser.version != '6.0')) {
//		$('#bandeau .publilist').hover(function(){
//			$(this).addClass("hover");
//			$(this).children('.hoverlist').show();
//		},function(){
//			$(this).removeClass("hover");
//			$(this).children('.hoverlist').hide();
//		})
//	}else
//	{
//		$('#bandeau .publilist').hover(function(){
//			$(this).addClass("hover");
//		},function(){
//			$(this).removeClass("hover");
//		})
//
//	}

	sessionactive();
	resize();
	$(window).resize(function(){
		resize();
		resizeSearch();
	});
	initAnswerForm();
	initBasketForm();
	$('#PREVIEWHD').bind('click',function(){
		$(this).hide();
		$(this).empty();
	});

	$('#PREVIEWHD').trigger('click');
	getBaskets();
	afterSearch();
	$(this).bind('keydown',function(event)
	{
		if(p4.preview.open)
		{
			switch(event.keyCode)
			{
				case 39:
					getNext();
					break;
				case 37:
					getPrevious();
					break;
				case 27:
					if ($('#MODALDL').is(':visible')) {
						hideDwnl();
					}
					else {
						closePreview();
					}
					break;
				case 32:
					if(p4.slideShow)
						stopSlide();
					else
						startSlide();
					break;
			}
		}
		else
		{
			switch(event.keyCode)
			{
				case 39:
					$('#NEXT_PAGE').trigger('click');
					break;
				case 27:
						hideDwnl();
					break;
				case 37:
					$('#PREV_PAGE').trigger('click');
					break;
				case 38:
					$('#answers').scrollTop($('#answers').scrollTop()-50);
					break;
				case 40:
					$('#answers').scrollTop($('#answers').scrollTop()+50);
					break;
			}
		}
	});

	$('.boxPubli .diapo').css('width','').addClass('w160px').css('margin','0pt 0px 8px 8px');

	}
);

function resizePreview(){
	$('#PREVIEWCURRENTCONT').width($('#PREVIEWCURRENT').width() - 80 - ($('#PREVMAINREG').length>0?$('#PREVMAINREG').width():0) - 90);

	var h = $('#PREVIEWBOX').height();
	h = h - $('#PREVIEWTITLE').height();
	$.each($('div.preview_col'), function(i, n){
		$(n).height(h);
	});
	$('#PREVIEWIMGCONT').height(h - $('#PREVIEWCURRENT').height());

	$('#PREVIEWIMGDESC').height(h-$('#PREVIEWOTHERS').height());
	$('#PREVIEWIMGDESC .descBoxes').height($('#PREVIEWIMGDESC').height() - 30);

	p4.preview.height = $('#PREVIEWIMGCONT').height();
	p4.preview.width = $('#PREVIEWIMGCONT').width();
	setPreview();
}

function controlPubliSize()
{
	$('#publications ul').height('auto');
	if(50+$('#publications ul').height()>bodySize.y)
		$('#publications ul').height(bodySize.y-50);
}

function pquit(){
	if (parent.opener)
		self.close();
	else
	{
		document.forms['logout'].submit();
	}

}

function resize(){

	var h = bodySize.y = $(window).height() - $('#mainMenu').outerHeight();
	var w = bodySize.x = $(window).width();


	controlPubliSize();
	var rightw = w - 265;
	rightw = ((rightw) > 0) ? rightw : 0;

	$('#container').height($(window).height());
	$('#container').width($(window).width());
	$('#right').width(rightw);

	$('#answers').height(h - $('#nb_answersEXT').outerHeight() - $('#navigation').outerHeight() - 20);
	$('#answers').width(rightw);

	resizeSearch();

	if (p4.preview.open) {

		resizePreview();
	}
	if ($.browser.msie && $.browser.version == '6.0') {
		$('#PREVIEWBOX').height(h * 0.94);
		$('#OVERLAY,#OVERLAY2').width(w);
		$('#OVERLAY,#OVERLAY2').height(h);
		$('#left').height(h);
		$('#right').height(h);
	}
	bodyW = rightw;

	if($('#MODALDL').is(':visible'))
	{
		$('#MODALDL').css({
			top:((h-$('#MODALDL').height())/2),
			left:((w-$('#MODALDL').width())/2)
		});
	}
	reModCol();
}

function getHome(cas){
	switch (cas) {
		case 'QUERY':
			newSearch();
			break;
		case 'PUBLI':
		case 'HELP':
			$.ajax({
				type: "POST",
				url: "/client/clientFeedBack.php",
				dataType: 'html',
				data: {
					action: "HOME",
					type: cas
				},
				beforeSend: function(){
//					if (answAjaxrunning)
//						answAjax.abort();
					clearAnswers();
					answAjaxrunning = true;
					$('#answers').addClass('loading');

				},
				error: function(){
					answAjaxrunning = false;
					$('#answers').removeClass('loading');
				},
				timeout: function(){
					answAjaxrunning = false;
					$('#answers').removeClass('loading');
				},
				success: function(data){
					$('#answers').append(data);
					afterSearch();

					if(cas == 'PUBLI')
					{
						$('.boxPubli .diapo').css('width','').addClass('w160px').css('margin','0pt 0px 8px 8px');
					}

					return;
				}

			});
			break;


		default:
			break;
	}
}


function changeModCol(){
	reModCol();
	doSearch();
}

function getLanguage(){
	$.ajax({
		type: "POST",
		url: "./clientFeedBack.php",
		dataType: 'json',
		data: {
			action: "LANGUAGE"
		},
		success: function(data){
			language = data;
			return;
		}
	});
}
function initBasketForm(){
	var options = {
		target: '#baskets',
		beforeSend: function(){
			if (baskAjaxrunning)
				baskAjax.abort();
			baskAjaxrunning = true;
			$('.baskIndicator').addClass('baskLoading');
		},
		error: function(){
			baskAjaxrunning = false;
			$('#baskets').removeClass('loading');
			$('.baskIndicator').removeClass('baskLoading');
		},
		timeout: function(){
			baskAjaxrunning = false;
			$('#baskets').removeClass('loading');
			$('.baskIndicator').removeClass('baskLoading');
		},
		success: function(){
			baskAjaxrunning = false;
			if(p4.preview.open && $.browser.msie && $.browser.version == '6.0')
			{
				$('select').css({
					visibility: 'hidden'
				});
			}
			setBaskStatus();
			$('#baskets').removeClass('loading');
			$('.baskIndicator').removeClass('baskLoading');
			$('#blocBask img.baskTips').tooltip();

			$("#flechenochu").bind('click', function(){
				baskDisplay = false;
				saveBaskStatus(false);
				$("#blocBask").slideToggle("slow");
				$("#blocNoBask").slideToggle("slow").queue(function(){
					$('#baskets').height($('#blocNoBask').height() + 6);
					resizeSearch();
					$(this).dequeue();
				});
			});
			$("#flechechu").bind('click', function(){
				baskDisplay = true;
				saveBaskStatus(true);
				$("#blocNoBask").slideToggle("slow");
				$("#blocBask").slideToggle("slow").queue(function(){
					$('#baskets').height($('#blocBask').height() + 6);
					resizeSearch();
					$(this).dequeue();
				});
			});
			$('#formChuBaskId')[0].value = $('#chutier_name')[0].options[$('#chutier_name')[0].selectedIndex].value;
			$('#formChubas')[0].value = $('#formChuact')[0].value = $('#formChup0')[0].value = '';
			return;
		}
	};
	baskAjax = $('#formChu').ajaxForm(options);
}
function setBaskStatus(){
	if (baskDisplay) {
		$("#blocNoBask").hide();
		$("#blocBask").show();
		$('#baskets').height($('#blocBask').height() + 6);
	}
	else {
		$("#blocNoBask").show();
		$('#baskets').height($('#blocNoBask').height() + 6);
		$("#blocBask").hide();
	}
	resizeSearch();
}

function saveBaskStatus(value) {
	$.post("clientFeedBack.php", {
		action: "BASK_STATUS",
		mode: (value?'1':'0')
	}, function(data){
		return;
	});
}


function checkBaskets(){
	$.post("clientFeedBack.php", {
		action: 'BASKUPDATE'
	}, function(data){
		if(parseInt(data)>p4.nbNoview)
			getBaskets();
		window.setTimeout("checkBaskets();", 52000);
		return;
	});
}

function initAnswerForm(){
	var options = {
		target: '#answers',
		beforeSend: function(formData){
			clearAnswers();
			if (answAjaxrunning)
				return;
			answAjaxrunning = true;
			$('#tooltip').css({
				'display': 'none'
			});
			$('#answers').addClass('loading');
		},
		error: function(){
			answAjaxrunning = false;
			$('#answers').removeClass('loading');
		},
		timeout: function(){
			answAjaxrunning = false;
			$('#answers').removeClass('loading');
		},
		success: function(){
			answAjaxrunning = false;
			afterSearch();
			return;
		}
	};
	$('#searchForm').ajaxForm(options);
}
/*************
 * SEARCH
 **************/

function afterSearch()
{

	$('#answers').removeClass('loading');
	$("#answers").scrollTop(0);
	$('div.infoTips, div.previewTips,img.captionTips',$('#answers')).tooltip();
	$('#nb_answers a.infoTips').tooltip();
}

function chkSbas(val,el){
	var bool = false;
	if($(el)[0].checked)
		bool = true;
	$('.basItem' + val).each(function(){
		$(this)[0].checked = bool;
	});
}

function chgOngSearch(tab){
	tTabs = new Array("ongSearch", "ongAdvSearch", "ongTopic");
	if($('#'+tab).length == 0)
		return;
	for (i = 0; i <= tTabs.length; i++) {
		if ((o = $('#' + tTabs[i])))
			var rmC = (tTabs[i] == tab) ? "inactif" : "actif";
		var addC = (tTabs[i] == tab) ? "actif" : "inactif";
		o.addClass(addC).removeClass(rmC);


		if ((o = document.getElementById("id" + tTabs[i]))) {
			o.style.display = (tTabs[i] == tab) ? "" : "none";
		}
	}
	$('#idongAdvSearch :text').each(function(){
		this.value = "";
	});
	if (tab == "ongAdvSearch") {
		document.getElementById("idongSearch").style.display = "";
	}

	resizeSearch();
}

function doSpecialSearch(qry, allbase){
//	if($('#ongSearch').length>0)
//		chgOngSearch('ongSearch');
//	else
//		if($('#ongAdvSearch').length>0)
//			chgOngSearch('ongAdvSearch');
	if (allbase) {
		$('input.basItem').each(function(){
			this.checked = true;
		});
		var first = true;
		$('#basSelector option').each(function(){
			this.selected = first;
			first = false;
		});
	}
	$("form[name='search'] input[name='qry']")[0].value = decodeURIComponent(qry).replace(/\+/g, " ");
	doSearch();
}

function clearAnswers(){
	$('#formAnswerPage')[0].value = '';
	$("#nb_answers").empty();
	$("#navigation").empty();
	$("#answers").empty();
}

function newSearch()
{
	$('#searchForm').submit();
}

function doSearch()
{
	$('#searchForm').submit();
}

function chgOng(num){
	for (i = 1; i <= 5; i++) {
		if ((o = document.getElementById("idOnglet" + i)))
			o.className = (i == num) ? "actif" : "inactif";
		if ((o = document.getElementById("onglet" + i)))
			o.style.display = (i == num) ? "block" : "none";
	}
	return;
}

function checkBases(etat){
	$('.basItem, .basChecker').each(function(){
		this.checked = etat;
	});
}

function resizeSearch(){

	var searchh = (bodySize.y-$('#baskets').height());
	searchh = ((searchh)>0)?searchh:0;
	var menu = $('#bigTabsBckg').height();
	$('#search').height(searchh);
	$('#idongTopic').height($("#search").height()-8-menu);
	$('#searchMiddle').height($("#search").height()-8-menu-$('#mainSearch').height());
}
/*************
 * Topics
 **************/

function doThesSearch(type,sbid,term,field)
{

	if(type=='T')
		v = '*:"' + term.replace("(", "[").replace(")", "]") + '"';
	else
		v = '"' + term + '" IN ' + field;
	doSpecialSearch(v, true);
}

function chgProp(path, v, k){
	var q2;
	if (!k)
		k = "*";
	if (k != null)
		v = v + " [" + k + "]";
	document.getElementById("thprop_a_" + path).innerHTML = '"' + v + '"';

	q = document.getElementById("thprop_q").innerText;
	if (!q)
		if (document.getElementById("thprop_q") && document.getElementById("thprop_q").textContent)
			q = document.getElementById("thprop_q").textContent;

	q2 = "";

	for (i = 0; i < q.length; i++)
		q2 += q.charCodeAt(i) == 160 ? " " : q.charAt(i); // correction pour safari !
	doSpecialSearch(q2, true);
	return (false);
}

function clktri(id){
	var o = $('#TOPIC_UL' + id);
	if ($('#TOPIC_UL' + id).hasClass('closed'))
		$('#TOPIC_TRI' + id + ' ,#TOPIC_UL' + id).removeClass('closed').addClass('opened');
	else
		$('#TOPIC_TRI' + id + ' ,#TOPIC_UL' + id).removeClass('opened').addClass('closed');
}



/*************
 * CHUTIER
 **************/

function evt_add_in_chutier(base_id, record_id){
	$('#formChubas')[0].value = base_id;
	$('#formChuact')[0].value = "ADDIMG";
	$('#formChup0')[0].value = record_id;
	$('#formChu').submit();
}

function chg_chu()
{
	var id = document.getElementById("chutier_name").value;
	document.forms["formChu"].courChuId.value = id;
	$("#formChu").submit();
}

function getBaskets()
{
	$('#formChu').submit();
}


function newBasket(){
	var buttons = {};

	buttons["OK"] =  function(e){saveNewBask();$(this).dialog('close');};
	buttons[language.annuler] =  function(e){$(this).dialog('close');};

	$('#DIALOG').empty().append("<input type='text' value='' id='newBaskName' />").attr('title',language.createWinInvite).dialog({
		autoOpen:false,
		closeOnEscape :true,
		resizable:false,
		draggable:false,
		modal:true
	}).dialog('open').dialog('option','buttons',buttons);

}

function saveNewBask(){
	var tmp = $('#newBaskName')[0].value;
	if (tmp == null)
		return;
	mytest = false;
	for (k = 0; (k < tmp.length && !mytest); k++) {
		if (tmp.charAt(k) != " ")
			mytest = true;
	}
	if (!mytest) {
		alert(language.chuNameEmpty);
		return;
	}
	document.forms["formChu"].act.value = "NEWCHU";
	document.forms["formChu"].p0.value = tmp;
	$("#formChu").submit();
}

function evt_chutier(arg_commande){
	switch (arg_commande) {
		case "DELSSEL":
			if (confirm(language.confirmDelBasket)) {
				if (document.forms["formChu"]) {
					document.forms["formChu"].act.value = "DELCHU";
					document.forms["formChu"].p0.value = document.forms["formChu"].courChuId.value;
					$("#formChu").submit();
				}
			}
			break;
	}
}

function reload_chu(id){
	document.forms["formChu"].courChuId.value = id;
	$("#formChu").submit();
}

function evt_del_in_chutier(selid){
	document.forms["formChu"].act.value = "DELIMG";
	document.forms["formChu"].p0.value = selid;
	$("#formChu").submit();
}

function openCompare(sselid){
	$('#ssel2val')[0].value = sselid;
	$('#validatorEject').submit();
}

function setVisible(el){
	el.style.visibility = 'visible';
}

function beforeAnswer(){
	if ($('#basSelector')) {
		var serialBas = $('#basSelector')[0].options[$('#basSelector')[0].selectedIndex].value;
		serialBas = serialBas.split(';');
		$.each($('.basItem'), function(i, el){
			el.checked = false;
		});
		$.each(serialBas, function(i, n){
			$('#basChk' + n)[0].checked = true;
		});
	}
	return true;
}

function gotopage(pag){
	if (document.forms["search"]) {
		document.forms["search"].nba.value = p4.tot;
		document.forms["search"].pag.value = pag;
		$("#answers").innerHTML = "";
		$('#searchForm').submit();
	}
	return (false);
}


function evt_print(basrec){
	var url = "/include/printpage.php?callclient=1";


	if(typeof(basrec) == 'undefined')
		url += "&SSTTID="+$('#chutier_name')[0].options[$('#chutier_name')[0].selectedIndex].value;
	else
		url +=	"&lst=" + basrec;

	var top;
	var left;

	$('#MODALDL').attr('src',url);


	var t = (bodySize.y - 300) / 2;
	var l = (bodySize.x - 490) / 2;

	$('#MODALDL').css({
		'display': 'block',
		'opacity': 0,
		'width': '490px',
		'position': 'absolute',
		'top': t,
		'left': l,
		'height': '300px'
	}).fadeTo(500, 1);

	showOverlay(2);
}


function evt_dwnl(lst)
{
	var dialog_box = $('#dialog_dwnl');

	dialog_box = $('#dialog_dwnl');

	dialog_box.empty().addClass('loading').dialog({
		width:800,
		height:600,
		modal:true,
		closeOnEscape : true,
		resizable : false,
    zIndex:10000,
		overlay: {
			backgroundColor: '#000',
			opacity: 0.7
		},
		beforeclose:function(){
			tinyMCE.execCommand('mceRemoveControl',true,'sendmail_message');
			tinyMCE.execCommand('mceRemoveControl',true,'order_usage');
		}
	}).dialog('open');

	if(typeof(lst) == 'undefined')
		var datas = "&SSTTID="+$('#chutier_name')[0].options[$('#chutier_name')[0].selectedIndex].value;
	else
		var datas =	"&lst=" + lst;

	$.post("/include/multiexports.php", datas, function(data) {

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

function profil(value)
{
	var top;
	var left;

	if(value==undefined)
		value = "0";

	var url = "" +
	"/include/profile.php" +
	"?callclient=1&onglet="+value;

	$('#MODALDL').attr('src',url);


	var t = (bodySize.y - 550) / 2;
	var l = (bodySize.x - 490) / 2;

	$('#MODALDL').css({
		'display': 'block',
		'opacity': 0,
		'width': '490px',
		'position': 'absolute',
		'top': t,
		'left': l,
		'height': '550px'
	}).fadeTo(500, 1);

	showOverlay(2);
}

function setCss(color)
{
	$('#skinCss').attr('href','/include/minify/f=skins/common/main.css,skins/client/'+color+'/clientcolor.css,'+
			'skins/client/'+color+'/ui.core.css,'+
			'skins/client/'+color+'/ui.datepicker.css,'+
			'skins/client/'+color+'/ui.theme.css');
	$.post("clientFeedBack.php", {
		action: "CSS",
		color: color,
		t: Math.random()
	}, function(data){
		return;
	});
	if ($.browser.msie && $.browser.version == '6.0')
		$('select').hide().show();
}

function lessPubli(sselid)
{
	$('#PUBLICONTMORE'+sselid+', #PUBLICONTLESS'+sselid).toggle();
	$('#PUBLICONT'+sselid).css({height: '135px'});
	$('#PUBLIMORE'+sselid+', #PUBLILESS'+sselid).toggle();
}

function morePubli(sselid)
{
	$('#PUBLICONTMORE'+sselid+', #PUBLICONTLESS'+sselid).toggle();
	$('#PUBLICONT'+sselid).css({height: 'auto'});
	$('#PUBLIMORE'+sselid+', #PUBLILESS'+sselid).toggle();
}

function toggleFilter(filter,ele)
{
	var el = $('#'+filter);
	if(el.is(':hidden'))
		$(ele).parent().addClass('open');
	else
		$(ele).parent().removeClass('open');
	$('#'+filter).slideToggle('fast');
}

function checkFilters()
{
	var danger = false;
	var d = {};
	$('.filter_danger').each(function(){
		d[$(this).attr('id')] = false;

	});

	$('.basContTitle .base_filter :text').each(function(){
		if($(this)[0].value != "")
		{
			danger = true;

			d['filter_danger'+parseInt($(this).attr('db'))] = true;
		}
	});
	$('.basContTitle .base_filter :checkbox').each(function(){
		if($(this)[0].checked)
		{
			danger = true;

			d['filter_danger'+parseInt($(this).attr('db'))] = true;
		}
	});
	$('.basContTitle .base_filter select').each(function(){
		if($(this)[0].selectedIndex != 0)
		{
			danger = true;

			d['filter_danger'+parseInt($(this).attr('db'))] = true;
		}
	});

	$.each(d,function(i,bool){
		if(bool)
		$('#'+i).show();
		else
		$('#'+i).hide();
	});
	if(danger)
		$('#filter_danger').show();
	else
		$('#filter_danger').hide();
}

function removeFilters(bas)
{
	if (typeof(bas) == 'undefined') {
		$('.basContTitle .base_filter :checkbox').each(function(){
			$(this)[0].checked = false;
		});
		$('.basContTitle .base_filter :text').each(function(){
			$(this)[0].value = "";
		});
		$('.basContTitle .base_filter select').each(function(){
			$(this)[0].selectedIndex = 0;
		});
	}
	else {
		$('#Filters' + bas + ' :checkbox').each(function(){
			$(this)[0].checked = false;
		});
		$('#Filters' + bas + ' :text').each(function(){
			$(this)[0].value = "";
		});
		$('#Filters' + bas + ' select').each(function(){
			$(this)[0].selectedIndex = 0;
		});
	}
	checkFilters();
}

function execLastAct(lastAct)
{
	if(lastAct.act)
	{
		switch (lastAct.act)
		{
			case 'dwnl':
				if(lastAct.SSTTID)
				{
					if (baskAjaxrunning) {
						setTimeout("execLastAct(lastAct);", 500);
					}
					else {
						if($('#chutier_name')[0].options[$('#chutier_name')[0].selectedIndex].value != lastAct.SSTTID)
						{
							$('#chutier_name option').each(function(i, n){
								if (lastAct.SSTTID == this.value) {
										$('#chutier_name')[0].selectedIndex = i;
										$('#chutier_name').trigger('change');
										setTimeout("execLastAct(lastAct);", 500);
								}
							});
						}else
							evt_dwnl();
					}
				}
				else
					if(lastAct.lst)
					{
						evt_dwnl(lastAct.lst);
					}
				break;
		}
	}
	return;
}

