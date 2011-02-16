<?php

class cache_record
{

	private static $_instance = false;
	var $_c_obj = false;
	
	function __construct()
	{
		$this->_c_obj = cache::getInstance();
	}
	
	
	/**
	 * @return cache_record
	 */
	public static function getInstance()
	{
		
		if (!(self::$_instance instanceof self))
            self::$_instance = new self();
 
        return self::$_instance;
		
	}
	public function get($sbas_id,$record_id,$type)
	{
		cache_databox::refresh($sbas_id);
		
		return $this->_c_obj->get(GV_ServerName.'_'.$sbas_id.'_'.$type.'_'.$record_id);
	}
	
	public function set($sbas_id,$record_id,$type,$value)
	{
		
		return $this->_c_obj->set(GV_ServerName.'_'.$sbas_id.'_'.$type.'_'.$record_id,$value,14400);
	}
	/**
	 * Update Delayed Cache on distant databoxes for other clients
	 * @param1 $sbas_id : sbas_id of databox
	 * @param2 $record_id : record_id updated
	 */
	public function delete($sbas_id,$record_id,$update_distant_boxes = true)
	{
		$this->_c_obj->delete(GV_ServerName.'_'.$sbas_id.'_caption_'.$record_id);
		$this->_c_obj->delete(GV_ServerName.'_'.$sbas_id.'_caption_bounce_'.$record_id);
		$this->_c_obj->delete(GV_ServerName.'_'.$sbas_id.'_title_'.$record_id);
		
		if($update_distant_boxes)
			cache_databox::update($sbas_id,'record',$record_id);
		
		return true;
	}
	
}