<?php
class patch_th_2_0_0
{
	function patch($version, &$domct, &$domth, &$connbas)
	{
		if($version == "2.0.0")
		{
			$th = $domth->documentElement;
			$ct = $domct->documentElement;
			
			$xp = new DOMXPath($domth);
	
			$te = $xp->query("/thesaurus//te");
			for($i=0; $i<$te->length; $i++)
			{
				$id = $te->item($i)->getAttribute("id");
				if($id[0]>="0" && $id[0]<="9")
					$te->item($i)->setAttribute("id", "T".$id);
			}
			$ct->setAttribute("version", $version="2.0.1");
			$th->setAttribute("version", $version="2.0.1");
			$th->setAttribute("modification_date", date("YmdHis"));
			$sql = "UPDATE thit SET value=CONCAT('T',value) WHERE LEFT(value,1)>='0' AND LEFT(value,1)<='9'";
			$connbas->query($sql);
			$version = "2.0.1";
		}
		return($version);
	}
}
?>