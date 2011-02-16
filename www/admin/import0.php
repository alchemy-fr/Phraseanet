<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();
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
else{
		phrasea::headers(403);
}

phrasea::headers();

?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
		<script type="text/javascript"> 
		var needrefresh = false;
		var oMyObject = parent.window.dialogArguments;
		var myOpener  = oMyObject.myOpener;
		function imp0rloadusr()
		{	
			 myOpener.document.forms[0].action = "./users.php";
			 myOpener.document.forms[0].submit();
		}
		
		window.onbeforeunload = function() 
							{ 
								if(needrefresh)
									imrloadusr();
							};
							
		</script>
	</head>
	<body>
		<iframe style="z-index:1; visibility:visible; position:absolute; top:0px; left:0px; width:543px; height:300px;border:0px" scrolling="yes" id="idHFrameIW" src="import.php"  name="HFrameIW"></iframe>
	</body>
</html>