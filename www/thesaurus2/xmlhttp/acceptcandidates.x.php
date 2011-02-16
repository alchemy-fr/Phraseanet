<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "piv"
					, "cid"
					, "pid"
					, "typ"		// "TS"=cr�er nouvo terme spec. ou "SY" cr�er simplement synonyme 
					, "debug"
				);

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                          // HTTP/1.0
if($parm["debug"])
{
	header("Content-Type: text/html; charset=UTF-8");
	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 FRAMESET//EN" "http://www.w3.org/TR/REC-html40/strict.dtd">
<META http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<?php
}
else
{
	header("Content-Type: text/xml; charset=UTF-8");
}

$ret = new DOMDocument("1.0", "UTF-8");
$ret->standalone = true;
$ret->preserveWhiteSpace = false;
$root = $ret->appendChild($ret->createElement("result"));
$root->appendChild($ret->createCDATASection( var_export($parm, true) ));
// $ct_accepted = $root->appendChild($ret->createElement("ct_accepted"));
$refresh_list = $root->appendChild($ret->createElement("refresh_list"));

if($parm["bid"] !== null)
{			
	$loaded = false;

	$connbas = connection::getInstance($parm['bid']);
	if($connbas)
	{
		$sql = 'SELECT p1.value AS cterms, p2.value AS thesaurus FROM pref p1, pref p2 WHERE p1.prop=\'cterms\' AND p2.prop=\'thesaurus\'';
		
		if($rsbas = $connbas->query($sql))
		{
			$domct = $domth = false;
			if($rowbas = $connbas->fetch_assoc($rsbas))
			{
				$domct = @DOMDocument::loadXML($rowbas["cterms"]);
				$domth = @DOMDocument::loadXML($rowbas["thesaurus"]);
			}
			if($domct !== false && $domth !== false)
			{
				$xpathth = new DOMXPath($domth);
				if($parm["pid"] == "T")
					$q = "/thesaurus";
				else
					$q = "/thesaurus//te[@id='".$parm["pid"]."']";
				if($parm["debug"])
					printf("qth: %s<br/>\n", $q);
				$parentnode = $xpathth->query($q)->item(0);
				if($parentnode)
				{
					$xpathct = new DOMXPath($domct);
					$ctchanged = $thchanged = false;
					
					$icid = 0;
					foreach($parm["cid"] as $cid)
					{
						$q = "//te[@id='".$cid."']";
						if($parm["debug"])
							printf("qct: %s<br/>\n", $q);
						$ct = $xpathct->query($q)->item(0);
						if($ct)
						{
							if($parm["typ"] == "TS")
							{
								// importer tt la branche candidate comme nouveau ts
								$nid = $parentnode->getAttribute("nextid");
								$parentnode->setAttribute("nextid", (int)$nid + 1);

								$oldid = $ct->getAttribute("id");
								$te = $domth->importNode($ct, true);
								$chgids = array();
								if( ($pid=$parentnode->getAttribute("id")) == "" )
									$pid  = "T".$nid;	// racine
								else
									$pid .= ".".$nid;
									
								renum($te, $pid, $chgids);
								$te = $parentnode->appendChild($te);
								
								if($parm["debug"])
									printf("newid=%s<br/>\n", $te->getAttribute("id"));
								
								$soldid = str_replace(".", "d", $oldid)."d";
								$snewid = str_replace(".", "d", $pid)."d";
								$l = strlen($soldid)+1;
								$sql = "UPDATE thit SET value=CONCAT('$snewid', SUBSTRING(value FROM $l)) WHERE value LIKE '$soldid%'";
								if($parm["debug"])
									printf("soldid=%s ; snewid=%s<br/>\nsql=%s<br/>\n", $soldid, $snewid, $sql);
								else 
									$connbas->query($sql);
									
								if($icid == 0)	// on update la destination une seule fois
								{
									$r = $refresh_list->appendChild($ret->createElement("refresh"));
									$r->setAttribute("id", $parentnode->getAttribute("id"));
									$r->setAttribute("type", "TH");
								}
								$thchanged = true;

								$r = $refresh_list->appendChild($ret->createElement("refresh"));
								$r->setAttribute("id", $ct->parentNode->getAttribute("id"));
								$r->setAttribute("type", "CT");
								
								$ct->parentNode->removeChild($ct);

								$ctchanged = true;
							}
							elseif ($parm["typ"] == "SY")
							{
								// importer tt le contenu de la branche sous la destination
								for($ct2=$ct->firstChild; $ct2; $ct2=$ct2->nextSibling)
								{
									if($ct2->nodeType != XML_ELEMENT_NODE)
										continue;

									$nid = $parentnode->getAttribute("nextid");
									$parentnode->setAttribute("nextid", (int)$nid + 1);

									$oldid = $ct2->getAttribute("id");
									$te = $domth->importNode($ct2, true);
									$chgids = array();
									if( ($pid=$parentnode->getAttribute("id")) == "" )
										$pid  = "T".$nid;	// racine
									else
										$pid .= ".".$nid;
										
									renum($te, $pid, $chgids);
									$te = $parentnode->appendChild($te);
									
									if($parm["debug"])
										printf("newid=%s<br/>\n", $te->getAttribute("id"));
									
									$soldid = str_replace(".", "d", $oldid)."d";
									$snewid = str_replace(".", "d", $pid)."d";
									$l = strlen($soldid)+1;
									$sql = "UPDATE thit SET value=CONCAT('$snewid', SUBSTRING(value FROM $l)) WHERE value LIKE '$soldid%'";
									if($parm["debug"])
										printf("soldid=%s ; snewid=%s<br/>\nsql=%s<br/>\n", $soldid, $snewid, $sql);
									else 
										$connbas->query($sql);
										
									$thchanged = true;
								}
								if($icid == 0)	// on update la destination une seule fois
								{
									$r = $refresh_list->appendChild($ret->createElement("refresh"));
									$r->setAttribute("id", $parentnode->parentNode->getAttribute("id"));
									$r->setAttribute("type", "TH");
								}
								$r = $refresh_list->appendChild($ret->createElement("refresh"));
								$r->setAttribute("id", $ct->parentNode->getAttribute("id"));
								$r->setAttribute("type", "CT");
								
								$ct->parentNode->removeChild($ct);
								$ctchanged = true;
							}
							$icid++;
						}
					}
					$sql = array();
					if($ctchanged)
					{
						$domct->documentElement->setAttribute("modification_date", $now = date("YmdHis"));
						$sql[] = "UPDATE pref SET value='".$connbas->escape_string($domct->saveXML())."', updated_on='".$connbas->escape_string($now)."' WHERE prop='cterms'";
					}
					if($thchanged)
					{
						$domth->documentElement->setAttribute("modification_date", $now = date("YmdHis"));
						$sql[] = "UPDATE pref SET value='".$connbas->escape_string($domth->saveXML())."', updated_on='".$connbas->escape_string($now)."' WHERE prop='thesaurus'";
					
					}
					foreach($sql as $s)
					{								
						if($parm["debug"])
							printf("sql : %s<br/>\n", $s);
						else
						{
							$connbas->query($s);
						}
					}
					if($thchanged)
					{
						$cache_abox = cache_appbox::getInstance();
						$cache_abox->delete('thesaurus_'.$parm['bid']);
					}
					// print("<pre>" . htmlentities($domth->saveXML()) . "</pre>");
					// print("<pre>" . htmlentities($domct->saveXML()) . "</pre>");
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

function renum($node, $id, &$chgids, $depth=0)
{
  global $parm;
	if($parm["debug"])
		printf("renum('%s' -> '%s')<br/>\n", $node->getAttribute("id"), $id);
	$node->setAttribute("id", $id);
	$nchild = 0;
	for($n=$node->firstChild; $n; $n=$n->nextSibling)
	{
		if($n->nodeType==XML_ELEMENT_NODE && ($n->nodeName=="te" || $n->nodeName=="sy"))
		{
			renum($n, $id.".".$nchild, $chgids, $depth+1);
			$nchild++;
		}
	}
	$node->setAttribute("nextid", $nchild);
}
	

?>
