<?php
require_once dirname( __FILE__ ) . "/../lib/bootstrap.php";



$request = httpRequest::getInstance();
$parm = $request->get_parms("inf");

if(strlen($parm["inf"])==0)
	exit();
	
$datas = random::helloToken($parm['inf']);
if(!$datas)
	phrasea::headers(404);

$usr_id = $datas['usr_id'];

phrasea::use_i18n(user::get_locale($usr_id));
	
$conn = connection::getInstance();

if(!$conn)
	phrasea::headers(500);
	
header('Content-Type: application/atom+xml');

$destroy_session = false;	
$session = session::getInstance();
if(!isset($session->usr_id) || !isset($session->ses_id))
{
	$destroy_session = true;
	$ses_id = phrasea_create_session($usr_id);
	$session->ses_id = $ses_id;
	$session->usr_id = $usr_id;
}
else
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
}

if(!$phsession = phrasea_open_session($ses_id,$usr_id))
	die();

$feed = new internalpubli($usr_id, $ses_id);

$xml_datas = $feed->format_atom();

if($destroy_session)
{
	p4::logout();
}

echo $xml_datas;



