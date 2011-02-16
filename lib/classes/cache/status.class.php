<?php

class cache_status
{

	private static $_instance = false;
	var $_c_obj = false;
	
	function __construct()
	{
		$this->_c_obj = cache::getInstance();
	}
	
	
	/**
	 * @return cache_status
	 */
	public static function getInstance()
	{
		
		if (!(self::$_instance instanceof self))
            self::$_instance = new self();
 
        return self::$_instance;
		
	}
	public function get($usr_id)
	{
			
		return $this->_c_obj->get(GV_ServerName.'_status_'.$usr_id);
	}
	
	public function set($usr_id,$value)
	{
		$statuspool = array();
		
		if(($tmp = $this->_c_obj->get(GV_ServerName.'_statuspool_')) != false)
			$statuspool = array();
		
		$statuspool[] = $usr_id;
			
		if($this->_c_obj->set(GV_ServerName.'_statuspool_',$statuspool))
			return $this->_c_obj->set(GV_ServerName.'_status_'.$usr_id,$value);
			
		return false;
	}
	
	public function delete($usr_id)
	{
		
		return $this->_c_obj->delete(GV_ServerName.'_status_'.$usr_id);
	}
	
}