<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();
$request = httpRequest::getInstance();

$parm = $request->get_parms(
						 "p0", 'TOU', 'test', 'valid'
					 );
$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
	if(!$session->admin)
	{
		phrasea::headers(403);
	}
}
else
	phrasea::headers(403);


if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	phrasea::headers(403);	
	

if(is_null($parm['p0']))
	phrasea::headers(400);

$user = user::getInstance($usr_id);
if(!isset($user->_rights_sbas[$parm['p0']]) || !$user->_rights_sbas[$parm['p0']]['bas_modify_struct'])
{
	phrasea::headers(403);
}

phrasea::headers();

$update = false;
$TOU = array();
if((int)$parm['p0'] > 0 && is_array($parm['TOU']))
{
	foreach($parm['TOU'] as $loc=>$terms)
	{
		
		$connsbas = connection::getInstance($parm['p0']);
		if($connsbas)
		{
			$terms = str_replace(array("\r\n","\n","\r"),array('','',''),strip_tags($terms,'<p><strong><a><ul><ol><li><h1><h2><h3><h4><h5><h6>'));
			$sql = 'UPDATE pref SET value="'.$connsbas->escape_string($terms).'" '.($parm['valid'] ? ', updated_on=NOW()' : '').' WHERE prop="ToU" AND locale="'.$connsbas->escape_string($loc).'"';
			if($connsbas->query($sql))
			{
				$update = true;
				$TOU[$loc] = $terms;
			}
		}
	}
}
$avLanguages = user::detectlanguage($session->locale);
if(!$update)
{
	$connsbas = connection::getInstance($parm['p0']);
	
	
	
	if($connsbas)
	{
		$sql = 'SELECT value, locale FROM pref WHERE prop ="ToU"';
		if($rs = $connsbas->query($sql))
		{
			while($row = $connsbas->fetch_assoc($rs))
				$TOU[$row['locale']] = $row['value'];
			$connsbas->free_result($rs);
		}
		
		$missing_locale = array();
		
		foreach($avLanguages as $lang)
			foreach($lang as $k=>$v)
				if(!isset($TOU[$k]))
					$missing_locale[] = $k;
					
		foreach($missing_locale as $v)
		{
			$sql = "INSERT INTO pref (id, prop, value, locale, updated_on, created_on) VALUES (null, 'ToU', '', '".$connsbas->escape_string($v)."', NOW(), NOW())";
			if($connsbas->query($sql))
			{
				$TOU[$v] = '';
			}
		}
	}
}




?>

<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/959595/jquery-ui.css" />

		<script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js,include/jslibs/jquery-ui-1.8.6/jquery-ui-1.8.6.js"></script>
		<script type="text/javascript" src="/include/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>
		<script type="text/javascript">
			tinyMCE.init({
				mode : "textareas",
				theme : "advanced",
				plugins : "paste,searchreplace",
				paste_auto_cleanup_on_paste : true,
				paste_remove_styles: true,
				paste_strip_class_attributes:'all',
				paste_use_dialog : false,
		        paste_convert_headers_to_strong : false,
		        paste_remove_spans : true,
		    	theme_advanced_buttons1 : "bold,italic,underline,strikethrough,formatselect,|,cut,copy,paste,|,search,replace,|,bullist,numlist,undo,redo,|,link,unlink",
		    	theme_advanced_buttons2 : "",
		    	theme_advanced_buttons3 : "",
		    	theme_advanced_buttons4 : "",
		    	theme_advanced_toolbar_location : "top",
		    	theme_advanced_toolbar_align : "left",
		    	theme_advanced_statusbar_location : "bottom"
			});
			$(document).ready(function(){
				$('#tabs').tabs({
					selected:$("#tabs ul li").index($('#tabs ul li.selected'))
				});
			});
		</script>
		<style type="text/css">
//			.ui-state-default a, .ui-state-default a:link, .ui-state-default a
//			{
//				color:#959595;
//			}
//			.ui-state-active a, .ui-state-active a:link, .ui-state-active a
//			{
//				color:white;
//			}
		</style>
	</head>
	<body>
		<form target="_self" method="post" action="cgus.php">
			<div style="text-align:center;margin:10px 0;">
				<input type="submit" value="<?php echo _('Mettre a jour');?>" id="valid"/><input type="checkbox" value="1" name="valid"/><label for="valid"><?php echo _('admin::CGU Les utilisateurs doivent imperativement revalider ces conditions'); ?></label>
				<input type="hidden" name="p0" value="<?php echo $parm['p0'];?>"/>
			</div>
			<div id="tabs" style="background:transparent;padding:0;">
				<ul style="background:transparent;border:none;border-bottom:1px solid #959595;">
				<?php
					foreach($avLanguages as $lang)
					{
						foreach($lang as $k=>$v)
						{
							if(isset($TOU[$k]))
							{
								$s = ( $k == $session->locale ? 'selected':'' );
								echo '<li class="'.$s.'" style="border:none;"><a href="#terms-'.$k.'">'.$v['name'].'</a></li>';
							}
						}
					}
				?>
				</ul>
				<?php
					foreach($avLanguages as $lang)
					{
						foreach($lang as $k=>$v)
						{
							if(isset($TOU[$k]))
							{
							?>
								<div id="terms-<?php echo $k;?>">
									<textarea name="TOU[<?php echo $k;?>]" style="width:100%;height:600px;margin:0 auto;">
										<?php echo $TOU[$k];?>
									</textarea>
								</div>
							<?php
							}
						}
					}
				?>
			</div>
		</form>
	</body>
</html>