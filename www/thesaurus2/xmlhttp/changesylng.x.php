<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );

require("../xmlhttp.php");


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "id"
					, "typ"		// "TH" (thesaurus) ou "CT" (cterms)
					, "piv"		// lng de consultation (pivot)
					, "newlng"	// nouveau lng du sy
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
if($parm["bid"] !== null)
{			
	$loaded = false;
	
	$connbas = connection::getInstance($parm['bid']);
	
	if($connbas)
	{
		if($parm["typ"]=="CT")
			$xqroot = "cterms";
		else
			$xqroot = "thesaurus";
		$sql = "SELECT value AS xml FROM pref WHERE prop='".$connbas->escape_string($xqroot)."'";
		if($rsbas = $connbas->query($sql))
		{
			if($rowbas = $connbas->fetch_assoc($rsbas))
			{
				$xml = trim($rowbas["xml"]);
				
				if(($dom = @DOMDocument::loadXML($xml)))
				{
					$xpath = new DOMXPath($dom);
					$q = "/$xqroot//sy[@id='".$parm["id"]."']";
					if($parm["debug"])
						print("q:".$q."<br/>\n");
						
					$sy0 = $xpath->query($q)->item(0);
					if($sy0)
					{
						$sy0->setAttribute("lng", $parm["newlng"]);
						$dom->documentElement->setAttribute("modification_date", $now = date("YmdHis"));
						$sql  = "UPDATE pref SET value='" . $connbas->escape_string($dom->saveXML()) . "'" ;
						$sql .= ", updated_on='" .$connbas->escape_string($now). "'";
						$sql .= " WHERE prop='".$xqroot."'";

						if($parm["debug"])
							;	// printf("sql: %s<br/>\n", $sql);
						else
							$connbas->query($sql);
							
						if($xqroot == 'thesaurus')
						{
							$cache_abox = cache_appbox::getInstance();
							$cache_abox->delete('thesaurus_'.$parm['bid']);
						}
							
						$url = "./getterm.x.php";
						$url .= "?bid=" . urlencode($parm["bid"]);
						$url .= "&typ=" . urlencode($parm["typ"]);
						$url .= "&piv=" . urlencode($parm["piv"]);
						$url .= "&id="  . urlencode($sy0->parentNode->getAttribute("id"));
						$url .= "&sel=" . urlencode($parm["id"]);
						$url .= "&nots=1";		// liste des ts inutile
						if($parm["debug"])
						{
							printf("url: %s<br/>\n", $url);
						//	printf("<pre>" . htmlentities($gt->saveXML()) . "</pre>");
						}
						$ret = xmlhttp($url);	// �crase le ret inital !
						$root = $ret->getElementsByTagName("result")->item(0);
						$refresh_list = $root->appendChild($ret->createElement("refresh_list"));
						$r = $refresh_list->appendChild($ret->createElement("refresh"));
					//	$r->setAttribute("id", $parm["id"]);
						$r->setAttribute("id", $sy0->parentNode->parentNode->getAttribute("id"));
						$r->setAttribute("type", $parm["typ"]);
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
?>