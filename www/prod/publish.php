<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
phrasea::headers();

$request = httpRequest::getInstance();
$parm = $request->get_parms("ssel");

$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	if(!($ph_session = phrasea_open_session((int)$ses_id, $usr_id)))
	{
		header("Location: /login/?err=no-session");
		exit();
	}
}
else
{
	header("Location: /login/");
	exit();
}


$conn = connection::getInstance();

$right = false;

$basket = basket::getInstance($parm['ssel']);

?>

<html lang="<?php echo $session->usr_i18n;?>">
	<head>
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/jquery-ui.css" />
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/prodcolor.css" />
	
	<script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js,include/jslibs/jquery-ui-1.7.2.js"></script>
	<script type="text/javascript" src="/include/minify/f=include/tinymce/jscripts/tiny_mce/jquery.tinymce.js"></script>
	<script type="text/javascript">
		$('textarea.tinymce').tinymce({
			script_url : '/include/tinymce/jscripts/tiny_mce/tiny_mce.js',
			theme : "simple",
			width:"660px"
		});
		
	$(document).ready(function(){
		$('#tabs').tabs();
		$('input.ui-state-default').hover(
				function(){$(this).addClass('ui-state-hover')},
				function(){$(this).removeClass('ui-state-hover')}
		);
	});
	</script>
		</head>
		
		<body class="bodyprofile">
			<div style="overflow-y:auto;overflow-x:auto;text-align:center;position:absolute;top:5px;left:5px;right:245px;bottom:50px;">
				<div id="MCEcontainer" style="position:relative;width:800px;height:100%;margin:0 auto;">
					<div id="tabs">
						<ul>
							<li><a href="#compose">COMPOSEUR</a></li>
							<li><a href="#vizualize" onclick="$('#vizualize').empty().append(tinyMCE.get('editor').getContent());">VIZUAAALIZ</a></li>
						</ul>
						<div id="compose">
							<textarea id="editor" style="width:800px;height:100%;"><?php echo $basket->descript?> <img src="http://utf8.romain/web/db_alch_utf8/subdefs/108_thumbnail.jpg"/></textarea>
						</div>
						<div id="vizualize" style="text-align:left;border:1px dotted #414141;">
						</div>
					</div>
				</div>
			</div>
			
			<div style="position:absolute;top:5px;width:230px;right:5px;bottom:50px;overflow-x:hidden;overflow-y:auto;text-align:center;">
				<div>
					<a href="/test/quand/jeveux/voir">Documents publiés</a> :
					Si vous souhaitez integrer certains au texte, cliquez-deposez la vignette à l'endroit souhaité
				</div>
			<?php 
			
				foreach($basket->elements as $basket_element)
				{
	
					if( $basket_element->width > $basket_element->height )
						$style = 'width:200px;';
					else
						$style = 'height:200px;';
					
					echo '<div><img src="'.$basket_element->url.'" style="'.$style.'" /></div>';
				}
			
			?>
			</div>
			<div style="position:absolute;text-align:center;bottom:5px;left:5px;right:5px;height:40px;">
				<input class="input-button" type="button" value="<?php echo _('boutton::fermer')?>" onclick="parent.hideDwnl();" /> 
			</div>
			
	</body>
</html>