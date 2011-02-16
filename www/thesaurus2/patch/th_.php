<?php
class patch_th_
{
	function patch($version, &$domct, &$domth, &$connbas)
	{
		if($version=="")
		{
			$th = $domth->documentElement;
			$ct = $domct->documentElement;
	
			$th->setAttribute("id", "0");
			
			$xp = new DOMXPath($domth);
			$te = $xp->query("/thesaurus/te");
			if($te->length > 0)
			{
				$te0 = $te->item(0);
				$th->setAttribute("nextid", $te0->getAttribute("nextid"));
				$te = $xp->query("te", $te0);
				$te1 = array();
				for($i=0; $i<$te->length; $i++)
				{
					$te1[] = $te->item($i);
				}
				foreach($te1 as $tei)
				{
					$th->appendChild($tei);
					$this->fixThesaurus2($domth, $tei);
					// $tei->parentNode->removeChild($tei);
				}
				$te0->parentNode->removeChild($te0);
			}
			$ct->setAttribute("version", $version="2.0.0");
			$th->setAttribute("version", "2.0.0");
			$th->setAttribute("creation_date", $now = date("YmdHis"));
			$th->setAttribute("modification_date", $now);
			$version = "2.0.0";
		}
		return($version);
	}
	
	function fixThesaurus2(&$domth, &$tenode, $depth=0)
	{
		$sy = $tenode->appendChild($domth->createElement("sy"));
		$sy->setAttribute("lng", $v = $tenode->getAttribute("lng"));
		$sy->setAttribute("v", $v = $tenode->getAttribute("v"));
		$sy->setAttribute("w", noaccent_utf8($v, PARSED));
		if(($k = $tenode->getAttribute("k")) != "")
			$sy->setAttribute("k", $k);
		$tenode->removeAttribute("lng");
		$tenode->removeAttribute("v");
		$tenode->removeAttribute("w");
		$tenode->removeAttribute("k");
		if($tenode->getAttribute("nextid")=="")
			$tenode->setAttribute("nextid", "0");
		// $tenode->setAttribute("id", "0.".$tenode->getAttribute("id"));
		$todel = array();
		for($n=$tenode->firstChild; $n; $n=$n->nextSibling)
		{
			if($n->nodeName=="ta")
				$todel[] = $n;
			if($n->nodeName=="te")
				$this->fixThesaurus2($domth, $n, $depth+1);
		}
		foreach($todel as $n)
		{
			$n->parentNode->removeChild($n);
		}
	}
}
?>