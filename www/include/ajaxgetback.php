<?php
require_once dirname( __FILE__ ) . '/../../lib/bootstrap.php';
$session = session::getInstance();

$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
}
else{
	header("Location: /login/prod/");
	exit();
}
$output = '';

$request = httpRequest::getInstance();
$parm = $request->get_parms('action', 'type', 'base_id', 'name');

$action = $parm['action'];

switch($action)
{
	case 'images':
		if(isset($parm['type']))
		{
			switch($parm['type'])
			{
				case 'minilogos':
					header('Content-type: image/jpeg');
					$output = collection::printLogo($parm['base_id']);
					break;
				case 'watermark':
					header('Content-type: image/jpeg');
					$output = collection::printWatermark($parm['base_id']);
					break;
				case 'presentation':
					header('Content-type: image/jpeg');
					$output = collection::printPresentation($parm['base_id']);
					break;
				case 'stamp':
					header('Content-type: image/jpeg');
					$output = collection::printStamp($parm['base_id']);
					break;
				case 'status':
					header('Content-type: image/jpeg');
					$output = databox::printStatus($parm['name']);
					break;
				case 'print':
					header('Content-type: image/jpeg');
					$output = databox::getPrintLogo($parm['name']);
					break;
			}
		}
		break;
}
echo $output;
