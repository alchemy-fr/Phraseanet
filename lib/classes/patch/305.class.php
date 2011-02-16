<?php 
class patch_305 implements patch
{
	
	private $release = '3.0.5';
	private $concern = array('application_box');
	
	function get_release()
	{
		return $this->release;
	}
	
	function concern()
	{
		return $this->concern;
	}
	
	function apply($id)
	{
		$conn = connection::getInstance();
		
		$sql = 'INSERT INTO usr_settings (SELECT usr_id, "start_page_query" as prop, last_query as value FROM usr WHERE model_of="0" AND usr_login NOT LIKE "(#deleted_%")';
		
		$conn->query($sql);
		
		return true;
	}
}