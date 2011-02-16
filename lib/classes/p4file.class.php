<?php
class p4file
{
	
	public static function apache_tokenize($file)
	{
		
		$ret = false;
		
		if(GV_h264_streaming && is_file($file))
		{
			if(($pos = mb_strpos($file,GV_mod_auth_token_directory_path)) === false)
			{
				return false;
			}
			
			$server = new server();
			
			if($server->is_nginx())
			{
				$fileToProtect = mb_substr($file, mb_strlen(GV_mod_auth_token_directory_path));
				
				$secret = GV_mod_auth_token_passphrase;
				$protectedPath = p4string::addFirstSlash(p4string::delEndSlash(GV_mod_auth_token_directory));
				
				$hexTime = strtoupper(dechex(time()+3600));
				
				$token = md5($protectedPath . $fileToProtect.'/'.$secret.'/'.$hexTime);

				$url = $protectedPath . $fileToProtect.'/'. $token. '/' . $hexTime;

				$ret = $url;
			}
			elseif($server->is_apache())
			{
				$fileToProtect = mb_substr($file, mb_strlen(GV_mod_auth_token_directory_path));
				
				
				$secret = GV_mod_auth_token_passphrase;        // Same as AuthTokenSecret
				$protectedPath = p4string::addEndSlash(p4string::delFirstSlash(GV_mod_auth_token_directory));         // Same as AuthTokenPrefix
				$hexTime = dechex(time());             // Time in Hexadecimal      
				
				$token = md5($secret . $fileToProtect. $hexTime);
				
				// We build the url
				$url = '/'.$protectedPath . $token. "/" . $hexTime . $fileToProtect;
		
				
				$ret = $url;
			}
		}
		return $ret;
	}
	
	public static function archiveFile($filename,$base_id,$delete=true,$name=false)
	{
		require_once GV_RootPath.'lib/index_utils2.php';
		
		if(!is_file($filename))
		{
			if(GV_debug)
				echo "\n Le fichier n'existe pas";
			return false;
		}	
		$conn = connection::getInstance();
		$session = session::getInstance();
		
		$mimeExt = giveMimeExt($filename);

		$ext = pathinfo($filename);
		
		$tfile = array(
				'recordid' => NULL,
				'extension'=>isset($ext['extension'])?$ext['extension']:'',
				'mime' => $mimeExt['mime'],
				'size' => filesize($filename),
				'hotfoldercaptionfile' => NULL,
				'hotfolderfile' => $filename,
				'pathhd' => '',
				'file' => '',
				'originalname'=> $name ? $name : basename($_FILES['Filedata']["name"]),
				'inbase' => NULL,
				'type' => giveMeDocType($mimeExt['mime']),
				'parentdirectory' => '',
				'subpath' => '',
				
				'width' => 0,
				'height' => 0,
	
				"error"=> 0
			);
		
		$go = false;
		$sql = "SELECT base_id FROM basusr WHERE base_id='".$conn->escape_string($base_id)."' AND usr_id='".$conn->escape_string($session->usr_id)."' AND canaddrecord='1'";	
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$go = true;
			}
		}
		
		if(!$go)
		{
			echo "\ndroits insuffisants";
			return false;
		}
		
		
		$server_coll_id = $sbas_id = $server_coll_id = $xmlprefs = false;
			
			
		$sql = "SELECT server_coll_id, bas.sbas_id FROM (bas INNER JOIN sbas ON sbas.sbas_id=bas.sbas_id AND base_id=" . $conn->escape_string($base_id) . ")";
	
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$sbas_id = $row['sbas_id'];
				$connbas = connection::getInstance($row['sbas_id']);
				$server_coll_id = $row["server_coll_id"];
			}
			$conn->free_result($rs);
		}
		
		if(!$connbas)
		{
			if(GV_debug)
				echo "\n Erreur connection";
			return false;
		}	
		$sql = "SELECT value AS baseprefs, prefs AS collprefs FROM pref p, coll c WHERE prop='structure' AND coll_id='" . $connbas->escape_string($server_coll_id)."'";
		if($rs = $connbas->query($sql))
		{
			$xmlprefs = $connbas->fetch_assoc($rs);
			$connbas->free_result($rs);
		}
		
		
		if(($sxBasePrefs = simplexml_load_string($xmlprefs["baseprefs"])) === false)
		{
			if(GV_debug)
				echo 'Error loading baseprefs';
			return false;
		}
		if(($collprefs = simplexml_load_string($xmlprefs["collprefs"])) === false)
		{
			if(GV_debug)
				echo 'Error loading collprefs';
			return false;
		}
					
		$pathhd = p4string::addEndSlash(p4::dispatch(trim((string)($sxBasePrefs->path))));
		
		if(!is_dir($pathhd) || !is_writeable($pathhd))
		{
			if(GV_debug)
				echo 'erreur de repertoire de destination';
			return false;		
		}
					
		if(! ($record_id = $connbas->getId("RECORD")))
		{
			if(GV_debug)
				echo 'upload::error: erreur lors de la reservation des id pour les fichiers';
			return false;
		}
		$tfile['recordid'] = $record_id;
		$tfile['pathhd'] = $pathhd;

		if(($xml = get_xml($sxBasePrefs, $tfile, false)) === false)
		{
			if(GV_debug)
				echo 'erreur de lecture xml';
			return false;
		}
		
		if($xml===false || !isset($xml["xml"]))
		{
			if(GV_debug)
				echo 'erreur xml';
			return false;
		}
		
		$status = "0";
		
		if($collprefs->status)
			$status = (string)($collprefs->status);
		
		$file_uuid = new uuid($filename);
		$uuid = $file_uuid->check_uuid();
		
		
		$sql = 'INSERT INTO record (coll_id, record_id, status, jeton, moddate, credate, xml, type, sha256, uuid) 
				VALUES ("'.$connbas->escape_string($server_coll_id) . '", "' .$connbas->escape_string($record_id) . '", 
					(' . $status . ' | 0x0F), 0x0, NOW(), NOW(), \'' . $connbas->escape_string($xml["xml"]->saveXML()) . '\', 
					\''.$connbas->escape_string($tfile["type"]).'\', \''.$connbas->escape_string(hash_file('sha256',$filename)).'\', 
					"'.$connbas->escape_string($uuid).'")';
		
		if(!$connbas->query($sql))
		{
			if(GV_debug)
				echo 'upload::error: erreur lors de linsert sql';
			return false;
		}
		
		$newname = $record_id . "_document.".$tfile["extension"];
		if(!copy($filename, $tfile["pathhd"] . $newname))
		{
			if(GV_debug)
				echo "\n Erreur lors de la copie";
			
			$sql = "DELETE FROM record WHERE record_id = '".$connbas->escape_string($record_id)."'" ;
			$connbas->query($sql);
			return false;
		}
		
		$tfile['file'] = $newname;
		$tfile['inbase'] = 1;
		
		$fl  = "record_id, name, path, file, baseurl, inbase, width, height, mime, size, dispatched";
		$vl  = "'".$connbas->escape_string($record_id)."'";
		$vl .= ", 'document'";
		$vl .= ", '" . $connbas->escape_string($tfile['pathhd']) . "'";
		$vl .= ", '" . $connbas->escape_string($tfile['file']) . "'";
		$vl .= ", ''";
		$vl .= ", '" . $connbas->escape_string($tfile['inbase']) . "'";
		$vl .= ", '" . $connbas->escape_string($tfile['width']) . "'";
		$vl .= ", '" . $connbas->escape_string($tfile['height']) . "'";
		$vl .= ", '" . $connbas->escape_string($tfile['mime']) . "'";
		$vl .= ", '" . $connbas->escape_string($tfile['size']) . "'";
		$vl .= ", '1'";
		$sql = "INSERT INTO subdef (".$fl.") VALUES (".$vl.")";

		if(!$connbas->query($sql))
		{
			if(GV_debug)
				echo 'erreur lors de l\'insert du document dans la base';
			return false;
		}
		
		if(isset($session->logs[$sbas_id]))
		{
			$sql = 'INSERT INTO log_docs (id, log_id, date, record_id, action, final, comment) VALUES (null, "'.$connbas->escape_string($session->logs[$sbas_id]).'", now(), "'.$connbas->escape_string($record_id).'", "add", "'.$connbas->escape_string($server_coll_id).'","")';
			$connbas->query($sql);
		}
							
		$sql = 'UPDATE record SET status=status | 0x04, jeton=jeton | '.JETON_READ_META_DOC_MAKE_SUBDEF.' WHERE record_id="'.$connbas->escape_string($record_id). '"';
		if(!$connbas->query($sql))
		{
			if(GV_debug)
				echo 'erreur update finale';
			return false;
		}		
		
		if($delete)
			@unlink($filename);

// prevent building subdefs before reading metadatas
//		record::rebuild_subdef($base_id.'_'.$record_id);
		
		return $record_id;
	}
	
	
	public static function check_file_error($filename, $sbas_id)
	{
		require_once GV_RootPath.'lib/index_utils2.php';
		$infos = giveMimeExt($filename);
		
		$doctype = giveMeDocType($infos['mime']);
		
		if($baseprefs = databox::get_sxml_structure($sbas_id)  )
		{
			$file_checks = $baseprefs->filechecks;
		}
		else
			throw new Exception(_('prod::erreur : impossible de lire les preferences de base'));

		
		$errors = array();
		
		$datas = exiftool::get_fields($filename, array('Color Space Data', 'Color Space', 'Color Mode', 'Image Width', 'Image Height'));
		
		if($checks = $file_checks->$doctype)
		{
			foreach($checks[0] as $name=>$value)
			{
				switch($name)
				{
					case 'size':
						$min = min($datas['Image Height'], $datas['Image Width']);
						if($min < (int)$value)
						{
							$errors[] = sprintf(_('Taille trop petite : %dpx'), $min);
						}
						break;
					case 'color_space':
						$required = in_array($value, array('sRGB','RGB')) ? 'RGB' : $value;
						$go = false;
						
						$results = array($datas['Color Space'], $datas['Color Space Data'], $datas['Color Mode']);
						
						$results_str = implode(' ', $results);
						
						if(trim($results_str) === '')
						{
							$go = true;
						}
						else 
						{
							if($required == 'RGB' && count(array_intersect($results, array('sRGB','RGB'))) > 0)
							{
								$go = true;
							}
							elseif(in_array($required, $results))
							{
								$go = true;
							}
						}
						
						
						if( !$go )
						{
							$errors[] = sprintf(_('Mauvais mode colorimetrique : %s'), $results_str);
						}
						
						break;
				}
			}
		}
		
		return $errors;
	}
	
	
	public static function substitute($base_id, $record_id, $new_pathfile, $filename, $update_filename = false)
	{
		require_once GV_RootPath.'lib/index_utils2.php';
		
		$sbas_id =phrasea::sbasFromBas($base_id); 
		$connbas = connection::getInstance($sbas_id);
		$session = session::getInstance();
		
		
		if( $baseprefs = databox::get_sxml_structure($sbas_id)  )
		{
			$pathhd = p4string::addEndSlash((string)($baseprefs->path));
			$baseurl = (string)($baseprefs->baseurl);
		}
		else
			throw new Exception(_('prod::erreur : impossible de lire les preferences de base'));

		if(trim($pathhd)=="" || !is_dir($pathhd) )
			throw new Exception(_('prod::substitution::erreur : impossible d\'acceder au dossier de stockage "'.$pathhd.'" '));
		
		$sd = phrasea_subdefs($session->ses_id, $base_id, $record_id, "document");
		
		if(isset($sd) && isset($sd["document"]))
		{
			$sd["document"]["path"] = p4string::addEndSlash($sd["document"]["path"]);
			
			$pathhd = p4string::addEndSlash($sd["document"]["path"]);
			$filehd = $sd["document"]["file"];
			
			$pathfile = $pathhd.$filehd;
			
			if( file_exists($pathfile) && !is_dir($pathfile))
			{
				if(!@unlink($pathfile) )
					throw new Exception(_('prod::substitution::erreur : impossible de supprimer l\'ancien document'));
			}
		}
		else
		{
			$ext = "" ;
			$pitmp = pathinfo($filename);
			if(isset($pitmp['extension']))
				$ext = $pitmp['extension'];
			$filehd = $record_id . "_document.".$ext;
			$pathhd = p4::dispatch($pathhd);
			
			$pathfile = $pathhd.$filehd;
		}

		$sql = "DELETE FROM subdef WHERE record_id='".$connbas->escape_string($record_id)."' AND name='document'";
		$connbas->query($sql);
		
		$width = $height = 0 ;
		
		if(!rename($new_pathfile, $pathfile ) )
			throw new Exception(_('prod::substitution::document remplace avec succes'));
			
		if($tempHD = getimagesize($pathfile) )
		{
			$width  = $tempHD[0];
			$height = $tempHD[1];
		}
		
		$mimeExt = giveMimeExt($pathfile);
		
		$sql = "INSERT INTO subdef (record_id, name, path, file, baseurl, inbase, width, height, mime, size) VALUES
				('".$connbas->escape_string($record_id)."', 'document', '".$connbas->escape_string($pathhd)."', 
					'".$connbas->escape_string($filehd)."' , '".$connbas->escape_string($baseurl)."','1', 
					'".$connbas->escape_string($width)."'  ,  '".$connbas->escape_string($height)."' , 
					'".$connbas->escape_string($mimeExt['mime'])."', '".$connbas->escape_string(filesize($pathfile))."')";
		
		if($connbas->query($sql))
		{
			answer::logEvent($sbas_id,$record_id,'substit','HD','');
		}
		else
			throw new Exception(_('Erreur lors de l\'enregistrement en base'));
		
		$desc = phrasea_xmlcaption($session->ses_id, $base_id, $record_id);
		
		$doc = new DOMDocument();
		$doc->loadXML($desc);
		$params = $doc->getElementsByTagName('doc');
		$newname = null;
		
		foreach ($params as $param)
		{
			$oldname = $param->getAttribute('originalname'); 
			if($oldname)
			{
				$oldpi = pathinfo($oldname);
				$val = "";
				$pi = pathinfo($filename);
				if(isset($pi["extension"]))
					$val = $pi["extension"];
				
				if($update_filename)	
					$newname = $pi['basename'];
				else				
					$newname = $oldpi['filename'].".".$val;
				
			} 
			if($newname)
				$param->setAttribute('originalname',$newname);
			$param->removeAttribute('channels');
			$param->removeAttribute('bits');
			
			$param->setAttribute('height',$height);
			$param->setAttribute('width',$width);
			$param->setAttribute('size',filesize($pathfile));
			$param->setAttribute('mime',$mimeExt['mime'] );
			if(isset($tempHD["bits"]))
				$param->setAttribute('bits',$tempHD["bits"] );			
			if(isset($tempHD["channels"]))
				$param->setAttribute('channels',$tempHD["channels"] );
		}
		
		$xp_rec = new DOMXPath($doc);
		$sx_struct = databox::get_sxml_structure($sbas_id);
		foreach($sx_struct->description->children() as $fn=>$fv)
		{
			if( isset($fv["src"]) && (substr($fv["src"],0,2)=="tf") )
			{	
				switch($fv["src"])
				{
					case "tf-chgdocdate":
						if($recdesc = $xp_rec->query("/record/description/$fn"))
						{							
							if($recdesc->item(0))
								$recdesc->item(0)->nodeValue = date('Y/m/d H:i:s', time());
							else
							{
								$recdesc = $xp_rec->query("/record/description")->item(0);
								$node = $doc->createElement($fn);
								$node->nodeValue = 	date('Y/m/d H:i:s', time());
								$newnode = $recdesc->appendChild($node);
							}
						}
					break;
					
					case "tf-filename":
						if($newname && ($recdesc = $xp_rec->query("/record/description/$fn")))
						{							
							if($recdesc->item(0))
								$recdesc->item(0)->nodeValue = $newname;
							else
							{
								$recdesc = $xp_rec->query("/record/description")->item(0);
								$node = $doc->createElement($fn);
								$node->nodeValue = 	$newname;
								$newnode = $recdesc->appendChild($node);
							}
						}
					break;
					
					case "tf-extension":
						if($recdesc = $xp_rec->query("/record/description/$fn"))
						{
							$val = "";
							$pi=pathinfo($filename);
							if(isset($pi["extension"]))
								$val = $pi["extension"];
							if($recdesc->item(0))
								$recdesc->item(0)->nodeValue = $val;
							else
							{
								$recdesc = $xp_rec->query("/record/description")->item(0);
								$node = $doc->createElement($fn);
								$node->nodeValue = 	$val;
								$newnode = $recdesc->appendChild($node);
							}
						}
					break;
					
					case "tf-mimetype":
						if($recdesc = $xp_rec->query("/record/description/$fn"))
						{
							$val = $mimeExt['mime'] ;
							if($recdesc->item(0))
								$recdesc->item(0)->nodeValue = $val;
							else
							{
								$recdesc = $xp_rec->query("/record/description")->item(0);
								$node = $doc->createElement($fn);
								$node->nodeValue = 	$val;
								$newnode = $recdesc->appendChild($node);
							}
						}
						
					break;
					
					case "tf-size":
						if($recdesc = $xp_rec->query("/record/description/$fn"))
						{
							if($recdesc->item(0))
								$recdesc->item(0)->nodeValue = filesize($pathfile);
							else
							{
								$recdesc = $xp_rec->query("/record/description")->item(0);
								$node = $doc->createElement($fn);
								$node->nodeValue = 	filesize($pathfile);
								$newnode = $recdesc->appendChild($node);
							}
						}
					break;
					
					case "tf-width":
						if($recdesc = $xp_rec->query("/record/description/$fn"))
						{
							if($recdesc->item(0))
								$recdesc->item(0)->nodeValue = $width;
							else
							{
								$recdesc = $xp_rec->query("/record/description")->item(0);
								$node = $doc->createElement($fn);
								$node->nodeValue = 	$width;
								$newnode = $recdesc->appendChild($node);
							}
						}
					break;

					case "tf-height":
						if($recdesc = $xp_rec->query("/record/description/$fn"))
						{
							if($recdesc->item(0))
								$recdesc->item(0)->nodeValue = $height;
							else
							{
								$recdesc = $xp_rec->query("/record/description")->item(0);
								$node = $doc->createElement($fn);
								$node->nodeValue = 	$height;
								$newnode = $recdesc->appendChild($node);
							}
						}
					break;
					
					case "tf-bits":
						if($recdesc = $xp_rec->query("/record/description/$fn"))
						{
							$val = "";
							if(isset($tempHD["bits"]))
								$val = $tempHD["bits"];
							if($recdesc->item(0))
								$recdesc->item(0)->nodeValue = $val;
							else
							{
								$recdesc = $xp_rec->query("/record/description")->item(0);
								$node = $doc->createElement($fn);
								$node->nodeValue = 	$val;
								$newnode = $recdesc->appendChild($node);
							}
						}
					break;
					
					case "tf-channels":
						if($recdesc = $xp_rec->query("/record/description/$fn"))
						{
							$val = "";
							if(isset($tempHD["channels"]))
								$val = $tempHD["channels"];
							if($recdesc->item(0))
								$recdesc->item(0)->nodeValue = $val;
							else
							{
								$recdesc = $xp_rec->query("/record/description")->item(0);
								$node = $doc->createElement($fn);
								$node->nodeValue = 	$val;
								$newnode = $recdesc->appendChild($node);
							}
						}
					break;
				
				}
				
			}
		}		 
		$newXml = $doc->savexml();
		
	 	if(  phrasea_setxmlcaption($session->ses_id, $base_id, $record_id,$newXml)  )
		{
			$sql = "UPDATE record SET status=status & ~3, moddate=NOW() WHERE record_id='" . $connbas->escape_string($record_id)."'";
			$connbas->query($sql);
		}
		
		record::rebuild_subdef($base_id.'_'.$record_id);
		$filesToSet = null;	
		$filesToSet[] = $pathfile;
	 	answer::writeIPTC($sbas_id , $desc , $filesToSet );
		
		return true;
	}
}