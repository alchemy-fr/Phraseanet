<?php 
class patch_304 implements patch
{
	
	private $release = '3.0.4';
	private $concern = array('data_box');
	
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
		$connbas = connection::getInstance($id);
		
		if(!$connbas || !$connbas->isok())
			return true;
			
		$sql = 'INSERT INTO pref (id, prop, value, locale, updated_on, created_on)
		VALUES (null, "indexes", "1", "", NOW(), NOW())';
		$connbas->query($sql);
		
		return true;
	}
}
