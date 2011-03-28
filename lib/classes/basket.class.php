<?php
class basket
{
	private $name = false;
	private $desc = false;
	private $created_on = false;
	private $updated_on = false;
	private $pusher = 0;
	private $noview = false;
	private $public = false;
	private $instance_key = false;
	private $pub_restrict = false;
	private $valid = false;
	private $homelink = false;
	private $is_grouping = false;
	private $record_id = false;
	private $is_mine = false;
	private $usr_id;
	private $elements;
	private $ssel_id;
	
	private $validating_users = array();
	private $validation_see_others = false;
	private $validation_end_date = false;
	private $validation_is_confirmed = false;
	
	private $sbas_id = false;
	private $coll_id = false;
	private $base_id = false;
	
	private $is_draft = false;
	private $is_new = false;
	
	private $owner_changed = false;

	static $_regfields = null;
	
	private static $_instance = array();
	  
	
	public function __isset($name)
	{
		if (isset($this->$name))
		{
			return true;
		}
		
		return false;
	}
	
	public static function load_regfields()
	{
		
		$lb = phrasea::bases();
		self::$_regfields = array();
		foreach($lb['bases'] as $oneBase)
		{
			if(isset($oneBase["xmlstruct"]))
				self::$_regfields[$oneBase['sbas_id']] = self::searchRegFields($oneBase["xmlstruct"]);
			else
				self::$_regfields[$oneBase['sbas_id']] = array('regdesc'=>'','regname'=>'','regdate'=>'');
		}
		return self::$_regfields;
	}
	
	/**
	 * @return basket
	 */
	public static function getInstance($ssel_id, $usr_id = false)
	{
		$session = session::getInstance();
		
		if($usr_id === false)
			$usr_id = $session->usr_id;
			
		if(is_int((int)$ssel_id) && (int)$ssel_id > 0)
		{
			$ssel_id = (int)$ssel_id;
		}
		else
		{
			throw new Exception('Wrong basket id');
		}
		
		$instance_key = $ssel_id.'_'.$usr_id;
			
		if(!isset(self::$_instance[$instance_key]))
		{

			$cache_basket = cache_basket::getInstance();
			$session = session::getInstance();
			if($cache_basket && (($tmp = $cache_basket->get($usr_id, $ssel_id)) != false))
			{
				self::$_instance[$instance_key] = $tmp;
			}
			else
			{
				self::$_instance[$instance_key] = new basket($ssel_id, $usr_id);
			
				if($cache_basket)
					$cache_basket->set($usr_id, $ssel_id, self::$_instance[$instance_key]);
			}
		}
		
		return array_key_exists($instance_key, self::$_instance) ? self::$_instance[$instance_key] : false;
	}
	
	/**
	 * @param Integer $ssel_id[optionnal] -- If no $ssel_id , create new basket
	 * @return basket Object
	 */
	function __construct($ssel_id=false, $usr_id = false)
	{
		$conn = connection::getInstance();
		$session = session::getInstance();
		
		if($usr_id === false)
			$usr_id = $session->usr_id;

		if($ssel_id)
		{
			
			$this->instance_key = $ssel_id.'_'.$usr_id;
		
			$sql = 'SELECT s.pushFrom, n.id as noview, s.usr_id as owner, s.rid, s.sbas_id, s.temporaryType,
				s.name, s.descript, s.pushFrom, s.date, s.updater, s.public, s.pub_restrict, s.homelink,
				v.id as validate_id, v.can_see_others, v.expires_on, v.confirmed 
				FROM ssel s 
					LEFT JOIN validate v ON (s.ssel_id = v.ssel_id AND v.usr_id = "'.$conn->escape_string($usr_id).'") 
					LEFT JOIN sselnew n ON (n.usr_id = "'.$conn->escape_string($usr_id).'" AND n.ssel_id = s.ssel_id)  
				WHERE s.ssel_id="'.$ssel_id.'" 
          AND (s.usr_id="'.$conn->escape_string($usr_id).'" OR v.id IS NOT NULL OR s.public = "1")';
			
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					$this->name = $row['name'];
					$this->ssel_id = $ssel_id;
					$this->desc = $row['descript'];
					$this->created_on = $row['date'];
					$this->updated_on = $row['updater'];
					$this->public = $row['public'];
					$this->pub_restrict = $row['pub_restrict'];
					$this->usr_id = $row['owner'];
					
					if($row['owner'] == $usr_id)
						$this->is_mine = true;
	
					
					if($row['validate_id'] != null)
					{
						$this->valid = 'valid';
						if($this->is_mine)
						{
							$this->valid = 'myvalid';
							$this->validation_see_others = true;
						}
						elseif($row['can_see_others'] == '1')
						{
							$this->validation_see_others = true;
						}
						$this->validation_end_date = $row['expires_on'];
						$this->validation_is_confirmed = $row['confirmed'];
							
						$this->load_validation_users();
					}
					
					
					if((int)$row['pushFrom'] > 0)
						$this->pusher = $row['pushFrom'];
					
					$this->homelink = $row['homelink'];
					$this->noview = (int)$row['noview'] > 0 ? true : false;
					
					if($row['temporaryType'] == 1)
					{
						$this->is_grouping = true;
						$this->record_id = $row['rid'];
						
						
						$connsbas = connection::getInstance($row['sbas_id']);
						if($connsbas)
						{
							$sqlReg = 'SELECT coll_id, xml FROM record WHERE record_id="'.$connsbas->escape_string($row['rid']).'"';
							if($connsbas && $rsReg = $connsbas->query($sqlReg))
							{
								if($rowReg = $connsbas->fetch_assoc($rsReg))
								{
									$this->coll_id = $rowReg['coll_id'];
									$this->base_id = phrasea::baseFromColl($row['sbas_id'], $rowReg['coll_id']);
				 					
				 					$regfield = self::getRegFields($row['sbas_id'],$rowReg['xml']);
									
									$this->name = $regfield['regname'];
									$this->desc = $regfield['regdesc'];
									$this->created_on = $regfield['regdate'];
								}
							}
						}
							
						$this->sbas_id = $row['sbas_id'];
					}
				}
				$conn->free_result($rs);
			}
			if($this->name === false)
			{
				throw new Exception('No basket found sselid ');
			}
			if(trim($this->name) === '')
			{
				$this->name = '<i>'._('sans titre').'</i>';
			}
			$this->load_elements();
		
		}
		else
		{
			$this->name = _("default selection");
			if(isset($usr_id))
			{
				$this->usr_id = $usr_id;
				$this->is_mine = true;
			}
			$this->is_draft = true;
			$this->is_new = true;
		}
		
		return $this;
	}

	public function __clone()
	{
		try
		{
			$this->ssel_id = false;
			$this->is_grouping = false;
			$this->record_id = false;
			$this->is_new = true;
			$this->valid = false;

                        $elems = array();
                        foreach($this->elements as $basket_element)
                        {
                          $elems[] = array(
                              'base_id'   => $basket_element->base_id,
                              'record_id' => $basket_element->record_id
                          );
                        }

			$this->save();

                        foreach($elems as $elem)
                        {
                                $this->push_element($elem['base_id'], $elem['record_id'], false, false);
                        }
                        $this->update_instance();
		}
		catch(Exception $e)
		{
			
		}
		return $this;
	}
	
	function push_list($lst, $fixing)
	{
		$ret = array('error'=>false,'datas'=>array());
	
		if(!is_array($lst))
			$lst = explode(';', $lst);
			
		foreach($lst as $basrec)
		{
			if(!is_array($basrec))
				$basrec = explode('_',$basrec);
			if(count($basrec) != 2)
				continue;
			$push = $this->push_element($basrec[0], $basrec[1], $this->record_id, $fixing);
			if($push['error'])
				$ret['error'] = $push['error'];
			else
				$ret['datas'] = array_merge($ret['datas'], $push['datas']);
		}
		return $ret;
	}
	
	function push_element($base_id, $record_id, $parent_record_id, $fixing)
	{
		if($parent_record_id && phrasea::sbasFromBas($base_id) != $this->sbas_id)
			return array(
					'error'=>_('panier:: Un reportage ne peux recevoir que des elements provenants de la base ou il est enregistre'), 
					'datas'=>array()
					);
			
		if($this->valid && !$this->is_mine)
			return 	array('error'=>_('Ce panier est en lecture seule'),'datas'=>array());
			
		try{
			$sselcont_id = new basketElement(false);
				
			$sselcont_id->parent_record_id = $parent_record_id;
			
			$sselcont_id->create_into_basket($this->ssel_id, $base_id, $record_id, $this->valid, $fixing);
			
			$this->elements[$sselcont_id->sselcont_id] = $sselcont_id;
			$this->elements[$sselcont_id->sselcont_id]->display_id = count($this->elements) + 1;

			$this->update_instance();

			$ret['error'] = false;
			$ret['datas'] = array($sselcont_id->sselcont_id);
		}
		catch (Exception $e)
		{
			$ret['error'] = $e->getMessage();
			$ret['datas'] = array();
		}

		return $ret;
	}
	
	
	function __get($name)
	{
		if (isset($this->$name))
		{
            return $this->$name;
        }

        $trace = debug_backtrace();
        trigger_error('Undefined property via __get(): ' . $name . ' in ' . $trace[0]['file'] .
        				' on line ' . $trace[0]['line'],   E_USER_NOTICE);
        return null;
	}
	
	
	function __set($name, $value)
	{
	
		$session = session::getInstance();
		$this->is_draft = true;
		switch($name)
		{
			case 'name':
			case 'desc':
			case 'usr_id':
			case 'pusher':
			case 'is_grouping':
			case 'base_id':
			case 'record_id':
				if(!$this->is_new && $name == 'is_grouping')
				{
					throw new Exception ('Cannot change a basket into grouping');
				}
				if($name == 'usr_id' && $session->usr_id != $value)
				{
					$this->is_mine = false;
					if($this->is_new)
					{
						$this->owner_changed = true;
					}
					else
					{
						throw new Exception ('Can\'t modify owner');
					}
				}
				$this->$name = $value;
				break;
			case 'created_on':
			case 'updated_on':
					$this->$name = $value;
				break;
			default:
			        $trace = debug_backtrace();
			        trigger_error('Undefined property via __set(): ' . $name . ' in ' . $trace[0]['file'] .
			        				' on line ' . $trace[0]['line'],   E_USER_NOTICE);
			        return null;
					$this->is_draft = false;
				break;
		}
		return;
	}
	
	protected function load_elements()
	{
		if(!is_null($this->elements))
			return;
			
		$this->elements = array();
		$conn = connection::getInstance();
		
		$user = user::getInstance($this->usr_id);
		 
		if($this->public && $this->pub_restrict)
		{
			$sbas_tests =array();
			$sql = 'SELECT sselcont_id, base_id, record_id FROM sselcont 
					WHERE ssel_id = "'.$conn->escape_string($this->ssel_id).'" ORDER BY ord ASC';
			
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					if(!isset($user->_rights_bas[$row['base_id']]))
					{
						continue;
					}
					$sbas_tests[phrasea::sbasFromBas($row['base_id'])][] = 
								array(
									'record_id'		=> $row['record_id'],
									'sselcont_id'	=> $row['sselcont_id'],
									'mask_and'		=> $user->_rights_bas[$row['base_id']]['mask_and'],
									'mask_xor'		=> $user->_rights_bas[$row['base_id']]['mask_xor'] 
								);
				}
				$conn->free_result($rs);
			}
			
			foreach($sbas_tests as $sbas_id => $records)
			{
				$connbas = connection::getInstance($sbas_id);
				
				if(!$connbas)
					continue;
					
				$sql = 'SELECT record_id FROM record 
						WHERE ';
				
				$sql_records = array();
				$sselcont_id_ref = array();
				
				foreach($records as $record)
				{
					$sselcont_id_ref[$record['record_id']] = $record['sselcont_id'];
					$sql_records[] = '(((status ^ '.$record['mask_xor'].') & '.$record['mask_and'].')=0 
										AND record_id="'.$connbas->escape_string($record['record_id']).'") ';
				}
				
				$sql .= implode(' OR ',$sql_records);
				
				$display_id = 1;
				
				if($rs = $connbas->query($sql))
				{
					while($row = $connbas->fetch_assoc($rs))
					{
						$sselcont_id = $sselcont_id_ref[$row['record_id']];
						$this->elements[$sselcont_id] = new basketElement($sselcont_id);
						$this->elements[$sselcont_id]->display_id = $display_id;
						$display_id ++;
					}
					$connbas->free_result($rs);
				}
			}
			
		}
		else
		{
			
			$sql = 'SELECT sselcont_id FROM sselcont WHERE ssel_id = "'.$conn->escape_string($this->ssel_id).'" ORDER BY ord ASC';
		
			$display_id = 1;

                        if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					$this->elements[$row['sselcont_id']] = new basketElement($row['sselcont_id']);
					$this->elements[$row['sselcont_id']]->display_id = $display_id;
					$display_id ++;
				}
				$conn->free_result($rs);
			}
		}
	}
	
	public function save()
	{
		$ret = 0;
		$conn = connection::getInstance();
		
		if(!$this->usr_id)
			throw new Exception('No user defined');
		
		if($this->is_new)
		{
			if($this->is_grouping)
			{
				
				
				$sxbaseprefs = databox::get_sxml_structure(phrasea::sbasFromBas($this->base_id));
				$coll_id = phrasea::collFromBas($this->base_id);
				$sbas_id = phrasea::sbasFromBas($this->base_id);
				$connbas = connection::getInstance($sbas_id);
				
				if(!$connbas)
					throw new Exception('No database connection');

				$user = user::getInstance($this->usr_id);
				
				if(!isset($user->_rights_bas[$this->base_id]) || !$user->_rights_bas[$this->base_id]['canaddrecord'])
					throw new Exception('No rights');

				$ret = FALSE;
				
				// on cree un record		 					
				if(($record_id = $connbas->getId('RECORD', 1)) !== null)
				{
					$zeXml = new DOMDocument('1.0', 'UTF-8');
					$zeXml->preserveWhiteSpace = true;
					$zeXml->formatOutput = true;
					$zeRec = $zeXml->appendChild($zeXml->createElement('record'));
					$zeRec->setAttribute('record_id', $record_id);
					$zeDsc = $zeRec->appendChild($zeXml->createElement('description'));
					foreach($sxbaseprefs->description->children() as $fname=>$field)
					{
						if(p4field::isyes($field['regname']))
							$zeDsc->appendChild($zeXml->createElement($fname))->appendChild($zeXml->createTextNode($this->name));
						elseif(p4field::isyes($field['regdesc']))
							$zeDsc->appendChild($zeXml->createElement($fname))->appendChild($zeXml->createTextNode($this->desc));
					}
					$zeRec->appendChild($zeXml->createElement('doc'))->setAttribute('originalname', 'regroup_'.$record_id.'.jpg');
					
					$fl = '';					$vl = '' ;
					$fl.= 'coll_id';			$vl.= '"'.$connbas->escape_string($coll_id).'"' ;
					$fl.= ', record_id';		$vl.= ', "' .$connbas->escape_string($record_id).'"' ;
					$fl.= ', parent_record_id';	$vl.= ', "' .$connbas->escape_string($record_id).'"' ;
					$fl.= ', status';			$vl.= ', ((15)&~3)'  ;
					$fl.= ', type';				$vl.= ', \'image\''  ;
					$fl.= ', moddate';			$vl.= ', NOW()' ;
					$fl.= ', credate';			$vl.= ', NOW()' ;
					$fl.= ', xml';				$vl.= ', \''.$connbas->escape_string($zeXml->saveXML()).'\'' ;
					
					$sql = 'INSERT INTO record ('.$fl.') VALUES ('.$vl.')'; 
					$connbas->query($sql);
					$ret = true;
					if(file_exists($regroup_doc = GV_RootPath.'www/skins/icons/substitution/regroup_doc.png'))
					{
						
						$this->record_id = $record_id;
						$this->sbas_id = $sbas_id;
						
						$imsize = getimagesize($regroup_doc);
						$imfsize = filesize($regroup_doc);
						
						$tsub = array();
						$tsub['document'] = array(
												'name'=>'document', 
												'path'=>p4string::addEndSlash((string)($sxbaseprefs->path)), 
												'file'=>$record_id.'_document.png', 
												'baseurl'=>(string)($sxbaseprefs->baseurl)
											);

						$subdefs = databox::get_subdefs($sbas_id);
						
						if(isset($subdefs['image']))
						{
							foreach($subdefs['image'] as $subdef)
							{
								$subname = (string)$subdef->attributes()->name;
								if(!in_array($subname,array('thumbnail', 'preview')) || isset($tsub[$subname]))
									continue;
								$tsub[$subname] = array(
													'name'=>$subname, 
													'path'=>p4string::addEndSlash((string)($subdef->path)), 
													'file'=>$record_id.'_'.$subname.'.png', 
													'baseurl'=>(string)($subdef->baseurl));
							}
						}
							
						foreach($tsub as $sub)
						{
							if(!is_dir($sub['path']))
								mkdir($sub['path'],0777,true);
							if( @copy($regroup_doc, $sub['path'].$sub['file']) )
							{
								p4::chmod($sub['path'].$sub['file']);
			
								$sql2  = 'INSERT INTO subdef (record_id, name, path, file, baseurl, inbase, width, height, mime, size)';
								$sql2 .= ' VALUES ("'.$connbas->escape_string($record_id).'", ';
								$sql2 .= '\''.$connbas->escape_string($sub['name']).'\', ';
								$sql2 .= '\''.$connbas->escape_string($sub['path']).'\', ';
								$sql2 .= '\''.$connbas->escape_string($sub['file']).'\', ';
								$sql2 .= '\''.$connbas->escape_string($sub['baseurl']).'\', ';
								$sql2 .= '"1", "'.$connbas->escape_string($imsize[0]).'", "'.$connbas->escape_string($imsize[1]).'",';
								$sql2 .= '\''.$connbas->escape_string($imsize['mime']).'\', ';
								$sql2 .= '"' . $connbas->escape_string($imfsize) . '")';
								$connbas->query($sql2);
							}
						}
					}
				}
			}
			if(($ssel_id = $conn->getId("SSEL")) !== null)
			{
				$this->instance_key = $ssel_id.'_'.$this->usr_id;
				$sql = 'INSERT INTO ssel (ssel_id, name, usr_id, descript, pushFrom, date, updater, temporaryType, rid, sbas_id) 
						VALUES ("'.$ssel_id.'", "'.$conn->escape_string($this->name).'", "'.$conn->escape_string($this->usr_id).'",
						"'.$conn->escape_string($this->desc).'","'.$conn->escape_string($this->pusher).'", NOW(), NOW(),
						"'.$conn->escape_string($this->is_grouping ? '1' : '0').'", "'.$conn->escape_string($this->record_id).'", 
						"'.$conn->escape_string($this->sbas_id).'") ';

				if($conn->query($sql))
				{
					$ret = 1;
					$this->is_draft = false;
					$this->is_new = false;
					$this->ssel_id = $ssel_id;
					$date_obj = new DateTime();
					
					$this->updated_on = $this->created_on = phraseadate::format_mysql($date_obj);
                                        $this->elements = array();
					if($this->owner_changed)
					{
						$this->set_unread();
					}
				}
			}
		}
		elseif($this->is_draft)
		{
			$sql = 'UPDATE ssel SET 
				name="'.$conn->escape_string($this->name).'",  
				usr_id="'.$conn->escape_string($this->usr_id).'" ,  
				descript="'.$conn->escape_string($this->desc).'",
				updater=NOW() 
				WHERE ssel_id="'.$conn->escape_string($this->ssel_id).'"';
			
			if($conn->query($sql))
			{
				$ret = 1;
				$this->is_draft = false;
				
				$date_obj = new DateTime();
				
				$this->updated_on = phraseadate::format_mysql($date_obj);
				
				if($this->owner_changed)
				{
					$this->owner_changed = false;
				}
				
				$this->update_instance();
			}
		}
		return $ret;
	}
	
	/**
	 * Flattent a basket 
	 * ------------------
	 * Remove groupings from the basket and and their contents
	 */
	function flatten()
	{
		$session = session::getInstance();
		$ses_id = $session->ses_id;
		$usr_id = $session->usr_id;
		
		phrasea_open_session($ses_id, $usr_id);

                foreach($this->elements as $basket_element)
		{
			if(phrasea_isgrp($session->ses_id,$basket_element->base_id,$basket_element->record_id))
			{
				$lst = phrasea_grpchild(
							$session->ses_id,
							$basket_element->base_id,
							$basket_element->record_id,
							GV_sit,
							$session->usr_id);
				$this->push_list($lst, true);
				$this->remove_from_ssel($basket_element->sselcont_id);
			}
		}
	}
	

	function remove_from_ssel($sselcont_id)
	{
		if(!$this->is_mine)
			return array('error'=>'error', 'status'=>0);

		if($this->is_grouping)
			return $this->remove_grouping_elements($sselcont_id);
		else
			return $this->remove_basket_elements($sselcont_id);
	}
	
	protected function update_instance()
	{
		self::$_instance[$this->instance_key] = $this;
		$cache_basket = cache_basket::getInstance();
		$cache_basket->delete($this->usr_id,$this->ssel_id);
	}
	
	protected function delete_instance()
	{
		$cache_basket = cache_basket::getInstance();
		$cache_basket->delete($this->usr_id,$this->ssel_id);
		unset(self::$_instance[$this->instance_key]);
	}
	
	protected function remove_basket_elements($sselcont_id)
	{
		$conn = connection::getInstance();
			
 		$sql = 'DELETE FROM sselcont WHERE sselcont_id="'.$conn->escape_string($sselcont_id).'" AND ssel_id="'.$this->ssel_id.'"';
		if($conn->query($sql))
		{
			$sql = 'DELETE FROM validate_datas WHERE sselcont_id="'.$conn->escape_string($sselcont_id).'"';
			$conn->query($sql);
			
			$this->update_instance();
			return array('error'=>false,'status'=>1);
		}
		return array('error'=>true,'status'=>0);
	}
	
	protected function remove_grouping_elements($sselcont_id)
	{
		$conn = connection::getInstance();
		$session = session::getInstance();
			
	 	$sbas_id = $parent_record_id = $collid = $base_id = $record_id = null;
		
	 	$ssel_id = $this->ssel_id;
	 	
	 	$sql = 'SELECT s.sbas_id, s.ssel_id, s.rid, c.record_id, c.base_id FROM ssel s, sselcont c 
	 			WHERE c.sselcont_id="'.$conn->escape_string($sselcont_id).'" 
	 			AND c.ssel_id = s.ssel_id AND s.ssel_id="'.$this->ssel_id.'"';
				 	
	 	if($rs = $conn->query($sql))
	 	{
	 		if($row = $conn->fetch_assoc($rs) )
	 		{
	 			$parent_record_id = $row["rid"] ;
	 			$base_id = $row['base_id'];
	 			$sbas_id = $row['sbas_id'];
	 			$record_id = $row['record_id'];
	 		}
			$conn->free_result($rs);
	 	}			
		
		$ret = array('error'=>false, 'status'=>0);
			
		$user = user::getInstance($session->usr_id);
		
		if(isset($user->_rights_bas[$base_id]) && $user->_rights_bas[$base_id]['canmodifrecord'])
		{
			$connbas = connection::getInstance($sbas_id);
			
			$sql = "DELETE FROM regroup WHERE rid_parent='".$connbas->escape_string($parent_record_id)."' 
					AND rid_child = '".$connbas->escape_string($record_id)."'";
			if($connbas->query($sql))
			{
				
				
				$sql = 'SELECT sselcont_id, s.ssel_id, s.usr_id FROM ssel s, sselcont c WHERE s.rid="'.$parent_record_id.'" AND s.sbas_id="'.$sbas_id.'"
						AND temporaryType="1" AND c.ssel_id = s.ssel_id AND c.base_id="'.$base_id.'" AND c.record_id="'.$record_id.'"';
				
				$first = true;
				$good = false;

				$cache_basket = cache_basket::getInstance();
				
				if($rs = $conn->query($sql))
			 	{
			 		while($row = $conn->fetch_assoc($rs) )
			 		{
						$sql = 'DELETE FROM sselcont WHERE sselcont_id = "'.$conn->escape_string($row['sselcont_id']).'"';
			 			if($first)
			 				$good = true;
			 			$first = false;
						if(!$conn->query($sql))
							$good = false;
						else
						{
							$cache_basket->delete($row['usr_id'], $row['ssel_id']);
							$basket_usr = self::getInstance($row['ssel_id'], $row['usr_id']);
							$basket_usr->set_unread();
						}
			 		}
					$conn->free_result($rs);
			 	}		
			 		
				if(!$good)
					$ret = array('error'=>_('panier:: erreur lors de la suppression'), 'status'=>0);
				else
					$ret = array('error'=>false,'status'=>1);
		
			}	
			$this->update_instance();
		}
		else
		{
			$ret = array(
					'error'=>_('phraseanet :: droits insuffisants, vous devez avoir les doits d\'edition sur le regroupement '), 
					'status'=>0);
		}	
		
		return $ret;
	}
	
	

	public static function fix_grouping($lst)
	{
		
		$session = session::getInstance();
		$usr_id = $session->usr_id;
		$ses_id = $session->ses_id;
		
		phrasea_open_session($ses_id, $usr_id);
		
		$conn = connection::getInstance();
			
		$retour = array();
		
		if(!is_array($lst))
		 	$lst = explode(";", $lst);
		 	
		foreach($lst as $basrec)
		{
			$basrec = explode('_',$basrec);
			$base_id = $basrec[0];
			$record_id = $basrec[1];
			
			$xml = phrasea_xmlcaption($ses_id, $base_id, $record_id);
			
			$regfield = self::getRegFields(phrasea::sbasFromBas($base_id),$xml);
			$connbas = connection::getInstance(phrasea::sbasFromBas($base_id));
			
			$moddate = '';
			if($connbas)
			{
				if($rs = $connbas->query('SELECT moddate FROM record WHERE record_id = "'.$connbas->escape_string($record_id).'"'))
				{
					if($row = $connbas->fetch_assoc($rs))
						$moddate = $row['moddate'];
					$connbas->free_result($rs);
				}
			}
			
			
		 	$sql = 'SELECT ssel_id FROM ssel WHERE usr_id="'.$conn->escape_string($usr_id).'" 
		 			AND temporaryType=1 AND rid="'.$conn->escape_string($record_id).'" 
		 			AND sbas_id="'.phrasea::sbasFromBas($base_id).'"';
	
		 	if($rs = $conn->query($sql))
		 	{
		 		if($conn->num_rows($rs)==0)
		 		{
		 			
		 			
		 			if(($id = $conn->getId("SSEL")) !== null)
					{
						$sql = 'INSERT INTO ssel (ssel_id, usr_id, date,temporaryType , rid , sbas_id, updater,name,descript ) 
								VALUES ("'.$conn->escape_string($id).'", "'.$conn->escape_string($usr_id).'", NOW(),"1" , 
								"'.$conn->escape_string($record_id).'" ,"'.phrasea::sbasFromBas($base_id).'", 
								'.($moddate != '' ? '"'.$conn->escape_string($moddate).'"' : '').',
								"'.$conn->escape_string($regfield['regname']).'",
								"'.$conn->escape_string($regfield['regdesc']).'" )';
		
						if($conn->query($sql))
						{
							
				 			$basket = self::getInstance($id);
				 			
							if($children = phrasea_grpchild($ses_id,$base_id,$record_id,GV_sit,$usr_id))
							{
								$basket->push_list($children, true);
							}
							$retour[] = $id;
						}
					}
		 		}
		 		else
		 		{
		 			if($row = $conn->fetch_assoc($rs))
		 				$retour[] = $row['ssel_id'];
		 		}
		 		
				$conn->free_result($rs);
		 	}
		}
	 	return p4string::jsonencode($retour);
	}


	public static function unfix_grouping($sselid)
	{
		$conn = connection::getInstance();
		$session = session::getInstance();
		
		$ret = false;
		
		$sql = 'DELETE FROM ssel WHERE ssel_id = "'.$conn->escape_string($sselid).'" 
				AND usr_id = "'.$conn->escape_string($session->usr_id).'"';
		if($rs = $conn->query($sql))
		{
			if($conn->affected_rows() == 1)
			{
				$conn->query('DELETE FROM sselcont WHERE ssel_id = "'.$conn->escape_string($sselid).'"');
				$ret = true;
			}
		}	
		
		return $ret;
	}

	public static function getRegFields($sbas_id , $desc)
	{
		if(!self::$_regfields)
			self::load_regfields();
			
		$arrayRegFields = self::$_regfields[$sbas_id];
			
		$fields = array();
		$fields["regname"] = "";
		$fields["regdesc"] = "";
		$fields["regdate"] = "";	 
		if($sxe =  simplexml_load_string($desc))
		{
			foreach($arrayRegFields as $field=>$balise)
				$fields[$field] =  (string)$sxe->description->$balise;		
		}	
		return $fields;
	}
	
	function searchRegFields($struct)
	{ 
		$fields = null;
		$fields["regname"] = "";
		$fields["regdesc"] = "";
		$fields["regdate"] = "";
		
		if($sxe = simplexml_load_string($struct))
		{
			$z = $sxe->xpath('/record/description');
			if($z && is_array($z))
			{
				foreach($z[0] as $ki => $vi)
				{
					if($vi["regname"]=="1")
						$fields["regname"] = $ki;
					elseif($vi["regdesc"]=="1")
						$fields["regdesc"] = $ki;
					elseif($vi["regdate"]=="1")		
						$fields["regdate"] = $ki;
				}
			}
		}
		return $fields;
	}
	
	
	/**
	 * Toggle homelink status of the basket
	 * @param boolean $status
	 * @return Json serialized array
	 */
	function homelink($status='1')
	{
		$session = session::getInstance();
		$user = user::getInstance($session->usr_id);
		
		$conn = connection::getInstance();
		$ret = 'error';
		
		if($status == '1')
		{
			$isOk = false;
			
			$sql = "SELECT distinct b.sbas_id FROM bas b, sselcont c, ssel s 
					WHERE s.usr_id = '".$conn->escape_string($session->usr_id)."' 
					AND s.ssel_id='".$conn->escape_string($this->ssel_id)."' 
					AND c.ssel_id = s.ssel_id AND b.base_id = c.base_id";
			
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					if(!isset($user->_rights_sbas[$row['sbas_id']]) || !$user->_rights_sbas[$row['sbas_id']]['bas_chupub'])
					{
						$isOk = false;
						break;
					}
					$isOk = true;
				}
				$conn->free_result($rs);
			}
			
			if($isOk)
			{
				$sql = 'UPDATE ssel SET homelink="1", homelink_update = NOW() 
						WHERE ssel_id = "'.$conn->escape_string($this->ssel_id).'"';
				if($conn->query($sql))
				{
					$ret = '1';
					
					$sql = 'SELECT base_id, record_id FROM sselcont 
							WHERE ssel_id = "'.$conn->escape_string($this->ssel_id).'" ORDER BY sbas_id ASC';
					if($rs = $conn->query($sql))
					{
						while($row = $conn->fetch_assoc($rs))
						{
							answer::logEvent(phrasea::sbasFromBas($row['base_id']),$row['record_id'],'publish','homelink','');
						}
					}
				}
			} 
		}
		elseif($status == '0')
		{
			$sql = 'UPDATE ssel SET homelink="0" WHERE ssel_id = "'.$conn->escape_string($this->ssel_id).'" 
					AND usr_id="'.$conn->escape_string($session->usr_id).'"';
			if($conn->query($sql))
				$ret = '0';
	
			$sql = 'SELECT b.*, c.base_id, c.record_id FROM sselcont c, bas b 
					WHERE c.ssel_id = "'.$conn->escape_string($this->ssel_id).'" AND c.base_id = b.base_id ORDER BY sbas_id ASC';
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					answer::logEvent(phrasea::sbasFromBas($row['base_id']),$row['record_id'],'publish','nohomelink','');
				}
			}
		}
		
		$this->update_instance();
		
		$feed_cache = cache_feed::getInstance();
		$feed_cache->delete('_homelink_');
		
		return p4string::jsonencode(array('status'=>$ret));
	}
	
	
	
	function get_excerpt()
	{
		$ret = '';
		
		$i = 0;
		
		foreach($this->elements as $basket_element)
		{
			$i ++;
			if($i > 9)
				break;

			$ratio = $basket_element->width / $basket_element->height;
			$top= $left = 0;
			if($basket_element->width > $basket_element->height)//paysage
			{
				$h = 80;
				$w = $h * $ratio;
				$left = round((80-$w)/2);
			}
			else
			{
				$w = 80;
				$h = $w / $ratio;
				$top = round((80-$h)/2);
			}
			$ret .= '<div style="margin:5px;position:relative;float:left;width:80px;height:80px;overflow:hidden;">
						<img style="position:relative;top:'.$top.'px;left:'.$left.'px;width:'.$w.'px;
								height:'.$h.'px;" src="'.$basket_element->url.'" />
					</div>';
		}

		return $ret;
	}
	
	/**
	 * Return the total HD size of documents inside the basket
	 * @return number
	 */
	public function get_size()
	{
		$totSize = 0;
		$session = session::getInstance();
		
		foreach($this->elements as $basket_element)
		{
			$sd = phrasea_subdefs($session->ses_id, $basket_element->base_id, $basket_element->record_id,"document");
			if(isset($sd["document"]["size"]))
				$totSize += $sd["document"]["size"] ;		
		}
		
		$totSize = round($totSize / (1024 * 1024), 2);
		
		return $totSize;
	}
	
	function getOrderDatas()
	{
		
		$session = session::getInstance();
		
		$out = '';
		$n = 0;
		
		foreach($this->elements as $basket_element)
		{
			
		
			if($basket_element->width > $basket_element->height)
			{
				$h = (int)(82*$basket_element->height/$basket_element->width);
				$w = 82;
			}
			else
			{
				$w = (int)(82*$basket_element->width/$basket_element->height);
				$h = 82;
			}
		
			$xml = phrasea_xmlcaption($session->ses_id,$basket_element->base_id,$basket_element->record_id);
			$title = answer::format_title(phrasea::sbasFromBas($basket_element->base_id),$basket_element->record_id,$xml);
			
			$out .= '<div id="ORDER_'.$basket_element->sselcont_id.'" class="CHIM diapo" style="height:130px;overflow:hidden;">' .
						'<div class="title" title="'.$title.'" 
						style="position:relative;z-index:1200;height:30px;overflow:visible;text-align:center;">
						<span>'.$title.'</span></div>'.
						'<img ondragstart="return false;" class="CHIM_'.$basket_element->base_id.'_'.$basket_element->record_id.'" 
						src="'.$basket_element->url.'" 
						style="position:relative;width:'.$w.'px;height:'.$h.'px;
								padding:'.(floor((82-$h)/2)+9).'px '.(floor((82-$w)/2)+9).'px;z-index:1000;"/>';
			$out .= '<form style="display:none;">
				<input type="hidden" name="id" value="'.$basket_element->sselcont_id.'"/>';
			
			$out .= '<input type="hidden" name="record_id" value="'.$basket_element->record_id.'"/>';
			
			$out .= '<input type="hidden" name="base_id" value="'.$basket_element->base_id.'"/>
				<input type="hidden" name="title" value="'.
				trim(str_replace(array("\r\n","\r","\n"),array(" "," "," "),strip_tags($title))).'"/>
				<input type="hidden" name="default" value="'.$n.'"/>
			</form>';
			$out .= '</div>';
			
			$n++;
		}
	
		return $out.'<form style="display:none;" name="save">
						<input type="hidden" name="ssel_id" value="'.$this->ssel_id.'"/>
					</form>';
	}
	

	
	/**
	 * Save re-ordered basket
	 * @param Json serialized array
	 * @return Json serialized array
	 */
	public function saveOrderDatas($value)
	{
		$conn = connection::getInstance();
		
		$conn->start_transaction();
		
		$error = false;

		$value = json_decode($value);
		
		$rid_parent = $this->record_id;
		$ssel_id = $this->ssel_id;
		$sbas_id = $this->sbas_id;
		
		$cacheusers = array();
		$sselcont_equiv = array();
		
		
		if($this->is_grouping)
		{
			$sql = 'SELECT c1.sselcont_id, s.usr_id, s.ssel_id, c2.sselcont_id as equiv FROM sselcont c1, sselcont c2, ssel s 
					WHERE temporaryType="1" AND s.rid="'.$this->record_id.'" AND s.sbas_id="'.$this->sbas_id.'"
					AND s.ssel_id = c1.ssel_id AND s.ssel_id != "'.$this->ssel_id.'" AND c1.base_id = c2.base_id 
					AND c1.record_id = c2.record_id AND c2.ssel_id = "'.$conn->escape_string($ssel_id).'"';
			
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					if(!isset($cacheusers[$row['usr_id']]))
						$cacheusers[$row['usr_id']] = array();
						
					$cacheusers[$row['usr_id']][$row['ssel_id']] = $row['ssel_id'];
						
					$sselcont_equiv[$row['equiv']][] = $row['sselcont_id']; 
				}
				$conn->free_result($rs);
			}
		}

		foreach($value as $id=>$infos)
		{
			if($this->is_grouping)
			{
				$connbas = connection::getInstance($sbas_id);
				
				$sql = 'UPDATE regroup SET ord="'.$connbas->escape_string($infos->order).'" 
						WHERE rid_parent="'.$connbas->escape_string($rid_parent).'" 
						AND rid_child="' . $connbas->escape_string($infos->record_id).'"';
				
				if(!$connbas->query($sql))
					$error = true;
					
				if(isset($sselcont_equiv[trim($id)]))
				{
					$sql = "UPDATE sselcont SET ord='".$conn->escape_string(trim($infos->order))."' 
							WHERE sselcont_id IN (".implode(', ', $sselcont_equiv[trim($id)]).")";
					if(!$conn->query($sql))
						$error = true;
				}
			}
				
			$sql = "UPDATE sselcont SET ord='".$conn->escape_string(trim($infos->order))."' 
					WHERE sselcont_id='".$conn->escape_string(trim($id))."' AND ssel_id='".trim($ssel_id)."'";

			if(!$conn->query($sql))
				$error = true;
		}
			
		$cache_basket = cache_basket::getInstance();
		
		foreach($cacheusers as $usr_id => $ssel_ids)
		{
			foreach($ssel_ids as $ssel_id)
			{
				$cache_basket->delete($usr_id, $ssel_id);
				$basket_usr = self::getInstance($ssel_id, $usr_id);
				$basket_usr->set_unread();
			}
		}
		
		if(!$error)
		{
			$conn->commit();
			if($ssel_id)
			{
				$this->update_instance();
			}
		}
		else
		{
			$conn->rollback();
		}
			
		$ret = array('error'=>$error);
		
		return p4string::jsonencode($ret);
	}

	
	/**
	 * Delete the basket
	 * @return boolean
	 */
	public function delete()
	{
		$conn = connection::getInstance();
		$session = session::getInstance();
			
	 	$sql = 'UPDATE ssel SET deleted="1" WHERE ssel_id="'.$conn->escape_string($this->ssel_id).'" 
	 			AND usr_id="'.$conn->escape_string($session->usr_id).'"';
	 	
		if($conn->query($sql))
		{
			$sql = 'DELETE FROM sselnew WHERE ssel_id= "'.$conn->escape_string($this->ssel_id).'"';
			$rs = $conn->query($sql);
			
			$sql = "DELETE FROM ssel WHERE deleted='1' AND public='0'";
			$conn->query($sql);
			
			$this->delete_instance();
			
			return true;
		}
		return false;
	}
	
	/**
	 * Set the basket unread for the user
	 * @return boolean
	 */
	public function set_unread()
	{
		$conn = connection::getInstance();
		
		$sql = 'INSERT INTO sselnew (id, ssel_id, usr_id) 
				VALUES (null, "'.$conn->escape_string($this->ssel_id).'", "'.$conn->escape_string($this->usr_id).'")';
		if($conn->query($sql))
		{
			$this->noview = true;
			
			$this->update_instance();
			
			return true;
		}
		return false;
	}
	
	
	/**
	 * Set the basket read for the user
	 * @return boolean
	 */
	public function set_read()
	{
		if(!$this->noview)
			return true;
		$conn = connection::getInstance();
		$session = session::getInstance();
		
		$sql = 'DELETE FROM sselnew WHERE ssel_id="'.$conn->escape_string($this->ssel_id).'"
				AND usr_id="'.$conn->escape_string($session->usr_id).'"';
		if($conn->query($sql))
		{
			$this->noview = false;
			
			$this->update_instance();
			
			return true;
		}
		return false;
	}
	
	/**
	 * Add users to the validation process
	 * @param String datetime $expire
	 * @param Integer $usr_id
	 * @param boolean $can_agree
	 * @param boolean $can_see_others
	 * @param boolean $can_hd
	 */
	public function validation_to_users($expire, $usr_id, $can_agree, $can_see_others, $can_hd)
	{
		$conn = connection::getInstance();
		
		$sql = 'REPLACE INTO validate (id, ssel_id, created_on, updated_on, expires_on, last_reminder, 
					usr_id, confirmed, can_agree, can_see_others, can_hd)
				VALUES
					(null, "'.$conn->escape_string($this->ssel_id).'", NOW(), NOW(), 
					'.(is_null($expire) ? 'null' : '"'.$conn->escape_string($expire).'"').', 
					null, "'.$conn->escape_string($usr_id).'", 0, "'.($can_agree ? '1' : '0').'", 
					"'.($can_see_others ? '1' : '0').'", "'.($can_hd ? '1' : '0').'")';

		if($conn->query($sql))
		{
			$insert_id = $conn->insert_id();

      if($can_hd)
      {
          $cache_user = cache_user::getInstance();
          $cache_user->delete($usr_id);
      }

			foreach($this->elements as $basket_element)
			{
				$sql = 'REPLACE INTO validate_datas (id, validate_id, sselcont_id, updated_on, agreement) 
						VALUES (null, "'.$insert_id.'", "'.$basket_element->sselcont_id.'", null, 0)';
				$conn->query($sql);
			}
		}
		
		$this->update_instance();
		
	}
	
	/**
	 * Load users in current validation process
	 * @return void
	 */
	protected function load_validation_users()
	{
		$conn = connection::getInstance();
		
		$sql = 'SELECT id, usr_id, confirmed, can_agree, can_see_others, can_hd 
				FROM validate WHERE ssel_id="'.$conn->escape_string($this->ssel_id).'"';
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$this->validating_users[$row['usr_id']] = array(
															'usr_id'		=> $row['usr_id'],
															'usr_name'		=> user::getInfos($row['usr_id']),
															'confirmed'		=> $row['confirmed'],
															'can_agree'		=> $row['can_agree'],
															'can_see_others'=> $row['can_see_others'],
															'can_hd'		=> $row['can_hd']
															);
			}
			$conn->free_result($rs);
		}
		return $this;
	}
	
	public function get_first_element()
	{
		foreach($this->elements as $basket_element)
			return $basket_element;
		return null;
	}
	
	public function get_validation_end_date()
	{
		if(!$this->valid || !$this->validation_end_date)
			return null;
		return phraseadate::getPrettyString(new DateTime($this->validation_end_date));
	}
	
	public function is_validation_finished()
	{
		if(!$this->valid || !$this->validation_end_date)
			return null;
		$end = new DateTime($this->validation_end_date);
		$now = new DateTime();
		return ($now>$end);
	}
	
	public function is_confirmed()
	{
		if(!$this->valid)
			return null;

		return $this->validation_is_confirmed == '0' ? false : true;
	}
	
	public function get_validation_infos()
	{
		if($this->is_mine)
		{
			if($this->is_validation_finished())
				return sprintf(_('Vous aviez envoye cette demande a %d utilisateurs'), (count($this->validating_users) - 1));
			else
				return sprintf(_('Vous avez envoye cette demande a %d utilisateurs'), (count($this->validating_users) - 1));
		}
		else
		{
			if($this->validation_see_others)
				return sprintf(_('Processus de validation recu de %s et concernant %d utilisateurs'), user::getInfos($this->usr_id) ,(count($this->validating_users) - 1));
			else
				return sprintf(_('Processus de validation recu de %s'), user::getInfos($this->usr_id));
		}
	}
	
	public function set_released()
	{
		$conn = connection::getInstance();
		$session = session::getInstance();
		
		$sql = 'UPDATE validate SET confirmed="1" WHERE ssel_id = "'.$this->ssel_id.'" AND usr_id="'.$session->usr_id.'"';
		
		if($conn->query($sql))
		{
			$evt_mngr = eventsmanager::getInstance();
			
			$sql = 'SELECT s.usr_id FROM validate v, ssel s 
					WHERE s.ssel_id = v.ssel_id 
					AND v.usr_id="'.$session->usr_id.'" 
					AND v.ssel_id = "'.$this->ssel_id.'"';
			if($rs = $conn->query($sql))
			{
				if($row = $conn->fetch_assoc($rs))
				{
					$to = $row['usr_id'];
				}
				$conn->free_result($rs);
			}
			
			$params = array(
				'ssel_id'	=> $this->ssel_id,
				'from'		=> $session->usr_id,
				'to'		=> $to
			);
			
			$evt_mngr->trigger('__VALIDATION_DONE__', $params);
			return true;
		}
		return false;
	}

	public function to_json()
	{
		return p4string::jsonencode($this->to_array());
	}
	
	public function to_array()
	{
		$ret = array();
		
		foreach($this as $key=>$value)
		{
			if($key == 'elements')
				continue;
			$ret[$key] = $value;
		}
		foreach($this->elements as $sselcont_id=>$basket_element)
			$ret['elements'][$sselcont_id] = $basket_element->to_array();
		
		return $ret;
	}
	
	public function sort($order)
	{
		if(!$this->valid || !in_array($order, array('asc', 'desc')))
			return;
			
		if($order == 'asc')
			uasort($this->elements,array('basket','order_validation_asc'));
		else
			uasort($this->elements,array('basket','order_validation_desc'));
	}
	
	protected function order_validation_asc($a, $b)
	{
		if(!isset($a->avrDisAgree) || !isset($b->avrDisAgree))
		{
			return 0;
		}	
		$comp = $a->avrDisAgree - $b->avrDisAgree;
		
		if($comp == 0)
		{
			$comp = $b->avrAgree - $a->avrAgree;
			if($comp == 0)
				return 0;
		}	
		return $comp > 0 ? -1 : 1;
	}
	
	protected function order_validation_desc($a, $b)
	{
		if(!isset($a->avrAgree) || !isset($b->avrAgree))
		{
			return 0;
		}	
		$comp = $a->avrAgree - $b->avrAgree;
		
		if($comp == 0)
		{
			$comp = $b->avrDisAgree - $a->avrDisAgree;
			if($comp == 0)
				return 0;
		}	
		return $comp > 0 ? -1 : 1;
	}
}