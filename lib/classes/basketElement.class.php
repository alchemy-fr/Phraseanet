<?php
class basketElement
{
	protected $choices;
	protected $is_validation_item = false;
	
	protected $validate_id;
	
	protected $my_agreement;
	protected $my_note;
	
	protected $avrAgree;
	protected $note = "";
	protected $avrDisAgree;
	
	
	protected $sselcont_id;
	protected $base_id;
	protected $record_id;
	protected $parent_record_id;
	protected $order;
	protected $ssel_id;
	protected $display_id;
	protected $height;
	protected $width;
	protected $url;
	
	
	protected $is_new = false;
	
	
	function __construct($sselcont_id)
	{
		if(!$sselcont_id)
		{
			$this->is_new = true;
			return $this;
		}
		
		$session = session::getInstance();
		$conn = connection::getInstance();
		
		$sql = 'SELECT s.usr_id as owner, v.id as validate_id, v.can_see_others, c.base_id, c.record_id, c.ord, c.ssel_id, v.usr_id, d.agreement, d.note, d.updated_on 
				FROM (sselcont c, ssel s) LEFT JOIN (validate v, validate_datas d) ON (d.sselcont_id = c.sselcont_id AND d.validate_id = v.id ) 
				WHERE s.ssel_id = c.ssel_id AND c.sselcont_id = "'.$conn->escape_string($sselcont_id).'"';

		$first = true;
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				if($row['validate_id'] !== NULL)
				{
					$this->is_validation_item = true;
					
					if($row['owner'] == $session->usr_id)
						$see_others = true;
					else
						$see_others = $row['can_see_others'] == '1' ? true : false;
						
					if(!$see_others)
					{
						if($row['usr_id'] != $session->usr_id)
							continue;
					}
				}

				if($first)
				{
					$this->base_id 		= $row['base_id'];
					$this->sselcont_id 	= $sselcont_id;
					$this->record_id 	= $row['record_id'];
					$this->ssel_id 		= $row['ssel_id'];
					$this->order 		= $row['ord'];
				
					if($this->is_validation_item)
					{
						$this->choices 		= array();
						$this->avrAgree 	= 0;
						$this->avrDisAgree 	= 0;
						$this->avrRate		= 0;
					}
					
					$this->load_datas();
					$first = false;
				}
				
				if($this->is_validation_item)
				{
					if($row['usr_id'] == $session->usr_id)
					{
						$this->my_agreement = $row['agreement'];
						$this->my_note 		= $row['note'];
						$this->validate_id	= $row['validate_id'];
					}	
					$this->choices[]	=  array(
												'usr_id'=>$row['usr_id'], 
												'usr_name'=>user::getInfos($row['usr_id']),
												'is_mine'=>($row['usr_id'] == $session->usr_id), 
												'agreement'=>$row['agreement'], 
												'updated_on'=>$row['updated_on'], 
												'note'=>$row['note']
											);
					$this->avrAgree		+= $row["agreement"] > 0 ? 1 : 0;
					$this->avrDisAgree	+= $row["agreement"] < 0 ? 1 : 0;
				}
			}
			$conn->free_result($rs);
		}
		
		return $this;
	}

	function __get($name)
	{
        switch($name)
        {
        	case 'sbas_id':
        		return phrasea::sbasFromBas($this->base_id);
        		break;
        }
		
		if (isset($this->$name))
		{
            return $this->$name;
        }
        
        $trace = debug_backtrace();
        trigger_error('Undefined property via __get(): ' . $name . ' in ' . $trace[0]['file'] .
        ' on line ' . $trace[0]['line'],   E_USER_NOTICE);
        return null;
	}
	
	public function __isset($name)
	{
		if (isset($this->$name))
		{
			return true;
		}
		if($name == 'sbas_id')
			return true;
			
		return false;
	}
	
	function __set($name, $value)
	{
	
		$this->is_draft = true;
		switch($name)
		{
			case 'base_id':
			case 'record_id':
			case 'sselcont_id':
			case 'parent_record_id':
				if($this->is_new)
					$this->$name = $value;
				break;
			case 'order':
			case 'is_validation_item':
			default:
				$this->$name = $value;
				break;
		}
		return;
	}

	function create_into_basket($ssel_id, $base_id, $record_id, $adjust_validation_datas, $fixing)
	{
		if(!$ssel_id)
		{
			throw new Exception('A basket must be saved before addings elements');
		}
		
		$session = session::getInstance();
		$user = user::getInstance($session->usr_id);
		$conn = connection::getInstance();
		
		if(!$conn)
			throw new Exception('No connection.');
		
		$this->ssel_id = $ssel_id;

	
		
		if(!isset($user->_rights_bas[$base_id]))
			throw new Exception('You do not have rights on this base.');
		if(!$user->_rights_bas[$base_id]['canputinalbum'])
			throw new Exception('You do not have rights to use this document in basket.');
			
		$exists = false;
		
		$sql = 'SELECT sselcont_id FROM sselcont 
				WHERE ssel_id="'.$conn->escape_string($ssel_id).'" 
				AND base_id="'.$conn->escape_string($base_id).'" 
				AND record_id="'.$conn->escape_string($record_id).'"';
		
		if($rs = $conn->query($sql))
		{
			if($conn->num_rows($rs)>0)
				$exists = true;
			$conn->free_result($rs);
		}
		if($exists)
		{
			throw new Exception('Some elements already are in this basket');
		}
		
		
		//If member of grouping
		if($this->parent_record_id)
		{
			$sbas_id = phrasea::sbasFromBas($base_id);
			$connbas = connection::getInstance($sbas_id);

			if(!isset($user->_rights_bas[$base_id]) || (!$user->_rights_bas[$base_id]['canaddrecord'] && $fixing === false))
				throw new Exception ('You do not have right');
			
			if(!$sbas_id)
				throw new Exception ('Unknown database');
				
			if(phrasea_isgrp($session->ses_id,$base_id,$record_id))
				throw new Exception ('Can\'t add grouping to grouping');
			
			$ord = 0 ;		
			$sql = "SELECT (max(ord)+1) as ord FROM regroup WHERE rid_parent='".$connbas->escape_string($this->parent_record_id)."'";
			if($rs = $connbas->query($sql) )
			{
				if($row = $connbas->fetch_assoc($rs))
				{
					$ord = $row["ord"];
				}
				$connbas->free_result($rs);
			}
			if(trim($ord) == '')
				$ord = 0;
			
			$sql = 'INSERT INTO regroup (rid_parent, rid_child, dateadd,ord) 
					VALUES ("'.$connbas->escape_string($this->parent_record_id).'","'.$connbas->escape_string($record_id).'", 
							NOW(),"'.$connbas->escape_string($ord).'")';
			$connbas->query($sql);
			$sql = 'UPDATE record SET moddate = NOW() WHERE record_id = "'.$connbas->escape_string($this->parent_record_id).'"';
			$connbas->query($sql);
		}
		
		if(($id = $conn->getId("SSELCONT"))!== null)
		{
			$ord = 0;
			if($rs = $conn->query('SELECT max(ord) as ord FROM sselcont WHERE ssel_id="'.$conn->escape_string($ssel_id).'"'))
			{
				if($row = $conn->fetch_assoc($rs) )
					$ord = (int)$row['ord'] + 1;
				$conn->free_result($rs);							
			}
			
			$sqlUp = ' INSERT INTO sselcont (sselcont_id, ssel_id, base_id, record_id,ord) 
						VALUES ("'.$conn->escape_string($id).'", "'.$conn->escape_string($ssel_id).'", 
						"'.$conn->escape_string($base_id).'", "'.$conn->escape_string($record_id).'",
						"'.$conn->escape_string($ord).'") ';

			if($conn->query($sqlUp))
			{
				$this->base_id 		= $base_id;
				$this->record_id 	= $record_id;
				$this->order 		= $ord;
				$this->sselcont_id 	= $id;
				$this->load_datas();
				
				$ret['error'] = false;
				$ret['datas'][] = $id;
				$sql = 'UPDATE ssel SET updater=NOW() WHERE ssel_id="'.$conn->escape_string($ssel_id).'"';
				$conn->query($sql);
				
				if($adjust_validation_datas == 'myvalid')
				{
					$sql = 'INSERT INTO validate_datas 
					(SELECT distinct null as id, id as validate_id, "'.$conn->escape_string($id).'" as sselcont_id,
					 null as updated_on, 0 as agreement, "" as note 
					FROM validate WHERE ssel_id="'.$conn->escape_string($ssel_id).'") ';
					
					$conn->query($sql);
				}
			}
		}
		
	

			
		if($this->parent_record_id)
		{
			$sql = 'SELECT null as id, ssel_id, usr_id 
							FROM ssel 
							WHERE usr_id!="'.$conn->escape_string($session->usr_id).'" 
							AND rid="'.$conn->escape_string($this->parent_record_id).'" 
							AND sbas_id = "'.$conn->escape_string($sbas_id).'" AND temporaryType="1"';
		
			$cache_basket = cache_basket::getInstance();
			if($rs = $conn->query($sql) )
			{
				while($row = $conn->fetch_assoc($rs))
				{
					
					if(($id = $conn->getId("SSELCONT"))!== null)
					{
						$ord = 0;
						if($rs2 = $conn->query('SELECT max(ord) as ord FROM sselcont WHERE ssel_id="'.$conn->escape_string($row['ssel_id']).'"'))
						{
							if($row2 = $conn->fetch_assoc($rs2) )
								$ord = (int)$row2['ord'] + 1;
							$conn->free_result($rs2);							
						}
						
						
						$sqlUp = ' INSERT INTO sselcont (sselcont_id, ssel_id, base_id, record_id,ord) 
									VALUES ("'.$conn->escape_string($id).'", "'.$conn->escape_string($row['ssel_id']).'", 
									"'.$conn->escape_string($base_id).'", "'.$conn->escape_string($record_id).'",
									"'.$conn->escape_string($ord).'") ';

						
						if($conn->query($sqlUp))
						{
							$sql = 'UPDATE ssel SET updater=NOW() WHERE ssel_id="'.$conn->escape_string($row['ssel_id']).'"';
							$conn->query($sql);
						}
						
						$conn->query('INSERT INTO sselnew (null, "'.$row['ssel_id'].'", "'.$row['usr_id'].'"');
						$cache_basket->delete($row['usr_id'], $row['ssel_id']);
					}
					
				}
				$conn->free_result($rs);
			}
		}
			
		$session = session::getInstance();
		$cache_basket = cache_basket::getInstance();
		$cache_basket->delete($session->usr_id, $ssel_id);
			
		$this->is_new = false;
		
		return true;
	}
	
	protected function load_datas()
	{
		$session = session::getInstance();
		
		$thumbnail = answer::getThumbnail($session->ses_id, $this->base_id, $this->record_id);
		
		$this->url		= $thumbnail['thumbnail'];
		$this->height	= $thumbnail['h'];
		$this->width	= $thumbnail['w'];
		
	
		if($thumbnail['w'] > $thumbnail['h'])
		{
			$h_82	= 82 * $thumbnail['h'] / $thumbnail['w'];
			$w_82	= 82;
			$h_100	= 100 * $thumbnail['h'] / $thumbnail['w'];
			$w_100	= 100;
			$t		= (82 - $h_82) * 50 / 82;
			$squarebox_top	=  0;
			$squarebox_left	=  (82 - $thumbnail['w'] / $thumbnail['h'] * 82) / 2 * 100 / 82;
		}
		else
		{
			$w_82	= 82 * $thumbnail['w'] / $thumbnail['h'];
			$h_82	= 82;
			$w_100	= 100 * $thumbnail['w'] / $thumbnail['h'];
			$h_100	= 100;
			$t		= 0;
			$squarebox_top	=  (82 - 82 / $thumbnail['w'] * $thumbnail['h']) / 2 * 100 / 82;
			$squarebox_left	=  0;
		}
		
		$this->orientation	= $thumbnail['orientation'];
		$this->height_82	= (int)$h_82;
		$this->width_82		= (int)$w_82;
		$this->height_100	= (int)$h_100;
		$this->width_100	= (int)$w_100;
		$this->top			= (int)$t;
		$this->squarebox_top	= (int)$squarebox_top;
		$this->squarebox_left	= (int)$squarebox_left;
		
		return;
	}

	function set_note($note)
	{
		if(!$this->is_validation_item)
		{
			throw new Exception ('Element is not a validation item');
			return false;
		}
		$conn = connection::getInstance();
		
		$note = strip_tags($note);
		
		if(!$this->validate_id)
			return false;
		
		$sql = 'UPDATE validate_datas SET note="'.$conn->escape_string($note).'" 
				WHERE sselcont_id = "'.$conn->escape_string($this->sselcont_id).'" 
				AND validate_id = "'.$conn->escape_string($this->validate_id).'"';
		
		if($conn->query($sql))
		{
			$this->my_note = $note;
			foreach($this->choices as $key=>$values)
			{
				if($values['is_mine'])
				{
					$this->choices[$key]['note'] = $note;
					break;
				}
			}
			
			$session = session::getInstance();
			$cache_basket = cache_basket::getInstance();
			$cache_basket->delete($session->usr_id, $this->ssel_id);
			return true;
		}
		
		return false;
	}
	
	function load_users_infos()
	{
		if(!$this->is_validation_item)
		{
			throw new Exception ('Element is not a validation item');
			return false;
		}
		
		foreach($this->choices as $key=>$value)
		{
			$this->choices[$key]['usr_display'] = user::getInfos($value['usr_id']);
		}
	}
	
	function get_note_count()
	{
		if(!$this->is_validation_item)
		{
			throw new Exception ('Element is not a validation item');
			return false;
		}
		
		$n = 0;
		foreach($this->choices as $key=>$value)
		{
			if(trim($value['note']) != '')
				$n++;
		}
		return $n;
	}
	
	function set_agreement($boolean)
	{
		$session = session::getInstance();
		if(!$this->is_validation_item)
		{
			throw new Exception ('Element "'.$this->sselcont_id.'" is not a validation item');
			return false;
		}
		$conn = connection::getInstance();
		
		$ret = array('error'=>true,'datas'=>_('Erreur lors de la mise a jour des donnes '));

		if(!$this->validate_id)
			return $ret;
		
		$boolean = in_array($boolean, array('1','-1')) ? $boolean : '0';
		
		$sql = 'UPDATE validate_datas SET agreement="'.$conn->escape_string($boolean).'" 
				WHERE sselcont_id = "'.$conn->escape_string($this->sselcont_id).'" 
				AND validate_id = "'.$conn->escape_string($this->validate_id).'"';

		if($conn->query($sql))
		{
			$cache_basket = cache_basket::getInstance();
			$cache_basket->delete($session->usr_id, $this->ssel_id);
			return array('error'=>false,'datas'=>'');
		}	
		return $ret;
	}
	
	public function get_caption()
	{
		$session = session::getInstance();
		
		$xml = phrasea_xmlcaption($session->ses_id, $this->base_id, $this->record_id);
		
		return answer::format_caption($this->base_id, $this->record_id, $xml, false);
	}
	
	public function get_title()
	{
		$session = session::getInstance();
		
		$xml = phrasea_xmlcaption($session->ses_id, $this->base_id, $this->record_id);
		
		return answer::format_title(phrasea::sbasFromBas($this->base_id), $this->record_id, $xml);
	}

	public function get_preview()
	{
		$preview = answer::get_preview($this->base_id, $this->record_id, true);
		
		return $preview;
	}
	
	public function to_json()
	{
		return p4string::jsonencode($this->to_array());
	}
	
	public function get_preview_icon()
	{
		$session = session::getInstance();
		$thumbnail = answer::getThumbnail($session->ses_id, $this->base_id, $this->record_id,GV_zommPrev_rollover_clientAnswer);
		
		return '<div title="'.str_replace(array('&','"'),array('&amp;','&quot;'),answer::get_preview_rollover($this->base_id, $this->record_id, $session->ses_id, true, $session->usr_id,$thumbnail['preview'],$thumbnail['type'])).'" class="previewTips"></div>';
	}
	
	public function get_status()
	{
		$session = session::getInstance();
		$dstatus = status::getDisplayStatus();
		
		$sbas_id = phrasea::sbasFromBas($this->base_id);
		$dstatus = status::getDisplayStatus();
		$status = strrev(phrasea_status($session->ses_id, $this->base_id, $this->record_id));
		
		while(strlen($status) < 64)
			$status .= '0';
			
		$statuses = '';
		
		$user = user::getInstance($session->usr_id);
		
		if($status && isset($dstatus[$sbas_id]))
		{			
			foreach($dstatus[$sbas_id] as $n=>$statbit)
			{
				if(!isset($status[$n]))
					continue;
				if($statbit['printable'] == '0' && (!isset($user->_rights_bas[$this->base_id]) || $user->_rights_bas[$this->base_id]['chgstatus'] === false))
				{
					continue;
				}	
	
				if($status[$n] === '1')
				{
					if($statbit["img_on"])
					{
						$statuses .= "<img style=\"margin:1px;cursor:help;\" src=\"".$statbit["img_on"]."\" title=\"".(isset($statbit["labelon"])?$statbit["labelon"]:$statbit["lib"])."\"/>";
					}
				}
				else
				{
					if($statbit["img_off"])
					{
						$statuses .= ("<img style=\"margin:1px;cursor:help;\" src=\"".$statbit["img_off"]."\" title=\"".(isset($statbit["labeloff"])?$statbit["labeloff"]:("non-".$statbit["lib"]))."\"/>");
					}
				}
			}
		}
		return $statuses;
	}
	
	
	public function to_array()
	{
		$ret = array();
		
		foreach($this as $key=>$value)
		{
			$ret[$key] = $value;
		}
		$ret['preview'] = $this->get_preview();
		$ret['caption'] = $this->get_caption();
		$ret['title'] = $this->get_title();
		
		if($this->is_validation_item)
		{
			$this->load_users_infos();
		}
		return $ret;
	}
	
}