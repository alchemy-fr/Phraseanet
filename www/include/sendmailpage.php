<?php
require_once dirname( __FILE__ ) . '/../../lib/bootstrap.php';
$session = session::getInstance();
require(GV_RootPath."lib/index_utils2.php");

ob_start(null, 0);

$request = httpRequest::getInstance();
$parm = $request->get_parms("lst","obj","destmail","subjectmail","reading_confirm","textmail","ssttid","type");

$lng = isset($session->locale)?$session->locale:GV_default_lng;

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

phrasea::headers();

$conn = connection::getInstance();

$user = user::getInstance($session->usr_id);

$from =array('name'=>$user->display_name,'email'=>$user->email);

if($parm["type"]=="title")
	$titre=true;
else
	$titre=false;
	
$exportname = "Export_".date("Y-n-d").'_'.mt_rand(100,999);
	
if($parm["ssttid"]!="")
{
	$basket = new basket($parm['ssttid']);
	$exportname = str_replace(' ', '_',$basket->name) . "_".date("Y-n-d");
}
	
$download = new export($parm['lst'],$parm['ssttid']);

$list = $download->prepare_export($parm['obj'], $titre);

$list['export_name'] = $exportname.'.zip';

$endDate = phraseadate::format_mysql(new DateTime('+1 day'));

$token = random::getUrlToken('download',false,$endDate,serialize($list));

$url = GV_ServerName.'mail-export/'.$token.'/';

$emails = explode(',',$parm["destmail"]);

$dest = array();

foreach($emails as $email)
	$dest = array_merge($dest,explode(';',$email));
	
$res = array();

$reading_confirm_to = false;
if($parm['reading_confirm'] == '1')
{
	$reading_confirm_to = $user->email;
}

foreach($dest as $email)
{
	if(($result = mail::send_documents(trim($email), $url,$from, $parm["textmail"], $reading_confirm_to))!==true)
		$res[] = $email;
}
 
if(count($res) == 0) 
	echo "<script type='text/javascript'>parent.$('#sendmail .close_button:first').trigger('click');</script>"	;
else
{
	echo "<script type='text/javascript'>alert('".str_replace("'","\'",sprintf(_('export::mail: erreur lors de l\'envoi aux adresses emails %s'),implode(', ',$res)))."');</script>";
}

echo ob_get_clean();
ob_flush();flush();

set_time_limit(0);
session_write_close();
ignore_user_abort(true);

$zipFile = GV_RootPath.'tmp/download/'.$token.'.zip';

export::build_zip($token,$list,$zipFile);

	
