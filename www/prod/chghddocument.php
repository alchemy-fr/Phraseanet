<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
phrasea::headers();
require(GV_RootPath."lib/index_utils2.php");

$request = httpRequest::getInstance();
$parm = $request->get_parms("act", "bid","rid","cchd","ccfilename");

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

$sbas_id = phrasea::sbasFromBas($parm["bid"]);

$pathhd = null ;
$baseurl = null ;


?> 
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/jquery-ui.css" />
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/prodcolor.css" />
	</head>
	<body onload="parent.hideDwnl();">

<?php

if(  !isset($_FILES["newHD"]) || $_FILES["newHD"]["tmp_name"]=="" || $_FILES["newHD"]["size"]=="" ||  ($_FILES["newHD"]["size"]+0)==0 )
{
		echo  '<center>', _('prod::substitution::erreur : document de substitution invalide'),'<br/><br/>';
		echo "<a href=\"#\" onClick=\"parent.hideDwnl();return false;\">"._('boutton::fermer')."</a>";
		die('</body></html>');
}

try {
	p4file::substitute($parm['bid'], $parm['rid'], $_FILES["newHD"]["tmp_name"], $_FILES["newHD"]["name"], ($parm['ccfilename'] == '1'));	
}
catch(Exception $e)
{
		echo  '<center>', $e->getMessage(),'<br/><br/>';
		echo "<a href=\"#\" onClick=\"parent.hideDwnl();return false;\">"._('boutton::fermer')."</a>";
		die('</body></html>');
}

echo  '<center>', _('prod::substitution::document remplace avec succes'),'<br/><br/>';
echo "<a href=\"#\" onClick=\"parent.hideDwnl();return false;\">"._('boutton::fermer')."</a>";

?>

</body>
</html>