<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();
$ret = array('status'=>'unknown','message'=>false);

$request = httpRequest::getInstance();
$parm = $request->get_parms('usr', 'app');

if(isset($session->ses_id))
{
	$ses_id = 	$session->ses_id;
	$usr_id = $session->usr_id;
	if($usr_id != $parm['usr']) //i logged with another user
	{
		$ret['status'] = 'disconnected';
		die(p4string::jsonencode($ret));
	}
}
else
{
	$ret['status'] = 'disconnected';
	die(p4string::jsonencode($ret));
}

if(!($ph_session = phrasea_open_session($ses_id, $usr_id))){
		$ret['status'] = 'session';
		die(p4string::jsonencode($ret));
}
	
$conn = connection::getInstance();

if(!$conn){
		die(p4string::jsonencode($ret));
}

$ret['apps'] = 1;

if(trim($parm["app"]) != '')
{
	$sql = "SELECT app FROM cache WHERE session_id='".$conn->escape_string($ses_id)."' AND usr_id = '".$conn->escape_string($usr_id)."'" ;
	$apps = array();
	if($rs = $conn->query($sql))
	{
		if($row = $conn->fetch_assoc($rs))
			$apps  = unserialize($row['app']);
		$conn->free_result($rs);
	}
	if(!in_array($parm["app"],$apps))
		$apps[] = $parm["app"];

	$ret['apps'] = count($apps);
	
	$sql = "UPDATE cache SET lastaccess=now(),app='".$conn->escape_string(serialize($apps))."' WHERE session_id='".$conn->escape_string($ses_id)."' AND usr_id = '".$conn->escape_string($usr_id)."'";
	$conn->query($sql);
}

$ret['status'] = 'ok';
$ret['notifications'] = false;

$evt_mngr = eventsmanager::getInstance();

$ret['notifications'] = $evt_mngr->get_notifications();

$ret['changed'] = array();

$sql = 'SELECT ssel_id FROM sselnew WHERE usr_id = "'.$session->usr_id.'"';
if($rs = $conn->query($sql))
{
	while($row = $conn->fetch_assoc($rs))
	{
		$ret['changed'][] = $row['ssel_id']; 
	}
	$conn->free_result($rs);
}
		
		
if(!isset($session->prefs['message']) || $session->prefs['message'] == '1')
{
	if(GV_maintenance)
	{
		
		$ret['message'] .= '<div>'._('The application is going down for maintenance, please logout.').'</div>';
		
	}
	
	if(GV_message_on)
	{
		
		$ret['message'] .= '<div>'.strip_tags(GV_message).'</div>';
		
	}
}
		
echo p4string::jsonencode($ret);	
