<?php
class internalpubli extends feed
{
	function __construct($usr_id, $ses_id, $page=0)
	{
		$this->cache_id = '_internalpubli_'.$usr_id.'_'.$page;
		$this->page = $page;
		
		
		$conn = connection::getInstance();
		
		$total = 0;
		$update = 0;
		
		$sql = 'SELECT count(distinct s.ssel_id) as total, MAX(s.updater) as MAJ FROM ssel s, sselcont c, bas b WHERE s.ssel_id = c.ssel_id AND c.base_id = b.base_id AND s.public="1" AND (s.pub_restrict="0" ' .
					'OR (s.pub_restrict="1" AND c.base_id IN ' .
					' (SELECT base_id FROM basusr WHERE usr_id = "'.$conn->escape_string($usr_id).'" AND actif = "1"))) ORDER BY s.pub_date DESC, s.ssel_id DESC';
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$total 	= $row['total'];
				$update = $row['MAJ'];
			}
			$conn->free_result($rs);
		}
	
		$feed_cache = cache_feed::getInstance();
		
		if(($tmp = $feed_cache->get($this->cache_id)) !== false)
		{
			$last_update = $tmp['infos']['updated'];
			
			$update_obj = new DateTime($update);
			
			if($last_update == $update_obj->format(DATE_ATOM))
			{
				$this->infos = $tmp['infos'];
				$this->items = $tmp['items'];
				return $this;
			}
				
			$feed_cache->delete($this->cache_id);
		}
		
		
		$n = 10;

		$this->infos['next'] = ((((int)$page + 1) * $n) < $total) ? ((int)$page + 1) : false ;
		$this->infos['previous'] = $page > 0 ? ((int)$page - 1 ) : false;
			
		$sql = 'SELECT DISTINCT s.ssel_id FROM ssel s, sselcont c, bas b WHERE s.ssel_id = c.ssel_id AND b.base_id = c.base_id AND s.public="1" AND (s.pub_restrict="0" ' .
					'OR (s.pub_restrict="1" AND c.base_id IN ' .
					' (SELECT base_id FROM basusr WHERE usr_id = "'.$conn->escape_string($usr_id).'" AND actif = "1"))) ORDER BY s.pub_date DESC, s.ssel_id DESC LIMIT '.((int)$page * $n).', '.$n;
		
		$ssel_ids = array();
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$ssel_ids[] = $row['ssel_id'];
			}
			$conn->free_result($rs);
		}
		
		
		$RN = array("\r\n", "\n", "\r");
					
		phrasea_open_session($ses_id, $usr_id);
		
		$sql = 'SELECT c.ord, s.public, s.pub_restrict,c.sselcont_id, c.base_id, b.sbas_id, c.record_id, s.ssel_id, s.name, s.descript, ' .
				's.pub_date, ' .
				's.updater, ' .
				' u.usr_nom, u.usr_prenom, u.usr_login, u.usr_mail ' .
				', bu.mask_and ' .
				', bu.mask_xor ' .
				' FROM (sselcont c, ssel s, usr u, bas b) ' .
				' LEFT JOIN basusr bu ON (bu.base_id = b.base_id  AND bu.usr_id = "'.$conn->escape_string($usr_id).'" )' .
				' WHERE s.ssel_id = c.ssel_id AND s.public="1" ' .
				' AND s.ssel_id IN ('.implode(', ',$ssel_ids).')' .
				' AND u.usr_id = s.usr_id AND temporaryType=0 ' .
				' AND b.base_id = c.base_id' .
				' ORDER BY s.pub_date DESC, s.ssel_id DESC, c.ord ASC';
		

		$sqlMe = 'SELECT usr_login, usr_password FROM usr WHERE usr_id = "'.$conn->escape_string($usr_id).'"';
		
		$info_usr = null;
		
		if($rsMe = $conn->query($sqlMe))
		{
			if($rawMe = $conn->fetch_assoc($rsMe))
			{
				$info_usr = $rawMe;
			}
			$conn->free_result($rsMe);
		}
		
		
		
		if($rs = $conn->query($sql))
		{
			$date_obj = new DateTime($update);
			
			$rss_infos = user::getMyRss();
			
			$this->infos['title'] = '';
			$this->infos['link_self'] = GV_ServerName.'atom/'.$rss_infos['token'];
			$this->infos['link_enclosure'] = GV_ServerName;
			$this->infos['updated'] = $date_obj->format(DATE_ATOM);
			$this->infos['id'] = GV_ServerName;
			$this->infos['icon'] = GV_ServerName.'favicon.ico';
			$this->infos['generator'] = 'Phraseanet';
			$this->infos['rights'] = '';
			$this->infos['subtitle'] = '';
		
			$sselid = null;
			
			while($row = $conn->fetch_assoc($rs))
			{
				$isItem = false;
				
				if($row['ssel_id'] != $sselid)
				{	
					$isItem = true;
				}
				
				$sselid = $row['ssel_id'];
				
				if($isItem)
				{
					
					$n = count($this->items);
					$this->items[$n] = array();
					
					if($info_usr !== null)
					{
						$encryptedurl = random::getUrlToken('view',$usr_id,false,$row['ssel_id']);
						$this->items[$n]['id'] = GV_ServerName.'lightbox/index.php?LOG='.$encryptedurl;
						$this->items[$n]['link'] = GV_ServerName.'lightbox/index.php?LOG='.$encryptedurl;
					}
					
					$date_obj_pub = new DateTime($row['pub_date']);
					$date_obj_upd = new DateTime($row['updater']);
					
					$this->items[$n]['published'] = $date_obj_pub->format(DATE_ATOM); 
					$this->items[$n]['updated'] = $date_obj_upd->format(DATE_ATOM);
					$this->items[$n]['title'] = html_entity_decode($row['name'],ENT_COMPAT,'UTF-8');
					
					$this->items[$n]['restricted'] = $row['pub_restrict'] == 1 ? true : false;
					
					$o = 0;
	
					$usr = $row['usr_prenom'].' '.$row['usr_nom'];
					
					if(trim($usr) == '')
						$usr = 'Unknown User';
					
					$this->items[$n]['name'] = $usr;
					$this->items[$n]['ssel_id'] = $row['ssel_id'];
					$this->items[$n]['email'] = $row['usr_mail'];
					$this->items[$n]['content'] = $row['descript'];
					
					$this->items[$n]['unread'] = false;// $row['id'] != '' ? true : false;
					
					$isItem = false;
//					$imgs ='';
				}
					
				$statOk = true;
				if($row['pub_restrict'] == 1)
				{
					$statOk = false;
					
					$connsbas = connection::getInstance($row['sbas_id']);
					
					if($connsbas)
					{
						$sql = 'SELECT record_id FROM record WHERE ((status ^ '.$row['mask_xor'].') & '.$row['mask_and'].')=0 AND record_id="'.$connsbas->escape_string($row['record_id']).'"';
			
						if($rsRec = $connsbas->query($sql))
						{
							if($connsbas->num_rows($rsRec)>0){
								$statOk = true;
				
							}
							$connsbas->free_result($rsRec);
						}
					}
				}
				if($statOk)
				{
					$o++;
					$thumbnail = answer::getThumbnail($ses_id, $row["base_id"], $row["record_id"],GV_zommPrev_rollover_clientAnswer);
						
						
					$sbas_id = phrasea::sbasFromBas($row['base_id']);
					$captionXML = phrasea_xmlcaption($ses_id,  $row['base_id'], $row['record_id']);
					$title = answer::format_title($sbas_id, $row["record_id"], $captionXML);
					$exifinfos = answer::format_infos($captionXML, $sbas_id, $row["record_id"],$thumbnail['type']);
					$caption = answer::format_caption($row['base_id'], $row["record_id"],$captionXML);
					
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
						'base_id' => $row['base_id'],
						'record_id' => $row['record_id'],
						'src' => GV_ServerName.$thumbnail['thumbnail'],
						'title' => $title,
						'subdefs' => $thumbnail,
						'technical_infos' => $exifinfos,
						'caption' => $caption,
						'height' => $thumbnail["h"],
						'width' => $thumbnail["w"],
						'order' => $row['ord'],
						'duration' => $duration
					);
//					'<img src="'.GV_ServerName.$thumbnail['thumbnail'].'" width="'.$thumbnail['w'].'" height="'.$thumbnail['h'].'" />';
				}
			}
		}
		
		$datas = array('infos'=>$this->infos,'items'=>$this->items);
		$feed_cache->set($this->cache_id,$datas);
		
		return $this;
	}
	
	public function get_datas()
	{
		
		return array('items'=>$this->items,'infos'=>$this->infos);
	}
	
	public function format_html()
	{
		
		$RN = array("\r\n", "\n", "\r");
		$session = session::getInstance();
		$usrRight = user::getInstance($session->usr_id);
		
		$feed = '';	

		if($this->page == 0)
		{
			$feed .= '<div style="height:50px;" class="homePubTitleBox">' .
				'<div style="float:left;width:350px;"><h1 style="font-size:20px;margin-top:15px;">'._('publications:: dernieres publications').'</h1></div>' .
				'<div style="float:right;width:160px;text-align:right;cursor:pointer;" class="subscribe_my_rss">
					<h1 style="font-size:17px;margin-top:19px;"> ' .
					_('publications:: s\'abonner aux publications').' '.
					'<img style="border:none;" src="'.GV_ServerName.'skins/icons/rss16.png" />
					</h1>' .	
				'</div></div>';
		}

		
		foreach($this->items as $n=>$publi)			
		{
			
			$feed .= ' <br/><div style="width:100%;position:relative;float:left;" id="PUBLICONT'.$publi['ssel_id'].'">';
			$sselid = $publi['ssel_id'];
					
			$neverSeen = $publi['unread'] ? _('publications:: publication non lue') : '';
			
			$date_obj_pub = new DateTime($publi['published']);
			
			$published = phraseadate::getPrettyString($date_obj_pub);
			
			$feed .= '<div class="boxPubli">' .
					'<div class="titlePubli">' .
					'<h2 class="htitlePubli">' .
					'<a class="homePubTitle" onclick="openPreview(\'BASK\',1,'.$sselid.');return false;" href="#">'.$publi['title'].
					'</a> <span style="font-size:12px;color:red;">'.$neverSeen.'</span></h2>' .
					'<span class="publiInfos">' .
					' '.$published.'  ';
			
			if(trim($publi['email']) != '')
				$feed .= '<a class="homePubLink" href="mailto:'.$publi['email'].'">';
			
			$feed .= $publi['name'];
			
			if(trim($publi['email']) != '')
				$feed .= '</a>';

			$date_obj_update = new DateTime($publi['updated']);
			
			$updated = phraseadate::getPrettyString($date_obj_update);
				
			if($updated != $published && $date_obj_update->format('U') > $date_obj_pub->format('U'))
			{
				$feed .= '<br/><span style="font-style:italic;">'._('publications:: derniere mise a jour').' '.$updated.'</span><br/><br/>';
			}
			$feed .= '</span></div><div class="descPubli"><div style="margin:10px 0 10px 20px;width:80%;">';
					
			
			if(trim(str_replace($RN,'',$publi['content'])) != '')
			{
				$publi['content'] = str_replace($RN,'<br/>',$publi['content']);
				$feed .= ''.$publi['content'];
			}
			$feed .= '</div>';

			if(isset($publi['document']))
			{
				foreach($publi['document'] as $doc)
				{
					$ord = $doc['order'];
				
					$layoutmode = user::getPrefs('view');
					$th_size = user::getPrefs('images_size');
			
					$bottom = 0;
					$right=10;
					$left=0;
					$top = 10;
				
					$preview = '';
					if(GV_zommPrev_rollover_clientAnswer)
					{
						$canprev = false;
						if(isset($usrRight->_rights_bas[$doc["base_id"]]) && $usrRight->_rights_bas[$doc["base_id"]]['canpreview']=='1')
							$canprev = true;
						$thumbnail = answer::getThumbnail($session->ses_id, $doc["base_id"], $doc["record_id"],GV_zommPrev_rollover_clientAnswer);
							
						$preview = answer::get_preview_rollover($doc["base_id"],$doc["record_id"],$session->ses_id,$canprev,$session->usr_id,$thumbnail['preview'],$doc['type']);
					}
				
					$ratio = $doc["width"] / $doc["height"];
							
					if($ratio > 1)
					{
						$cw = min(((int)$th_size-30),$doc["width"]);
						$ch = $cw/$ratio;
						$pv = floor(($th_size-$ch)/2);
						$ph = floor(($th_size-$cw)/2);
						$imgStyle = 'width:'.$cw.'px;padding:'.$pv.'px '.$ph.'px;';
					}
					else
					{
						$ch = min(((int)$th_size-30),$doc["height"]);
						$cw = $ch*$ratio;
						
						$pv = floor(($th_size-$ch)/2);
						$ph = floor(($th_size-$cw)/2);
						
						$imgStyle = 'height:'.$ch.'px;padding:'.$pv.'px '.$ph.'px;';
					}
					
					$ident = $doc["base_id"]."_".$doc["record_id"];
					
					$feed .= "<div style='width:".($th_size+30)."px;' sbas=\"".$doc['sbas_id']."\" id='IMGT_".$doc['base_id']."_".$doc['record_id']."_PUB_".$publi['ssel_id']."' class='IMGT diapo' onDblClick=\"openPreview('BASK','".$ord."','".$publi['ssel_id']."');\">";
					
					$feed .= '<div style="height: 40px; position: relative; z-index: 100;">';
					$feed .= "<div class=\"title\">";
		
					$feed .= $doc['title'];//$data['title'];
		
					$feed .= "</div>\n";
		
					$feed .= '</div>';
			
					$feed .= "<div class=\"thumb\" style=\"height:".(int)$th_size."px;\">\n";
					
					if(trim($doc['duration']) != '')
						$feed .= '<div class="duration">'.$doc['duration'].'</div>';
					
					$th_title = '';
					if(user::getPrefs('rollover_thumbnail') == 'caption')
						$th_title = $doc['caption'];
					if(user::getPrefs('rollover_thumbnail') == 'preview' && $preview != '')
						$th_title = $preview;
					
					$feed .= "<img title=\"".str_replace('"','&quot;',$th_title)."\" class=\"".$doc['subdefs']['imgclass']." captionTips\" src=\"".$doc["src"]."\" style=\"".$imgStyle."\" />";
							
					$feed .= "</div>";
						
					$feed .= '<div style="height: 25px;position:relative;">';
					$feed .= '<table class="bottom" style="width:100%;border:none;" cellspacing="0" cellpadding="0" border="0">';
					$feed .= '<tr>';
					$feed .= '<td>';
			
					$feed .= "</td>\n";
					$feed .= "<td style='text-align:right;' valign='bottom' nowrap>";
					
					
					if(user::getPrefs('rollover_thumbnail') == 'caption' && $preview != '')
						$feed .= "<div title='".$preview."' class=\"previewTips\"></div>&nbsp;";
					if(user::getPrefs('rollover_thumbnail') == 'preview')
						$feed .= '<div title="'.str_replace(array('"','&'),array('&quot;','&amp;'),$doc['caption']).'" class="captionRolloverTips"></div>';
			
					$feed .= "</td>";
					$feed .= "</tr>";
					$feed .= "</table>";
					$feed .= "</div>";
					$feed .= "</div>";
		
				}
			}
	
			$feed .= '</div></div></div>';
		}
		
		$feed = '<div>'.$feed.'</div>';
		
		if($this->infos['next'])
		{
			$feed .= '<div style="text-align: center; font-size: 14px; margin: 20px 0pt; position: relative;float:left;width:100%;">
				<a href="#" class="next_publi_link" onclick="getHome(\'PUBLI\',\''.$this->infos['next'].'\');return false;">'._('Charger les publications precedentes').'</a>
				</div>';
		}
		return $feed;
	}
}