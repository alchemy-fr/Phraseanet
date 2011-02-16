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


$request = httpRequest::getInstance();
$parm = $request->get_parms("tsk",
								 "p0",
								 "p1", // id de la collection locale
								 "p2",
								 "nrc", // nb de records au debut du vidage
								 "page",
								 "htmlname",
								 "framename",
								 "formname",
								 "type"
);


?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<title>Phraseanet IV</title>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
		<script type="text/javascript">
		var type = "<?php echo $parm["type"]?>";
		var init = false;
		</script>
	</head>
	
<FRAMESET rows="110, *"  border="1" framespacing="0" >
	<FRAME src="progress1.php?formname=<?php echo urlencode($parm["formname"])?>&htmlname=<?php echo $parm["htmlname"]?>&type=<?php echo $parm["type"]?>" name="topframe" id="topframe" noresize  >
<?php if ($parm["framename"]) { ?>
	<FRAME src="about:blank" noresize name="<?php echo $parm["framename"]?>bottomframe" id="bottomframe" >
<?php } else { ?>
	<FRAME src="<?php echo $parm["page"]?>?p0=<?php echo $parm["p0"]?>&p1=<?php echo $parm["p1"]?>&p2=<?php echo $parm["p2"]?>&first=1&u=<?php echo mt_rand()?>" noresize name="bottomframe" id="bottomframe" >
<?php } ?>
</FRAMESET>