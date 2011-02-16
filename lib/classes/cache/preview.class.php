<?php

class cache_preview
{

	private static $_instance = false;
	var $_c_obj = false;
	
	function __construct()
	{
		$this->_c_obj = cache::getInstance();
	}
	
	
	/**
	 * @return cache_preview
	 */
	public static function getInstance()
	{
		
		if (!(self::$_instance instanceof self))
            self::$_instance = new self();
 
        return self::$_instance;
		
	}
	public function get($sbas_id,$record_id, $canPrev)
	{
			
		cache_databox::refresh($sbas_id);
		return $this->_c_obj->get(GV_ServerName.'_preview_'.$sbas_id.'_'.$record_id.'_'.($canPrev ? '1':'0'));
	}
	
	public function set($sbas_id,$record_id, $canPrev, $value)
	{
		
		return $this->_c_obj->set(GV_ServerName.'_preview_'.$sbas_id.'_'.$record_id.'_'.($canPrev ? '1':'0'),$value);
	}
	
	public function delete($sbas_id,$record_id,$update_distant_boxes = true)
	{
		
		$this->_c_obj->delete(GV_ServerName.'_preview_'.$sbas_id.'_'.$record_id.'_0');
		$this->_c_obj->delete(GV_ServerName.'_preview_'.$sbas_id.'_'.$record_id.'_1');
		
		if($update_distant_boxes)
			cache_databox::update($sbas_id,'record',$record_id);
		
		return true;
	}
	
	
	
}