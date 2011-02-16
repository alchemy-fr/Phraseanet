<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();

if(!isset($session->usr_id) || !isset($session->ses_id))
{
	die('0');
}
if(!($ph_session = phrasea_open_session($session->ses_id,$session->usr_id)))
{
	die('0');
}

$request = httpRequest::getInstance();
$parm = $request->get_parms('token');

$token = (string)($parm["token"]);

$datas = ((random::helloToken($token)));

if(!$datas)
	die('0');

if(!is_string($datas['datas']))
	die('0');
	
if(($list = @unserialize($datas['datas'])) == false)
{
	die('0');
}

set_time_limit(0);
session_write_close();
ignore_user_abort(true);

$zipFile = GV_RootPath.'tmp/download/'.$datas['value'].'.zip';
export::build_zip($token, $list,$zipFile);

echo '1';