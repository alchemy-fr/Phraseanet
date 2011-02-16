<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
require_once( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
include(GV_RootPath.'lib/clientUtils.php');
$session = session::getInstance();

$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
}
else{
	header("Location: /login/client");
	exit();
}
	
$output = '';

$request = httpRequest::getInstance();
$parm = $request->get_parms('action', 'env', 'pos', 'cont', 'roll', 'mode', 'color');

switch($parm['action'])
{
	case 'LANGUAGE':
		$output = getLanguage($lng);
		break;
	case 'PREVIEW':
		$output = getPreviewWindow($usr_id,$ses_id,$lng,$parm['env'],$parm['pos'],$parm['cont'],$parm['roll']);
		break;
	case 'HOME':
		$output = phrasea::getHome('PUBLI','client');
		break;
	case 'CSS':
		$output = setCss($usr_id,$ses_id,$parm['color']);
		break;
	case 'BASK_STATUS':
		$output = setBaskStatus($usr_id,$ses_id,$parm['mode']);
		break;
	case 'BASKUPDATE':
		$output = updateBask($usr_id,$ses_id);
		break;
		
}
echo $output;

