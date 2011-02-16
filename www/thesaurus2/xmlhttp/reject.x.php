<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );

require("../xmlhttp.php");


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "id"
					// , "typ"		// "TH" (thesaurus) ou "CT" (cterms)
					, "piv"		// lng de consultation (pivot)
					// , "newlng"	// nouveau lng du sy
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
		$sql = "SELECT value AS xml FROM pref WHERE prop='cterms'";
		if($rsbas = $connbas->query($sql))
		{
			if($rowbas = $connbas->fetch_assoc($rsbas))
			{
				$xml = trim($rowbas["xml"]);
				
				if(($dom = @DOMDocument::loadXML($xml)))
				{
					$xpath = new DOMXPath($dom);
					$q = "/cterms//te[@id='".$parm["id"]."']";
					if($parm["debug"])
						print("q:".$q."<br/>\n");
						
					$te = $xpath->query($q)->item(0);
					if($te)
					{
						if($parm["debug"])
							printf("found te : id=%s<br/>\n", $te->getAttribute("id"));
							
						rejectBranch($connbas, $te);	
							
					//	$te->setAttribute("id", "R".substr($te->getAttribute("id"), 1));
						$dom->documentElement->setAttribute("modification_date", $now = date("YmdHis"));

						$sql  = "UPDATE pref SET value='" . $connbas->escape_string($dom->saveXML()) . "'" ;
						$sql .= ", updated_on='" .$connbas->escape_string($now). "'";
						$sql .= " WHERE prop='cterms'";

						if($parm["debug"])
							printf("sql: %s<br/>\n", $sql);
						else
							$connbas->query($sql);

						$r = $refresh_list->appendChild($ret->createElement("refresh"));
					//	$r->setAttribute("id", $parm["id"]);
						$r->setAttribute("id", $te->parentNode->getAttribute("id"));
						$r->setAttribute("type", "CT");
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
	
function rejectBranch(&$connbas, &$node)
{
	global $parm;
	if( strlen($oldid = $node->getAttribute("id")) > 1 )
	{
		$node->setAttribute( "id", $newid=("R".substr($oldid,1)) );
		
		$thit_oldid = str_replace(".", "d", $oldid)."d";
		$thit_newid = str_replace(".", "d", $newid)."d";
		$sql = "UPDATE thit SET value='$thit_newid' WHERE value='$thit_oldid'";
		if($parm["debug"])
			printf("sql: %s<br/>\n", $sql);
		else
			$connbas->query($sql);
	}
	for($n=$node->firstChild; $n; $n=$n->nextSibling)
	{
		if($n->nodeType==XML_ELEMENT_NODE)
			rejectBranch($connbas, $n);
	}
}
?>