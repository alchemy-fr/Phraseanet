<?php

class cache_thumbnail
{
	private static $_instance = false;
	private $_c_obj = false;
	
	function __construct()
	{
		$this->_c_obj = cache::getInstance();
	}
	
	/**
	 * @return cache_thumbnail
	 */
	public static function getInstance()
	{
		
		if (!(self::$_instance instanceof self))
            self::$_instance = new self();
 
        return self::$_instance;
		
	}
	
	public function get($sbas_id, $rid, $getPrev)
	{
			
		cache_databox::refresh($sbas_id);
		
		return $this->_c_obj->get(GV_ServerName.'_thumbnail_'.$sbas_id.'_'.$rid.'_'.($getPrev ? '1':'0'));
	}
	
	public function set($sbas_id, $rid, $getPrev,$value)
	{
		
		return $this->_c_obj->set(GV_ServerName.'_thumbnail_'.$sbas_id.'_'.$rid.'_'.($getPrev ? '1':'0'),$value);
	}
	
	public function delete($sbas_id, $rid,$update_distant_boxes = true)
	{
		
		$this->_c_obj->delete(GV_ServerName.'_thumbnail_'.$sbas_id.'_'.$rid.'_1');
		$this->_c_obj->delete(GV_ServerName.'_thumbnail_'.$sbas_id.'_'.$rid.'_0');
		
		if($update_distant_boxes)
			cache_databox::update($sbas_id,'record',$rid);
		
		return true;
	}
	
	
	
}