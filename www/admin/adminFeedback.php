<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

header("Content-Type: text/html; charset=UTF-8");

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

$request = httpRequest::getInstance();
$parm = $request->get_parms('action', 'position', 'test', 'renew');

$output = '';

$action = $parm['action'];
	
switch($action)
{
	case 'LANGUAGE':
		include(GV_RootPath.'lib/prodUtils.php');
		$output = getLanguage($lng);
		break;
	case 'TREE':
		include(GV_RootPath.'lib/adminUtils.php');
		$output = getTree($usr_id,$ses_id,$parm['position']);;
		break;
	case 'APACHE':
		if($parm['test'] == 'success')
			$output = '1';
		else
			$output = '0';
		break;
	case 'SCHEDULERKEY':
		$output = GV_ServerName.'admin/runscheduler.php?key='.urlencode(phrasea::scheduler_key(!!$parm['renew']));
		break;
}
echo $output;


