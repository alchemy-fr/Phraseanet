<?php
class export
{
	protected $storage = array();
	
	public function __construct($lst, $sstid)
	{
		require_once GV_RootPath.'lib/unicode/lownodiacritics_utf8.php';
		
		$session = session::getInstance();
		
		$user = user::getInstance($session->usr_id);
		
		$this->ssttid = false;
		
		$download_list = array();
		
		$remain_hd = array();
		
		if($sstid != "")
		{
			$basket = basket::getInstance($sstid);
			
			foreach($basket->elements as $basket_element)
			{
				$base_id	= $basket_element->base_id;
				$record_id	= $basket_element->record_id;
				
				if(isset($user->_rights_bas[$base_id]) && $user->_rights_bas[$base_id]['restrict_dwnld'])
				{
					if(!isset($remain_hd[$base_id]))
						$remain_hd[$base_id] = $user->_rights_bas[$base_id]['remain_dwnld'];
				}
				else
					$remain_hd[$base_id] = false;
					
				$current_element = $download_list[] = new exportElement($base_id, $record_id, ''.$basket->name.'/', $remain_hd[$base_id]);
				
				$remain_hd[$base_id] = $current_element->remain_hd;
			}
		}
		else
		{
			$tmp_lst = explode(';', $lst);
			$n = 1;
			foreach($tmp_lst as $basrec)
			{
				$basrec = explode('_',$basrec);
				if(count($basrec) != 2)
					continue;
				
				if(phrasea_isgrp($session->ses_id, $basrec[0], $basrec[1]) != false)
				{
					$grpchild = phrasea_grpchild($session->ses_id, $basrec[0], $basrec[1], GV_sit, $session->usr_id);
					
					$xml = phrasea_xmlcaption($session->ses_id, $basrec[0], $basrec[1]);	
					
					$regfield = basket::getRegFields(phrasea::sbasFromBas($basrec[0]),$xml);
			
					if($grpchild !== null)
					{
						foreach($grpchild as $child_basrec)
						{
//							$child_basrec = explode('_',$child_basrec);
							
							$base_id	= $child_basrec[0];
							$record_id	= $child_basrec[1];
							
							if(isset($user->_rights_bas[$base_id]) && $user->_rights_bas[$base_id]['restrict_dwnld'])
							{
								if(!isset($remain_hd[$base_id]))
									$remain_hd[$base_id] = $user->_rights_bas[$base_id]['remain_dwnld'];
							}
							else
								$remain_hd[$base_id] = false;
							
							$current_element = $download_list[] = new exportElement($child_basrec[0], $child_basrec[1],''.$regfield['regname'].'_'.$n.'/', $remain_hd[$base_id]);
							
							$remain_hd[$base_id] = $current_element->remain_hd;
						}
					} 
				}
				else 
				{
					$base_id	= $basrec[0];
					$record_id	= $basrec[1];
					
					if(isset($user->_rights_bas[$base_id]) && $user->_rights_bas[$base_id]['restrict_dwnld'])
					{
						if(!isset($remain_hd[$base_id]))
							$remain_hd[$base_id] = $user->_rights_bas[$base_id]['remain_dwnld'];
					}
					else
						$remain_hd[$base_id] = false;
					
					$current_element = $download_list[$basrec[0].'_'.$basrec[1]] = new exportElement($basrec[0], $basrec[1], '', $remain_hd[$base_id]);
				
					$remain_hd[$base_id] = $current_element->remain_hd;
				}
				$n++;
			}		
		}
		
		$this->lst = $download_list;
		
		$display_download = array();
		$display_orderable = array();
		
		$this->total_download = 0;
		$this->total_order = 0;
		$this->total_ftp = 0;
		
		foreach($this->lst as $download_element)
		{
			foreach($download_element->downloadable as $name=>$properties)
			{
				if(!isset($display_download[$name]))
				{
					$display_download[$name] = array('size'=>0, 'total' => 0, 'avalaible'	=> 0, 'refused'=>array());
				}
				
				$display_download[$name]['total'] ++;
				
				if($properties !== false)
				{
					$display_download[$name]['avalaible'] ++;
					$display_download[$name]['label'] = $properties['label'];
					$this->total_download ++;
					$display_download[$name]['size'] += $download_element->size[$name];
				}
				else
				{
					$display_download[$name]['refused'][] = answer::getThumbnail($session->ses_id, $download_element->base_id, $download_element->record_id);
				}
			}
			foreach($download_element->orderable as $name=>$properties)
			{
				if(!isset($display_orderable[$name]))
				{
					$display_orderable[$name] = array('total' => 0, 'avalaible'	=> 0, 'refused'=>array());
				}
				
				$display_orderable[$name]['total'] ++;
				
				if($properties !== false)
				{
					$display_orderable[$name]['avalaible'] ++;
					$this->total_order ++;
				}
				else
				{
					$display_orderable[$name]['refused'][] = answer::getThumbnail($session->ses_id, $download_element->base_id, $download_element->record_id);
				}
			}
//			var_dump($display_orderable['document']['avalaible'],$display_orderable['document']['total'],$display_orderable['document']['refused']);
		}
		
		foreach($display_download as $name=>$values)
		{
			$display_download[$name]['size'] = (int)$values['size'];
		}
		
		$display_ftp = array();
		
		$hasadminright = $user->_global_rights['addrecord'] || $user->_global_rights['deleterecord'] || $user->_global_rights['modifyrecord']
			|| $user->_global_rights['coll_manage'] || $user->_global_rights['coll_modify_struct'];
		
		$this->ftp_datas = array();	
		
		if(GV_activeFTP && ($hasadminright || GV_ftp_for_user))
		{
			$display_ftp = $display_download;
			$this->total_ftp = $this->total_download;
			
			$lst_base_id = array();
			
			$conn = connection::getInstance();
			
			foreach($user->_rights_bas as $base_id=>$rights)
			{
				$lst_base_id[] = (int)$base_id;
			}
			
			if($hasadminright)
			{
				$sql = "SELECT usr.usr_id,usr_login,usr.addrFTP,usr.loginFTP,usr.sslFTP,
						usr.pwdFTP,usr.destFTP,prefixFTPfolder,usr.passifFTP,usr.retryFTP,usr.usr_mail 
						FROM (usr INNER JOIN basusr 
							ON ( activeFTP=1 AND usr.usr_id=basusr.usr_id AND (basusr.base_id='".implode("' OR basusr.base_id='",$lst_base_id)."') ) ) 
						GROUP BY usr_id  ";
	 		}
			elseif(GV_ftp_for_user)
			{
				$sql = "SELECT usr.usr_id,usr_login,usr.addrFTP,usr.loginFTP,usr.sslFTP,
						usr.pwdFTP,usr.destFTP,prefixFTPfolder,usr.passifFTP,usr.retryFTP,usr.usr_mail 
						FROM (usr INNER JOIN basusr 
							ON ( activeFTP=1 AND usr.usr_id=basusr.usr_id 
								AND usr.usr_id='".$conn->escape_string($session->usr_id)."' 
									AND (basusr.base_id='".implode("' OR basusr.base_id='",$lst_base_id)."') ) ) 
						GROUP BY usr_id  ";
	 		}
	 		
	 		$datas[] = array(
						'name'				=> _('export::ftp: reglages manuels'),
						'usr_id'			=> '0',
						'addrFTP'			=> '',
						'loginFTP'			=> '',
						'pwdFTP'			=> '',
						'ssl'				=> '0',
						'destFTP'			=> '',
						'prefixFTPfolder'	=> 'Export_'.date("Y-m-d_H.i.s"),
						'passifFTP'			=> false,
						'retryFTP'			=> 5,
						'mailFTP'			=> '',
						'sendermail'		=> $user->email
					);

	 		if($rs = $conn->query($sql) )
			{
				while($row = $conn->fetch_assoc($rs))
				{
					$datas[] = array(
						'name'				=> $row["usr_login"],
						'usr_id'			=> $row['usr_id'],
						'addrFTP'			=> $row['addrFTP'],
						'loginFTP'			=> $row['loginFTP'],
						'pwdFTP'			=> $row['pwdFTP'],
						'ssl'				=> $row['sslFTP'],
						'destFTP'			=> $row['destFTP'],
						'prefixFTPfolder'	=> (strlen(trim($row['prefixFTPfolder']))>0?trim($row['prefixFTPfolder']):'Export_'.date("Y-m-d_H.i.s")),
						'passifFTP'			=> ($row['passifFTP']>0 ? true:false),
						'retryFTP'			=> $row['retryFTP'],
						'mailFTP'			=> $row['usr_mail'],
						'sendermail'		=> $user->email
					);
				}	
				$conn->free_result($rs);
			}		
			
			$this->ftp_datas = $datas;
		}
		
		$this->display_orderable	= $display_orderable;
		$this->display_download		= $display_download;
		$this->display_ftp			= $display_ftp;
		
		
		return $this;
	}

	
	
	public function __get($key)
	{
		if(isset($this->storage[$key]))
		{
			return $this->storage[$key];
		}
		return null;
	}
	
	public function __set($key, $value)
	{
		$this->storage[$key] = $value;
		
		return $this;
	}
	
	public function __isset($key)
	{
		if(isset($this->storage[$key]))
			return true;
		return false;
	}
	
	
	public function prepare_export($subdefs,$rename_title=false)
	{
		if(!is_array($subdefs))
		{
			throw new Exception('No subdefs given');
		}
		
		require_once GV_RootPath.'lib/unicode/lownodiacritics_utf8.php';
		
		$session = session::getInstance();
		
		$files = array();
		
		$n_files = 0;
		
		$xsl_titles = $xsls = $file_names = array() ;
		
		$size = 0;
			$user = user::getInstance($session->usr_id);
			
		foreach($this->lst as $download_element)
		{
			$id = count($files);
			
			$files[$id] = array(
				'base_id'=>$download_element->base_id,
				'record_id'=>$download_element->record_id,
				'original_name' => '',
				'export_name' => '',
				'subdefs' => array()
			);
				
			$sbas_id = phrasea::sbasFromBas($download_element->base_id);
			
			$rename_done = false;
				
			$desc = phrasea_xmlcaption($session->ses_id, $download_element->base_id, $download_element->record_id);
			$files[$id]['original_name'] = $files[$id]['export_name'] = answer::getOriginalName($desc) ;
			
			$files[$id]['original_name'] = trim($files[$id]['original_name']) != '' ? $files[$id]['original_name'] : $id;

			$infos = pathinfo($files[$id]['original_name']);
			
			$extension = isset($infos['extension']) ? $infos['extension'] : '';
			
		
			if($rename_title)
			{
				$title = strip_tags(answer::format_title($sbas_id, $download_element->record_id, $desc));
				
				$files[$id]['export_name'] = noaccent_utf8($title, true);
				$rename_done = true;
			}
			else
			{
				$files[$id]["export_name"] = $infos['filename'];
			}

			$sizeMaxAjout = 0;
			$sizeMaxExt   = 0;
				
					
					
			$sd = phrasea_subdefs($session->ses_id, $download_element->base_id, $download_element->record_id);
			
			foreach($download_element->downloadable as $name=>$properties)
			{
				if($properties === false || !in_array($name,$subdefs))
				{
					continue;
				}
				if(!in_array($name, array('caption')) && !isset($sd[$name]) )
				{
					continue;
				}
				
				set_time_limit(100);
				$subdef_export = $subdef_alive = false;
				
				if(!isset($user->_rights_bas[$download_element->base_id]))
					$rights = false;
				else
					$rights = $user->_rights_bas[$download_element->base_id];
				
				$n_files++;	
				
				switch($properties['class'])
				{
					case 'caption':
					case 'thumbnail':
						$subdef_export = true;
						$subdef_alive = true;
						break;
					case 'document':
//							if($rights["candwnldhd"]=="1" || in_array($id, $user->_rights_records))
//							{
//								if($rights["restrict_dwnld"]=="1" && (int)$rights["remain_dwnld"] > 0)
//								{
//									$subdef_export = true;
//									$user->_rights_bas[$download_element->base_id]["remain_dwnld"]=$user->_rights_bas[$download_element->base_id]["remain_dwnld"]-1;
//								}
//								else
									$subdef_export = true;
//							}
//							if($subdef_export && isset($sd['document']))
//							{
								$path = record_image::stamp($download_element->base_id, $download_element->record_id, true);
								if( file_exists($path) )
								{						
									$sd["document"]["path"] = dirname($path);	
									$sd["document"]["file"] = basename($path);			
									$subdef_alive = true;
								}
//							}
							break;
							
						case 'preview':
//							if($rights["candwnldpreview"]=="1" || array_key_exists($id, $user->_rights_records))
								$subdef_export = true;
									
//							if($subdef_export)
//							{
//								if( $o == 'preview' )
//								{
									if($rights["needwatermark"]=="1")
									{
										$path = record_image::watermark($download_element->base_id, $download_element->record_id);
										if( file_exists($path) )
										{						
											$sd["preview"]["path"] = dirname($path);	
											$sd["preview"]["file"] = basename($path);			
											$subdef_alive = true;
										}
									}
									else
									{
										$subdef_alive = true;
									}
//								}
//								else
//									$subdef_alive = true;
//							}
						break;
					
				}
	
				if($subdef_export===true && $subdef_alive===true)
				{
//					if(!isset($sd[$o]))
//						continue;
					switch($properties['class'])
					{
						case 'caption':
							$files[$id]["subdefs"][$name]["ajout"] 		= "_caption";
							$files[$id]["subdefs"][$name]["exportExt"] 	= "xml";
							$files[$id]["subdefs"][$name]["label"] 		= $properties['label'];
							$files[$id]["subdefs"][$name]["path"]		= null;
							$files[$id]["subdefs"][$name]["file"]		= null;
							$files[$id]["subdefs"][$name]["size"]		= 0;
							$files[$id]["subdefs"][$name]["folder"]		= $download_element->directory;
							$files[$id]["subdefs"][$name]["mime"]		= "text/xml";
							
							break;
						case 'document':
						case 'preview':
						case 'thumbnail':
							$infos = pathinfo(p4string::addEndSlash($sd[$name]["path"]).$sd[$name]["file"]);
							
							$files[$id]["subdefs"][$name]["ajout"]		= $properties['class'] == 'document' ? '' : "_".$name;
							$files[$id]["subdefs"][$name]["path"]		= $sd[$name]["path"];
							$files[$id]["subdefs"][$name]["file"]		= $sd[$name]["file"];
							$files[$id]["subdefs"][$name]["label"]		= $properties['label'];
							$files[$id]["subdefs"][$name]["size"]		= $sd[$name]["size"];
							$files[$id]["subdefs"][$name]["mime"]		= $sd[$name]["mime"];
							$files[$id]["subdefs"][$name]["folder"]		= $download_element->directory;
							$files[$id]["subdefs"][$name]["exportExt"]	= isset($infos['extension']) ? $infos['extension'] : '';
							
							$size += $sd[$name]["size"];
							
							break;
						
					}

					$longueurAjoutCourant = mb_strlen($files[$id]["subdefs"][$name]["ajout"]);
					$sizeMaxAjout = max($longueurAjoutCourant, $sizeMaxAjout);
					
					$longueurExtCourant = mb_strlen($files[$id]["subdefs"][$name]["exportExt"]);
					$sizeMaxExt = max($longueurExtCourant, $sizeMaxExt);
				}
			}

			$max_length = 31 - $sizeMaxExt - $sizeMaxAjout;

			$name = $files[$id]["export_name"] ;

			$start_length = mb_strlen(  $name  );
			if( $start_length > $max_length )
				$name = mb_substr($name,0,$max_length);

			$n = 1 ;
			while(in_array($name, $file_names))
			{
				$n++;
				$suffix = "-".$n; // pour diese si besoin
				$max_length = 31 - $sizeMaxExt - $sizeMaxAjout - mb_strlen($suffix);
				$name = $files[$id]["export_name"];
				if( $start_length > $max_length )
					$name = mb_substr($name,0,$max_length).$suffix;
				else
					$name = $name.$suffix;
			}
			$file_names[] = $name;
			$files[$id]["export_name"] = $name;
	
			$files[$id]["export_name"] = noaccent_utf8($files[$id]["export_name"], UNPARSED);
			$files[$id]["original_name"] = noaccent_utf8($files[$id]["original_name"], UNPARSED);
			
			$i = 0;
			$name = utf8_decode($files[$id]["export_name"]);
			$tmp_name = "";
			$good_keys = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p'
			,'q','r','s','t','u','v','w','x','y','z','0','1','2','3','4','5','6','7','8','9','-','_'
			,'.','#');
				
			while(isset($name[$i]))
			{
				if(!in_array(mb_strtolower($name[$i]),$good_keys))
					$tmp_name .= '_';
				else
					$tmp_name .= $name[$i];

				$tmp_name = str_replace('__','_',$tmp_name);
				
				$i++;
			}
			$files[$id]["export_name"] = $tmp_name;
				
			if(in_array('caption', $subdefs))
			{
				$caption_dir = GV_RootPath.'tmp/desc_tmp/'.time().$session->usr_id.$session->ses_id.'/';
				p4::fullmkdir($caption_dir);
		
				$desc = self::get_caption($download_element->base_id, $download_element->record_id, $session->ses_id);
				
				$file =  $files[$id]["export_name"] . $files[$id]["subdefs"]['caption']["ajout"].'.'. $files[$id]["subdefs"]['caption']["exportExt"];

				$path = $caption_dir;
				
				if( $handle = fopen( $path . $file , "w") )
				{
					fwrite($handle, $desc);
					fclose($handle);
					$files[$id]["subdefs"]['caption']["path"] = $path;
					$files[$id]["subdefs"]['caption']["file"] = $file;
					$files[$id]["subdefs"]['caption']["size"] = filesize($path . $file);
				}					
			}
		}
		$this->list = array('files'=>$files,'names'=>$file_names,'size'=>$size,'count'=>$n_files);
		return $this->list;
	}
	
	public static function build_zip($token,$list,$zipFile)
	{
		$zip = new ZipArchiveImproved();
		
		if ($zip->open($zipFile,ZIPARCHIVE::CREATE) !== true) {
		    return false;
		}
		if(isset($list['complete']) && $list['complete'] === true)
			return;
		
		
		$files = $list['files'];
		
		
		$list['complete'] = false;
		
		random::updateToken($token, serialize($list));
		
		$str_in  = array("à","á","â","ã","ä","å","ç","è","é","ê","ë","ì","í","î","ï","ð","ñ","ò","ó","ô","õ","ö","ù","ú","û","ü","ý","ÿ");
		$str_out = array("a","a","a","a","a","a","c","e","e","e","e","i","i","i","i","o","n","o","o","o","o","o","u","u","u","u","y","y");
		
		$caption_dirs = $unlinks = array();
		
		foreach ($files as $record)
		{
			if(isset($record["subdefs"]))
			{
				foreach($record["subdefs"] as $o=>$obj)
				{
					$path = p4string::addEndSlash($obj["path"]) . $obj["file"];
					if(is_file($path))
					{
						$name =  $obj["folder"].$record["export_name"] . $obj["ajout"].'.'. $obj["exportExt"];
			
						$name  = str_replace($str_in, $str_out, $name);
			
						$zip->addFile($path, $name);
						
						if($o == 'caption')
						{
							if(!in_array(dirname($path), $caption_dirs))
								$caption_dirs[] = dirname($path);
							$unlinks[] = $path;
						}
					}			
				}
			}
		}
	
		$zip->close();	
	
		$list['complete'] = true;
		
		random::updateToken($token, serialize($list));

		foreach($unlinks as $u)
		{
			if(GV_debug)
			{
				if(unlink($u))
					file_put_contents(GV_RootPath.'logs/download.log', "deleting file $u after building zip\n", FILE_APPEND);
				else
					file_put_contents(GV_RootPath.'logs/download.log', "CANNOT delete file $u after building zip\n", FILE_APPEND);
			}
			else
				@unlink($u);
		}
		foreach($caption_dirs as $c)
		{
			if(GV_debug)
			{
				if(rmdir($c))
					file_put_contents(GV_RootPath.'logs/download.log', "deleting directory $c after building zip\n", FILE_APPEND);
				else
					file_put_contents(GV_RootPath.'logs/download.log', "CANNOT delete directory $c after building zip\n", FILE_APPEND);
			}
			else
				@rmdir($c);
		}
		
		p4::chmod($zipFile);		
		
		return $zipFile;
	}
	
	public static function get_caption($bas, $rec, $ses_id, $check_rights=true)
	{
		
		$dom = new DOMDocument();
		$dom->formatOutput = true;
		$dom->xmlStandalone = true;
		$dom->encoding = 'UTF-8';

		$dom_record = $dom->createElement('record');
		$dom_desc = $dom->createElement('description');
		
		$dom_record->appendChild($dom_desc);
		$dom->appendChild($dom_record);
		
		$restrict = array();
		
		$desc = phrasea_xmlcaption($ses_id, $bas, $rec);
		$session = session::getInstance();
		
		$struct = databox::get_structure(phrasea::sbasFromBas($bas));
		
		$rights = true;
		
		if($check_rights && isset($session->usr_id))
		{
			$user = user::getInstance($session->usr_id);
			$rights = isset($user->_rights_bas[$bas])?($user->_rights_bas[$bas]['canmodifrecord'] == '1') : false;
		
			if($rights == false)
			{
				if($sxe = simplexml_load_string($struct) )
				{
					$z = $sxe->xpath('/record/description');
					if($z && is_array($z))
					{
						foreach($z[0] as $ki => $vi)
						{
							if(isset($vi["export"]) && ($vi["export"]=="0" || $vi["export"]=="off") )
								$restrict[$ki] = true;		
						}
					}
				}
			}
		}
		
		if($sxe = simplexml_load_string($desc))
		{
			$z = $sxe->xpath('/record/description');
			if($z && is_array($z))
			{
				foreach($z[0] as $ki=>$vi)
				{
					if(($rights || !isset($restrict[$ki])))
					{
						$dom_el = $dom->createElement($ki);
						$dom_el->appendChild($dom->createTextNode(trim($vi)));
						$dom_desc->appendChild($dom_el);
					}
				}
			}
		}
		
		
		$ret = $dom->saveXML();
		
		return $ret;
	}
	
	public static function stream_file($file,$exportname, $mime, $disposition='attachment')
	{
		
		$disposition = in_array($disposition, array('inline', 'attachment')) ? $disposition : 'attachment';
		
		if(is_file($file))
		{
			if(GV_modxsendfile)
			{
				
        $file_xaccel = str_replace(
                        array(
                            GV_X_Accel_Redirect,
                            GV_RootPath . 'tmp/download/',
                            GV_RootPath . 'tmp/lazaret/'
                        )
                        , array(
                    '/' . GV_X_Accel_Redirect_mount_point . '/',
                    '/download/',
                    '/lazaret/'
                        )
                        , $file
        );
				
				header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
				header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
				header("Cache-Control: no-store, no-cache, must-revalidate");
				header("Cache-Control: post-check=0, pre-check=0", false);
				header("Pragma: no-cache");
				header("X-Sendfile: $file");
				header("X-Accel-Redirect: $file_xaccel");
				header("Content-Type: ".$mime);
				header("Content-Name: ".$exportname.";");
				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: max-age=3600, must-revalidate ");
				header("Content-Disposition: ".$disposition."; filename=".$exportname.";");
				header("Content-Length: ".filesize($file));
				return true;
			}
			else
			{
				if($fp = @fopen($file, "rb"))
				{
					
					header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
					header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
					header("Cache-Control: no-store, no-cache, must-revalidate");
					header("Cache-Control: post-check=0, pre-check=0", false);
					header("Pragma: no-cache");
					header("Content-Type: ".$mime);
					header("Content-Length: " . filesize($file));
					header("Cache-Control: max-age=3600, must-revalidate ");
					header("Content-Disposition: ".$disposition."; filename=".$exportname.";");
					while (!feof($fp))
					{
						set_time_limit(300);	// 5 minutes !
			  			$bin = fread($fp, 8192);
						echo($bin);
						flush();
					}
					fclose($fp);
					return true;
				}
			}
		}
		return false;
	}
	
	function stream_data($data,$exportname, $mime, $disposition='attachment')
	{
		
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
			header("Content-Type: ".$mime);
			header("Content-Length: " . strlen($data));
			header("Cache-Control: max-age=3600, must-revalidate ");
			header("Content-Disposition: ".$disposition."; filename=".$exportname.";");
			
			echo $data;
			
			return true;
	}
	
	public static function log_download($list, $type, $anonymous = false)
	{
		//download
		$session = session::getInstance();
		$user = false;
		if($anonymous)
		{
			$ses_id = phrasea_create_session();
			$user = user::getInstance($session->usr_id);
		}
		else
		{
			$ses_id = $session->ses_id;
		}
			
		$tmplog = array();
		$files = $list['files'];
		
		$event_names = array(
			'mail-export'	=> 'mail',
			'download'		=> 'download'
		);
		
		$event_name = isset($event_names[$type]) ? $event_names[$type] : 'download';
		
		foreach($files as $record)
		{
			foreach($record["subdefs"] as $o=>$obj)
			{
				if($o=="caption")
				{
					answer::logEvent(phrasea::sbasFromBas($record['base_id']),$record['record_id'],$event_name,'caption','');
				}
				else
				{
					answer::logEvent(phrasea::sbasFromBas($record['base_id']),$record['record_id'],$event_name,$o,'');
					$log["rid"] = $record['record_id'] ;
					$log["subdef"] = $o ;
					$log["poids"] = $obj["size"];
					$log["shortXml"] = phrasea_xmlcaption($ses_id, $record['base_id'], $record['record_id']);
					$tmplog[$record['base_id']][]=$log;
					if(!$anonymous && isset($user->_rights_bas[$record['base_id']]))
						$user->_rights_bas[$record['base_id']]['remain_dwnld']--;
				}
			}
		}

		$conn = connection::getInstance();
		
		$export_types = array(
			'download'		=> 0,
			'mail-export'	=> 2,
			'ftp'			=> 4
		);
		
		$export_type = isset($export_types[$type]) ? $export_types[$type] : 0;
		
		$allcoll = array();
		$dst_logid = null ;

		$list_base = array_unique(array_keys($tmplog));
	
		$sql = "SELECT * FROM bas WHERE base_id='" . implode("' OR base_id='",$list_base) . "'";
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$sbas_id =  $row["sbas_id"];
				$allcoll[$sbas_id][$row["base_id"]]=$tmplog[$row["base_id"]];
				$allcoll[$sbas_id][$row["base_id"]]["server_coll_id"]= $row["server_coll_id"];
			}
			$conn->free_result($rs);
		}
	
	
		if(!$anonymous)
		{
			foreach ($list_base as $base_id)
			{
				if(isset($user->_rights_bas[$base_id])  && $user->_rights_bas[$base_id]["restrict_dwnld"]=="1")
				{
					$user->_rights_bas[$base_id]["remain_dwnld"] = $user->_rights_bas[$base_id]["remain_dwnld"] < 0 ? 0 : $user->_rights_bas[$base_id]["remain_dwnld"];
					$sql2 = "UPDATE basusr SET remain_dwnld=".$conn->escape_string($user->_rights_bas[$base_id]["remain_dwnld"]). " WHERE base_id='".$conn->escape_string($base_id)."' AND usr_id='".$conn->escape_string($usr_id)."'";
					$conn->query($sql2);
				}
			}
		}
		
		$sql2 = "SELECT dist_logid FROM cache WHERE session_id='".$conn->escape_string($session->ses_id)."'";
		if($rs2 = $conn->query($sql2))
		{
			if( $row2 = $conn->fetch_assoc($rs2) )
			{
				$dst_logid = unserialize($row2["dist_logid"]);
			}
		}
	
	
		foreach($allcoll as $sbas_id =>$colls)
		{
			$conn2 = connection::getInstance($sbas_id);
			if($conn2)
			{
				$sql2 = "select * from uids where name='EXPORTS'";
				if($rs2 = $conn2->query($sql2))
				{
					if( $conn2->num_rows($rs2)==0 )
					{
						$sql3 = "INSERT INTO uids (uid, name) VALUES (1, 'EXPORTS')" ;
						$rs2 = $conn2->query($sql3);
					}
				}
	
				foreach($colls as $baseid=>$allmydl)
				{
					foreach($allmydl as $ind=>$mydl)
					{
						if( is_numeric($ind) && isset($dst_logid[$sbas_id]) )
						{
							$newid = $conn2->getId("EXPORTS");
	
							$sql3  = "INSERT INTO exports (id, logid, date, rid, collid, weight, type, exportType, shortXml) VALUES " ;
							$sql3 .= "('".$conn2->escape_string($newid)."','".$conn2->escape_string($dst_logid[$sbas_id])."',now() ,'".$conn2->escape_string($mydl["rid"])."', '".$conn2->escape_string($allmydl["server_coll_id"])."', '".$conn2->escape_string($mydl["poids"])."' , '".$conn2->escape_string($mydl["subdef"])."', '".$conn2->escape_string($export_type)."' , '".$conn2->escape_string( $mydl["shortXml"] )."')" ;
							$rs2 = $conn2->query($sql3);
						}
					}
				}
			}
		}
		
		if($anonymous)
		{
			phrasea_close_session($ses_id);
		}
		
	}
	
}