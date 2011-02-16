<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
$session = session::getInstance();


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "id"
					, "piv"		// lng de consultation (pivot)
					, "debug"
				);

$lng = isset($session->locale)?$session->locale:GV_default_lng;
if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
}
else
{
	header("Location: /login/?error=auth&lng=".$lng);
	exit();
}


if($parm["debug"])
{
	header("Content-Type: text/html; charset=UTF-8");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
	header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");                          // HTTP/1.0
	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 FRAMESET//EN" "http://www.w3.org/TR/REC-html40/strict.dtd">
<META http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<?php
}
else
{
	header("Content-Type: text/xml; charset=UTF-8");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
	header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");                          // HTTP/1.0
}


$ret = new DOMDocument("1.0", "UTF-8");
$ret->standalone = true;
$ret->preserveWhiteSpace = false;
$root = $ret->appendChild($ret->createElement("result"));
$root->appendChild($ret->createCDATASection( var_export($parm, true) ));
$refresh_list = $root->appendChild($ret->createElement("refresh_list"));

if($parm["bid"] !== null)
{			
	$loaded = false;
	$connbas = connection::getInstance($parm['bid']);
	if($connbas)
	{
		$sql = "SELECT p1.value AS cterms, p2.value AS thesaurus FROM pref p1, pref p2 WHERE p1.prop='cterms' AND p2.prop='thesaurus'";
		if($rsbas = $connbas->query($sql))
		{
			if($rowbas = $connbas->fetch_assoc($rsbas))
			{
				if( ($domth = @DOMDocument::loadXML($rowbas["thesaurus"])) && ($domct = @DOMDocument::loadXML($rowbas["cterms"])) )
				{
					$xpathth = new DOMXPath($domth);
					$xpathct = new DOMXPath($domct);
					if($parm["id"] !== "")				// s�cu pour pas exploser le th�saurus
					{
						$q = "/thesaurus//te[@id='" . $parm["id"] . "']";
						if($parm["debug"])
							printf("q:%s<br/>\n", $q);
						$thnode = $xpathth->query($q)->item(0);
						if($thnode)
						{
							$chgids = array();
							$pid = $thnode->parentNode->getAttribute("id");
							if($pid==="")
								$pid = "T";
							
							moveToDeleted($thnode, $chgids, $connbas);
							
							if($parm["debug"])
								printf("chgids: %s<br/>\n", var_export($chgids, true));
								
							$domct->documentElement->setAttribute("modification_date", $now = date("YmdHis"));
							$domth->documentElement->setAttribute("modification_date", $now);
							
							$sql = array();
							
							$sql[] = "UPDATE pref SET value='".$connbas->escape_string($domct->saveXML())."', updated_on='".$connbas->escape_string($now)."' WHERE prop='cterms'";
							$sql[] = "UPDATE pref SET value='".$connbas->escape_string($domth->saveXML())."', updated_on='".$connbas->escape_string($now)."' WHERE prop='thesaurus'";
							
							foreach($sql as $s)
								if($parm["debug"])
									printf("sql: %s<br/>\n", $s);
								else
									$connbas->query($s);
							
							$cache_abox = cache_appbox::getInstance();
							$cache_abox->delete('thesaurus_'.$parm['bid']);
									
							/*
							foreach($chgids as $chgid)
							{
								$oldid = str_replace(".", "d", $chgid["oldid"]) . "d";
								$newid = str_replace(".", "d", $chgid["newid"]) . "d";
								$sql = "UPDATE thit SET value='$newid' WHERE value='$oldid'";
								if($parm["debug"])
									printf("sql: %s<br/>\n", $sql);
								else
									$connbas->query($sql);
							}
							*/
							$r = $refresh_list->appendChild($ret->createElement("refresh"));
							$r->setAttribute("id", $pid);
							$r->setAttribute("type", "TH");
						}
					}
				}
			}
			$connbas->free_result($rsbas);
		}
	}
}
if($parm["debug"])
	print("<pre>" . $ret->saveXML() . "</pre>");
else
	print($ret->saveXML());
	
function moveToDeleted(&$thnode, &$chgids, &$connbas)
{
  global $parm, $root, $ret, $domth, $domct, $xpathct, $refresh_list;

	$nodes = $xpathct->query("/cterms/te[@delbranch='1']");
	if(!$nodes || ($nodes->length == 0))
	{
		$id = $domct->documentElement->getAttribute("nextid");
		if($parm["debug"])
			printf("creating 'deleted' branch : id=%s<br/>\n", $id);
		$domct->documentElement->setAttribute("nextid", (int)($id)+1);
		$ct	= $domct->documentElement->appendChild($domct->createElement("te"));
		$ct->setAttribute("id", "C".$id);
		$ct->setAttribute("field", _('thesaurus:: corbeille'));
		$ct->setAttribute("nextid", "0");
		$ct->setAttribute("delbranch", "1");

		$r = $refresh_list->appendChild($ret->createElement("refresh"));
		$r->setAttribute("id", "C");
		$r->setAttribute("type", "CT");
	}
	else
	{
		$ct = $nodes->item(0);
		$r = $refresh_list->appendChild($ret->createElement("refresh"));
		$r->setAttribute("id", $ct->getAttribute("id"));
		$r->setAttribute("type", "CT");
	}
	$teid = (int)($ct->getAttribute("nextid"));
	$ct->setAttribute("nextid", $teid+1);
	
	$newte = $ct->appendChild( $domct->importNode($thnode, true) );
	$oldid = $newte->getAttribute("id");

	renum($newte, $ct->getAttribute("id").".".$teid, $chgids);
	// $newte->setAttribute("id", "R".substr($newte->getAttribute("id"), 1));
	
	$newid = $ct->getAttribute("id").".".$teid;
	$soldid = str_replace(".", "d", $oldid)."d";
	$snewid = str_replace(".", "d", $newid)."d";
	$l = strlen($soldid)+1;

	$sql = "UPDATE thit SET value=CONCAT('$snewid', SUBSTRING(value FROM $l)) WHERE value LIKE '$soldid%'";
	if($parm["debug"])
		printf("sql : %s<br/>\n", $sql);
	else
		$connbas->query($sql);
	
	$thnode->parentNode->removeChild($thnode);
	
	if($parm["debug"])
	{
		printf("<pre>%s</pre>", $domct->saveXML());
	}
}

function renum($node, $id, &$chgids)
{
  global $parm;
	if($parm["debug"])
		printf("renum(%s)<br/>\n", $id);
	$oldid = $node->getAttribute("id");
	$newid = $id;
	//if($node->nodeName=="sy")
	//	$newid = "S".substr($newid, 1);
	// $chgids[] = array("oldid"=>$oldid, "newid"=>$newid);
	$node->setAttribute("id", $newid);
	$nchild = 0;
	for($n=$node->firstChild; $n; $n=$n->nextSibling)
	{
		if($n->nodeType==XML_ELEMENT_NODE && ($n->nodeName=="te" || $n->nodeName=="sy"))
		{
			renum($n, $id.".".$nchild, $chgids);
			$nchild++;
		}
	}
	$node->setAttribute("nextid", $nchild);
}
?>