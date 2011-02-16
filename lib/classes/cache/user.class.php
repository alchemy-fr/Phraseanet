<?php

class cache_user
{

	private static $_instance = false;
	var $_c_obj = false;
	
	function __construct()
	{
		$this->_c_obj = cache::getInstance();
	}
	
	
	/**
	 * @return cache_user
	 */
	public static function getInstance()
	{
		
		if (!(self::$_instance instanceof self))
            self::$_instance = new self();
 
        return self::$_instance;
		
	}
	public function get($id)
	{
			
		return $this->_c_obj->get(GV_ServerName.'_user_'.$id);
	}
	
	public function set($id,$value)
	{
		
		return $this->_c_obj->set(GV_ServerName.'_user_'.$id,$value);
	}
	
	public function delete($id)
	{
		$cache_basket = cache_basket::getInstance();
		$cache_basket->revoke_baskets_usr($id);
		
		return $this->_c_obj->delete(GV_ServerName.'_user_'.$id);
	}
	
	
	
}