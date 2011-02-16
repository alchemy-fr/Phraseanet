function check_value(check,el)
{
	
	var el_loader = $(el).parent().find('img.loader');
	if(el_loader.length == 0)
	{
		$(el).after('<img src="/skins/icons/loader-black.gif" class="loader" style="display:none;"/>');
		el_loader = $(el).parent().find('img.loader');
	}
	var el_status = $(el).parent().find('img.status');
	if(el_status.length == 0)
	{
		$(el).after('<img src="/skins/icons/delete.png" class="status"/>');
		el_status = $(el).parent().find('img.status');
	}
	if($(el).attr('disabled'))
	{
		$(el).removeClass('error');
		el_loader.hide();
		el_status.hide();
		return;
	}
	el_status.show();
	
	if(check == 'url')
	{
		var value = $.trim($(el).val());
		if(!value || value == '')
		{
			el_status.attr('src','/skins/icons/delete.png');
			$(el).addClass('error');
			return;
		}
		if(value.substring(0,1) != '/')
		{
			value = '/'+value;
		}
		$.ajax({
			type: "GET",
			url: value,
			beforeSend:function(){
				el_loader.show();
			},
			success: function(data){
				el_loader.hide();
				el_status.attr('src','/skins/icons/ok.png');
				$(el).removeClass('error');
				return;
			},
			timeout:function(){
				el_loader.hide();
				el_status.attr('src','/skins/icons/delete.png');
				$(el).addClass('error');
			},
			error:function(XHRobject){
				el_loader.hide();
                                if(XHRobject.status == 403)
                                {
                                    el_status.attr('src','/skins/icons/ok.png');
                                    $(el).removeClass('error');
                                }
                                else
                                {
                                    el_status.attr('src','/skins/icons/delete.png');
                                    $(el).addClass('error');
                                }
			}
		});
	}
	else
	{
		$.ajax({
			dataType: 'json',
			type: "POST",
			url: "./index.php",
			data: {
				action	:	"CHECK",
				check	:	check,
				value	:	$(el).val()
			},
			beforeSend:function(){
				el_loader.show();
			},
			success: function(data){
				el_loader.hide();
				if(data.result == 1)
				{
					el_status.attr('src','/skins/icons/ok.png');
					$(el).removeClass('error');
				}
				else
				{
					el_status.attr('src','/skins/icons/delete.png');
					$(el).addClass('error');
				}
				return;
			},
			timeout:function(){
				el_loader.hide();
			},
			error:function(){
				el_loader.hide();
			}
		});
	}
	
	
}

$(document).ready(function(){

	$('.databox_creator').bind('change', function(){
		if($(this).attr('checked'))
		{
			$('.databox_creator_dependant').removeAttr('disabled').trigger('keyup');
		}
		else
		{
			$('.databox_creator_dependant').attr('disabled', 'disabled').trigger('keyup');
		}
		$('#create_task_index').trigger('change');
	});
	$('#create_task_index').bind('change',function(){
		if($(this).attr('checked'))
		{
			$('#indexer_path').removeAttr('disabled');
		}
		else
		{
			$('#indexer_path').attr('disabled', 'disabled');
		}
	});

	$('.writable_check').bind('keyup',function(){
		check_value('writable',$(this));
	}).trigger('keyup');
	$('.executable_check').bind('keyup',function(){
		check_value('executable',$(this));
	}).trigger('keyup');
	$('.url_check').bind('keyup',function(){
		check_value('url',$(this));
	}).trigger('keyup');
				
	$('.steps:first').show();
	if($('.blocker').length == 0 )
		$('button.verify_exts').show();
	else
		$('h1.verify_exts').show();

	$('.create_base').bind('click',function(){
		createBase();
		});
	$('button.verify_exts').bind('click',function(){
		$('.steps:visible').hide().next().show();
		});
	$('.create_admin').bind('click',function(){
		createAdmin();
		});
	$('.finish_it').bind('click',function(){
		finishInstall();
		});
	$('.next_step').bind('click',function(){
		$('.steps:visible').hide().next().show();
		});
	$('.prev_step').bind('click',function(){
		$('.steps:visible').hide().prev().show();
		});



				
	$("#create_admin").validate({
				rules: {
					email:{	email:true},

					password : {password:"#create_admin input[name=user]"},
					password_confirm : {required:true,equalTo:"#create_admin input[name=password]"}
				},
				messages: {
					email:{
						email : language.validateEmail
					},
					password : {
						required :  language.validatePassword
					},
					password_confirm : {
						required :  language.validatePasswordConfirm,
						equalTo :  language.validatePasswordEqual
					}
				},
				errorPlacement: function(error, element) {
					error.prependTo( element.next() );
				}
			}
	);
	$("#create_admin").valid();				
});

function setLanguage()
{
	var date = new Date();
	date.setMonth(date.getMonth() + 2);
	$.cookie('locale', $('#lng-select')[0].value, { path: '/', expires: date });
	window.location.replace(window.location.protocol+"//"+window.location.host+window.location.pathname);
}
						
function createBase(){
	var form 		= $('#create_base');
	var dbname 		= $('input[name=abname]',form).val();

	if(!dbname || $.trim(dbname) == '')
	{
		alert(language.wrongDatabasename);
		return;
	}
	
	if($('input.error', form).length > 0)
	{
		alert(language.someErrors);
		return;
	}
	
	var datas = $(form).serialize()+'&action=CREATE_BASE';
	
	$.ajax({
		dataType: 'json',
		type: "POST",
		url: "./index.php",
		data: datas,
		beforeSend:function(){
			$('.base_alerts',form).fadeOut();
			$('.create_base_loader').show();
			$('.create_base').attr('disabled','disabled');
		},
		success: function(data){
			$('.create_base_loader').hide();
			$('.base_alerts',form).hide();
			$('.create_base').removeAttr('disabled');
			if(data.error)
				$(form).append('<div class="wrong_database base_alerts">'+data.message+'</div>');
			else
				$('.steps:visible').hide().next().show();
			return;
		},
		timeout:function(){
			$('.create_base_loader').hide();
			$('.create_base').removeAttr('disabled');
			$(form).append('<div class="wrong_database base_alerts">'+language.ajaxTimeout+'</div>');
		},
		error:function(){
			$('.create_base_loader').hide();
			$('.create_base').removeAttr('disabled');
			$(form).append('<div class="wrong_database base_alerts">'+language.ajaxError+'</div>');
		}
	});
}
			
function createAdmin(){
	var form 	= $('#create_admin');
	var email 	= $('input[name=email]:not(.error)',form).val();
	var passwd	= $('input[name=password]:not(.error)',form).val();

	if($.trim(email) < 4  || $.trim(passwd).length < 6 || $('input[name=password]', form).val() != $('input[name=password_confirm]', form).val())
	{
		alert(language.wrongCredentials);
		return;
	}

	var databox = $('input[name=databox]').attr('disabled') ? false : $('input[name=databox]').val();

	if(databox !== false && $.trim(databox) == '')
	{
		alert(language.wrongDatabasename);
		return;
	}

	if($('input.error', form).length > 0)
	{
		alert(language.someErrors);
		return;
	}
	
	var datas = $(form).serialize()+'&action=CREATE_ADMIN';

	$.ajax({
		dataType: 'json',
		type: "POST",
		url: "./index.php",
		data: datas,
		beforeSend:function(){
			$('.base_alerts',form).fadeOut();
			$('.create_admin_loader').show();
			$('.create_admin').attr('disabled','disabled');
		},
		success: function(data){
			$('.create_admin_loader').hide();
			$('.base_alerts',form).hide();
			$('.create_admin').removeAttr('disabled');
			
			if(data.error)
				$(form).append('<div class="wrong_database base_alerts">'+data.message+'</div>');
			else
				document.location.replace(document.location.protocol+'//'+document.location.hostname+'/admin?section=taskmanager');
			return;
		},
		timeout:function(){
			$('.create_admin_loader').hide();
			$('.create_admin').removeAttr('disabled');
			$(form).append('<div class="wrong_database base_alerts">'+language.ajaxTimeout+'</div>');
		},
		error:function(){
			$('.create_admin_loader').hide();
			$('.create_admin').removeAttr('disabled');
			$(form).append('<div class="wrong_database base_alerts">'+language.ajaxError+'</div>');
		}
	});
}
			
