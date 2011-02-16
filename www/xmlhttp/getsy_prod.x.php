<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
$session = session::getInstance();


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "id"
					, "debug"
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
				
header("Content-Type: text/xml; charset=UTF-8");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                          // HTTP/1.0

$ret = new DOMDocument("1.0", "UTF-8");
$ret->standalone = true;
$ret->preserveWhiteSpace = false;
$root = $ret->appendChild($ret->createElement("result"));
$root->appendChild($ret->createCDATASection( var_export($parm, true) ));

if($parm["bid"] !== null)
{		
	$loaded = false;
	
	$dom = databox::get_dom_thesaurus($parm['bid']);
				
	if($dom)
	{
		$xpath = databox::get_xpath_thesaurus($parm['bid']);//new DOMXPath($dom);
		$q = "/thesaurus//sy[@id='".$parm["id"]."']";
		if($parm["debug"])
			print("q:".$q."<br/>\n");
			
		$nodes = $xpath->query($q);
		if($nodes->length > 0)
		{
			$n2 = $nodes->item(0);
			$root->setAttribute("t", $n2->getAttribute("v"));
		}
	}
}
if($parm["debug"])
	print("<pre>" . $ret->saveXML(). "</pre>");
else
	print($ret->saveXML());
?>