<?php
require_once( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
$session = session::getInstance();
if(!$ph_session = phrasea_open_session($session->ses_id,$session->usr_id))
	die();

$th_size = user::getPrefs('images_size');


$RN = array("\r\n", "\n", "\r");


$usrRight = null;
$sql = 'SELECT base_id,canpreview FROM usr INNER JOIN basusr ON (usr.usr_id="'.$conn->escape_string($session->usr_id).'" AND usr.usr_id=basusr.usr_id AND basusr.actif=1 )';
if($rssql = $conn->query($sql))
{
	while($row = $conn->fetch_assoc($rssql))
	{
		$usrRight[$row['base_id']] = $row ;
	}
	$conn->free_result($rssql);
}	


	$sql = 'SELECT s.public, s.pub_restrict,c.sselcont_id, c.base_id, sb.*, c.record_id, s.ssel_id, s.name, s.descript, c.ord, ' .
			'DATE_FORMAT(s.pub_date,"'._('phraseanet::technique::datetime').'") AS pub_date, ' .
			'DATE_FORMAT(s.updater,"'._('phraseanet::technique::date').'") AS updater, ' .
			's.updater as dateC1, s.pub_date as dateC2,' .
			' n.id, u.usr_nom, u.usr_prenom, u.usr_login, u.usr_mail ' .
			', bu.mask_and ' .
			', bu.mask_xor ' .
			'FROM (sselcont c, ssel s, usr u, bas b, sbas sb) ' .
			'LEFT JOIN sselnew n ' .
			'ON (n.ssel_id = s.ssel_id AND n.usr_id = "'.$conn->escape_string($session->usr_id).'") ' .
			' LEFT JOIN basusr bu ON (bu.base_id = b.base_id  AND bu.usr_id = "'.$conn->escape_string($session->usr_id).'" )' .
			'WHERE s.ssel_id = c.ssel_id AND s.public="1" ' .
			'AND (s.pub_restrict="0" ' .
					'OR (s.pub_restrict="1" AND c.base_id IN ' .
						' (SELECT base_id FROM basusr WHERE usr_id = "'.$conn->escape_string($session->usr_id).'" AND actif = "1")))' .
			' AND u.usr_id = s.usr_id AND temporaryType=0 ' .
			' AND b.sbas_id = sb.sbas_id' .
			' AND b.base_id = c.base_id' .
			' ORDER BY s.pub_date DESC, c.ord ASC';

	$info_usr = null;

	$sqlMe = 'SELECT usr_login, usr_password FROM usr WHERE usr_id = "'.$conn->escape_string($session->usr_id).'"';
	
	$info_usr = null;
	
	if($rsMe = $conn->query($sqlMe))
	{
		if($rawMe = $conn->fetch_assoc($rsMe))
		{
			$info_usr = $rawMe;
		}
	}
				
	if($rs = $conn->query($sql))
	{
		$sselid = null;
		$o = 0;
		$out = '';
		
		$feed = '';	
		$feed .= '<div style="height:50px;" class="homePubTitleBox">' .
				'<div style="float:left;width:350px;"><h1 style="font-size:20px;margin-top:15px;">'._('publications:: dernieres publications').'</h1></div>' .
				'<div style="float:right;width:160px;text-align:right;cursor:pointer;" class="subscribe_my_rss">
					<h1 style="font-size:17px;margin-top:19px;"> ' .
					_('publications:: s\'abonner aux publications').' '.
					'<img style="border:none;" src="/skins/icons/rss16.png" />
					</h1>' .	
				'</div></div>';
		
		while($row = $conn->fetch_assoc($rs))
		{
			if($row['ssel_id'] != $sselid)
			{
				if($sselid !== null){

					
					
					$item .= '<div style="width:100%;position:relative;float:left;" id="PUBLICONT'.$sselid.'">'.$out;
					$item .= '</div>' .
				'</div></div>';
					
					if($itemIsOk)
						$feed .= $item;
				}
				
				$itemIsOk = false;
				
				$sselid = $row['ssel_id'];
				$ord = $row['ord'];
				$o=0;
				$out = '';
				$neverSeen = '';
				$publisher = $row['usr_prenom'].' '.$row['usr_nom'];
				if($publisher == ' ')
					$publisher = $row['usr_mail'];
				if($publisher == '')
					$publisher = 'Unreferenced user';
					
				if($row['id'] != '')
					$neverSeen = _('publications:: publication non lue');
					
				$item = '';
				$item .= '<div class="boxPubli">' .
						'<div class="titlePubli">' .
						'<h2 class="htitlePubli">' .
						'<a class="homePubTitle" onclick="openCompare(\''.$sselid.'\');">'.$row['name'].
						'</a> <span style="font-size:12px;color:red;">'.$neverSeen.'</span></h2>' .
						'<span class="publiInfos">' .
						' '.$row['pub_date'].
							'  ';
				
				if($row['usr_mail'] != '')
					$item .= '<a class="homePubLink" href="mailto:'.$row['usr_mail'].'">';
				
				$item .= $publisher;
				
				if($row['usr_mail'] != '')
					$item .= '</a>';

				if($row['dateC1'] != $row['dateC2'])
					$item .= '<br/><span style="font-style:italic;">'._('publications:: derniere mise a jour').' '.$row['updater'].'</span><br/><br/>';
				
				$item .= '</span></div><div class="descPubli"><div style="margin:10px 0 10px 20px;width:80%;">';
						
				
				if(trim(str_replace($RN,'',$row['descript'])) != '')
				{
					$row['descript'] = str_replace($RN,'<br/>',$row['descript']);
					$item .= ''.$row['descript'];
				}
				$item .= '</div>';
			}





			$ord = $row['ord'];
			$statOk = true;
			if($row['public'] == 1 && $row['pub_restrict'] == 1)
			{
				$statOk = false;
				
				$connsbas =  connection::getInstance($row['sbas_id']);
				
				if($connsbas)
				{
					$sql = 'SELECT record_id FROM record WHERE ((status ^ '.$row['mask_xor'].') & '.$row['mask_and'].')=0 AND record_id="'.$connsbas->escape_string($row['record_id']).'"';
					$rsRec = $connsbas->query($sql);
					if($connsbas->num_rows($rsRec)>0){
						$statOk = true;
	
					}
				}
			}
			
			
			$layoutmode = user::getPrefs('view');
			
			if($statOk)
			{
				$sbas_id = phrasea::sbasFromBas($row['base_id']);

				$captionXML = phrasea_xmlcaption($session->ses_id,  $row['base_id'], $row['record_id']);
				
				$thumbnail = answer::getThumbnail($session->ses_id, $row["base_id"], $row["record_id"],GV_zommPrev_rollover_clientAnswer);
				
				$title = answer::format_title($sbas_id, $row["record_id"], $captionXML);
				$exifinfos = answer::format_infos($captionXML, $sbas_id, $row["record_id"],$thumbnail['type']);
				$caption = answer::format_caption($row['base_id'], $row["record_id"],$captionXML);
				

				$o++;
				$itemIsOk = true;
				$bottom = 0;
				$right=10;
				$left=0;
				$top = 10;
					
				
				$preview = '';
				if(GV_zommPrev_rollover_clientAnswer)
				{
					$canprev = false;
					if(isset($usrRight[$row["base_id"]]) && $usrRight[$row["base_id"]]['canpreview']=='1')
						$canprev = true;
					$preview = answer::get_preview_rollover($row["base_id"],$row["record_id"],$session->ses_id,$canprev,$session->usr_id,$thumbnail['preview'],$thumbnail['type']);
					if(trim($preview) != '')
						$preview = "<div title='".$preview."' class=\"previewTips\"></div>&nbsp;";						
				}
			
				$docType = $thumbnail['type'];
				$isVideo = $docType == 'video' ? true:false;
				$isAudio = $docType == 'audio' ? true:false;
				$isImage = $docType == 'image' ? true:false;
				$isDocument = $docType == 'document' ? true:false;
				
				$duration = '';
				
				if(!$isVideo && !$isAudio)
					$isImage = true;
					
				if($isVideo){
					$duration = answer::get_duration($captionXML);
					if($duration != '00:00')
						$duration ='<div class="duration">'.$duration.'</div>';
				}
				if($isAudio){
					$duration = answer::get_duration($captionXML);
					if($duration != '00:00')
						$duration ='<div class="duration">'.$duration.'</div>';
				}
				
			
				$ratio = $thumbnail["w"] / $thumbnail["h"];
						
				if($ratio > 1)
				{
					$cw = min(((int)$th_size-30),$thumbnail["w"]);
					$ch = $cw/$ratio;
					$pv = floor(($th_size-$ch)/2);
					$ph = floor(($th_size-$cw)/2);
					$imgStyle = 'xwidth:'.$cw.'px;xpadding:'.$pv.'px '.$ph.'px;';
				}
				else
				{
					$ch = min(((int)$th_size-30),$thumbnail["h"]);
					$cw = $ch*$ratio;
					
					$pv = floor(($th_size-$ch)/2);
					$ph = floor(($th_size-$cw)/2);
					
					$imgStyle = 'xheight:'.$ch.'px;xpadding:'.$pv.'px '.$ph.'px;';
				}
				
				
				
				$ident = $row["base_id"]."_".$row["record_id"];
				
				
				$out .= "<div style='width:".($th_size+30)."px;' sbas=\"".$row['sbas_id']."\" id='IMGT_".$row['base_id']."_".$row['record_id']."_PUB_".$sselid."' class='IMGT diapo' onclick=\"openPreview('BASK','".$ord."','".$sselid."');\">";
				
				$out .= '<div>';
				$out .= "<div class=\"title\" style=\"height:40px;\">";
	
				$out .= $title;//$data['title'];
	
				$out .= "</div>\n";
	
				$out .= '</div>';
		
				$out .= "<table class=\"thumb w160px h160px\" style=\"xheight:".(int)$th_size."px;\" cellspacing='0' cellpadding='0' valign='middle'>\n<tr><td>";
				
				$out .= $duration."<img title=\"".str_replace('"','&quot;',$caption)."\" class=\"".$thumbnail['imgclass']." captionTips\" src=\"".$thumbnail["thumbnail"]."\" style=\"".$imgStyle."\" />";
						
				$out .= "</td></tr></table>";
					
				$out .= '<div style="height: 25px;position:relative;">';
				$out .= '<table class="bottom">';
				$out .= '<tr>';
				$out .= '<td>';
		
				$out .= "</td>\n";
		
				$out .= "<td style='text-align:right;' valign='bottom' nowrap>\n";
		
				$out .= $preview;
			
				$out .= "</td>";
				$out .= "</tr>";
				$out .= "</table>";
				$out .= "</div>";
					
					
				$out .= "</div>";
	
			}
			
	
		}
	
		if(isset($item))
		{
			$item .= ' <br/><div style="width:100%;position:relative;float:left;" id="PUBLICONT'.$sselid.'">'.$out;
			$item .= '</div>' .
					'</div></div>';
			if($itemIsOk)
				$feed .= $item;
		}
		
		echo '<div>'.$feed.'</div>';
	
		}
		
		$sql = 'DELETE FROM sselnew WHERE usr_id="'.$session->usr_id.'" AND ssel_id IN (SELECT ssel_id FROM ssel WHERE public="1")';
		$conn->query($sql);
