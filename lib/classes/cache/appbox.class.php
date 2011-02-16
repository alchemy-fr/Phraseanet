<?php

class cache_appbox
{

	private static $_instance = false;
	var $_c_obj = false;
	
	function __construct()
	{
		$this->_c_obj = cache::getInstance();
	}
	
	
	/**
	 * @return cache_appbox
	 */
	public static function getInstance()
	{
		
		if (!(self::$_instance instanceof self))
            self::$_instance = new self();
 
        return self::$_instance;
		
	}
	
	public function get($id)
	{
		return $this->_c_obj->get(GV_ServerName.'_appbox_'.$id);
	}
	
	public function set($id,$value)
	{
		return $this->_c_obj->set(GV_ServerName.'_appbox_'.$id,$value);
	}

	public function delete($id)
	{
//		if(in_array($id, array('sbas_names','bas_names')))
//		{
//			$this->delete('list_bases');
//		}
		if($id == 'list_bases')
		{
			
			$this->delete('bas_names');
			$this->delete('sbas_names');
			$this->delete('sbas_from_bas');
			
			$avLanguages = user::detectlanguage();
			foreach($avLanguages as $lng=>$languages)
			{
				foreach($languages as $locale=>$language)
				{
					$this->delete('bases_settings_'.$locale);
				}
			}	
		}
		
		return $this->_c_obj->delete(GV_ServerName.'_appbox_'.$id);
	}
	public function is_ok()
	{		
		return $this->_c_obj->is_ok();
	}
	
	
	
}