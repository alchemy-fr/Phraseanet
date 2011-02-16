<?php

class cache_feed
{

	private static $_instance = false;
	var $_c_obj = false;
	
	function __construct()
	{
		$this->_c_obj = cache::getInstance();
	}
	
	
	/**
	 * @return cache_feed
	 */
	public static function getInstance()
	{
		
		if (!(self::$_instance instanceof self))
            self::$_instance = new self();
 
        return self::$_instance;
		
	}
	public function get($feed_id)
	{
			
		return $this->_c_obj->get(GV_ServerName.'_feed_'.$feed_id);
	}
	
	public function set($feed_id,$datas)
	{
		
		return $this->_c_obj->set(GV_ServerName.'_feed_'.$feed_id,$datas, 1800);
	}
	
	public function delete($feed_id)
	{
		
		return $this->_c_obj->delete(GV_ServerName.'_feed_'.$feed_id);
	}
	
	
	
}