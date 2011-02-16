<?php
class lazaret
{
	protected $storage = array();
	
	function __construct()
	{
		$conn = connection::getInstance();
		$session = session::getInstance();
		$user = user::getInstance($session->usr_id);
		
		$base_ids = array();
		
		foreach($user->_rights_bas as $base_id=>$rights)
		{
			if($rights['canaddrecord'] === true)
				$base_ids[] = $base_id;
		}
		
		$sql = "SELECT id, filepath, filename, base_id, uuid, errors, created_on, usr_id 
				FROM lazaret WHERE base_id IN (".implode(', ', $base_ids).")
				ORDER BY uuid, id DESC";
		
		$lazaret_group = array();
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$sbas_id = phrasea::sbasFromBas($row['base_id']);
				
				$row['uuid'] = trim($row['uuid']) !== '' ? $row['uuid'] : mt_rand(1000000,9999999);
				
				$key = $row['uuid'].'__'.$sbas_id;
				
				$pathfile = GV_RootPath.'tmp/lazaret/'.$row['filepath'];
				
				if(!file_exists($pathfile))
				{
					$sql = 'DELETE FROM lazaret WHERE id="'.$row['id'].'"';
					$conn->query($sql);
					
					if(file_exists($pathfile.'_thumbnail.jpg'))
						unlink($pathfile.'_thumbnail.jpg');
					continue;
				}
				if(!isset($lazaret_group[$key]))
					$lazaret_group[$key] = array('candidates'=>array(),'potentials'=>array());
					
				$pathfile_thumbnail = $pathfile.'_thumbnail.jpg';
					
				if(is_file($pathfile_thumbnail) && $gis = @getimagesize($pathfile_thumbnail))
					$is = $gis;
				else
					$is = array(80,80);
					
				$thumbnail = array(
					'w'			=> $is[0],
					'h'			=> $is[1],
					'thumbnail'	=> '/upload/lazaret_image.php?id='.$row['id']
				);
				
				$row['created_on'] = new DateTime($row['created_on']);
				
				if($row['usr_id'])
					$row['usr_id'] = user::getInstance($row['usr_id']);
				else
					$row['usr_id'] = array('display_name'=>_('tache d\'archivage'));

				$lazaret_group[$key]['candidates'][$row['id']] = array_merge(
						array(
							'thumbnail'	=>$thumbnail,
							'title'		=> $row['filename'],
							'caption'	=> '',
							'potential_relationship'=>array()
						),
						$row
					);
			}
			$conn->free_result($rs);
		}
		
		foreach($lazaret_group as $key_group=>$lazaret)
		{
			$infos = explode('__', $key_group);
			
			$uuid = $infos[0];
			$sbas_id = $infos[1];
			
			$connbas = connection::getInstance($sbas_id);
			
			if(!$connbas)
			{
				continue;
			}
			
			$sql = "SELECT record_id, coll_id FROM record WHERE uuid='".$connbas->escape_string($uuid)."'";

			if($rs = $connbas->query($sql))
			{
				while($row = $connbas->fetch_assoc($rs))
				{
					$record_id = $row['record_id'];
					
					$base_id = phrasea::baseFromColl($sbas_id, $row['coll_id']);
					
					$xml = phrasea_xmlcaption($session->ses_id, $base_id, $row['record_id']);
					
					$thumbnail = answer::getThumbnail($session->ses_id, $base_id, $row['record_id'],GV_zommPrev_rollover_clientAnswer);
						
					$lazaret_group[$key_group]['potentials'][$record_id] = array(
						'record_id'	=> $row['record_id'],
						'base_id'	=> $base_id,
						'thumbnail'	=> $thumbnail,
						'title'		=> answer::format_title($sbas_id, $row['record_id'], $xml),
						'caption'	=> answer::format_caption($base_id, $row['record_id'], $xml, false),
						'preview'	=> answer::get_preview_rollover($base_id, $row['record_id'],$session->ses_id, true, $session->usr_id,$thumbnail['preview'],$thumbnail['type'])
					);
				}
				$connbas->free_result($rs);
			}
				
			foreach($lazaret['candidates'] as $lazaret_id=>$lazaret_item)
			{
				foreach($lazaret_group[$key_group]['potentials'] as $record_id => $properties)
				{
					$can_substitute = false;
					
					$potential_base_id = $properties['base_id'];
					
					if(isset($user->_rights_bas[$potential_base_id]))
					{
						if($user->_rights_bas[$potential_base_id]['canaddrecord'] && $user->_rights_bas[$potential_base_id]['candeleterecord'])
							$can_substitute = false;
					}
						
					$lazaret_group[$key_group]['candidates'][$lazaret_id]['potential_relationship'][$record_id] = array(
						'can_substitute'	=> $can_substitute,
						'same_coll'			=> ($potential_base_id == $lazaret_item['base_id']),
						'title'				=> $properties['title']
					);
				}
			}
		}
		$this->elements = $lazaret_group;
		
		return $this;
	}
	
	public function get_count()
	{
		$conn = connection::getInstance();
		$session = session::getInstance();
		$user = user::getInstance($session->usr_id);
		
		$base_ids = array();
		
		foreach($user->_rights_bas as $base_id=>$rights)
		{
			if($rights['canaddrecord'] === true)
				$base_ids[] = $base_id;
		}
		
		$sql = "SELECT id, filepath, filename, base_id, uuid, created_on, usr_id 
				FROM lazaret WHERE base_id IN (".implode(', ', $base_ids).")
				ORDER BY uuid, id DESC";
		
		$n = false;
		
		if($rs = $conn->query($sql))
		{
			if($count = $conn->num_rows($rs))
				$n = $count;
			$conn->free_result($rs);
		}
		
		return $n;
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
	
	
}