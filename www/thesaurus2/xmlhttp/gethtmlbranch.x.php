<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "id"
					, "typ"		// "TH" (thesaurus) ou "CT" (cterms)
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

// $html2 = new DOMDocument("1.0");

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
						
					$node = $xpath->query($q)->item(0);

					getHTML2($node, $ret, $html, 0);

					// getHTML3($node, $html2, $html2, 0);
					//$html_src = $root->appendChild($ret->createElement("html_src"));
					//$html_src->appendChild($ret->createTextNode( $html2->saveHTML() ));
					
					/*
					$html = getHTML($dom, $node, $parm["typ"]);
					{
						if($parm["debug"])
							print("<pre>" . $html->saveXML() . "</pre>");
					}
					*/
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
	
	
function getHTML(&$dom, &$node, $typ)
{
	$html = new DOMDocument("1.0", "UTF-8");
	$html->standalone = true;
	$html->preserveWhiteSpace = false;
	$root = $html->appendChild($html->createElement("body"));
	
	getHTML2($node, $html, $root, 0);
	
	// return($html);
}

function getHTML3($srcnode, $dstdom, $dstnode, $depth)
{
	// printf("in: depth:%s<br/>\n", $depth);
	
	$allsy = "";
	$nts = 0;
	for($n=$srcnode->firstChild; $n; $n=$n->nextSibling)
	{
		if($n->nodeName=="te" && $depth<10)
		{
			$nts++;
			
			$id = $n->getAttribute("id");
			$div_the = $dstnode->appendChild($dstdom->createElement("div"));
			$div_the->setAttribute("id", "THE_".$id);
			$div_the->setAttribute("class", "s_");
			
			$u = $div_the->appendChild($dstdom->createElement("u"));
			$u->setAttribute("id", "THP_".$id);
		
			$div_thb = $dstnode->appendChild($dstdom->createElement("div"));
			$div_thb->setAttribute("id", "THB_".$id);
	
			$t = getHTML3($n, $dstdom, $div_thb, $depth+1);
			if($t["nts"]==0)
			{
				$u->setAttribute("class", "nots");
				$div_thb->setAttribute("class", "ob");
			}
			else
			{
				$u->appendChild($dstdom->createTextNode("-"));
				$div_thb->setAttribute("class", "OB");
			}
			
			$div_the->appendChild($dstdom->createTextNode($t["allsy"]));
			
			//if(!$div_thb->firstChild)
			//	$div_thb->appendChild($dstdom->createTextNode("-"));
		}
		elseif($n->nodeName=="sy")
			$allsy .= ($allsy?" ; ":"") . $n->getAttribute("v");
	}
	if($allsy=="")
		$allsy = "THESAURUS";
	return(array("allsy"=>$allsy, "nts"=>$nts));
	// printf("out: depth:%s<br/>\n", $depth);
	// return($depth==0 ? $div_thb : null);
}

function getHTML2($srcnode, $dstdom, $dstnode, $depth)
{
	// printf("in: depth:%s<br/>\n", $depth);
	
	$allsy = "";
	$nts = 0;
	for($n=$srcnode->firstChild; $n; $n=$n->nextSibling)
	{
		if($n->nodeName=="te" && $depth<100)
		{
			$nts++;
			
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
				$u->appendChild($dstdom->createTextNode("-"));
				$div_thb->setAttribute("class", "OB");
			}
			
			$div_the->appendChild($dstdom->createTextNode($t["allsy"]));
			
			//if(!$div_thb->firstChild)
			//	$div_thb->appendChild($dstdom->createTextNode("-"));
		}
		elseif($n->nodeName=="sy")
			$allsy .= ($allsy?" ; ":"") . $n->getAttribute("v");
	}
	if($allsy=="")
		$allsy = "THESAURUS";
	return(array("allsy"=>$allsy, "nts"=>$nts));
	// printf("out: depth:%s<br/>\n", $depth);
	// return($depth==0 ? $div_thb : null);
}
?>

















