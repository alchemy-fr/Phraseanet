var xhr_object;
var p4 = {};
var $tabs;
var selected_tab;
var class_selected_tab = -1;

bodySize = {x:0,y:0};
$(document).ready(function()
{	
	//resize window		
	$(window).bind('resize',function(){
		resize();
	});
	//do tabs and resize window on show
	$('#mainTabs:visible').tabs({
		show:function(){
			resize();
		}
	});
	//datepicker 
	$(function() {
		var dates = $('.dmin, .dmax').datepicker({
			defaultDate: -10,
			changeMonth: true,
			changeYear: true,
			dateFormat:'dd-mm-yy',
			numberOfMonths: 3,
			maxDate: "-0d",
			onSelect: function(selectedDate) {
				var option = $(this).hasClass("dmin") ? "minDate" : "maxDate";
				var instance = $(this).data("datepicker");
				var date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
				$(dates).not(':hidden').not(this).datepicker("option", option, date);
			}
		});
	});
	//hide report menu
	$("button.hided").button({
        icons: {
            primary: 'ui-icon-triangle-1-n'
        },
        text: false
	 }).unbind("click").bind("click", function(){
   		$('.hided').hide();
   		$(".showed").show();
		$(".form").slideToggle("slow", function(){
			bodySize.y = $('#mainContainer').height();
			$('.answers:visible').height(Math.round(bodySize.y - $('.answers:visible').position().top));
		});
	});
	// show report menu
	$("button.showed").button({
        icons: {
            primary: 'ui-icon-triangle-1-s'
        },
        text: false
    }).unbind("click").bind("click", function(){
    	 bodySize.y = $('#mainContainer').height();
    	 $('.showed').hide();
         $(".hided").show();
		 $(".form").slideToggle("slow", function(){
			bodySize.y = $('#mainContainer').height();
			$('.answers:visible').height(Math.round(bodySize.y - $('.answers:visible').position().top));
			
		});
	});
	//multiselect list box plugin
	$(function(){
		$("select.multiselect").multiSelect();
	});
	//press enter to submit the main form
	$('form .entersubmiter').bind('keypress',function(event){
		if(event.keyCode == '13')
		{
			$("#DOC-input").attr("checked", "checked").trigger('click');
		}
	});
	//submit button for the main form
	$('form .formsubmiter').bind('click',function(){
		var form = $(this).closest('form');
		var container = $(this).closest('.inside-container');
		var scroller_width = 0;
		var right = 0;
		data = form.serializeArray();
		//check if a base is selected, pop an alert message if not
		if(!form.find(".ui-multiselect-checkboxes input:checked").length)
		{
			 $('body').append("<div id='dialog'><div>");
			 $('#dialog').dialog({
				title: language.choix_collection,
				height: 0,
				width:380,
				draggable: false,
				modal: true,
				resizable: false,
				hide: 'explode',						
				close:function(ev, ui) {
					$(this).dialog('destroy').remove();
				  } 
			});
		}
		else
		{
			//load content from report.php
			var request = form.data("request");
			if(request && typeof(request.abort) == 'function')
			{
				request.abort();
			}

			request = $.ajax({
				type: "POST",
				url: "./report.php",
				data:form.serializeArray(),
				beforeSend:function(){
					container.find('.content').empty();
					container.find('.answers').addClass('onload');
				},
				success: function(data)
				{
					//remove loader
					container.find('.answers').removeClass('onload');
					//load the result
					container.find('.content').empty().append(data);
					//init index of selected tab to 0
					if( typeof(selected_tab) == 'undefined')
						selected_tab = 0;
					//do tab with the loaded content
					$tabs = container.find('div.tabs').tabs({
						selected: -1, //no panel selected when load
						cache: true,//enable cache
						select: function(event, ui){
							//define the div where the table is loaded
							load = $(ui.panel).find('.load');
							//define the form that contain all parameters to build the table in the loaded div
							form = $(ui.panel).find("form");
							//serialize all the form values
							f_val = form.serialize(true);
							//build the table
							update_tab(load, form, f_val);
						}
					});
					//open selected tab
					if(class_selected_tab == -1)
					{
						selected_tab = 0;
						$('.tabb:visible li').eq(selected_tab).find("a").trigger("click");
					}
					else if(typeof class_selected_tab == "undefined")
					{
						$('.tabb:visible li').eq(0).find("a").trigger("click");
					}
					else
					{
						theclass = class_selected_tab.split(" ");
						if($('.' + theclass[0] + ':visible').length > 0)
							$('.' + theclass[0] + ':visible').find("a").trigger("click");
						else
							$('.tabb:visible li').eq(0).find("a").trigger("click");
					}
					
					//start position
				    if(selected_tab == 0)
				    {
				    	right = 0;
				    }
				    else
				    {
				    	$('.tabb:visible li').each(function(index){
				    		if(index == selected_tab)
				    		{
				    			return false;
				    		}
				    		right += $(this).width();
				    	});
				    }
				    //$('.answers .ui-tabs-nav:visible').css("right", right);
				    	
				    //scroller
					$(".horizontal-scroller:visible").nicoslider({
						start : right
					});	
				}
			});
			form.data('request', request);
		}
	});
	//show radio option for download with precise word etc..
	$('.inside-container form input:radio').bind('change',function(){
		var opt = $(this).closest('div').find('.options');
		$(this).closest('form').find('.options').hide();
		if($(this).get(0).checked)
			opt.show();
	});
	//remove attr checked from radio if user change options
	$('.inside-container form input:not(:radio)').bind('click', function(){
		$(this).closest("form").find('input:radio:visible:checked').removeAttr("checked");
	});
	//pop dialog box on table link in the dashboard
	$('td a:visible').bind('click', function(){
		var cl = $(this).attr("class");
		var arr = cl.split('_');
		var w = $('#mainTabs:visible').width();
		$.ajax({
			type: "POST",
			url: "./tab.php",
			dataType : "json",
			data: ({tbl : "what", rid : arr[0], sbasid : arr[1], collection : "", from : "DASH", dmin : date.dmin, dmax : date.dmax }),
			success: function(data)
			{
			 	$('body').append("<div id='dialog'>" + data.rs + "</div>");
			 	$('#dialog').dialog({
				  	title: data.title,
					width:w,
					modal: true,
					close:function(ev, ui) {
					    $(this).dialog('destroy').remove();
					} 
			 	});
			}
		});
	});
});
//#############END DOCUMENT READY ######################################//
//unserialize value
function unserialize(str)
{
        var n_str = str.split("&");
        var serialised = new Array();
        $.each(n_str, function(){
            var properties = this.split("=");
            serialised[properties[0]] = properties[1];
        });
        return serialised;
}
//pop a dialog box with who download what content
function who()
{
	$("td a.bound").unbind("click").bind('click',function(){
		//get form
		form = $(this).closest(".ui-tabs-panel").find(".report_form");
		//get col
		var col = $(this).closest("td").attr("class");
		//get link value
		var val_a = $(this).html();
		//split link classes
		var arr = $(this).attr("class").split(" ");
		//get usrid
		var usrid = arr[0];
		//get the current tbl
		var tbl = form.find("input[name=tbl]").val();
		//replace the new tbl
		form.find("input[name=tbl]").val("infouser");
		//put the old in the from input
		form.find("input[name=from]").val(tbl);
		
		if(col == 'user')
		{
			form.find("input[name=user]").val(usrid);
		}
		else if(tbl == 'CNXB')
		{
			form.find("input[name=tbl]").val("infonav");
			form.find("input[name=user]").val(val_a);
			form.find("input[name=on]").val(col);
		}
		else
		{
			form.find("input[name=user]").val(val_a);
			form.find("input[name=on]").val(col);
		}
		//load content
		$.ajax({
			type: "POST",
			url: "./tab.php",
			dataType: "json",
			data:form.serializeArray(),
			success: function(data){
				//replace tbl with the from tbl
				form.find("input[name=tbl]").val(tbl);
				//pop the dialog window
				dialog_record(form, data.rs, data.title, tbl);
			}
		});
	});
}
//group by the element of each column
function group()
{
	$("a.groupby").unbind("click").bind("click", function(){
		form = $(this).closest(".ui-tabs-panel").find(".report_form");
		var col = $(this).attr('class');
		var tbl = form.find("input[name=tbl]").val();
		form.find("input[name=groupby]").val(col);
		$.ajax({
			type: "POST",
			url: "./tab.php",
			dataType: "json",
			data:form.serializeArray(),
			success: function(data){
				form.find("input[name=tbl]").val(tbl);
				form.find("input[name=groupby]").val("");
				dialog_record(form, data.rs, data.title, tbl);
			}
		});
	});	
}
//pop & load the record box informations
function what()
{
	$(".record_id a").unbind("click").bind('click',function(){
		form = $(this).closest(".ui-tabs-panel").find(".report_form");
		var rid = $(this).html();
		var arr = $(this).attr("class").split(" ");
		var usrid = arr[0];
		var tbl = form.find("input[name=tbl]").val();
		form.find("input[name=from]").val(tbl);
		form.find("input[name=tbl]").val("what");
		form.find("input[name=rid]").val(rid);
		form.find("input[name=user]").val(usrid);
		$.ajax({
			type: "POST",
			url: "./tab.php",
			dataType: "json",
			data: form.serializeArray(),
			success: function(data){
				dialog_record(form, data.rs, data.title, tbl);
			}
		});
	});
}
//construct the dialog_record box
function dialog_record(form, p_message, p_title, tbl)
{
	//get size attribute
	var h = $(document).height();
	var w = $('#mainTabs:visible').width();
 	p_title = p_title || "";

 	$('body').append("<div id='dialog'>" + p_message + "</div>");
 	data_form = form.serializeArray();
 	var dial = $('#dialog');
 	dial.data('dataForm',form);
 	dial.dialog({
	  	title: p_title,
		height: h,
		width:w,
		modal: true,
		resizable: false,
		close:function(ev, ui) {
 			form.find("input[name=tbl]").val(tbl);
 			form.find("input[name=on]").val("");
		    $(this).dialog('destroy').remove();
		  } 
 	 });
 	csv();
}
//construct the dialog box filters.
function alert_dialog_filter(submit, form, p_message, p_title)
{
	var buttons = {};
	
	p_title = p_title || "";
	$('body').append("<div id='dialog'>" + p_message + "</div>");
	buttons[language.valider] = function() {
			value = $("select[name='filtre'] option:selected").val();
			form.find("input[name=filter_value]").val(value);
			f_val = form.serialize(true);
			update_tab(submit,form, f_val);
			form.find("input[name=filter_column]").val("");
			form.find("input[name=filter_value]").val("");
			$(this).dialog('destroy').remove();	
	 };
 	$('#dialog').dialog({
	  	title: p_title,
		width: 400,
		modal: true,
		resizable: false,
		draggable : false,
		buttons: buttons,
		close:function(ev, ui) {
			$(this).dialog('destroy').remove();
		} 
	});
}
//construct the configuration dialog box
function alert_dialog_conf(submit, form, p_message, p_title)
{
  var w = $('#mainTabs:visible').width();
  var buttons = {};
  
  buttons[language.valider] = function() {
		var idChecked = new Array();
		
		$("#dialog input:checked").each(function (id) {
			idChecked[id] = $(this).val();
		});
		var list_column = ",";
		$.each(idChecked, function(key, val){
			 list_column += val + ",";
			});
		form.find("input[name=list_column]").val(list_column);
		f_val = form.serialize(true);
		if(idChecked.length == 0)
		{
			alert("Vous devez cocher au minimum une case");
			return false;
		}
		else
		{
			update_tab(submit, form, f_val);
			$(this).dialog('destroy').remove();
		}
  };	

	p_title = p_title || "";
	$('body').append("<div id='dialog'>" + p_message + "</div>");
	$('#dialog').dialog({
	 	title: p_title,
		width: w,
		modal: true,
		buttons : buttons,
		close:function(ev, ui) {
			$(this).dialog('destroy').remove();
		} 
	});
}
//load the content of the filter box
function dofilter(submit, form, f_val)
{
	$("a.filter").unbind("click").bind("click", function(){
		var col = $(this).attr('class');
		form.find("input[name=filter_column]").val(col);
		form.find("input[name=liste]").val("on");
		$.ajax({
			type : "POST",
			url : "./tab.php",
			dataType : "json",
			data : form.serializeArray(),
			success : function(data){
				form.find("input[name=liste]").val("off");
				alert_dialog_filter(submit, form, data.diag, data.title);
			}
		});
	});
}
//pop & load the content of the dialog_conf window
function conf(submit, form, f_val)
{
	$("a.config").unbind("click").bind("click", function(){
		form.find("input[name=conf]").val("on");
		$.ajax({
			type : "POST",
			url : "./tab.php",
			dataType : "json",
			data : form.serializeArray(),
			success : function(data){
				form.find("input[name=conf]").val("off");
				alert_dialog_conf(submit, form, data.liste, data.title);
			}
		});
	});
}
//load the next page of data array
function next(submit, form, f_val, data)
{
	form.find(".next").unbind('click').bind('click',function(){
		form.find("input[name=page]").attr("value", data.next);
		form.find("input[name=liste_filter]").attr("value", data.filter);
		f_val = form.serialize(true);
		update_tab(submit, form, f_val);
		return false;
	});
}
//load the previous page of data array
function prev(submit, form, f_val, data)
{
	form.find(".prev").unbind('click').bind('click',function(){
		form.find("input[name=page]").attr("value", data.prev);
		form.find("input[name=liste_filter]").attr("value", data.filter);
		f_val = form.serialize(true);
		update_tab(submit, form, f_val);
	});
	return false;
}
//print the table when click on the print link
function print()
{
	$("a.jqprint").unbind("click").bind("click", function(){
		var $table = $(this).closest(".report-table");
		var $graph = $(this).closest(".graph");
		
		if($graph.length > 0){
			$graph.jqprint();
		}else{
			$table.jqprint();
		}
	});
}

function csv()
{
	var button = $(".form_csv input[name=submit]");
	
	button.unbind("click").bind("click", function(e){
		e.preventDefault();
		var $this = $(this);
		var $formm = $this.closest("form");
		
		if($this.closest("#dialog").length > 0)
		{
			var $form = $("#dialog").data("dataForm");
		}
		else
		{
			var $form = $this.closest(".ui-tabs-panel").find(".report_form");
		}
		
		$form.find("input[name=printcsv]").val("on");

		if(button.data('ajaxRunning'))
		{
			button.data('ajaxQuery').abort();
			button.data('ajaxRunning', false);
		}
		
		var query = $.ajax({
			type : "POST",
			url : "./tab.php",
			dataType : "json",
			data : $form.serializeArray(),
			beforeSend:function(){
				$formm.after("<div></div>");
				$formm.next("div").addClass("onload");
				button.data('ajaxRunning', true);
			},
			timeOut:function(){
				button.data('ajaxRunning', false);
			},
			error:function(){
				button.data('ajaxRunning', false);
			},
			success : function(data){
				$formm.next("div").remove();
				button.data('ajaxRunning', false);
				$form.find("input[name=printcsv]").val("off");
				
				if(typeof data.rs === "object")
				{
					$key = $this.closest("table").attr("class");
					$csv = data.rs[$key];
					$formm.find("textarea[name=csv]").val($csv);
				}
				else
				{
					$formm.find("textarea[name=csv]").val(data.rs);
				}
				$formm.find("input[name=doit]").trigger('click');
			}
		});
		
		button.data('ajaxQuery', query);
	});
}



//order the array by DESC or ASC when clicking a column
function orderArray()
{
	$('.orderby').unbind("click").bind('click',function(){
		var $this = $(this);
		var champ = $(this).parent().attr('class');
		var submit_click = $this.closest(".report-table").parent();
		var form_click = submit_click.parent().find("form");
		
		if(form_click.find("input[name=order]").val() == "")
			order = "asc";					
		
		if(form_click.find("input[name=order]").val() == "asc")
		{
			order = "desc";
			$this.find('.asc').hide();
		}
		else
		{
			order = "asc";
			$this.find('.desc').hide();
		}
		form_click.find("input[name=order]").val(order);
		form_click.find("input[name=champ]").val(champ);
		f_val = form_click.serialize(true);
		update_tab(submit_click, form_click, f_val);
		
		return false;
	});
}
//show hide prev & next or both according to the result
function navigation(form, prev, next, display_nav)
{
	if(prev == 0)
		form.find("input[name=prev]").hide();
	else
		form.find("input[name=prev]").show();
	
	if(next == false)
		form.find("input[name=next]").hide();
	else
		form.find("input[name=next]").show();
	
	if(display_nav == false || ((prev == 0) && (next == false)))
		form.hide();
}
//color filter'slink if a filter is active
function colorFilter(col)
{
	if($.isArray(col))
	{
		$.each(col, function(){
			$("a." + this).filter("a.filter").css("color", "#0000FF");
		});
	}
}
//show hide desc or asc arrow according to the order of the table
function arrow(form)
{
	var cl = form.find("input[name=champ]").val();

	if(cl != "")
	{
		th = $("th."+ cl +":visible");
		th.css("color", "white");
	}
	if(form.find("input[name=order]").val() == "asc")
	{
		th.find('.asc').hide();
	}
	else if(form.find("input[name=order]").val()  == "desc")
	{
		th.find('.desc').hide();
	}
}
//load default page and number of result if the user seizes crazy things in this inputs
function page(form, lim, array_val)
{
	var current_lim = form.find("input[name=limit]").val();

	if(current_lim < 1 || Math.floor(current_lim).toString() != current_lim.toString())
	{
		current_lim = 30;
		form.find("input[name=limit]").attr("value", current_lim);
	}
	
	if(current_lim != lim)
	{
		form.find("input[name=page]").attr("value", "1");
	}
	else
	{
		if(array_val["page"] < 1 || Math.floor(array_val["page"]).toString() != array_val["page"].toString())
		{
			array_val["page"] = 1;
		}
		form.find("input[name=page]").attr("value", array_val["page"]);
	}
}
//do a submit button to load new users conf
function go(submit, form, f_val, data)
{
	$(".submiter").bind("click", function(){
		form.find("input[name=liste_filter]").attr("value", data.filter);
		f_val = form.serialize(true);
		update_tab(submit, form, f_val);
	});
}
//save the value of the previous limit
var save_limit;

//submit is the div where we want to load the updated tab
//form is the current form
//f_val is the serialized form
function update_tab(submit, form, f_val)
{	
	//unserialize the form values
	array_val = unserialize(f_val);
	//attribute the current page & check values
	page(form, save_limit, array_val);
	//attribute the current filter
	form.find("input[name=liste_filter]").attr("value", array_val["liste_filter"]);
	
	$.ajax({
		type: "POST",
		url: "./tab.php",
		dataType: "json",
		data:form.serializeArray(),
		beforeSend:function(){
			submit.empty();
			submit.closest('.answers').addClass('onload');
			form.hide();
		},
		success: function(data){
			//remove loader
			submit.closest('.answers').removeClass('onload');
			//show form
			form.show();
			
			//attribute the new page
			form.find("input[name=page]").attr("value", data.page);
			//attribute the new filter
			form.find("input[name=liste_filter]").attr("value", data.filter);
			//save current limit
			save_limit = data.limit;
			//load the result
			submit.empty().append(data.rs);
			//get selected li index
			selected_tab = $tabs.tabs('option', 'selected');
			if( typeof(selected_tab) == 'undefined')
				selected_tab = 0;	
			class_selected_tab = $('.tabb:visible li').eq(selected_tab).attr('class');
			//hide show the next and previous button according to the results
			navigation(form, data.prev, data.next, data.display_nav);
			//color the filter link if active
			colorFilter(data.col);
			//load the next array
			next(submit,form, f_val, data);
			//load the previous one
			prev(submit,form, f_val, data);
			//submit new array
			go(submit,form, f_val, data);
			//load the who array
			who();
			//load the what array
			what();
			//load the group by array
			group();
			//load the array with new filters
			dofilter(submit,form, f_val);
			//load the configuration array
			conf(submit,form, f_val);
			//print the array
			print();
			//export csv
			csv();
			//load the new array with new order
			orderArray();
			//hide show desc or asc arrow
			arrow(form);
		}
	});
}


//resize report div
function resize()
{
	bodySize.y = $('#mainContainer').innerHeight();
	bodySize.x = $('#mainContainer').innerWidth();
	
	$('.answers:visible').height(Math.round(bodySize.y - $('.answers:visible').offset().top));
};
//update session
function sessionactive(){
	$.ajax({
		type: "POST",
		url: "/include/updses.php",
		dataType: 'json',
		data: {
			app : 10,
			usr : usrId
		},
		error: function(){
			window.setTimeout("sessionactive();", 10000);
		},
		timeout: function(){
			window.setTimeout("sessionactive();", 10000);
		},
		success: function(data){
			if(data)
				manageSession(data);
			var t = 20000;
			if(data.apps && parseInt(data.apps) > 1)
				t = Math.round((Math.sqrt(parseInt(data.apps)-1) * 1.3 * 20000));
			window.setTimeout("sessionactive();", t);
			return;
		}
	});
};

window.onbeforeunload = function() 
{ 
	xhr_object = null;      
	if(window.XMLHttpRequest) // Firefox   
	   xhr_object = new XMLHttpRequest();   
	else if(window.ActiveXObject) // Internet Explorer
	   xhr_object = new ActiveXObject("Microsoft.XMLHTTP");   
	else  // XMLHttpRequest non supporte par le navigateur   
	  return;   
	url= "/include/delses.php?app=4&t="+Math.random();
	xhr_object.open("GET", url, false);     
	xhr_object.send(null); 
	
};
sessionactive();