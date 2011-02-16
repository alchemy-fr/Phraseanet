<?php 
class patch_306 implements patch
{
	
	private $release = '3.0.6';
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
		
		$dom = databox::get_dom_structure($id);
		
		$xpath = databox::get_xpath_structure($id);

		$res = $xpath->query('/record/subdefs/preview/type');
		
		foreach($res as $type)
		{
			if($type->nodeValue == 'video')
			{
				$preview = $type->parentNode;
				
				$to_add = array(
					'acodec'=>'faac',
					'vcodec'=>'libx264',
					'bitrate'=>'700'
				);
				foreach($to_add as $k=>$v)
				{
					$el = $dom->createElement($k);
					$el->appendChild($dom->createTextNode($v));
					$preview->appendChild($el);
				}
				
			}
		}
		
		$sql = "UPDATE pref SET value='" . $connbas->escape_string($dom->saveXML()) . "', updated_on=NOW() WHERE prop='structure'" ;
		$connbas->query($sql);
		
		$cache_appbox = cache_appbox::getInstance();
		$cache_appbox->delete('list_bases');
		
		cache_databox::update($id,'structure');
		return true;
	}
}
