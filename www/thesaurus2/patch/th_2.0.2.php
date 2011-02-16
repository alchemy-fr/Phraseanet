<?php
class patch_th_2_0_2
{
	function patch($version, &$domct, &$domth, &$connbas)
	{
		if($version == "2.0.2")
		{
			$th = $domth->documentElement;
			$ct = $domct->documentElement;
	
			$sql = "ALTER TABLE `pref` ADD `cterms_moddate` DATETIME";
			$connbas->query($sql);
			$sql = "ALTER TABLE `pref` ADD `thesaurus_moddate` DATETIME";
			$connbas->query($sql);
			$sql = "UPDATE pref SET thesaurus_moddate='" . $th->getAttribute("modification_date") . "', cterms_moddate='" . $ct->getAttribute("modification_date") . "'";		
			$connbas->query($sql);
			
			$ct->setAttribute("version", $version="2.0.3");
			$th->setAttribute("version", $version="2.0.3");
			$th->setAttribute("modification_date", date("YmdHis"));
			
			$version = "2.0.3";
		}
		return($version);
	}
}
?>