<?php 
class patch_310 implements patch
{
	
	private $release = '3.1.0';
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
			return false;
		
		$sql = 'SELECT value FROM pref WHERE prop="structure"';
		
		$structure = false;
		
		if($rs = $connbas->query($sql))
		{
			if($row = $connbas->fetch_assoc($rs))
				$structure = $row['value'];
			$connbas->free_result($rs);
		}
		
		if(!$structure)
			exit('Impossible de charger la structure depuis la base de donnnee '.$id.' '.$connbas->last_error());
			
		$dom_structure = new DOMDocument();
		$dom_structure->formatOutput = true;
		$dom_structure->preserveWhiteSpace = false;
		
		if(!$dom_structure->loadXML($structure))
			exit('Impossible de charger la structure en DOM ');
		
		if(($sx_structure = simplexml_load_string($structure)) === false)
			exit('Impossible de charger la structure en sxml ');
			
		$subdefs = $sx_structure->xpath('/record/subdefs');
			
		if(count($subdefs) > 1)
			exit('La structure semble erronnée, veuillez la corriger');
		
			
		$new_subefs_node = $dom_structure->createElement('subdefs');
		
		$subdefs_groups = array();
		
		foreach($subdefs[0] as $k=>$v)
		{
			$type = isset($v->type) ? (string)$v->type : 'image';
			
			if($type == 'image')
				$media = 'image';
			elseif($type == 'audio')
			{
				if($v->method == 'MP3')
					$media = "audio";
				else
					$media = "image";
			}
			elseif($type == 'video')
			{
				if($v->method == 'AnimGIF')
					$media = "gif";
				elseif($$v->method == 'JPG')
					$media = "image";
				else
					$media = 'video';
			}
			
			echo 'found '.$k.' node with type '.$type.'<br>';
			
			if(!isset($subdefs_groups[$type]))
			{
				$subdefs_groups[$type] = $dom_structure->createElement('subdefgroup');
				$subdefs_groups[$type]->setAttribute('name',$type);
			}
			
			$dom_subdef = $dom_structure->createElement('subdef');
			$dom_subdef->setAttribute('class', ($k == 'preview' ? 'preview' : 'thumbnail'));
			$dom_subdef->setAttribute('name', $k);
			$dom_subdef->setAttribute('downloadable', 'true');
			
			foreach($v as $tag=>$value)
			{
				if(in_array($tag,array('type','name')))
					continue;
				
				$dom_element = $dom_structure->createElement($tag, $value);
				$dom_subdef->appendChild($dom_element);
					
			}
			$dom_element = $dom_structure->createElement('mediatype', $media);
			$dom_subdef->appendChild($dom_element);
			
			if($media == 'video')
			{
				$dom_element = $dom_structure->createElement('threads', '1');
				$dom_subdef->appendChild($dom_element);
			}
			
			
			//preview, thumbnail et thumbnailGIF
			if($k == 'preview')
			{
				$dom_element = $dom_structure->createElement('label', 'Prévisualisation');
				$dom_element->setAttribute('lang','fr');
				$dom_subdef->appendChild($dom_element);
				$dom_element = $dom_structure->createElement('label', 'Preview');
				$dom_element->setAttribute('lang','en');
				$dom_subdef->appendChild($dom_element);
			}
			elseif($k == 'thumbnailGIF')
			{
				$dom_element = $dom_structure->createElement('label', 'Animation GIF');
				$dom_element->setAttribute('lang','fr');
				$dom_subdef->appendChild($dom_element);
				$dom_element = $dom_structure->createElement('label', 'GIF animation');
				$dom_element->setAttribute('lang','en');
				$dom_subdef->appendChild($dom_element);
			}
			else
			{
				$dom_element = $dom_structure->createElement('label', 'Imagette');
				$dom_element->setAttribute('lang','fr');
				$dom_subdef->appendChild($dom_element);
				$dom_element = $dom_structure->createElement('label', 'Thumbnail');
				$dom_element->setAttribute('lang','en');
				$dom_subdef->appendChild($dom_element);
			}
			
			$subdefs_groups[$type]->appendChild($dom_subdef);
		}
		
		foreach($subdefs_groups as $type=>$node)
			$new_subefs_node->appendChild($node);
		
		$record = $dom_structure->documentElement;
			
		$record->replaceChild($new_subefs_node, $record->getElementsByTagName('subdefs')->item(0));
		
		$record->setAttribute("modification_date", $now = date("YmdHis"));

		$sql = "UPDATE pref SET value='" . $connbas->escape_string($dom_structure->saveXML()) . "', 
				updated_on='" . $now . "' WHERE prop='structure'" ;
		$connbas->query($sql);
		
		$cache_appbox = cache_appbox::getInstance();
		$cache_appbox->delete('list_bases');
		
		cache_databox::update($id,'structure');
		
		return true;
	}
}


