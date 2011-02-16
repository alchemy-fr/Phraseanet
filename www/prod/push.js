

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



function addNewUser()
{
	var baseinsc = new Array();
	$('.baseinsc:checked').each(function(){
		baseinsc[baseinsc.length] = $(this).val();
	});
	var basePreview = new Array();
	$('.basepreview:checked').each(function(){
		basePreview[basePreview.length] = $(this).val();
	});
	var baseWM = new Array();
	$('.basewm:checked').each(function(){
		baseWM[baseWM.length] = $(this).val();
	});
	
	$.ajax({
		type: "POST",
		url: "./push.feedback.php",
		data: {
		ACTION: "ADD_USR",
		IDENT:$('#add_ident').val(),
		MAIL:$('#NEW_MAIL').val(),
		NOM:$('#add_nom').val(),
		PREN:$('#add_prenom').val(),	
		SOCIE:$('#add_societe').val(),
		FUNC:$('#add_fonction').val(),
		ACTI:$('#add_activite').val(),
		COUNTRY:$('#add_pays').val(),
		CIV:$('input[name=CIV]:checked').val(),
		ID:$('#add_id').val(),
		DATE_END:$('#date_limit').val(),
		baseInsc:JSON.stringify(baseinsc),
		basePreview:JSON.stringify(basePreview),
		baseWm:JSON.stringify(baseWM)
		},
		success: function(data){
				if(parseInt(data)>0)
					adduserreset();
				cancelAddUser();
		}
	});
}

function editUsrs(n)
{
	if (usrEditList[n]) {
		$('ID_USR').value = usrEditList[n].usr_id;
		$('add_ident').value = usrEditList[n].usr_login;
		$('add_mail').value = usrEditList[n].usr_mail;
		$('add_fonction').value = usrEditList[n].fonction;
		$('add_activite').value = usrEditList[n].activite;
		$('add_societe').value = usrEditList[n].societe;
		$('add_prenom').value = usrEditList[n].usr_prenom;
		$('add_nom').value = usrEditList[n].usr_nom;
		$$('#add_pays option').each(function(el){
			if (el.value == usrEditList[n].pays) 
				el.selected = true;
		});
		$('CIV_' + usrEditList[n].usr_sexe).checked = true;
		
		$('add_ident').setAttribute('readonly','true');
		$('add_mail').setAttribute('readonly','true');
		$('add_nom').setAttribute('readonly','true');
		$('add_prenom').setAttribute('readonly','true');
		$('add_societe').setAttribute('readonly','true');
		$('add_activite').setAttribute('readonly','true');
		$('add_fonction').setAttribute('readonly','true');
		$('CIV_0').readonly = $('CIV_1').readonly = $('CIV_2').setAttribute('readonly','true');
		$('add_pays').setAttribute('readonly','true');
		$$('.baseinsc').each(function(el){
			if (usrEditList[n].base[el.value] && usrEditList[n].base[el.value] == '1') {
				el.checked = true;
				el.disabled = true;
			}else
			{
				el.disabled = false;
				el.checked = false;
			}
		});
		$$('.basepreview').each(function(el){
			if (usrEditList[n].canpreview[el.value] && usrEditList[n].canpreview[el.value] == '1') {
				el.checked = true;
				el.disabled = true;
			}else
			{
				el.disabled = false;
				el.checked = false;
			}
		});
		$$('.basewm').each(function(el){
			if (usrEditList[n].watermark[el.value] && usrEditList[n].watermark[el.value] == '1') {
				el.checked = true;
				el.disabled = true;
			}else
			{
				el.disabled = false;
				el.checked = false;
			}
		});
		
	}
}

function adduserreset()
{
	$('#MULTI_USER_SELECT').css('visibility','hidden');
	$('#ID_USR, #NEW_MAIL').val('');
	$('#add_ident, #add_nom, #add_mail, #add_prenom, #add_societe, #add_activite, #add_fonction, #add_pays').removeAttr('readonly');
	document.forms['add_usr_form'].reset();
	$('.baseinsc, .basepreview, .basewm').each(function(){
		$(this).attr('checked','checked').attr('disabled','disabled');
	});
	specialsearch(true,1);return(false);
}

function cancelAddUser()
{
	$('#ADD_USR').fadeOut();
	
}

function saveiList()
{
	var name = $('#INTELL_LIST').val();
	if(name == '')
	{
		alert('vous devez donner un nom a votre liste');
		return;
	}
	$('#INTELL_LIST').val('');


	$.ajax({
		type: "POST",
		url: "./push.feedback.php",
		data: {
		ACTION: "SAVEILIST",
		token: $('#token').val(),
		name: name,
		filters:currentFilters
		},
		success: function(data){
			if(data == '-1')
			{
				//display error
				return;
			}
			
			$('#searchilist').empty().append(data);
			$('#ilistremover').show();
		}
	});
	
	getCurrentFilters();
		
}

function deleteIlist()
{
	if(confirm(language.removeIlist))
	{

		$.ajax({
			type: "POST",
			url: "./push.feedback.php",
			data: {
			ACTION: "DELETEILIST",
			name: $('#searchilist').val()
			},
			success: function(data){
				if(data == '-1')
				{
					//display error
					return;
				}
				
				$('#searchilist').empty().append(data);
				iListChange();
				specialsearch(true,1);return(false);
			}
		});
	}
}




function deleteList()
{
	var lists = $.grep($('#searchlist').val(), function(n,i){
		return (n !== '' && parseInt(n) !== '');
	});
	
	if(lists.length == 0)
		return;
	
	if(confirm(language.removeList))
	{
		$.ajax({
			type: "POST",
			url: "./push.feedback.php",
			data: {
			ACTION: "DELETELIST",
			lists: JSON.stringify(lists)
			},
			success: function(data){
				if(data == '-1')
				{
					//display error
					return;
				}
				$('#searchlist').empty().append(data);
//				
//				$('#searchilist').empty().append(data);
//				iListChange();
//				specialsearch(true,1);return(false);
			}
		});
	}
}







/*********************************************************


**********************************************************/


//document.onselectstart=new Function ("return false")
var step = 1;
var totalsel = 0;
var language;
var lists;
var currentFilters;
var currentView = 'all';
var perPage = 20;
var page = 1;
var last_added = false;

var searchSort = currentSort = ['LA'];

function disableSelection(target){
	if (typeof target.onselectstart!="undefined") //IE route
		target.onselectstart=function(){return false;};
	else if (typeof target.style.MozUserSelect!="undefined") //Firefox route
		target.style.MozUserSelect="none";
	else //All other route (ie: Opera)
		target.onmousedown=function(){return false;};
	target.style.cursor = "default";
	}

$(document).ready(function(){
	
	getLanguage();
	$('.appLauncher').hover(
			function(){$(this).addClass('hover');},
			function(){$(this).removeClass('hover');}
	);
//	$(document).bind('mousemove',function(){return false});
	disableSelection(document.getElementById('search_list_wrapper'));
	activeStep(step);
	specialsearch(true);
	$('#date_limit').datepicker();
	$('#search_form input').bind('keyup',function(){
		specialsearch(true);
		
	});
	$('#search_form select').bind('change',function(){
		specialsearch(true);
	});
	$('#searchilist').bind('change',function(){
		iListChange();
		
	});
	$('#listDeleter').bind('click',function(){
		deleteList();
		
	});
});

function iListChange()
{
	$('#filters tr:not(:first)').remove();
	document.forms["search_form"].reset();
	
	$.ajax({
		type: "POST",
		url: "./push.feedback.php",
		dataType: 'json',
		data: {
		ACTION: "LOADILIST",
		name:$('#searchilist').val()
		},
		success: function(data){
			$('#filters tr:not(:first)').remove();
			$.each(data.strings,function(i,n){
				var tr = $('#filters tr:eq('+i+')');
				if(tr.length == 0)
				{
					$('#filters').append($('#filters tr:eq(0)').clone());
					tr = $('#filters tr:eq('+i+')');
				}
				$('select.operator',tr).val(n['operator']);
				$('select.field',tr).val(n['field']);
				$('select.fieldlike',tr).val(n['fieldlike']);
				$('input.fieldsearch',tr).val(n['fieldsearch']);
			});
			if(data.activite !== null && data.activite.length > 0)
			{
				var a = $('a.filtermultiactivite:not(.filterActive)');
				if(a.length>0)
					addFilterMulti('activite',a);
				$('#searchactivite').val(data.activite);
			}
			else
			{
				var a = $('a.filtermultiactivite.filterActive');
				if(a.length>0)
					addFilterMulti('activite',a);
				$('#searchactivite').val([]);
			}
			if(data.countries !== null && data.countries.length > 0)
			{
				var a = $('a.filtermulticountry:not(.filterActive)');
				if(a.length>0)
					addFilterMulti('country',a);
				$('#searchcountry').val(data.countries);
			}
			else
			{
				var a = $('a.filtermulticountry.filterActive');
				if(a.length>0)
					addFilterMulti('country',a);
				$('#searchcountry').val([]);
			}
			if(data.fonction !== null && data.fonction.length > 0)
			{
				var a = $('a.filtermultifonction:not(.filterActive)');
				if(a.length>0)
					addFilterMulti('fonction',a);
				$('#searchfunction').val(data.fonction);
			}
			else
			{
				var a = $('a.filtermultifonction.filterActive');
				if(a.length>0)
					addFilterMulti('fonction',a);
				$('#searchfunction').val([]);
			}
			if(data.lists !== null && data.lists.length > 0)
			{
				var a = $('a.filtermultilist:not(.filterActive)');
				if(a.length>0)
					addFilterMulti('lists',a);
				$('#searchlist').val(data.lists);
			}
			else
			{
				var a = $('a.filtermultilist.filterActive');
				if(a.length>0)
					addFilterMulti('lists',a);
				$('#searchlist').val([]);
			}
			if(data.societe !== null && data.societe.length > 0)
			{
				var a = $('a.filtermultisociete:not(.filterActive)');
				if(a.length>0)
					addFilterMulti('societe',a);
				$('#searchsociete').val(data.societe);
			}
			else
			{
				var a = $('a.filtermultisociete.filterActive');
				if(a.length>0)
					addFilterMulti('societe',a);
				$('#searchtemplate').val([]);
			}
			if(data.template !== null && data.template.length > 0)
			{
				var a = $('a.filtermultitemplate:not(.filterActive)');
				if(a.length>0)
					addFilterMulti('template',a);
				$('#searchtemplate').val(data.template);
			}
			else
			{
				var a = $('a.filtermultitemplate.filterActive');
				if(a.length>0)
					addFilterMulti('template',a);
				$('#searchtemplate').val([]);
			}
			specialsearch(true,1);return(false);
		}
	});
	if($('#searchilist').val() != '')
	{
		$('#ilistremover').show();
	}
	else
		$('#ilistremover').hide();
}

function checkMail(mail)
{
	mail = $.trim(mail);
	
	var filter=/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9_\.\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	if(filter.test(mail))
		return true;
	else
		return false;
}

function adduserDisp(usr_id)
{
	var mail = $('#NEW_MAIL').val();
	if(!checkMail(mail))
	{
		alert(language.wrongmail);
		return false;
	}
	
	if(typeof(usr_id) == 'undefined')
	usr_id = '';
	else
		usr_id = $(usr_id).val();

	$.ajax({
		type: "POST",
		url: "./push.feedback.php",
		data: {
		ACTION: "CHECKMAIL",
		mail: mail,
		usr_id : usr_id
		},
		success: function(data){
 			$('#ADD_USR').empty().append(data).slideDown();
		}
	});
	
}
function toggleView(el)
{
	currentView = $(el).val();
	specialsearch(false,page);
}

function activeSort()
{
	$('.REFL,.REFN,.REFM,.REFS,.REFJ,.REFA,.REFC,.REFT').hover(
			function(){$(this).addClass('hover');},
			function(){$(this).removeClass('hover');}
	);
	$('.REFL,.REFN,.REFM,.REFS,.REFJ,.REFA,.REFC,.REFT').bind('click',function(event){
		if(!is_ctrl_key(event) && !is_shift_key(event))
			currentSort = new Object();
		if($(this).hasClass('active'))
		{
			if($(this).hasClass('SortUp'))
			{
				currentSort[$(this).attr('id').substr(-1)] = 'D';
			}
			else
			{
				currentSort[$(this).attr('id').substr(-1)] = 'A';
			}
		}
		else
		{
			currentSort[$(this).attr('id').substr(-1)] = 'A';
		}
		searchSort = new Array();
		$.each(currentSort,function(i,n){
			searchSort[searchSort.length] = i+n;
		});

		specialsearch(false,page);
	});
}

function activeStep(n)
{
	step = n;
	$('.STEP').hide();
	$('#STEP_'+n).show();
	$('#stepN').empty().append(n);
}
function previousStep(){
	var n = step;
	n = (step < 1 || step > 3) ? 1 : (n-1);
	activeStep(n);
}
function nextStep(){
	var n = step;
	n = (step < 1 || step > 3) ? 3 : (n+1);
	activeStep(n);
}

function getLanguage()
{
	$.ajax({
		type: "POST",
		url: "./push.feedback.php",
		dataType: 'json',
		data: {
		ACTION: "GETLANGUAGE"
		},
		success: function(data){
			language = data;
		}
	});
}

function onlyValid(bool)
{
	activeStep(2);
	if(bool)
	{
		var els=$('div.VBOX');
		var tab=$('td.special_val');
		var els2=$('div.DBOX');
		var tab2=$('td.Nspecial_val');
		els.toggle();
		tab.show();
		
		$('#is_push').val(0);
		$('.VBOX, .VOT').show();
		$('#tr0').append('<td style="width:20px;" id="OT_R"></td>');
		
		$('#timeVal, #viewOpt').show();

		if($('#SSTTID').val()>0)
		{
			$('#BasketTitle').hide();
		}
	}
	else
	{
		var els=$('div.VBOX');
		var tab=$('td.special_val');
		var els2=$('div.DBOX');
		var tab2=$('td.Nspecial_val');
		els.toggle();
		tab.hide();
		$('#is_push').val(1); 
		$('#OT_R').remove();
		$('.VBOX, .VOT').hide();
		$('#timeVal, #viewOpt').hide();
		$('#BasketTitle').show();
	}
}
function checkHD(event,el,usr_id)
{
//	event.stopPropagation();

	$.ajax({
		type: "POST",
		url: "./push.feedback.php",
		dataType: 'json',
		data: {
			ACTION: "HD_USER",
			token: $('#token').val(),
			usrs: JSON.stringify([usr_id]),
			value:(el.checked?1:0)
		},
		success: function(data){
			
		}
	});
	
}

function doSendpush(el)
{
	
	var is_push = $('#is_push').val();
	if(jQuery.trim($("#nameBask").val())=="" && is_push=="1")
	{
		alert(language.selNameEmpty);
		return;
	}
	
	if(isNaN($('#SSTTID').val()))
	{
		if(jQuery.trim($("#nameBask").val())=="" && is_push=="0")
		{
			$('#nameBask').css('background-color',"#ffc9c9");
			alert(language.selNameEmptyVal);
			$('#nameBask').focus();
			setTimeout("$('#nameBask').css('background-color','white');",1500);
			return;
		}	
	}

	$(el).attr('disabled','disabled');
	$('#push_sending').css('visibility','visible');
	
	document.forms["formpushdoc"].submit();
}

function addUser(event,usr_id,el)
{

	var srcElement = (event.target) ? event.target : event.srcElement;
	if(srcElement.tagName != 'TD')
	{
		return true;
	}
	var usr_ids = {};

	usr_ids[usr_id] = {sel:($('#USER_'+usr_id).hasClass('selected')?0:1),hd:($('#USER_'+usr_id+' input:checkbox').attr('checked')?'1':'0')};
	
	
	
	if(last_added && is_shift_key(event) && $('#USER_'+last_added).length > 0 && $('#USER_'+usr_id).length > 0)
	{
		var lst = $('#search_list tbody tr');
		var index1 = $.inArray($('#USER_'+last_added)[0],lst);
		var index2 = $.inArray($('#USER_'+usr_id)[0],lst);
		
		if(index2<index1)
		{
			var tmp = index1;
			index1=(index2-1)<0?index2:(index2-1);
			index2=tmp;
		}
		
		if(index2 != -1 && index1 != -1)
		{
			var exp = '#search_list tbody tr:gt('+index1+'):lt('+(index2-index1)+')';//, #USER_'+(last_added == elem ? last_added:usr_id)+'';
			usr_ids = jQuery.map($(exp), function(n, i){
							return ($(n).attr('id').substr(5));
							});
			var obj = {};
			$.each(usr_ids,function(i,n){
				obj[n] = {};
				obj[n].sel = (n!=last_added)?(($('#USER_'+n).hasClass('selected') && (!is_ctrl_key(event)))?0:1):($('#USER_'+n).hasClass('selected')?1:0);
				obj[n].hd = $('#USER_'+n+' input:checkbox').attr('checked')?'1':'0';
			});
			usr_ids = obj;
		}
	}
	
	$.each(usr_ids,function(i,n){
		if($('#USER_'+i).hasClass('selected'))
			$('#USER_'+i).removeClass('selected');
		else
			$('#USER_'+i).addClass('selected');
	});
	
	last_added = usr_id;
	
	$.ajax({
		type: "POST",
		url: "./push.feedback.php",
		dataType: 'json',
		data: {
			ACTION: "ADDUSER",
			token: $('#token').val(),
			usr_id: JSON.stringify(usr_ids)
		},
		success: function(data){
			var success = data.result;
			var nsel = parseInt(data.selected);
			$('#alert_nbuser').empty().append(nsel);
			$.each(success,function(n,i){
				if(i == '1')
					{
						$('#USER_'+n).addClass('selected');
					}
					if(i == '0')
					{
						$('#USER_'+n).removeClass('selected');
					}
				}
			);
		}
	});
}


function loadUsers()
{
	$.ajax({
		type: "POST",
		url: "./push.feedback.php",
		dataType: 'json',
		data: {
			token: $('#token').val(),
			ACTION: "LOADUSERS",
			filters: currentFilters
		},
		success: function(data){
			$('#search_list_wrapper tbody tr').addClass('selected');
			$('#alert_nbuser').empty().append(parseInt(data));
		}
			
	});
}

function unloadUsers()
{
	$.ajax({
		type: "POST",
		url: "./push.feedback.php",
		dataType: 'json',
		data: {
			token: $('#token').val(),
			ACTION: "UNLOADUSERS",
			filters: ''
		},
		success: function(data){
			data = parseInt(data);
			if(data >= 0)
			{	
				$('#search_list_wrapper tr').removeClass('selected');
				$('#alert_nbuser').empty().append(data);
			}
			
		}
			
	});
}

function removeList(usr_id)
{
	totalsel--;
	all_listtab[usr_id]=0;
	all_list = "";
	for (cc in all_listtab) {
		if (all_list != "" && all_listtab[cc] == 1) 
			all_list += ",";
		if (all_listtab[cc] == 1) {
			all_list += cc;
		}
	}
	$("#alert_nbuser").empty().append(totalsel);
	$('#SEL_USER_'+usr_id).remove();

	$('#myselectlist tr').removeClass('g');
	$('#myselectlist tr:nth-child(even)').addClass('g');
}

function addFilter(el)
{
	$('#filters').append($('#filters .filter:last').clone());
	$('#filters .filter .fieldsearch:last').val('').removeClass('active');

	$('#search_form input').unbind('keyup').bind('keyup',function(){
		specialsearch(true);
	});
	$('#search_form select').unbind('change').bind('change',function(){
		specialsearch(true);
	});
}

function removeFilter(el)
{
	var tr = $(el).parent().parent();
	if($('table#filters tr').length>1)
	{
		
		$('#search_form input').unbind('keyup').bind('keyup',function(){
			specialsearch(true);
			
		});
		$('#search_form select').unbind('change').bind('change',function(){
			specialsearch(true);
		});
		tr.remove();
		specialsearch(true);
	}
}

function getCurrentFilters()
{
	var strings=new Array();
	$('.filter').each(function(){
		var lstrings = strings.length;
		strings[lstrings] = {};
		$(this).find('select,input').each(function(i){
			switch(i)
			{
				case 0:
					strings[lstrings].operator = $(this).val();
					break;
				case 1:
					strings[lstrings].field = $(this).val();
					break;
				case 2:
					strings[lstrings].fieldlike = $(this).val();
					break;
				case 3:
					strings[lstrings].fieldsearch = $(this).val();
					if($(this).hasClass('fieldsearch') && $.trim($(this).val()) != '')
						$(this).addClass('active');
					else
						$(this).removeClass('active');
					break;
			}
		});
	});
	currentFilters = JSON.stringify(
			{strings:strings,countries:$('#searchcountry').val(),
				lists:$('#searchlist').val(),activite:$('#searchactivite').val(),
				societe:$('#searchsociete').val(),
				fonction:$('#searchfunction').val(),
				template:$('#searchtemplate').val()});
}

function addFilterMulti(filter,link)
{
	var clone = $('#filter_multi_'+filter);
	var orig = clone;
	if(!$('#filter_multi_'+filter).is(':visible'))
	{
		clone = orig.clone(true);
		var par = orig.parent();
		orig.remove();
		par.append(clone);
	}
	clone.slideToggle();
	$(link).toggleClass('filterActive').val([]);
	return false;
}

function setPerPage(){
	perPage = $('#pagesizer').val();
}
var searchin = false;
var searchinActive = false;
function specialsearch(newSearch,Wpage)
{
	$('#search_list_wrapper').addClass('loading');
	if(newSearch == true)
	{
		getCurrentFilters();
	}
	if(typeof(Wpage) == 'undefined')
	{
		Wpage = 1;
	}
	page = Wpage;
	

	searchin = $.ajax({
		type: "POST",
		url: "push.feedback.php",
		data: {
			ACTION: "SEARCHUSERS",
			page: page,
			view: currentView,
			sort: JSON.stringify(searchSort),
			filters: currentFilters,
			token: $('#token').val(),
			perPage: perPage
		},
		beforeSend: function(){
			if (searchinActive)
				searchin.abort();
			searchinActive = true;
		},
		error: function(data){
			searchinActive = false;
			$('#search_list_wrapper').removeClass('loading');

		},
		timeout: function(){
			searchinActive = false;
			$('#search_list_wrapper').removeClass('loading');

		},
		success: function(data){
			$('#search_list_wrapper').empty().append(data).removeClass('loading');
			$('#BLABLA tr:nth-child(even)').addClass('g');
				activeSort();
			searchinActive = false;
			last_added = false;
		}
		
	});
}

function saveList()
{
	
	var name = $.trim($('#NEW_LST').val());
	
	if(name == '')
	{
		alert('vous devez donner un nom a votre liste');
		return;
	}
	if(parseInt($('#alert_nbuser').html()) == 0)
	{
		alert('aucun user selectionne');
		return;
	}

	$.ajax({
		type: "POST",
		url: "./push.feedback.php",
		data: {
		ACTION: "SAVELIST",
		token: $('#token').val(),
		name: name
		},
		success: function(data){
			if(data == '-1')
			{
				//display error
				return;
			}
			
			$('#searchlist').empty().append(data);
		}
	});
	$("#saveList, #saveListButton").toggle();
}