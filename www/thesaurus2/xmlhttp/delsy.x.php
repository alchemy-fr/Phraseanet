<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
$session = session::getInstance();

require("../xmlhttp.php");


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "id"
					, "piv"		// lng de consultation (pivot)
					, "typ"		// "TH" (thesaurus) ou "CT" (cterms)
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
<html lang="<?php echo $session->usr_i18n;?>">
<head></head>
<body>
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
// $sy_list      = $root->appendChild($ret->createElement("sy_list"));
$refresh_list = $root->appendChild($ret->createElement("refresh_list"));

if($parm["bid"] !== null)
{		
	$loaded = false;
	$connbas = connection::getInstance($parm['bid']);
	if($connbas)
	{		
		if($parm["typ"]=="CT")
		{
			$sql = "SELECT value AS xml, value AS cterms FROM pref WHERE prop='cterms'";
			$xqroot = "cterms";
		}
		else
		{
			$sql = "SELECT p1.value AS xml, p2.value AS cterms FROM pref p1, pref p2 WHERE p1.prop='thesaurus' AND p2.prop='cterms'";
			$xqroot = "thesaurus";
		}
		if($parm["debug"])
			print("sql:".$sql."<br/>\n");

		if($rsbas = $connbas->query($sql))
		{
			if($rowbas = $connbas->fetch_assoc($rsbas))
			{
				$xml = trim($rowbas["xml"]);
				
				if(($dom = @DOMDocument::loadXML($xml)) && ($domct = @DOMDocument::loadXML($rowbas["cterms"])))
				{
					$xpath = new DOMXPath($dom);
					$q = "/$xqroot//sy[@id='".$parm["id"]."']";

					if($parm["debug"])
						print("q:".$q."<br/>\n");
						
					$sy0 = $xpath->query($q)->item(0);
					if($sy0)
					{
						$xpathct = new DOMXPath($domct);
						
						// on cherche la branche 'deleted' dans les cterms
						$nodes = $xpathct->query("/cterms/te[@delbranch='1']");
						if(!$nodes || ($nodes->length == 0))
						{
							// 'deleted' n'existe pas, on la cree
							$id = $domct->documentElement->getAttribute("nextid");
							if($parm["debug"])
								printf("creating 'deleted' branch : id=%s<br/>\n", $id);
							$domct->documentElement->setAttribute("nextid", (int)($id)+1);
							$del = $domct->documentElement->appendChild($domct->createElement("te"));
							$del->setAttribute("id", "C".$id);
							$del->setAttribute("field", _('thesaurus:: corbeille'));
							$del->setAttribute("nextid", "0");
							$del->setAttribute("delbranch", "1");
							
							$r = $refresh_list->appendChild($ret->createElement("refresh"));
							$r->setAttribute("id", "C");
							$r->setAttribute("type", "CT");
						}
						else
						{
							// 'deleted' existe
							$del = $nodes->item(0);
							$r = $refresh_list->appendChild($ret->createElement("refresh"));
							$r->setAttribute("id", $del->getAttribute("id"));
							$r->setAttribute("type", "CT");
						}

						// on cree une branche 'te'
						$oldid = $sy0->getAttribute("id");
						$refrid = $sy0->parentNode->parentNode->getAttribute("id");
						$delid = $del->getAttribute("id");
						$delteid = (int)($del->getAttribute("nextid"));
						
						if($parm["debug"])
							printf("delid=$delid ; delteid=$delteid <br/>\n");

						$del->setAttribute("nextid", $delteid+1);
						$delte = $del->appendChild($domct->createElement("te"));
						$delte->setAttribute("id", $delid . "." . $delteid);
						$delte->setAttribute("nextid", "1");
						
						$delsy = $delte->appendChild($domct->createElement("sy"));
						$delsy->setAttribute("id", $newid = ($delid . "." . $delteid . ".0"));
						// $delsy->setAttribute("id", $newid = ($delid . "." . $delteid));
						$delsy->setAttribute("lng", $sy0->getAttribute("lng"));
						$delsy->setAttribute("v", $sy0->getAttribute("v"));
						$delsy->setAttribute("w", $sy0->getAttribute("w"));
						if($sy0->hasAttribute("k"))
							$delsy->setAttribute("k", $sy0->getAttribute("k"));

						$te = $sy0->parentNode;
						$te->removeChild($sy0);

						$sql_oldid = str_replace(".", "d", $oldid)."d";
						$sql_newid = str_replace(".", "d", $newid)."d";
						$sql = "UPDATE thit SET value='$sql_newid' WHERE value='$sql_oldid'";
						if($parm["debug"])
							printf("sql: %s<br/>\n", $sql);
						else
							$connbas->query($sql);
						
						$sql = array();
							
						if($parm["typ"]=="CT")
						{
							$domct->documentElement->setAttribute("modification_date", $now = date("YmdHis"));
							$sql[]  = "UPDATE pref SET"
								. "  value='" . $connbas->escape_string($domct->saveXML()) . "'" 
								. ", updated_on='" .$connbas->escape_string($now). "'"
								. " WHERE prop='cterms'";

							$r = $refresh_list->appendChild($ret->createElement("refresh"));
							$r->setAttribute("type", "CT");
							if($refrid)
								$r->setAttribute("id", $refrid);
							else
								$r->setAttribute("id", "C");
						}
						else
						{
							$xmlct = str_replace(array("\r", "\n", "\t"), array("", "", ""), $domct->saveXML());
							$xmlte = str_replace(array("\r", "\n", "\t"), array("", "", ""), $dom->saveXML());
							
							$dom->documentElement->setAttribute("modification_date", $now = date("YmdHis"));
							$domct->documentElement->setAttribute("modification_date", $now = date("YmdHis"));

							$sql[]  = "UPDATE pref SET"
								. "  value='" . $connbas->escape_string($xmlct) . "'" 
								. ", updated_on='" .$connbas->escape_string($now). "'"
								. " WHERE prop='cterms'";
								
							$sql[]  = "UPDATE pref SET"
								. "  value='" . $connbas->escape_string($xmlte) . "'" 
								. ", updated_on='" .$connbas->escape_string($now). "'"
								. " WHERE prop='thesaurus'";
								
							$r = $refresh_list->appendChild($ret->createElement("refresh"));
							$r->setAttribute("type", "TH");
							if($refrid)
								$r->setAttribute("id", $refrid);
							else
								$r->setAttribute("id", "T");
						}
						
						foreach($sql as $s)
						{
							if($parm["debug"])
								printf("sql: %s<br/>\n", $s);
							else
								$connbas->query($s);
						}
					
						$cache_abox = cache_appbox::getInstance();
						$cache_abox->delete('thesaurus_'.$parm['bid']);
						
						$url = "./getterm.x.php";
						$url .= "?bid=" . urlencode($parm["bid"]);
						$url .= "&typ=" . urlencode($parm["typ"]);
						$url .= "&piv=" . urlencode($parm["piv"]);
						$url .= "&id="  . urlencode($te->getAttribute("id"));
						// $url .= "&sel=" . urlencode($parm["id"]);
						$url .= "&nots=1";		// liste des ts inutile
						$ret2 = xmlhttp($url);
						if( $sl = $ret2->getElementsByTagName("sy_list")->item(0) )
						{
							$sl = $ret->importNode($sl, true);
							$sy_list = $root->appendChild($sl);
						}
						
						if($parm["debug"])
						{
							printf("url: %s<br/>\n", $url);
							printf("<pre>" . $ret2->saveXML() . "</pre>");
						}
					}
				}
			}
			$connbas->free_result($rsbas);
		}
	}
}
if($parm["debug"])
{
	print("<pre>" . $ret->saveXML() . "</pre>");
	print("</body></html>");
}
else
	print($ret->saveXML());
?>