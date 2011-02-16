<?php

function getLanguage($lng)
{
	$out = array();
	$out['createWinInvite'] = _('paniers:: Quel nom souhaitez vous donner a votre panier ?');
	$out['chuNameEmpty'] = _('paniers:: Quel nom souhaitez vous donner a votre panier ?');
	$out['noDLok'] = _('export:: aucun document n\'est disponible au telechargement');
	$out['confirmRedirectAuth'] = _('invite:: Redirection vers la zone d\'authentification, cliquez sur OK pour continuer ou annulez');
	$out['serverName'] = GV_ServerName;
	$out['serverError'] = _('phraseanet::erreur: Une erreur est survenue, si ce probleme persiste, contactez le support technique');
	$out['serverTimeout'] = _('phraseanet::erreur: La connection au serveur Phraseanet semble etre indisponible');
	$out['serverDisconnected'] = _('phraseanet::erreur: Votre session est fermee, veuillez vous re-authentifier');
	$out['confirmDelBasket'] = _('paniers::Vous etes sur le point de supprimer ce panier. Cette action est irreversible. Souhaitez-vous continuer ?');
	$out['annuler'] = _('boutton::annuler');
	$out['fermer'] = _('boutton::fermer');
	$out['renewRss'] = _('boutton::renouveller');
	
	return p4string::jsonencode($out);
}

function getPreviewWindow($usr,$ses,$lng,$env,$pos,$contId,$roll)
{
	
	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return;
	
	$conn = connection::getInstance();
	$session = session::getInstance();
		
	$bas = $rec = false;
	
	$isReg = $isBask = $basReg = $recReg = $isFullyPublic = false;
	$title = $caption = $preview = '';
	$doctype = 'unknown';
	$typedoc = null;
	$canPreview = $canHD = array();
	$width = $height = 1;
	$flashcontent = array();
		
	$sql = 'SELECT base_id, canpreview, candwnldhd, candwnldsubdef, candwnldpreview, canputinalbum, canhd FROM basusr WHERE usr_id="'.$conn->escape_string($usr).'"';

	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$canPreview[$row['base_id']] = ($row['canpreview']=='1')?true:false;
			$canBasket[$row['base_id']] = ($row['canputinalbum']=='1')?true:false;
			$canHD[$row['base_id']] = ($row['canhd']=='1')?true:false;
			$canDL[$row['base_id']] = ($row['candwnldhd']=='1' || $row['candwnldsubdef']=='1' || $row['candwnldpreview']=='1')?true:false;
		}
		$conn->free_result($rs);
	}
	
	$history = $popularity = '';

	switch($env)
	{
		case "RESULT":
				$results = phrasea_fetch_results($ses, $pos+1, 1, true, "[[em]]", "[[/em]]");
				$mypreview = array();
				if(isset($results['results']) && is_array($results['results']))
				{
					$mypreview = $results['results'];
				}
				else
				{
					$pos = 0;
					$mypreview = array(array('base_id'=>false,'record_id'=>false,'xml'=>false));	
				}
				
				$bas = $mypreview[0]["base_id"];
				$rec = $mypreview[0]["record_id"];
				$xmlMAIN = $mypreview[0]["xml"];
				$isFullyPublic = true;
				$title = sprintf(_('preview:: resultat numero %s / '),'<span id="current_result_n">'.($pos+1).'</span>');
			break;
		case "REG":
				$contId = explode('_',$contId);
				$basReg = $contId[0];
				$recReg = $contId[1];
				
				if($pos == 0)
				{
					$bas = $basReg;
					$rec = $recReg;
					$isReg = true;
					$title = _('preview:: regroupement ');
				}
				else
				{
					$children = phrasea_grpchild($ses,$basReg,$recReg,GV_sit,$usr);
					$bas = $children[($pos-1)][0];
					$rec = $children[($pos-1)][1];
					$title = sprintf(_('preview:: Previsualisation numero %s '),(count($children)-$pos+1).'/'.count($children));
				}
				$xmlMAIN = phrasea_xmlcaption($ses,  $bas, $rec);
				
			break;
		case "BASK":
				
				$basket = basket::getInstance($contId);
				
				$posChu = 0;
				$name = '';
				$isPub = false;
				$i = 0;
				$first = true;
				$posAlt = false;
	
				foreach($basket->elements as $element)
				{
						$i++;
						if($first)
						{
							$bas = $element->base_id;
							$rec = $element->record_id;
							$isBask = true;
							$xmlMAIN = phrasea_xmlcaption($ses,  $bas, $rec);

							$posChu = $i;
							$name = $basket->name;
							$isPub = !!$basket->public;
							$posAlt = $element->order;
						}
						$first = false;
						if($isPub && !!$basket->pub_restrict)
							$isFullyPublic = true;

						if($element->order == $pos)
						{
							$bas = $element->base_id;
							$rec = $element->record_id;
							$isBask = true;
							$xmlMAIN = phrasea_xmlcaption($ses,  $bas, $rec);

							$posChu = $i;
							$name = $basket->name;
							$isPub = !!$basket->public;
							$posAlt = $element->order;
						}
				}
				
				if($posAlt != $pos)
					$pos = $posAlt;
				if($isPub)
					$title = $name.' ('.$posChu.'/'.$i.')';
				else
					$title = $name.' ('.$posChu.'/'.$i.') ';

			break;
	}
	
	if(!$bas || !$rec)
	{
		return p4string::jsonencode(array(
			'error'	=>	_('preview:: erreur, l\'element demande est introuvable')
		));
	}
	
	
	$sbas = phrasea::sbasFromBas($bas);
	$connsbas = connection::getInstance($sbas);
	
	$sql = 'DELETE FROM sselnew WHERE ssel_id = "'.$conn->escape_string($contId).'" AND usr_id = "'.$conn->escape_string($usr).'"';
	$conn->query($sql);

	$sdMain = phrasea_subdefs($ses,$bas,$rec);
	
	$fullUrl = $bitly = false;
	if(isset($sdMain['document']['sha256']))
		$fullUrl = '/document/'.$bas.'/'.$rec.'/'.$sdMain['document']['sha256'].'/view/';
	if(isset($sdMain['document']['bitly']) && $sdMain['document']['bitly']!= '')
		$bitly = $sdMain['document']['bitly'];
	
	
	if(isset($connsbas) && $connsbas)
	{
		$report = false;
		
		if(isset($session->report[$sbas]))
		{
			if(in_array($bas,$session->report[$sbas]))
				$report = true;
		}
		
		
		if($report && GV_google_api)
		{
			$views = $dwnls = array();
			$top = 1;
			$day = 30;
			$min = 0;
			$average = 0;
			
			while($day>=0)
			{
				
				$datetime = new DateTime('-'.$day.' days');
				$date = date_format($datetime,'Y-m-d');
				$views[$date] = $dwnls[$date] = 0;
				$day --;
				
			}
			
			$sql = 'SELECT count(id) as views, DATE(date) as datee FROM `log_view` WHERE record_id="'.$connsbas->escape_string($rec).'" AND date > (NOW() - INTERVAL 1 MONTH) AND site_id="'.$connsbas->escape_string(GV_sit).'" GROUP BY datee ORDER BY datee ASC';
			
			if($rs = $connsbas->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					if(isset($views[$row['datee']]))
					{
						$views[$row['datee']] = (int)$row['views'];
						$top = max( (int)$row['views'] ,$top );
						$min = isset($min) ? min($row['views'],$min) : $row['views'];
						$average += $row['views'];
					}
				}
			}
			
			if($bitly)
				$popularity .= '<div>'._('preview::statistiques pour le lien').' <a class="bitly_link bitly_link_'.$bitly.'" href="http://bit.ly/info/'.$bitly.'" target="_blank">http://bit.ly/'.$bitly.'</a></div>';
			
			$topScale = round($top*1.2);	
			
			$average = $average / 30;
			$max = round(($top)*100/($topScale));
			$min = round($min*100/($topScale));
			$average = round($average*100/($topScale));
				
			$popularity .= '<br>'._('preview::statistiques de visualisation pour le lien').'<br/> <img src="http://chart.apis.google.com/chart?'.
				'chs=350x150'.
				'&chd=t:'.implode(',',$views).
				'&cht=lc'.
				'&chf=bg,s,00000000'.
				'&chxt=x,y,r'.
				'&chds=0,'.$topScale.
				'&chls=2.0&chxtc=2,-350'.
				'&chxl=0:|'.date_format(new DateTime('-30 days'),'d M').'|'.date_format(new DateTime('-15 days'),'d M').'|'.date_format(new DateTime(),'d M').'|1:|0|'.round($top/2,2).'|'.$top.'|2:|min|average|max'.
				'&chxp=2,'.$min.','.$average.','.$max.'" />';
			
			$publis = array();
			
			$sql = 'SELECT name, url FROM publi_settings WHERE usr_id=null OR usr_id="'.$conn->escape_string($session->usr_id).'"';
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					$publis[$row['name']] = $row['url'];
				}
				$conn->free_result($rs);
			}
			
			
			$sql = 'SELECT count( id ) AS views, referrer FROM `log_view` WHERE record_id = "'.$connsbas->escape_string($rec).'" AND date > ( NOW( ) - INTERVAL 1 MONTH ) GROUP BY referrer ORDER BY referrer ASC';
			$referrers = array();
			
			if($rs = $connsbas->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					if($row['referrer'] == 'NO REFERRER')
						$row['referrer'] = _('report::acces direct');	
					if($row['referrer'] == GV_ServerName.'prod/')
						$row['referrer'] = _('admin::monitor: module production');		
					if($row['referrer'] == GV_ServerName.'client/')
						$row['referrer'] = _('admin::monitor: module client');	
					if(strpos($row['referrer'],GV_ServerName.'login/') !== false )
						$row['referrer'] = _('report:: page d\'accueil');	
					if(strpos($row['referrer'],'http://apps.cooliris.com/') !== false )
						$row['referrer'] = _('report:: visualiseur cooliris');	

					foreach($publis as $n=>$u)
					{
						if(strpos($row['referrer'],$u) !== false )	
							$row['referrer'] = _('report:: publication : ').$n;	
					}
					if(strpos($row['referrer'],GV_ServerName.'document/') !== false )
					{
						if(strpos($row['referrer'],'/view/') !== false)
							$row['referrer'] = _('report::presentation page preview');
						else
							$row['referrer'] = _('report::acces direct');	
							
					}				
					if(!isset($referrers[$row['referrer']]))
						$referrers[$row['referrer']] = 0;
					$referrers[$row['referrer']] += (int)$row['views'];
				}
			}
			
			$popularity .= '<br/><img src="http://chart.apis.google.com/chart?cht=p3&chf=bg,s,00000000&chd=t:'.implode(',',$referrers).'&chs=550x100&chl='.urlencode(implode('|',array_keys($referrers))).'"/>';
			
			$sql = 'SELECT count(d.id) as dwnl, DATE(d.date) as datee FROM `log_docs` d, log l WHERE action="download" AND log_id=l.id AND record_id="'.$connsbas->escape_string($rec).'" AND d.date > (NOW() - INTERVAL 1 MONTH) AND site="'.$connsbas->escape_string(GV_sit).'" GROUP BY datee ORDER BY datee ASC';
			
			$top = 10;
			
			if($rs = $connsbas->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					if(isset($dwnls[$row['datee']]))
					{
						$dwnls[$row['datee']] = (int)$row['dwnl'];
						$top = max(((int)$row['dwnl']+10),$top);	
					}
				}
			}
			
			$popularity .= '<br>'._('preview::statistiques de telechargement').'<br/> <img src="http://chart.apis.google.com/chart?'.
				'chs=250x150'.
				'&chd=t:'.implode(',',$dwnls).
				'&cht=lc'.
				'&chf=bg,s,00000000'.
				'&chxt=x,y'.
				'&chds=0,'.$top.
				'&chxl=0:|'.date_format(new DateTime('-30 days'),'d M').'|'.date_format(new DateTime('-15 days'),'d M').'|'.date_format(new DateTime(),'d M').'|1:|0|'.round($top/2).'|'.$top.'" />';
		}
		
		
		
		
		
		
		$sql = 'SELECT d . * , l.user, l.usrid as usr_id, l.site
			FROM log_docs d, log l
			WHERE d.log_id = l.id
			AND d.record_id = "'.$connsbas->escape_string($rec).'"';

		if(!$report)
			$sql .= ' AND ((l.usrid ="'.$connsbas->escape_string($usr).'" AND l.site="'.$connsbas->escape_string(GV_sit).'" ) OR action="add" ) ';
		
		$sql .= 'ORDER BY d.date, usrid DESC';		
		
		$tab = array();
		
		if($rs = $connsbas->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$hour = phraseadate::getPrettyString(new DateTime($row['date']));
				
				if(!isset($tab[$hour]))
					$tab[$hour] = array();
				
				$site = $row['site'];
				
				if(!isset($tab[$hour][$site]))
					$tab[$hour][$site] = array();
				
				$action = $row['action'];
				
				if(!isset($tab[$hour][$site][$action]))
					$tab[$hour][$site][$action] = array();
					
				if(!isset($tab[$hour][$site][$action][$row['usr_id']]))	
					$tab[$hour][$site][$action][$row['usr_id']] = array('final'=>array(),'comment'=>array());
				
				if(!in_array($row['final'],$tab[$hour][$site][$action][$row['usr_id']]['final']))
					$tab[$hour][$site][$action][$row['usr_id']]['final'][] = $row['final'];
					
				if(!in_array($row['comment'],$tab[$hour][$site][$action][$row['usr_id']]['comment']))
					$tab[$hour][$site][$action][$row['usr_id']]['comment'][] = $row['comment'];
				
				
			}
		}
		
		
		$tab = array_reverse($tab);
		foreach($tab as $hour=>$sites)
		{		
			foreach($sites as $site=>$actions)
			{
				foreach($actions as $action=>$users)
				{
					foreach($users as $user=>$done)
					{
						$historydet = '';
						
						switch($action)
						{
							case 'push':
								$historydet .= sprintf(_('report::Push vers %d utilisateurs'),count($done['final'])).sprintf(_('report:: depuis lapplication box %s'),'<span class="provenance">'.$site.'</span>');
								break;
							case 'validate':
								$historydet .= sprintf(_('report::Demande de validation vers %d utilisateurs'),count($done['final'])).sprintf(_('report:: depuis lapplication box %s'),'<span class="provenance">'.$site.'</span>');
								break;
							case 'edit':
								$historydet .= _('report::Edition des meta-donnees');
								break;
							case 'collection':
								$historydet .= _('report::Changement de collection vers : ').' '.implode(', ',$done['final']);
								break;
							case 'status':
								$historydet .= _('report::Edition des status');
								break;
							case 'print':
								$historydet .= _('report::Impression des formats : ').' '.implode(', ',$done['final']);
								break;
							case 'substit':
								$historydet .= _('report::Substitution : ').' '.implode(', ',$done['final']);
								break;
							case 'publish':
								$historydet .= _('report::Publies :').' '.implode(', ',$done['final']);
								break;
							case 'download':
								$historydet .= _('report::Telecharges : ').' '.implode(', ',$done['final']);
								break;
							case 'mail':
								$historydet .= _('report::Envoi par mail aux destinataires suivants : ').' '.implode(', ',$done['comment'])._('report::Envoi des documents suivants').implode(', ',$done['final']);
								break;
							case 'ftp':
								$historydet .= _('report::Envoi par ftp aux destinataires suivants : ').' '.implode(', ',$done['comment'])._('report::Envoi des documents suivants').implode(', ',$done['final']);
								break;
							case 'delete':
								$historydet .= _('report::supression du document');
								break;
							case 'add':
								$historydet .= _('report::ajout du documentt');
								break;
							default:
								$historydet .= _('report::Modification du document -- je ne me souviens plus de quoi...');
								break;
						}
						
						
						$history .= '<div style="margin:3px 0">';
						
						$history .= '<div class="history-'.$action.'">'.$historydet;
						
						
						$history .= ' <span class="actor">';
						
						if($report)
						{
							if($user != $usr)
								$history .= ' '.sprintf(_('report:: par %s'),user::getInfos($user));
						}
								
						$history .= '</span></div>';
							
						$history .= '<div style="font-size:10px;text-decoration:italic;">'.$hour."</div>";
					}
				}
			}
		}
	}
	
	
	$docType = isset($sdMain['document'])?$sdMain['document']['type']:'unknown';
	
	$prev = answer::get_preview($bas,$rec,$isFullyPublic);
	
	$preview = $prev['preview'];
	$flashcontent = $prev['flashcontent'];
	$width = $prev['width'];
	$height = $prev['height'];
	$doctype = $prev['doctype'];
	
	$caption = "";
	
	if($bas && $rec)
	{
		$caption = answer::format_caption($bas,$rec, $xmlMAIN);
	            
		
		$caption .= '<hr style="margin:10px 0;"/>';
		
		$exifinfos = answer::format_infos($xmlMAIN,$sbas,$rec,$docType);
		
		$caption .= $exifinfos;
		
		$title .= (trim($title) !== '' ? ' - ' : '').answer::format_title($sbas,$rec,$xmlMAIN);
	}
	else
	{
		$caption = '';
	}
	
	$dstatus = status::getDisplayStatus();
	$status = strrev(phrasea_status($session->ses_id, $bas, $rec));
	
	while(strlen($status) < 64)
		$status .= '0';
		
	$statuses = '';
	
	$user = user::getInstance($session->usr_id);
	
	if($status && isset($dstatus[$sbas]))
	{			
		foreach($dstatus[$sbas] as $n=>$statbit)
		{
			if(!isset($status[$n]))
				continue;
			if($statbit['printable'] == '0' && (!isset($user->_rights_bas[$bas]) || $user->_rights_bas[$bas]['chgstatus'] === false))
			{
				continue;
			}	

			if($status[$n] === '1')
			{
				if($statbit["img_on"])
				{
					$statuses .= "<img style=\"margin:1px;cursor:help;\" src=\"".$statbit["img_on"]."\" title=\"".(isset($statbit["labelon"])?$statbit["labelon"]:$statbit["lib"])."\"/>";
				}
			}
			else
			{
				if($statbit["img_off"])
				{
					$statuses .= ("<img style=\"margin:1px;cursor:help;\" src=\"".$statbit["img_off"]."\" title=\"".(isset($statbit["labeloff"])?$statbit["labeloff"]:("non-".$statbit["lib"]))."\"/>");
				}
			}
		}
	}

	$caption = '<div style="text-align:center;">'.$statuses . '</div>' . $caption;
	
	$user = user::getInstance($session->usr_id);
	
	if(isset($user->_rights_bas[$bas]) && $user->_rights_bas[$bas]['canmodifrecord'])
		$caption = '<div class="edit_button" style="text-align:right"><a href="#" onclick="editThis(\'IMGT\',\''.$bas.'_'.$rec.'\');"> <img style="vertical-align:middle" src="/skins/prod/000000/images/ppen_history.gif" /> '._('action : editer').'</a></div>'.$caption;
	
	##############################		
		
		
	$tools = '' ;
	$hdpath = $hdW = $hdH = false;

	if(!($isBask && !$isPub) && isset($canBasket[$bas]) && $canBasket[$bas])
		$tools .= '<div sbas="'.$sbas.'" id="PREV_BASKADD_'.$bas.'_'.$rec.'" class="baskAdder" title="'._('action : ajouter au panier').'" onclick="evt_add_in_chutier(\''.$bas.'\',\''.$rec.'\',false,this);return(false);"></div>' ;//BASK
		
	$tools .= '<div class="printer" title="'._('action : print').'" onclick="evt_print(\''.$bas.'_'.$rec.'\');return(false);"></div>' ;//PRINT
	
	if(isset($canDL[$bas]) && $canDL[$bas])
		$tools .= '<div class="downloader" title="'._('action : exporter').'" onclick="evt_dwnl(\''.$bas.'_'.$rec.'\');return(false);"></div>';//DL
		
	
		$train = '';

	if($isReg)
	{
		$train = getRegTrain($ses,$basReg,$recReg,$usr,$pos);
	}
	
	if($isBask && $roll)
	{
		
		$basket = basket::getInstance($contId);
		
		foreach($basket->elements as $element)
			$children[] = array($element->base_id,$element->record_id,$element->order);
		
		
		$train .= '' .
				'<div id="PREVIEWCURRENTCONT" class="PNB10">' .
				'<ul>';
		$i = 1;
		foreach($children as $child)
		{
			$sd = answer::getThumbnail($ses,$child[0],$child[1]);
			if($sd['w']>$sd['h'])
				$style='width:65px;top:'.round((66-(65/($sd['w']/$sd['h'])))/2).'px;';
			else
				$style='height:65px;top:0;';
			
			$minirollover = "";
			if(GV_rollover_reg_preview)
			{
				if(!isset($canPreview[$child[0]]))
					$canPreview[$child[0]] = false;
				$minirollover = getMiniRollover($ses,$usr,$child,$canPreview[$child[0]]);
				$minirollover = str_replace(array("'",'"',"\n"),array("\'",'\'',''),$minirollover);
			}
			$class = '';
			if($pos == $child[2])
				$class .= ' selected';
			$train .= '<li class="'.$class.' prevTrainCurrent"><img title="'.$minirollover.'" jsargs="BASK|'.$child[2].'|'.$contId.'" class="openPreview prevRegToolTip" return(false);" src="'.$sd['thumbnail'].'" style="'.$style.'margin:7px;position:relative;"/></li>';
			$i++;
		}
		
		$train .= '</ul>' .
				'</div>' .
				'<div class="cont_infos">' .
					'<div>' .
						'<img src="/skins/icons/light_left.gif" style="margin-right:10px;" onclick="getPrevious();"/>' .
						'<img src="/skins/icons/light_right.gif" style="margin-left:10px;" onclick="getNext();"/><br/>' .
						'<span onclick="startSlide()" id="start_slide"> '._('preview:: demarrer le diaporama').' </span>' .
						'<span onclick="stopSlide()" id="stop_slide"> '._('preview:: arreter le diaporama').' </span>' .
					'</div>' .
				'</div>' .
				'<div id="PREVIEWTOOL">' .
				$tools .
				'</div>' .
			'';
	}
	
	if($env == 'RESULT')
	{
					$train .= '' .
				'<div id="PREVIEWCURRENTCONT" class="PNB10">' .
					'<div style="margin:2px 0;">' .
						'<img src="/skins/icons/light_left.gif" style="margin-right:20px;" onclick="getPrevious();"/>' .
						'<span onclick="startSlide()" id="start_slide"> '._('preview:: demarrer le diaporama').' </span>' .
						'<span onclick="stopSlide()" id="stop_slide"> '._('preview:: arreter le diaporama').' </span>' .
						'<img src="/skins/icons/light_right.gif" style="margin-left:20px;" onclick="getNext();"/><br/>' .
					'</div>' .
				'</div>' .
				'<div id="PREVIEWTOOL" style="top:0;bottom:auto;">' .
					$tools .
				'</div>' .
			'';
	}
	
	
	$others = '';
	if(($parents = phrasea_grpparent($ses,$bas,$rec,GV_sit,$usr)) && (($env != 'REG') || ($env=='REG' && count($parents)>1)))
	{
		$others .= '<ul>';
		$others .= '<li class="title">'._('Apparait aussi dans ces reportages').'</li>';
		foreach($parents as $parent)
		{
			if($parent[0]!=$basReg || $parent[1]!=$recReg)
			{
				$sd = answer::getThumbnail($ses,$parent[0],$parent[1]);
				if($sd['w']>$sd['h'])
					$style='width:65px;top:'.(($sd['w']-$sd['h'])/4).'%;';
				else
					$style='height:65px;top:0;';

				$minirollover = "";
				if(GV_rollover_reg_preview)
				{
					if(!isset($canPreview[$parent[0]]))
						$canPreview[$parent[0]] = false;
					$minirollover = getMiniRollover($ses,$usr,$parent,$canPreview[$parent[0]]);
					$minirollover = str_replace(array("'",'"',"\n"),array("\'",'\'',''),$minirollover);
				}
				$titre ='';

				$desc = phrasea_xmlcaption($ses,  $parent[0], $parent[1]);
				$titre = getRegName(phrasea::sbasFromBas($parent[0]) , $desc) ;	

				$liWidth = 'margin:0 10px;';
				$liWidth = 'width:49%;';
					
				$others .= '<li onclick="openPreview(\'REG\',0,\''.$parent[0].'_'.$parent[1].'\'); return(false);" class="otherRegToolTip" title="'.$minirollover.'">';
//				$others .= '<div class="others_img"><img src="'.$sd['thumbnail'].'" style="width:25px;height:25px;"/></div>';
				$others .= '<img src="'.$sd['thumbnail'].'" style="width:25px;height:25px;"/>';
				$others .= ' <span class="title"> '.$titre.'</span>' .
									'</li>';
			}
		}
		$others .= '</ul>';
	}
	
	$baskets = answer::getContainerBaskets($bas,$rec, $contId);
	if(count($baskets) > 0)
	{
		$others .= '<ul>';
		$others .= '<li class="title">'._('Apparait aussi dans ces paniers').'</li>';
		
		foreach($baskets as $b_id=>$b)
		{
			$others .= '<li onclick="openPreview(\'BASK\','.$b['ord'].',\''.$b_id.'\',true); return(false);" class="otherBaskToolTip" title="'.str_replace('"','&quot;',$b['description']).'"><img style="vertical-align:middle;" title="" src="/skins/icons/basket.gif"/> <span class="title">'.$b['name'].'</span></li>';
		}
		
		$others .= '</ul>';
	}
		

	
	$title = collection::getLogo($bas).' '.$title;
	
	
	return p4string::jsonencode(array(
		"prev"=>$preview
		,"flashcontent"=>$flashcontent
		,"desc"=>p4string::entitydecode($caption)
		,"width"=>$width
		,"height"=>$height
		,"others"=>$others
		,"hd"=>$hdpath
		,"record_id"=>$rec
		,"base_id"=>$bas
		,"hdH"=>$hdH
		,"hdW"=>$hdW
		,"current"=>$train
		,"history"=>$history
		,"popularity"=>$popularity
		,"tools"=>$tools
		,"pos"=>$pos
		,"type"=>$doctype
		,"uUrl"=>$fullUrl
		,"title"=>p4string::entitydecode($title)
	));
	
}

function getAnswerTrain($pos)
{
	$train = query::getPrevTrain($pos);
	return $train;
}

function getRegTrain($ses,$basReg,$recReg,$usr,$pos)
{
	// on prepare le train
	phrasea_open_session($ses,$usr);
	$children = phrasea_grpchild($ses,$basReg,$recReg,GV_sit,$usr);
	
	$train = '';
	$sd = answer::getThumbnail($ses,$basReg,$recReg);
	
	$ratio = $sd['w']/$sd['h'];
	if($sd['w']>$sd['h'])
		$style='width:80px;top:'.(8+(80-round(80/$ratio))/2).'px;';
	else
		$style='height:80px;top:8px;';
	
	$train .= '' .
			'<div id="PREVMAINREG" class="PNB10">' .
				'<img onclick="openPreview(\'REG\',0,\''.$basReg.'_'.$recReg.'\')" src="'.$sd['thumbnail'].'" style="position:relative;'.$style.'" />'.
			'</div>' .
			'<div id="PREVIEWCURRENTCONT" class="PNB10 group_case">' .
				'<ul>';
	$i = 1;
	$n = 0;
	if(!$children)
		$children = array();
	foreach($children as $child)
	{
		$sd = answer::getThumbnail($ses,$child[0],$child[1]);
		if($sd['w']>$sd['h'])
			$style='width:65px;top:'.round((66-(65/($sd['w']/$sd['h'])))/2).'px;';
		else
			$style='height:65px;top:0;';
		
		$minirollover = "";
		if(GV_rollover_reg_preview)
		{
			if(!isset($canPreview[$child[0]]))
				$canPreview[$child[0]] = false;
			$minirollover = getMiniRollover($ses,$usr,$child,$canPreview[$child[0]]);
			$minirollover = str_replace(array("'",'"',"\n"),array("\'",'\'',''),$minirollover);
		}
		$class='';
		if($i == $pos)
			$class='selected';
		
		$first = false;
		$train .= '<li class="'.$class.' prevTrainCurrent" style=""><img title="'.$minirollover.'" jsargs="REG|'.$i.'|'.$basReg.'_'.$recReg.'" class="openPreview prevRegToolTip" return(false);" src="'.$sd['thumbnail'].'" style="'.$style.'margin:7px;position:relative;"/></li>';
		$i++;
		$n++;
	}
	
	$train .= '</ul>' .
			'</div>' .
			'<div class="cont_infos">' .
				'<div>' .
					'<img src="/skins/icons/light_left.gif" style="margin-right:10px;" onclick="getPrevious();"/>' .
					'<img src="/skins/icons/light_right.gif" style="margin-left:10px;" onclick="getNext();"/><br/>' .
					'<span onclick="startSlide()" id="start_slide"> '._('preview:: demarrer le diaporama').' </span>' .
					'<span onclick="stopSlide()" id="stop_slide"> '._('preview:: arreter le diaporama').' </span>' .
				'</div>' .
			'</div>' .
			'<div id="PREVIEWTOOL">' .
//			$tools .
			'</div>' .
		'';
	return $train;
}


function getthemicrotime()
{ 
	list($usec, $sec) = explode(" ",microtime()); 
	return ((float)$usec + (float)$sec); 
}

function getMiniRollover($ses,$usr,$elem,$canPreview)
{
							$style = "padding:2px";	
							$minirollover = '';
							$minirollover .= '<table cellpadding="0" cellspacing="0" class="tabledescexp">';
							$minirollover .= '<tr>';
							$minirollover .= '<td valign="top">';
							
							$isVideo = $isImage = $isAudio = $isDocument = false;
							
							$sd = phrasea_subdefs($ses,$elem[0],$elem[1]);
							
							if(isset($sd['document']))
							{
								$docType = $sd['document']['type'];
								$isVideo = $docType == 'video' ? true:false;
								$isAudio = $docType == 'audio' ? true:false;
								$isImage = $docType == 'image' ? true:false;
								$isDocument = $docType == 'document' ? true:false;
							}

							$prev ="";
							
							if(!$isVideo && !$isAudio)
								$isImage = true;
		
							if($isImage)
							{
								if(isset($sd["preview"]["width"]) && $canPreview)
								{
									$prev = "/include/directprev.php?&bas=".$elem[0]."&rec=".$elem[1];							
									$minirollover .= '<img src="'.$prev .'" style="'.$style.'; width:'.round($sd["preview"]["width"]/2).'px;height:'.round($sd["preview"]["height"]/2).'px">';
								}
								elseif(isset($sd["thumbnail"]))
								{
									$sd["preview"] = $sd["thumbnail"];
									$prev = '/'.p4string::addEndSlash($sd["thumbnail"]["baseurl"]).$sd["thumbnail"]["file"];
									$minirollover .= '<img src="'.$prev .'" style="'.$style.'; width:'.round($sd["thumbnail"]["width"]/2).'px;height:'.round($sd["thumbnail"]["height"]/2).'px">';
								}
							}
							elseif($isVideo)
							{
								if(isset($sd["thumbnailGIF"]["width"]) && $canPreview)
								{
									$prev = "/include/directprev.php?type=thumbnailGIF&bas=".$elem[0]."&rec=".$elem[1];							
									$minirollover .= '<img src="'.$prev .'" style="'.$style.'; width:'.round($sd["preview"]["width"]/2).'px;height:'.round($sd["preview"]["height"]/2).'px">';
								}
							}
							elseif($isDocument)
							{
								
							}
							elseif($isAudio)
							{
								if(isset($sd["thumbnail"]["width"]))
								{
									$prev = "/include/directprev.php?type=thumbnail&bas=".$elem[0]."&rec=".$elem[1];							
									
									$dispwidth = "";
									if((int)$sd["thumbnail"]["width"]>200 || (int)$sd["thumbnail"]["height"]>200)
										$dispwidth = 'width="200"';
										
									$minirollover .= '<img src="'.$prev .'" style="'.$style.';" '.$dispwidth.'/>';
								}
							}					
							
							
							$minirollover.='</td>';
							$minirollover.='<td valign="top" >';
							$z = null;
							if($tmpxml=phrasea_xmlcaption($ses,$elem[0], $elem[1]))
							{
								$sxDesc = simplexml_load_string( $tmpxml );
								$z = $sxDesc->xpath('/record/description');	
							}
							
							$minirolloverTMP="";
							$lastkey = "";
							
							if($z && $z[0])
							{
								foreach($z[0] as $key=>$val)
								{
									$val2 = str_replace( "\n"," ",trim((string)$val) );
									$val2 = str_replace( "\r"," ", $val2  );
									$val2 = str_replace( "'","\'",$val2 );
									if($lastkey!=$key )
									{
										if( $lastkey!="")
											$minirollover		.='<div class="field">'.$minirolloverTMP.'</div>';
										$minirolloverTMP 	 ='<span class="field_title">'.(string)$key .':</span><span>'. $val2.'</span>'  ;
									}
									else 
										$minirolloverTMP.='<span>;'. $val2.'</span>'; 				 	
								 	$lastkey = $key;
								}
							}
							
							if( $lastkey!="")
								$minirollover		.='<div class="field">'.$minirolloverTMP.'</div>';
							else 
								$minirollover		.='<div class="field"> </div>';
					
							$minirollover.='</td>';
							$minirollover.='</tr>';				
							$minirollover.='</table>';
							$minirollover = str_replace(array("\t","\r","\n"),array('','',''),$minirollover);
	
	return $minirollover;
}



function getRegName($sbas_id , $desc)
{
	$balisename = null;
	$balisename = '';	
	
	$struct = databox::get_structure($sbas_id);
	
	if($sxe = simplexml_load_string($struct))
	{
		$z = $sxe->xpath('/record/description');
		if($z && is_array($z))
		{
			foreach($z[0] as $ki => $vi)			
				if($vi['regname']=='1')
					$balisename = $ki;
		}
	}
	$regname = ''; 
	if($sxe =  simplexml_load_string($desc))
	 	$regname =  (string)$sxe->description->$balisename;
	return $regname;
}

function updateBask($usr,$ses)
{
	$ret = null;
	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return $ret;
		
	$conn = connection::getInstance();
	if(!$conn)
	{
		return $ret;
	}
	
	$sql = 'SELECT s.*, n.id as noview' .
	' FROM ssel s ' .
	'	LEFT JOIN sselnew n' .
	'	ON (n.ssel_id = s.ssel_id AND n.usr_id = "'.$conn->escape_string($usr).'")' .
	' WHERE (' .
	'  (s.public = "1" AND s.pub_restrict="0")' .
	'  OR s.usr_id="'.$conn->escape_string($usr).'"' .
	'  OR (s.public="1" AND s.pub_restrict="1" AND' .
	'  (SELECT COUNT(c.sselcont_id) FROM sselcont c WHERE c.ssel_id=s.ssel_id AND c.base_id IN' .
	'    (SELECT base_id from basusr WHERE usr_id = "'.$conn->escape_string($usr).'" AND actif ="1") )>0 ))' .
	' AND temporaryType="0"' .
	' ORDER BY public,pushFrom,ssel_id asc';
		
	$nbNoview = 0;
	
	if($rs = $conn->query($sql))
	{				
		while($row = $conn->fetch_assoc($rs))
		{
			$isOk = true;
			if($row['public'] == 1 && $row['pub_restrict'] == 1)
			{
				$sqlA = 'SELECT c.record_id, s.*, u.mask_and, u.mask_xor FROM sselcont c, sbas s, bas b, basusr u WHERE c.ssel_id="'.$conn->escape_string($row['ssel_id']).'" AND c.base_id IN ' .
					' (SELECT base_id from basusr WHERE usr_id = "'.$conn->escape_string($usr).'" AND actif ="1")' .
					' AND b.base_id = c.base_id AND b.sbas_id = s.sbas_id AND u.usr_id = "'.$conn->escape_string($usr).'" AND u.base_id = b.base_id';
				$rsBas = $conn->query($sqlA);
				
				$isOk = false;

				while(($raw = $conn->fetch_assoc($rsBas)) && !$isOk)
				{
					$connsbas = connection::getInstance($raw['sbas_id']);
					if($connsbas)
					{
						$sql = 'SELECT record_id FROM record WHERE ((status ^ '.$raw['mask_xor'].') & '.$raw['mask_and'].')=0 AND record_id="'.$connsbas->escape_string($raw['record_id']).'"';
	
						$rsRec = $connsbas->query($sql);
						if($connsbas->num_rows($rsRec)>0)
							$isOk = true;
					}
				}
				$conn->free_result($rsBas);
				
			}
			
			if($isOk)
			{
				
				if($row['public'] == '1')
				{
					if($row['noview']!="" && $usr != $row['usr_id']){
						$nbNoview++;
					}
				}
				elseif($row['pushFrom']!='0' && $row['noview']!="")
				{
						$nbNoview++;
				}
				
			}
		}
	}
	
	
	return $nbNoview;
	
}

function setCss($usr, $ses, $color)
{
	if(($newPreffs = user::setPrefs('client', 'css', $color)) !== false)
	{
		return 1;
	}
	else
		return 0;
}

function setBaskStatus($usr,$ses,$mode)
{
	user::setPrefs('client_basket_status', $mode);
}


