<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();


$request = httpRequest::getInstance();
$parm = $request->get_parms("lst","SSTTID");

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	{
		header('Location: /include/logout.php');
		exit();
	}
}
else{
	header("Location: /login/");
	exit();
}


	
if(GV_needAuth2DL && $session->invite)
{
?>
	<script>
		parent.hideDwnl();
		parent.login('{act:"dwnl",lst:"<?php echo $parm['lst']?>",SSTTID:"<?php echo $parm['SSTTID']?>"}');
	</script>
<?php
	exit();
}
	

$download = new export($parm['lst'], $parm['SSTTID']);
$user = user::getInstance($session->usr_id);

$twig = new supertwig();

$twig->addFilter(array('geoname_display'=>'geonames::name_from_id'));
$twig->addFilter(array('format_octets'=>'p4string::format_octets'));

$twig->display('common/dialog_export.twig',array(
	'download'				=> $download,
	'ssttid'				=> $parm['SSTTID'],
	'lst'					=> $parm['lst'], 
	'user'					=> $user,
	'default_export_title'	=> GV_default_export_title,
	'choose_export_title'	=> GV_choose_export_title
));



