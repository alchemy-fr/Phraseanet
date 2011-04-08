<?php
class homelink extends feed
{
	
	function __construct()
	{
		
		$this->cache_id = '_homelink_';
		
		$feed_cache = cache_feed::getInstance();
		
		if(($tmp = $feed_cache->get($this->cache_id)) !== false)
		{
			$this->infos = $tmp['infos'];
			$this->items = $tmp['items'];
			
			return $this;
		}
		
		$conn = connection::getInstance();
		$session = session::getInstance();
		
		
		$usr_id = false;
		
		$sql = 'SELECT usr_id FROM usr WHERE usr_login="invite" LIMIT 1';
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
				$usr_id =  $session->usr_id = $row['usr_id'];
			$conn->free_result($rs);
		}

    $to_logout = false;
    if(!$session->is_authenticated())
    {
  		$ses_id = $session->ses_id = phrasea_create_session($usr_id);
      $to_logout = true;
    }
		$ses_id = $session->ses_id;
		
		$date_obj = new DateTime();
			
		$this->infos['title'] = '';
		$this->infos['link_self'] = GV_ServerName.'atom/cooliris/';
		$this->infos['link_enclosure'] = GV_ServerName;
		$this->infos['updated'] = $date_obj->format(DATE_ATOM);
		$this->infos['id'] = GV_ServerName;
		$this->infos['icon'] = GV_ServerName.'favicon.ico';
		$this->infos['generator'] = 'Phraseanet';
		$this->infos['rights'] = '';
		$this->infos['subtitle'] = '';
		
		$sql = 'SELECT s.descript, s.ssel_id, s.homelink_update, s.name, u.usr_nom, u.usr_prenom, u.usr_mail  
			FROM ssel s, usr u 
			WHERE homelink = "1" 
			AND s.usr_id = u.usr_id ORDER BY homelink_update DESC';
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$n = $row['ssel_id'];
//				$this->items[$n] = $row['ssel_id'];
				
				
				$this->items[$n]['id'] = GV_ServerName.'atom/cooliris/'.$row['ssel_id'];
				$this->items[$n]['link'] = GV_ServerName.'atom/cooliris/'.$row['ssel_id'];;
				
				$date_obj_pub = new DateTime($row['homelink_update']);
				
				$this->items[$n]['published'] = $date_obj_pub->format(DATE_ATOM); 
				$this->items[$n]['updated'] = $date_obj_pub->format(DATE_ATOM);
				$this->items[$n]['title'] = html_entity_decode($row['name'],ENT_COMPAT);
				
				$this->items[$n]['restricted'] = false;
				
				$o = 0;

				$usr = $row['usr_prenom'].' '.$row['usr_nom'];
				
				if(trim($usr) == '')
					$usr = 'Unknown User';
				
				$this->items[$n]['name'] = $usr;
				$this->items[$n]['ssel_id'] = $row['ssel_id'];
				$this->items[$n]['email'] = $row['usr_mail'];
				$this->items[$n]['content'] = $row['descript'];
				
				$this->items[$n]['unread'] = false;
				$this->items[$n]['document'] = array();
				$sql = 'SELECT ord, record_id, base_id FROM sselcont WHERE ssel_id="'.$row['ssel_id'].'" ORDER BY ord ASC';
				
				if($rs2 = $conn->query($sql))
				{
					while($row2 = $conn->fetch_assoc($rs2))
					{
						$thumbnail = answer::getThumbnail($ses_id, $row2["base_id"], $row2["record_id"],GV_zommPrev_rollover_clientAnswer);
							
						$sbas_id = phrasea::sbasFromBas($row2['base_id']);
						$captionXML = phrasea_xmlcaption($ses_id,  $row2['base_id'], $row2['record_id']);
						$title = answer::format_title($sbas_id, $row2["record_id"], $captionXML);
						$exifinfos = answer::format_infos($captionXML, $sbas_id, $row2["record_id"],$thumbnail['type']);
						$caption = answer::format_caption($row2['base_id'], $row2["record_id"],$captionXML, false, 'homelink');
						
						$duration = '';
						$docType = $thumbnail['type'];
						$isVideo = $docType == 'video' ? true:false;
						$isAudio = $docType == 'audio' ? true:false;
						
						if($isVideo){
							$duration = answer::get_duration($captionXML);
						}
						if($isAudio){
							$duration = answer::get_duration($captionXML);
						}
						
						$ratio = $thumbnail["w"]/$thumbnail["h"];
						
						if($thumbnail["w"] > $thumbnail["h"])
						{
							if($thumbnail["w"] > 200)
							{
								$thumbnail["w"] = 200;
							}
							$thumbnail["h"] = round($thumbnail["w"]/$ratio);
						}
						else
						{
							if($thumbnail["h"] > 200)
							{
								$thumbnail["h"] = 200;
							}
							$thumbnail["w"] = round($thumbnail["h"]*$ratio);
						}
						
						$this->items[$n]['document'][] = array(
							'type' => $docType,
							'sbas_id' => $sbas_id,
							'base_id' => $row2['base_id'],
							'record_id' => $row2['record_id'],
							'src' => GV_ServerName.$thumbnail['thumbnail'],
							'title' => $title,
							'subdefs' => $thumbnail,
							'technical_infos' => $exifinfos,
							'caption' => $caption,
							'height' => $thumbnail["h"],
							'width' => $thumbnail["w"],
							'order' => $row2['ord'],
							'duration' => $duration
						);
					}
					$conn->free_result($rs2);
				}	
			}
			$conn->free_result($rs);
		}

    if($to_logout === true)
    {
                p4::logout($ses_id);
    }
		
		$datas = array('infos'=>$this->infos,'items'=>$this->items);
		$feed_cache->set($this->cache_id,$datas);
		
		
		return $this;
	}
	

	public function get_datas()
	{
		return array('items'=>$this->items,'infos'=>$this->infos);
	}
}