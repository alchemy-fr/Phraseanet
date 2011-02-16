<?php
require_once( dirname(__FILE__) . '/../../lib/bootstrap.php' );
$session = session::getInstance();
if(!$ph_session = phrasea_open_session($session->ses_id,$session->usr_id))
	die();

$conn = connection::getInstance();
	
$request = httpRequest::getInstance();
$parm = $request->get_parms('page');

$page = 0;
if($parm['page'])
	$page = (int)$parm['page'];

$feed = new internalpubli($session->usr_id,$session->ses_id, $page);

echo $feed->format_html();
$sql = 'DELETE FROM sselnew WHERE usr_id="'.$session->usr_id.'" AND ssel_id IN (SELECT ssel_id FROM ssel WHERE public="1")';
$conn->query($sql);
