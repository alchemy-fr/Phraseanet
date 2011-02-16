<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "id"
					, "typ"		// "TH" (thesaurus) ou "CT" (cterms)
					, "t"
					, "method"	// "equal", "begins", "contains"
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
	// header("Content-Type: application/xhtml+xml; charset=UTF-8");
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
$html = $root->appendChild($ret->createElement("html"));

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
		
		$sql = "SELECT value AS xml FROM pref WHERE prop='".$xqroot."'";
		
		if($rsbas = $connbas->query($sql))
		{
			if($rowbas = $connbas->fetch_assoc($rsbas))
			{
				$xml = trim($rowbas["xml"]);
				
				if(($dom = @DOMDocument::loadXML($xml)))
				{
					$xpath = new DOMXPath($dom);
					if($parm["id"] == "T")
						$q = "/thesaurus";
					elseif($parm["id"] == "C")
						$q = "/cterms";
					else
						$q = "/$xqroot//te[@id='".$parm["id"]."']";
						
					if($parm["debug"])
						print("q:".$q."<br/>\n");
					if( ($znode = $xpath->query($q)->item(0)) )
					{
						if($parm["t"])
						{
							$t = splitTermAndContext($parm["t"]);
					//		$q2 = "@w='" . thesaurus::xquery_escape(noaccent_utf8($t[0], PARSED)) . "'";
							switch($parm["method"])
							{
								case "begins":
									$q2 = "starts-with(@w, '" . thesaurus::xquery_escape(noaccent_utf8($t[0], PARSED)) . "')";
									if($t[1])
										$q2 .= " and starts-with(@k, '" . thesaurus::xquery_escape(noaccent_utf8($t[1], PARSED)) . "')";
									break;
								case "contains":
									$q2 = "contains(@w, '" . thesaurus::xquery_escape(noaccent_utf8($t[0], PARSED)) . "')";
									if($t[1])
										$q2 .= " and contains(@k, '" . thesaurus::xquery_escape(noaccent_utf8($t[1], PARSED)) . "')";
									break;
								case "equal":
								default:
									$q2 = "(@w='" . thesaurus::xquery_escape(noaccent_utf8($t[0], PARSED)) . "')";
									if($t[1])
										$q2 .= " and (@k='" . thesaurus::xquery_escape(noaccent_utf8($t[1], PARSED)) . "')";
									break;
							}
							$q2 = "//sy[" . $q2 . "]";
						}
						if($parm["debug"])
							print("q2:".$q2."<br/>\n");
							
						$nodes = $xpath->query($q2, $znode);
						for($i=0; $i<$nodes->length; $i++)
						{
							for($n=$nodes->item($i)->parentNode; $n && $n->nodeType==XML_ELEMENT_NODE && $n->nodeName=="te"; $n=$n->parentNode)
							{
								$n->setAttribute("open", "1");
								if($parm["debug"])
									printf("opening node te id=%s<br/>\n", $n->getAttribute("id"));
							}
						}

						getHTML2($znode, $ret, $html, 0);
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
	
	

function getHTML2($srcnode, $dstdom, $dstnode, $depth)
{
  global $parm;
	// printf("in: depth:%s<br/>\n", $depth);
	
	$allsy = "";
	$nts = 0;
	for($n=$srcnode->firstChild; $n; $n=$n->nextSibling)
	{
		if($n->nodeType==XML_ELEMENT_NODE)
		{
			if($n->nodeName=="te")
			{
				$nts++;
				if($n->getAttribute("open"))
				{
					$id = $n->getAttribute("id");
					$div_the = $dstnode->appendChild($dstdom->createElement("div"));
					$div_the->setAttribute("id", "THE_".$id);
					$div_the->setAttribute("class", "s_");
					
					$u = $div_the->appendChild($dstdom->createElement("u"));
					$u->setAttribute("id", "THP_".$id);
				
					$div_thb = $dstnode->appendChild($dstdom->createElement("div"));
					$div_thb->setAttribute("id", "THB_".$id);
			
					$t = getHTML2($n, $dstdom, $div_thb, $depth+1);
					if($t["nts"]==0)
					{
						$u->setAttribute("class", "nots");
						$div_thb->setAttribute("class", "ob");
					}
					else
					{
						$u->appendChild($dstdom->createTextNode("..."));
						$div_thb->setAttribute("class", "hb");
					}
					
					$div_the->appendChild($dstdom->createTextNode($t["allsy"]));
					
					if($parm["debug"])
						printf("explored node te id=%s : nts=%s<br/>\n", $n->getAttribute("id"), $t["nts"]);
				}
			}
			elseif($n->nodeName=="sy")
			{
				$t = $n->getAttribute("v");
				if($k = $n->getAttribute("k"))
				{
	//				$t .= " ($k)";
				}
				$allsy .= ($allsy?" ; ":"") . $t;
			}
		}
	}
	if($allsy=="")
	{
		if($parm["typ"]=="TH")
			$allsy = "THESAURUS";
		elseif($parm["typ"]=="CT")
			$allsy = $srcnode->getAttribute("field");
	}
	return(array("allsy"=>$allsy, "nts"=>$nts));
	// printf("out: depth:%s<br/>\n", $depth);
	// return($depth==0 ? $div_thb : null);
}


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

















