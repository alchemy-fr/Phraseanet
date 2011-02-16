<?php
class patch_th_2_0_1
{
	function patch($version, &$domct, &$domth, &$connbas)
	{
		if($version == "2.0.1")
		{
			$th = $domth->documentElement;
			$ct = $domct->documentElement;
			
			$xp = new DOMXPath($domth);
			$te = $xp->query("/thesaurus//te");
			for($i=0; $i<$te->length; $i++)
			{
				// $id  = "S" . substr($te->item($i)->getAttribute("id"), 1);
				$id  = $te->item($i)->getAttribute("id");
				$nid = (int)($te->item($i)->getAttribute("nextid"));
				for($n=$te->item($i)->firstChild; $n; $n=$n->nextSibling)
				{
					if($n->nodeName=="sy")
					{
						$n->setAttribute("id", $id . "." . $nid);
						$te->item($i)->setAttribute("nextid", ++$nid);
					}
				}
			}
			
			$sql = "UPDATE record SET status=status & ~2";
			$connbas->query($sql);
			$sql = "DELETE FROM thit";
			$connbas->query($sql);
			
			// if( !($domct = @DOMDocument::load("./blank_cterms.xml")))	// loaded from thesaurus2
				$domct = @DOMDocument::load("../thesaurus2/blank_cterms.xml");
			$ct = $domct->documentElement;
			$ct->setAttribute("creation_date", $now = date("YmdHis"));
			$ct->setAttribute("modification_date", $now);
			
			$ct->setAttribute("version", $version="2.0.2");
			$th->setAttribute("version", $version="2.0.2");
			$th->setAttribute("modification_date", date("YmdHis"));
			$version = "2.0.2";
		}
		return($version);
	}
}
?>