<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();

$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	{
		phrasea::headers(403);
	}
}
else{
	phrasea::headers(403);
}



$request = httpRequest::getInstance();
$parm = $request->get_parms("lst", "obj", "ssttid" , "type");

$download = new export($parm['lst'],$parm['ssttid']);

if($parm["type"]=="title")
	$titre=true;
else
	$titre=false;
	
$list = $download->prepare_export($parm['obj'], $titre);

$exportname = "Export_".date("Y-n-d").'_'.mt_rand(100,999);
	
if($parm["ssttid"]!="")
{
	$basket = new basket($parm['ssttid']);
	$exportname = str_replace(' ', '_',$basket->name) . "_".date("Y-n-d");
}

$list['export_name'] = $exportname.'.zip';

$endDate = phraseadate::format_mysql(new DateTime('+3 hours'));

$url = random::getUrlToken('download',$session->usr_id,$endDate,serialize($list));

if($url)
{
	
	$params = array(
		'lst'=>explode(";", $parm["lst"]),
		'downloader'=>$session->usr_id,
		'subdefs'=>$parm['obj'],
		'from_basket'=>$parm["ssttid"],
		'export_file'=>$exportname
	);
	
	
	$events_mngr = eventsmanager::getInstance();
	$events_mngr->trigger('__DOWNLOAD__', $params);
	
	header('Location: /download/'.$url);
	exit();
}
phrasea::headers(500);


