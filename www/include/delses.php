<?php
ignore_user_abort(true);
set_time_limit(0);
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("app");


if(isset($session->ses_id) && isset($session->usr_id))
{
	$ses_id = 	$session->ses_id;
	$usr_id = $session->usr_id;
}
else
{
	die();
}

if(!($ph_session = phrasea_open_session($ses_id, $usr_id))){
		die();
}
	
$conn = connection::getInstance();
	
if(trim($parm["app"])!= '')
{
	$sql = "SELECT app FROM cache WHERE session_id='".$conn->escape_string($ses_id)."'" ;
	$apps = array();
	if($rs = $conn->query($sql))
	{
		if($row = $conn->fetch_assoc($rs))
			$apps  = unserialize($row['app']);
		$conn->free_result($rs);
	}

	if(in_array($parm["app"],$apps))
		foreach(array_keys($apps,$parm['app']) as $key)
			unset($apps[$key]);

	$sql = "UPDATE cache SET lastaccess=now(),app='".serialize($apps)."' WHERE session_id='".$ses_id."'";
	$conn->query($sql);
}

?>