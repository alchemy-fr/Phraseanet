<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					'bid'
					, 'rid'
					, 'stat'
					, 'val'
					, 'ses'
					, 'usr'
				);


if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	if(!($ph_session = phrasea_open_session((int)$ses_id, $usr_id)))
	{
		header("Location: /login/?err=no-session");
		exit();
	}
}
else
{
	header("Location: /login/");
	exit();
}
				
header('Content-Type: text/xml; charset=UTF-8');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');    // Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');  // always modified
header('Cache-Control: no-store, no-cache, must-revalidate');  // HTTP/1.1
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');                          // HTTP/1.0

				
if(!phrasea_open_session($parm['ses'],$parm['usr']))
	die();
				
$ret = new DOMDocument('1.0', 'UTF-8');
$ret->standalone = true;
$ret->preserveWhiteSpace = false;
$root = $ret->appendChild($ret->createElement('result'));

$ok = false;

if($parm['bid'] !== null)
{			
	$canChange = false;

	$user = user::getInstance($parm['usr']);
	if(isset($user->_rights_bas[$parm['bid']]) && $user->_rights_bas[$parm['bid']]['chgstatus'] == true)
		$canChange = true;
	
	if($canChange)
	{
		$connbas = connection::getInstance(phrasea::sbasFromBas($parm['bid']));
		if($connbas)
		{
			if($parm['val']==0)
				$sql = 'status & ~(1<<'.$parm['stat'].')';
			else
				$sql = 'status | (1<<'.$parm['stat'].')';
			$sql = 'UPDATE record SET status='.$sql.' WHERE record_id="'.$connbas->escape_string($parm['rid']).'"';
			

				if($connbas->query($sql))
				{
					$ok = true;
				}
		}
	}
}
$root->setAttribute('done', $ok?'1':'0');

	print($ret->saveXML());
	

?>