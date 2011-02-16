<?php
function xmlhttp($url)
{
	$fullurl = GV_ServerName . "thesaurus2/" . $url;
	if(!($xml = file_get_contents($fullurl)))
	{
		$ch = curl_init($fullurl);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER , true);
		$xml = curl_exec($ch);
		curl_close($ch);
	}
	$ret = new DOMDocument();
	// printf("<pre>%s</pre>\n", htmlentities($xml));
	$ret->loadXML($xml);
	return($ret);
}
function indentXML(&$dom)
{
	indentXML2($dom, $dom->documentElement, 0, 0);
}
function indentXML2(&$dom, $node, $depth, $ichild)
{
	$tab = str_repeat("\t", $depth);
	$fc = null;
	if($node->nodeType==XML_ELEMENT_NODE)
	{
		if($ichild==0)
			$node->parentNode->insertBefore($dom->createTextNode($tab), $node);
		else
			$node->parentNode->insertBefore($dom->createTextNode("\n".$tab), $node);
		$fc = $node->firstChild;
		if($fc)
		{
			if($fc->nodeType==XML_TEXT_NODE && !$fc->nextSibling)
			{
				
			}
			else
			{
				$node->insertBefore($dom->createTextNode("\n"), $fc);	
				for($i=0,$n=$fc; $n; $n=$n->nextSibling,$i++)
				{
					indentXML2($dom, $n, $depth+1,$i);
				}
				$node->appendChild($dom->createTextNode("\n".$tab));
			}
		}
	}
	elseif($node->nodeType==XML_TEXT_NODE)
	{
		$node->parentNode->insertBefore($dom->createTextNode($tab), $node);
	}
}
?>
