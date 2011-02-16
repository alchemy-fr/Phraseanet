<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "pid"
					, "t"
					, "k"
					, "sylng"
					, "reindex"		// '0' (non) ou '1' (oui = status '� r�indexer th�saurus = status &= ~2)
					, "debug"
				);

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
				if( ($domth = @DOMDocument::loadXML($rowbas["thesaurus"])) )
				{
					$xpathth = new DOMXPath($domth);
					if($parm["pid"] === "T")
						$q = "/thesaurus";
					else
						$q = "/thesaurus//te[@id='" . $parm["pid"] . "']";
					$parentnode = $xpathth->query($q)->item(0);
					if($parentnode)
					{
						$nid = $parentnode->getAttribute("nextid");
						$parentnode->setAttribute("nextid", (int)$nid + 1);
						$te = $parentnode->appendChild($domth->createElement("te"));
						if($parm["pid"] === "T")
							$te->setAttribute("id", $teid = "T" . ($nid));
						else
							$te->setAttribute("id", $teid = ($parm["pid"].".".$nid));
						$te->setAttribute("nextid", "1");
						$sy = $te->appendChild($domth->createElement("sy"));
						$sy->setAttribute("id", $teid.".0");
						if($parm["sylng"])
							$sy->setAttribute("lng", $parm["sylng"]);
						else
							$sy->setAttribute("lng", "");
							
						list($v, $k) = splitTermAndContext($parm["t"]);
						$k = trim($k).trim($parm["k"]);
						if($parm["debug"])
							printf("k='%s'<br/>\n", $k);
						$w = noaccent_utf8($v, PARSED);
						if($k)
							$v .= " (" . $k . ")";
						$k = noaccent_utf8($k, PARSED);
							
						$sy->setAttribute("v", $v);
						$sy->setAttribute("w", $w);
						if($parm["debug"])
							printf("v='%s' w='%s'<br/>\n", $v, $w);
						if($k)
						{
							$sy->setAttribute("k", $k);
							if($parm["debug"])
								printf("k='%s'<br/>\n", $k);
						}

						$domth->documentElement->setAttribute("modification_date", $now = date("YmdHis"));

						if($parm["debug"])
							print("<pre>" . $domth->saveXML() . "</pre>");

						$sql = "UPDATE pref SET value='" . $connbas->escape_string($domth->saveXML()) . "'";
						$sql .= ", updated_on='" .$connbas->escape_string($now). "'";
						$sql .= " WHERE prop='thesaurus'";

						if($parm["debug"])
							printf("sql: %s<br/>\n", $sql);
						else
							$connbas->query($sql);
							
						$cache_abox = cache_appbox::getInstance();
						$cache_abox->delete('thesaurus_'.$parm['bid']);
						
						if($parm["reindex"]=="1")
						{
							$sql = "UPDATE record SET status=status & ~2";

							if($parm["debug"])
								printf("sql: %s<br/>\n", $sql);
							else
								$connbas->query($sql);
						}

						$r = $refresh_list->appendChild($ret->createElement("refresh"));
						$r->setAttribute("type", "TH");
						$r->setAttribute("id", $parm["pid"]);
					}
				}
			}
			$connbas->free_result($rsbas);
		}
	}
}
if($parm["debug"])
	print("<pre>" . htmlentities($ret->saveXML()) . "</pre>");
else
	print($ret->saveXML());
	
				
function splitTermAndContext($word)
{
	$term = trim($word);
	$context = "";
	if(($po = strpos($term, "(")) !== false)
	{
		if(($pc = strpos($term, ")", $po)) !== false)
		{
			$context = trim(substr($term, $po+1, $pc-$po-1));
			$term = trim(substr($term, 0, $po));
		}
		
	}
	return(array($term, $context));
}


?>