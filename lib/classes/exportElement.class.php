<?php
class exportElement
{
	protected $storage = array();
	
	protected static $_order_masters;
	
	function __construct($base_id, $record_id, $directory='', $remain_hd=false)
	{
		$this->load_masters();
		
		$this->base_id		= $base_id;
		$this->record_id	= $record_id;
		$this->type			= 'unknown';
		$this->directory	= $directory;
		$this->remain_hd	= $remain_hd;
		$this->size 		= array();
		
		$this->get_actions($remain_hd);
	}
	
	protected function load_masters()
	{
		if(self::$_order_masters)
			return $this;
			
		$conn = connection::getInstance();
		
		$sql = 'SELECT usr_id, base_id FROM order_masters';
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				if(!isset(self::$_order_masters[$row['base_id']]))
					self::$_order_masters[$row['base_id']] = array();
					
				self::$_order_masters[$row['base_id']][] = $row['usr_id']; 
			}
			$conn->free_result($rs);
		}
		return $this;
	}
	
	protected function get_actions()
	{
		$this->downloadable	= $downloadable = array();
		$this->orderable	= $orderable = array();
			
		$session = session::getInstance();
		
		$sd = phrasea_subdefs($session->ses_id, $this->base_id, $this->record_id);
		
		$sbas_id = phrasea::sbasFromBas($this->base_id);	
		
		$user = user::getInstance($session->usr_id);	
		
		if(isset($sd['document']) && (isset($user->_rights_bas[$this->base_id]) || isset($user->_rights_records[$this->base_id.'_'.$this->record_id])))
		{
		
			$subdefgroups = databox::get_subdefs($sbas_id);
			
			$document_type = $sd['document']['type'];
			
			$this->type = $document_type;
			
			$subdefs = isset($subdefgroups[$document_type]) ? $subdefgroups[$document_type] : $subdefgroups['image'];
			
			$go_dl = array(
				'document'	=> false,
				'preview'	=> false,
				'thumbnail' => true
			);
	
			if(isset($user->_rights_bas[$this->base_id]))
			{
				if($user->_rights_bas[$this->base_id]['candwnldhd'])
				{
					$go_dl['document'] = true;
				}
				if($user->_rights_bas[$this->base_id]['candwnldpreview'])
				{
					$go_dl['preview'] = true;
				}
			}
			if(isset($user->_rights_records[$this->base_id.'_'.$this->record_id]))
			{
				$go_dl['document'] = true;
				$go_dl['preview'] = true;
			}
			
			$go_cmd = (isset(self::$_order_masters[$this->base_id]) && isset($user->_rights_bas[$this->base_id]) && $user->_rights_bas[$this->base_id]['cancmd']);
			
			$orderable['document'] = false;
			$downloadable['document'] = false;
			
			if(isset($sd['document']) && is_file(p4string::addEndSlash($sd['document']["path"]).$sd['document']["file"]))
			{
				if($go_dl['document'] === true)
				{
					if(isset($user->_rights_bas[$this->base_id]) && $user->_rights_bas[$this->base_id]['restrict_dwnld'])
					{
						$this->remain_hd--; 
						if($this->remain_hd >= 0)
							$downloadable['document'] = array('class'=>'document','label'=>_('document original'));
					}
					else
						$downloadable['document'] = array('class'=>'document','label'=>_('document original'));
				}
				if($go_cmd === true)
				{
					$orderable['document'] = true;
				}
				
				$this->add_count('document', $sd['document']['size']);
			}
			
			
			foreach($subdefs as $subdef)
			{
				$name = (string)$subdef->attributes()->name;
				$class = (string)$subdef->attributes()->class;
				
				
				$subdef_label = $name;
				foreach($subdef->label as $label)
				{
					if(trim((string)$label) == '')
						continue;
						
					$subdef_lng = (string)$label->attributes()->lang;
					
					if($subdef_lng == $session->usr_i18n)
					{
						$subdef_label = (string)$label;
						break;
					}
					elseif(trim($subdef_lng) == '')
					{
						$subdef_label = (string)$label;
					}
				}
				
				$downloadable[$name] = false;
				
				$downloadable_settings = p4field::isyes($subdef->attributes()->downloadable);
				
				if(!$downloadable_settings || $go_dl[$class] === false)
				{
					continue;
				}
					
				if($go_dl[$class])
				{
					if(isset($sd[$name]) && is_file(p4string::addEndSlash($sd[$name]["path"]).$sd[$name]["file"]))
					{
						if($class == 'document')
						{
							
							if($user->_rights_bas[$this->base_id]['restrict_dwnld'])
							{
								$this->remain_hd--; 
								if($this->remain_hd >= 0)
									$downloadable[$name] = array('class'=>$class,'label'=>$subdef_label);
							}
							else
								$downloadable[$name] = array('class'=>$class,'label'=>$subdef_label);
						}
						else
							$downloadable[$name] = array('class'=>$class,'label'=>$subdef_label);
							
						$this->add_count($name, $sd[$name]['size']);
					}
				}
			}
		}
	
		$xml = phrasea_xmlcaption($session->ses_id, $this->base_id, $this->record_id);
		
		if($xml)
		{
			$downloadable['caption'] = array('class'=>'caption','label'=>_('caption XML'));
			$downloadable['caption-yaml'] = array('class'=>'caption','label'=>_('caption YAML'));
			$this->add_count('caption', strlen($xml));
			$this->add_count('caption-yaml', strlen(strip_tags($xml)));
		}
		
		$this->downloadable = $downloadable;
		$this->orderable = $orderable;
		
		return $this;
	}
	
	private function add_count($name, $size)
	{
		if(!$this->size)
		{
			$objectsize = array();
		}
		else
			$objectsize = $this->size;
			
		$objectsize[$name] = $size;
		
		$this->size = $objectsize;
		
		return $this;
	}
	
	function __get($key)
	{
		if(isset($this->storage[$key]))
		{
			return $this->storage[$key];
		}
		return null;
	}
	
	function __set($key, $value)
	{
		$this->storage[$key] = $value;
		
		return $this;
	}
	
	
	
	
}