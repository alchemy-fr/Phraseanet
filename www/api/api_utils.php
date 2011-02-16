<?php

//SPECIAL ZINO
ini_set('display_errors','off');
ini_set('display_startup_errors','off');
ini_set('log_errors','off');
//SPECIAL ZINO


$request = httpRequest::getInstance();
$parm = $request->get_parms('p', 'ses_id', 'usr_id', 'debug');

$ses_id = $parm['ses_id'];
$usr_id = $parm['usr_id'];

if(!$parm['debug'])
{
	header('Content-Type: text/xml; charset=UTF-8');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');    // Date in the past
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');  // always modified
	header('Cache-Control: no-store, no-cache, must-revalidate');  // HTTP/1.1
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache');                          // HTTP/1.0
}


$sxParms = simplexml_load_string($parm['p']);



$action = (string)$sxParms['action'];

$dom = new DOMDocument('1.0', 'UTF-8');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$result = $dom->appendChild($dom->createElement('result'));
$result->setAttribute('action', $action);

$status = 'OK';

$f = '_'.mb_strtolower($action).'.php';
if(file_exists($f))
{
	include($f);
}
else
{
	err('bad action');
}

$result->appendChild($dom->createElement('status'))->appendChild($dom->createTextNode($status));

if($parm['debug'])
	echo('<pre>' . htmlentities($dom->saveXML()) . '</pre>');
else
	echo $dom->saveXML();


function err($msg)
{
	global $dom, $result, $status;
	$result->appendChild($dom->createElement('err_msg'))->appendChild($dom->createTextNode($msg));
	$status = 'ERR';
}
?>