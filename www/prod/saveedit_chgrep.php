<?php
	list($grpbid, $grprid) = explode('_' , $parm['regbasprid']);		// the rep. record 
	$imgrid = $parm['newrepresent'];							// the record FROM we copy image
	
	##                                                  ## 
	## On CHANGE l'image representative du regroupement ##
	##                                                  ## 
	
	$connbas = connection::getInstance($parm['sbid']);
	
	if($connbas)
	{
		// On detruit les anciennes subdefs du regroupement
		$oldsubdef_rep = phrasea_subdefs($ses_id, $grpbid, $grprid);
		foreach($oldsubdef_rep as $name=>$value)
		{
			@unlink(p4string::addEndSlash($value['path']).$value['file']);
//			printf("deleting %s \n", p4string::addEndSlash($value['path']).$value['file']);
		}
			
		// on vide les entrees de subdef
		$sql = 'DELETE FROM subdef WHERE record_id="'.$connbas->escape_string($grprid).'"';
		$connbas->query($sql);
		
		// on copie les subdefs du record choisi
		$filesToSet  = array();		// les fichiers ou reclaquer l'iptc
		$newsubdef_reg = phrasea_subdefs($ses_id, $grpbid, $imgrid);
		foreach($newsubdef_reg as $name=>$value)
		{

			// on remplace le record_id dans le 'file' d'origine
			$pi = pathinfo($value['file']);
			$newfilename = $grprid . '_0_' . $name . '.' . $pi['extension'] ;
			
			$path_file = p4string::addEndSlash($value['path']).$newfilename;
			
			// on duplique le fichier
//			printf("copy('%s' , '%s') \n", p4string::addEndSlash($value['path']).$value['file'] , p4string::addEndSlash($value['path']).$newfilename);
			if( @copy(p4string::addEndSlash($value['path']).$value['file'], $path_file) )
			{
				if($name=='document' || $name=='preview')
					$filesToSet[] = $path_file;	// pour iptc
					
				p4::chmod($path_file);
					
				// on complete le sql avec le resultat de phrasea_subdef corrige
				$value['file'] = $newfilename;	// correction, le reste inchange (path, width...)

				$sql = 'INSERT INTO subdef' .
					' (record_id, name, baseurl, file, width, height, mime, path, size, substit) VALUES ' .
					'("'.$connbas->escape_string($grprid).'","'.$connbas->escape_string($name).'","'.$connbas->escape_string($value['baseurl']).'",' .
					'"'.$connbas->escape_string($value['file']).'","'.$connbas->escape_string($value['width']).'","'.$connbas->escape_string($value['height']).'",' .
					'"'.$connbas->escape_string($value['mime']).'","'.$connbas->escape_string($value['path']).'","'.$connbas->escape_string($value['size']).'",' .
					'"'.$connbas->escape_string($value['substit']).'")';
				
				if($connbas->query($sql))
				{
					$cache_record = cache_thumbnail::getInstance();
					$cache_record->delete($parm['sbid'],$grprid);
				}
			}
		}
	
		answer::writeIPTC($parm['sbid'], phrasea_xmlcaption($ses_id, $grpbid, $grprid), $filesToSet );
	}
	