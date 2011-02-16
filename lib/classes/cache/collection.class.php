<?php

class cache_collection
{

	private static $_instance = false;
	var $_c_obj = false;

	function __construct()
	{
		$this->_c_obj = cache::getInstance();
	}
	
	
	/**
	 * @return cache_collection
	 */
	public static function getInstance()
	{
		
		if (!(self::$_instance instanceof self))
            self::$_instance = new self();
 
        return self::$_instance;
		
	}
	public function get($base_id,$what)
	{
			
		return $this->_c_obj->get(GV_ServerName.'_collection_'.$base_id.'_'.$what);
	}
	
	public function set($base_id,$what,$bin)
	{
		
		return $this->_c_obj->set(GV_ServerName.'_collection_'.$base_id.'_'.$what,$bin);
	}
	
	public function delete($base_id,$what)
	{
		
		return $this->_c_obj->delete(GV_ServerName.'_collection_'.$base_id.'_'.$what);
	}
	
	
	
}