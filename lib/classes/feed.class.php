<?php
class feed
{
	protected $infos = array();
	protected $items = array();
	protected $cache_id = false;
	
	function __construct($infos, $items,$cache_id=false)
	{
		$this->infos = $infos;
		$this->items = $items;
		$this->cache_id = $cache_id;
	}
	
	public function format_media_rss($item_id = false)
	{
		
		$rss = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>'.
			'<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/" xmlns:atom="http://www.w3.org/2005/Atom"><channel>';
		
		$prev = $next = false;
		
		$keys = array_keys($this->items);
		if($item_id == false || !isset($this->items[$item_id]))
		{
			if(count($keys) == 0)
				return $rss.'</channel></rss>';
			$item_id = $keys[0];
		}
		
		$tmp_prev = $tmp_next = false;
		
		foreach($this->items as $id=>$item)
		{
			if($tmp_prev === true)
			{
				$next = $id;
				$tmp_prev = false;
			}
			elseif($id == $item_id)
			{
				$prev = $tmp_prev;
				$tmp_prev = true;
			}
			else
				$tmp_prev = $id;
		}
		
		
		
		
		if($prev)
			$rss .= '<atom:link rel="previous" href="'.GV_ServerName.'atom/cooliris/'.$prev.'" />';
		
		if($next)
			$rss .= '<atom:link rel="next" href="'.GV_ServerName.'atom/cooliris/'.$next.'" />';
			
		if(isset($this->items[$item_id]))
		{
			foreach($this->items[$item_id]['document'] as $doc)		
			{
					
				$url = GV_ServerName."document/".$doc['base_id']."/".$doc['record_id']."/".$doc['subdefs']['sha256']."/";
				$rss .= '<item>
				            <title>'.strip_tags(p4string::entitydecode($doc['title'])).'</title>
				            <link>'.$url.'view/</link>
				            <media:thumbnail url="'.GV_ServerName.$doc['subdefs']['thumbnail'].'"/>
				            <media:content url="'.$url.'" '.($doc['subdefs']['type'] == 'video' ? 'type="'.$doc['subdefs']['mime'].'"':'').' />
				        </item>';
			}
		}
		
		$rss .= '</channel></rss>';
			
		return str_replace('&','&amp;',$rss);
	}
	
	public function format_atom()
	{
		
		$RN = array("\r\n", "\n", "\r");
			
		$rss = '<?xml version="1.0" encoding="utf-8"?>' .
			'<feed xmlns="http://www.w3.org/2005/Atom">';


		$rss .= '<title>'.$this->infos['title'].'</title>' .
	  		'<link rel="self" href="'.$this->infos['link_self'].'"/>' .
	  		'<link rel="enclosure" href="'.$this->infos['link_enclosure'].'"/>' .
	  		'<updated>'.$this->infos['updated'].'</updated>' .
	  		'<id>'.$this->infos['id'].'</id>' .
	  		'<icon>'.$this->infos['icon'].'</icon>' .
	  		'<generator>'.$this->infos['generator'].'</generator>' .
	  		'<rights>'.$this->infos['rights'].'</rights>' .
	  		'<subtitle type="xhtml">'.$this->infos['subtitle'].'</subtitle>' .
	  		'';

		foreach($this->items as $n=>$publi)			
		{
			
			$rss .= '<entry>';
			
			$rss .= '<id>'.$publi['id'].'</id>';
			$rss .= '<link rel="alternate" href="'.$publi['link'].'" title=""/>';
			$rss .= '<link rel="enclosure" href="'.$publi['link'].'" title=""/>';
			$rss .= '<link rel="self" href="'.$publi['link'].'" title=""/>';
			
			$rss .= '<published>'.$publi['published'].'</published>';
			
			$rss .= '<updated>'.$publi['updated'].'</updated>';
	
			$rss .= '<title>'.$publi['title'].'</title>';
			
			$rss .= '<author>';
			
			$rss .= '<name>'.$publi['name'].'</name>';
			
			if(trim($publi['email']) != '')
				$rss .= '<email>'.$publi['email'].'</email>';
			
			$rss .= '</author>';

			
			$rss .= '<content type="xhtml">
				<div xmlns="http://www.w3.org/1999/xhtml">'.str_replace($RN,'<br/>',$publi['content']);
			
			
			$o = count($publi['document']);
			$rss .= '<br/><br/>'.$o.' '._('rss:: nombre d\' elements ');
			
			foreach($publi['document'] as $document)
			{
			
				
				$rss .= ' <br/><br/>';
		
				$rss .= '<div>';
				
				$thumb = '';
				
				switch($document['type'])
				{
					case 'audio':
						$thumb = '<img src="'.$document['src'].'" />';
						break;
					case 'image':
						$thumb = '<img src="'.$document['src'].'" />';
						break;
					case 'video':
						$thumb = '<img src="'.$document['src'].'" />';
						break;
					case 'unknown':
						$thumb = '<img src="'.$document['src'].'" />';
						break;
					case 'flash':
						$thumb = '<img src="'.$document['src'].'" />';
						break;
				}
				
				$rss .= $document['title'].'<br/>'.$thumb.'</div>';
				
			}
			
			$rss .= '</div></content>';
			
			$rss .= '</entry>';
		}

		$rss .= '</feed>';
				
		return str_replace('&','&amp;',$rss);
		
	}
	
}