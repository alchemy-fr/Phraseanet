<?php

function deleteRecord($lst,$del_children)
{
	
	
	$session = session::getInstance();
	
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
	$ph_session = phrasea_open_session($ses_id,$usr_id);
	
	$usrRight = NULL;
	$lst = explode(";", $lst);
	
	$tcoll = array();
	$tbase = array();
	
	$conn = connection::getInstance();

	$sql = "select base_id,candeleterecord from (usr natural join basusr ) where usr.usr_id='".$conn->escape_string($usr_id)."'";
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$usrRight[$row["base_id"]] = $row["candeleterecord"];
		}
			
		$conn->free_result($rs);
	}	
			
	foreach($lst as $basrec)
	{
		$basrec = explode("_", $basrec);
		if($basrec && count($basrec)==2)
		{
			if(!isset($tcoll["c".$basrec[0]]))
			{
				
				$tcoll["c".$basrec[0]] = null;
				
				foreach($ph_session["bases"] as $bas)
				{
					foreach($bas["collections"] as $col)
					{
						if($col["base_id"] == $basrec[0])
						{
							$tcoll["c".$basrec[0]] = array("base_id"=>$bas["base_id"], "id"=>$basrec[0]);
							if(!isset($tbase["b".$bas["base_id"]]))
							{
								$x = isset($bas["xmlstruct"]) ? $bas["xmlstruct"] : null;
								$tbase["b".$bas["base_id"]] = array("id"=>$bas["base_id"], "base"=>$bas, "rids"=>array());
							}
							break;
						}
					}
				}
			}
			
			$temp = null;
			$temp[0]=$basrec[0];
			$temp[1]=$basrec[1];
			
			$tbase["b".$tcoll["c".$basrec[0]]["base_id"]]["rids"][] = $temp;

		}
	}
	$ret = array();
			 	
	foreach($tbase as $base)
	{
		$connbas = connection::getInstance(phrasea::sbasFromBas($base['id']));
		if($connbas)
		{
			foreach($base["rids"] as $rid)
			{
				if(isset($usrRight[$rid[0]]))
				{
					if($usrRight[$rid[0]]==1)
					{
						if($del_children=="1")
						{
							$allson = phrasea_grpchild($ses_id,$rid[0], $rid[1],GV_sit,$usr_id);
							if($allson)
							{											 
								foreach($allson as $oneson)
								{
									if( $usrRight[$oneson[0]]=="1")
									{
										$ret = array_merge($ret,delRecord(phrasea::sbasFromBas($oneson[0]),$oneson,$connbas,true));
									}
								}
							}
						} 
						$ret = array_merge($ret,delRecord(phrasea::sbasFromBas($rid[0]),$rid,$connbas));
		
					}				
				}	
			}
		}
	}
	
	$sql = array();
	foreach($ret as $basrec)
	{
		$br = explode('_',$basrec);
		$sql[] = '(base_id = "'.$conn->escape_string($br[0]).'" AND record_id = "'.$conn->escape_string($br[1]).'")';	
		
		$cache_basket = cache_basket::getInstance();
		
		$sql_ssel = 'SELECT ssel_id, usr_id FROM ssel WHERE sbas_id="'.phrasea::sbasFromBas($br[0]).'" AND rid="'.$conn->escape_string($br[1]).'"';
		if($rs = $conn->query($sql_ssel))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$cache_basket->delete($row['usr_id'], $row['ssel_id']);
			}
			$conn->free_result($rs);
		}
		
		
		$sql_ssel = 'DELETE FROM ssel WHERE sbas_id="'.phrasea::sbasFromBas($br[0]).'" AND rid="'.$conn->escape_string($br[1]).'"';
		$conn->query($sql_ssel);
	}
	
	if(count($sql)>0)
	{
		$cache_basket = cache_basket::getInstance();
		
		$sql_res = 'SELECT DISTINCT ssel.usr_id, ssel.ssel_id FROM sselcont ,ssel  WHERE '.implode(' OR ',$sql).' AND sselcont.ssel_id = ssel.ssel_id AND ssel.usr_id="'.$conn->escape_string($usr_id).'"';
		if($rs = $conn->query($sql_res))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$cache_basket->delete($usr_id, $row['ssel_id']);
			}
			$conn->free_result($rs);
		}
		
		
		$sql = 'DELETE FROM sselcont WHERE '.implode(' OR ',$sql).' AND ssel_id IN (SELECT ssel_id FROM ssel WHERE usr_id = "'.$conn->escape_string($usr_id).'")';
		$conn->query($sql);
	}
	
	return p4string::jsonencode($ret);
}



function delRecord( $sbas_id, $rid ,&$connbas, $child=false )
{
	
	$session = session::getInstance();
	
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	$dst_logid = $session->logs;
	
	$ftodel = array();
	
	$sql = "SELECT path, file FROM subdef WHERE record_id='" . $connbas->escape_string($rid[1]) . "'";
	if($rs = $connbas->query($sql))
	{
		while($row= $connbas->fetch_assoc($rs))
		{			
			$key = implode('_',$rid);
			
			if(!isset($ftodel[$key]))
				$ftodel[$key] = array();
				
			$ftodel[$key][] = p4string::addEndSlash($row["path"]) . $row["file"];
			$ftodel[$key][] = p4string::addEndSlash($row["path"]) . 'watermark_' . $row["file"];
			$ftodel[$key][] = p4string::addEndSlash($row["path"]) . 'stamp_' . $row["file"];
		}
		$connbas->free_result($rs);
	}
	
	$cache_thumb = cache_thumbnail::getInstance();
	$cache_thumb->delete($sbas_id, $rid[1]);
	$cache_preview = cache_preview::getInstance();
	$cache_preview->delete($sbas_id, $rid[1]);
	
	
	$info["origdate"] = "";
	$info["origcoll"] = "";
	$sqltmp = "SELECT coll_id,credate FROM record WHERE record_id='" . $connbas->escape_string($rid[1])."'";
	
	if($rstmp = $connbas->query($sqltmp))
	{
		if($rowtmp = $connbas->fetch_assoc($rstmp))
		{
			$info["origdate"] = $rowtmp["credate"];
			$info["origcoll"] = $rowtmp["coll_id"];
		}
		$connbas->free_result($rstmp);
	}
	
	$logid = null;
	if(isset($dst_logid[$sbas_id]))		
		$logid = $dst_logid[$sbas_id];
		
	$sql = "INSERT INTO histo (id  , logid, act, date, record     , origdate, origcoll) 
	VALUES 			  (NULL,'".$connbas->escape_string($logid)."',   '2', now(), '".$connbas->escape_string($rid[1])."', '".$connbas->escape_string($info["origdate"])."'    , '".$connbas->escape_string($info["origcoll"])."' )" ;
	$connbas->query($sql);
	
	$oldXml = '';
	$sql = 'SELECT xml FROM record WHERE record_id="'.$connbas->escape_string($rid[1]).'"';
	if($rs = $connbas->query($sql))
	{
		if($row = $connbas->fetch_assoc($rs))
		{
			$oldXml = $row['xml'];
		}
		$connbas->free_result($rs);
	}
	
	answer::logEvent($sbas_id,$rid[1],'delete',$info['origcoll'],$oldXml);
	
	$sql = "DELETE FROM record WHERE record_id='" . $connbas->escape_string($rid[1])."'";
	$connbas->query($sql);
	$sql = "DELETE FROM prop WHERE record_id='" . $connbas->escape_string($rid[1])."'";
	$connbas->query($sql);
	$sql = "DELETE FROM idx WHERE record_id='" . $connbas->escape_string($rid[1])."'";
	$connbas->query($sql);
	$sql = "DELETE FROM subdef WHERE record_id='" . $connbas->escape_string($rid[1])."'";
	$connbas->query($sql);
	$sql = "DELETE FROM thit WHERE record_id='" . $connbas->escape_string($rid[1])."'";
	$connbas->query($sql);
	
	$sql = "DELETE FROM regroup WHERE rid_parent='" . $connbas->escape_string($rid[1])."'";
	$connbas->query($sql);	
	$sql = "DELETE FROM regroup WHERE rid_child='" . $connbas->escape_string($rid[1])."'";
	$connbas->query($sql);					

	
	foreach($ftodel as $f)
		foreach($f as $s)
			@unlink($s);
	
	return array_keys( $ftodel );
}

function whatCanIDelete($lst)
{
	
	$session = session::getInstance();
	

	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
	$conn = connection::getInstance();

	$nbdocsel = 0;
	$nbgrp = 0 ;
	$oksel = array();
	$arrSel = explode(";",$lst);

	if(!is_array($lst))
		$lst = explode(';',$lst);
		
	foreach($lst as $sel)
	{
		if($sel=="")
			continue;
		$exp = explode("_",$sel);
		
		if(count($exp)==2)
		{
			$go = false;
			$sqlV = 'SELECT mask_and, mask_xor, sb.*' .
					' FROM (sbas sb, bas b, usr u)' .
					' LEFT JOIN basusr bu ON (bu.base_id = b.base_id AND bu.candeleterecord="1" AND bu.usr_id = "'.$conn->escape_string($usr_id).'" AND actif="1")' .
					' WHERE u.usr_id = "'.$conn->escape_string($usr_id).'"' .
					' AND b.base_id = "'.$conn->escape_string($exp[0]).'"' .
					' AND b.sbas_id = sb.sbas_id';
			if($rsV = $conn->query($sqlV))
			{
				if($rowV = $conn->fetch_assoc($rsV))
				{
					if($rowV['mask_and'] != '' && $rowV['mask_xor'] != '')
					{
						$connbas = connection::getInstance($rowV['sbas_id']);
						if($connbas)
						{
							$sqlS2 = 'SELECT record_id FROM record WHERE ((status ^ '.$rowV['mask_xor'].') & '.$rowV['mask_and'].')=0 AND record_id="'.$connbas->escape_string($exp[1]).'"';
							
							if($rsS2 = $connbas->query($sqlS2)){
								if(($connbas->num_rows($rsS2)) > 0)
								{
									$go = true;
									$oksel[] = implode('_',$exp);
								}
								$connbas->free_result($rsS2);
							}
						}
					}
				}
				$conn->free_result($rsV);
			}
			
			if($go)
			{
				$nbdocsel++;
				if(phrasea_isgrp($ses_id, $exp[0], $exp[1]))
					$nbgrp++;
			}
		}
	}
	
	$ret = array('lst'=>$oksel,'groupings'=>$nbgrp);
	
	return p4string::jsonencode($ret);
}


function getLanguage($lng)
{
	$out = array();
	$out['thesaurusBasesChanged'] 	=_('prod::recherche: Attention : la liste des bases selectionnees pour la recherche a ete changee.');
	$out['confirmDel'] 				=_('paniers::Vous etes sur le point de supprimer ce panier. Cette action est irreversible. Souhaitez-vous continuer ?');
	$out['serverError'] 			=_('phraseanet::erreur: Une erreur est survenue, si ce probleme persiste, contactez le support technique');
	$out['serverName'] 				= GV_ServerName;
	$out['serverTimeout'] 			=_('phraseanet::erreur: La connection au serveur Phraseanet semble etre indisponible');
	$out['serverDisconnected'] 		=_('phraseanet::erreur: Votre session est fermee, veuillez vous re-authentifier');
	$out['hideMessage'] 			=_('phraseanet::Ne plus afficher ce message');
	$out['confirmGroup'] 			=_('Supprimer egalement les documents rattaches a ces regroupements');
	$out['confirmDelete'] 			=_('reponses:: Ces enregistrements vont etre definitivement supprimes et ne pourront etre recuperes. Etes vous sur ?');
	$out['cancel'] 					=_('boutton::annuler');
	$out['deleteTitle'] 			=_('boutton::supprimer');
	$out['edit_hetero']				=_('prod::editing valeurs heterogenes, choisir \'remplacer\', \'ajouter\' ou \'annuler\'');
	$out['confirm_abandon']			=_('prod::editing::annulation: abandonner les modification ?');
	$out['loading']					=_('phraseanet::chargement');
	$out['valider']					=_('boutton::valider');
	$out['annuler']					=_('boutton::annuler');
	$out['rechercher']				=_('boutton::rechercher');
	$out['renewRss']				=_('boutton::renouveller');
	$out['candeletesome']			=_('Vous n\'avez pas les droits pour supprimer certains documents');
	$out['candeletedocuments']		=_('Vous n\'avez pas les droits pour supprimer ces documents');
	$out['needTitle']				=_('Vous devez donner un titre');
	$out['newPreset']				=_('Nouveau modele');
	$out['fermer']					=_('boutton::fermer');
	$out['removeTitle']				=_('panier::Supression d\'un element d\'un reportage');
	$out['confirmRemoveReg']		=_('panier::Attention, vous etes sur le point de supprimer un element du reportage. Merci de confirmer votre action.');
	$out['advsearch_title']			=_('phraseanet::recherche avancee');
	$out['bask_rename']				=_('panier:: renommer le panier');
	$out['reg_wrong_sbas']			=_('panier:: Un reportage ne peux recevoir que des elements provenants de la base ou il est enregistre');
	$out['error']					=_('phraseanet:: Erreur');
	$out['warningDenyCgus']			=_('cgus :: Attention, si vous refuser les CGUs de cette base, vous n\'y aures plus acces');
	$out['cgusRelog']				=_('cgus :: Vous devez vous reauthentifier pour que vos parametres soient pris en compte.');
	$out['editDelMulti']			=_('edit:: Supprimer %s du champ dans les records selectionnes');
	$out['editAddMulti']			=_('edit:: Ajouter %s au champ courrant pour les records selectionnes');
	$out['editDelSimple']			=_('edit:: Supprimer %s du champ courrant');
	$out['editAddSimple']			=_('edit:: Ajouter %s au champ courrant');
	$out['cantDeletePublicOne']		=_('panier:: vous ne pouvez pas supprimer un panier public');
	$out['wrongsbas']				=_('panier:: Un reportage ne peux recevoir que des elements provenants de la base ou il est enregistre');
	$out['max_record_selected']		=_('Vous ne pouvez pas selectionner plus de 400 enregistrements');
	$out['confirmRedirectAuth'] 	= _('invite:: Redirection vers la zone d\'authentification, cliquez sur OK pour continuer ou annulez');
	$out['error_test_publi']		=_('Erreur : soit les parametres sont incorrects, soit le serveur distant ne repond pas');
	$out['test_publi_ok']			=_('Les parametres sont corrects, le serveur distant est operationnel');
	$out['some_not_published']		=_('Certaines publications n\'ont pu etre effectuees, verifiez vos parametres');
	$out['error_not_published']		=_('Aucune publication effectuee, verifiez vos parametres');
	$out['warning_delete_publi']	=_('Attention, en supprimant ce preregalge, vous ne pourrez plus modifier ou supprimer de publications prealablement effectues avec celui-ci');
	$out['some_required_fields']	=_('edit::certains documents possedent des champs requis non remplis. Merci de les remplir pour valider votre editing');
	$out['nodocselected']			=_('Aucun document selectionne');
	return p4string::jsonencode($out);
}

function setCss($usr, $ses, $color)
{
	if(($newPreffs = user::setPrefs('css', $color)) !== false)
	{
		return 1;
	}
	else
		return 0;
}

function baskets($ssel,$srt='')
{
	
	$out = '';

	$conn = connection::getInstance();
	
	$out .= '<div class="bloc">';
	$out .= '<div class="insidebloc">';
	
	$srt = in_array($srt,array('date','name')) ? $srt : 'name';
	
	user::setPrefs('basket_sort_field',$srt);
	
	$srt .= ' ' . ($srt == 'date' ? 'desc' : 'asc');
	
	$basket_coll = new basketCollection($srt);//basket::getBaskets($srt);
	$baskets = $basket_coll->baskets;
	
	
	$firstBask = true;
	$firstBask = false;
	
	if(is_int((int)$ssel) && (int)$ssel>0)
		$firstBask = false;
		
	foreach($baskets as $baskType=>$basket)
	{
		if(count($basket)>0)
		{
			$isReg =  false;
			$is_push = false;
			switch($baskType){
				case 'recept':
						$is_push = true;
				break;
				case 'regroup':
						$isReg = true;
				break;
			}
			
			foreach($basket as $bask)
			{
				$ssel_id = $bask->ssel_id;
				
		  		$style = '';
		  		$sbas = '';
		  		$class = 'basket';
		  		$imgReg = '';
		  		if($isReg)
		  		{
		  			$sbas = $bask->sbas_id;
		  			$class = "grouping";
		  			
		  			$imgReg = collection::getLogo($bask->base_id);
				}
				else
				{
					
					$imgReg = "<img src='/skins/icons/basket.gif' title=''/>";
					
				}
				$date = $bask->updated_on;
				
				$noViewClass = $bask->noview ? ' unread ' : '';
				$push_class = $is_push ? ' received ' : '';
				$infos = '<div style="margin:5px;width:280px;"><div><span style="font-weight:bold;font-size:14px;">'.$bask->name.'</span> </div>'.($isReg?('<div style="text-align:right;">'._('phraseanet::collection').' '.collection::getName($bask->base_id, true).'</div>'):'').'<div style="margin:5px 0">'.nl2br($bask->desc).'</div>'.
				'<div style="margin:5px 0;text-align:right;font-style:italic;">'.sprintf(_('paniers: %d elements'),count($bask->elements)).' - '.phraseadate::getPrettyString(new DateTime($date)).'</div><hr/><div style="position:relative;float:left;width:270px;">'.$bask->get_excerpt().'</div>';
				
				$out .= '<div title="'.str_replace('"','&quot;',$infos).'" id="SSTT_'.$ssel_id.'" sbas="'.$sbas.'" class="basketTips ui-accordion-header ui-state-default ui-corner-all header SSTT '.$class.$noViewClass.' '.(($firstBask || $ssel_id==$ssel)?'active':'').'" onclick="loadBask(\''.$ssel_id.'\',this)">
					<div class="PNB title">'.$imgReg.' '.$bask->name.'</div>
					<div class="menu">';
				
				if($bask->homelink)
					$out .= '<img class="basketTips homelink_icon" title="'._('panier:: ce panier est publie en home page').'" src="/skins/icons/ligth-on.png"/>';
			
				if($bask->valid == 'myvalid')
				{
					$title = _('Vous avez envoye une demande de validation de document sur ce panier');
					$out .= '<img title="'.$title.'" class="basketTips" src="/skins/icons/myvalid.png"/>';
				}
				if($bask->valid == 'valid')
				{
					$title = _('Vous avez recu une demande de validation de document sur ce panier');
					$out .= '<img title="'.$title.'" class="basketTips" src="/skins/icons/valid.png"/>';
				}
				if($is_push)
					$out .= '<img class="basketTips" title="'.sprintf(_('paniers:: panier recu de %s'),user::getInfos($bask->pusher)).'" src="/skins/icons/pushed_bask.png"/>';
				if($bask->public == '1' || count(p4publi::getPublications($ssel_id))>0)
					$out .= '<img class="basketTips" title="'.sprintf(_('paniers:: ce panier est publie')).'" src="/skins/icons/rss16.png"/>';
				
				$out .= '<a style="cursor:pointer;display:inline;padding:0;margin:0;" class="contextMenuTrigger">&#9660;</a>';
					
					
				$out .= '</div>
					
					<table cellspacing="0" cellpadding="0" style="display:none;" class="contextMenu basketcontextmenu">
						<tbody>
							<tr>
								<td>
									<div class="context-menu context-menu-theme-vista">
										<div title="" class="context-menu-item">
											<div class="context-menu-item-inner" onclick="downloadThis(\'SSTTID='.$ssel_id.'\');">'._('action::exporter').'</div>
										</div>
										<div title="" class="context-menu-item menu3-custom-item">
											<div onclick="editThis(\'SSTT\',\''.$ssel_id.'\');" style="" class="context-menu-item-inner">'._('action::editer').'</div>
										</div>';
				
				
//									if($baskType != 'grouping' && (!in_array($bask->valid, array('valid','myvalid'))))
//									{
//										$out .= '<div title="" class="context-menu-item">
//											<div class="context-menu-item-inner"><a href="/lightbox/compare/'.$ssel_id.'/" target="_blank">Lightbox</a></div>
//										</div>';
//									}
										
									if($baskType == 'baskets')
									{
										$out .= '<div title="" class="context-menu-item">
											<div class="context-menu-item-inner" onclick="pubDialog(this,\''.$ssel_id.'\');return false;">'._('action::publier').'</div>
										</div>';
										$out .= '<div title="" class="context-menu-item">
											<div class="context-menu-item-inner '.($bask->homelink ? 'published':'').'" onclick="homelinkThis(this,\''.$ssel_id.'\');return false;">'._('action::page d\'accueil').'</div>
										</div>';
										$out .= '<div title="" class="context-menu-item">
											<div class="context-menu-item-inner" onclick="renameBasket(this,\''.$ssel_id.'\');return false;">'._('action::renommer').'</div>
										</div>';
										
									}
									
									if($baskType != 'regroup')
									{
										$out .= '<div title="" class="context-menu-item">
													<a target="_blank" href="/lightbox/validate/'.$ssel_id.'/">
														<div class="context-menu-item-inner">'.
                                                                          (in_array($bask->valid, array('valid','myvalid')) ? _('action::Valider') : 'Lightbox').'</div>
													</a>
										</div>';

									}
									if($baskType == 'regroup')
									{
										$out .= '<div title="" class="context-menu-item">
											<div id="UNFIX_'.$ssel_id.'" class="context-menu-item-inner" onclick="unFix(this);return false;">'._('action::detacher').'</div>
										</div>
										<div title="" class="context-menu-item">
											<div class="context-menu-item-inner" onclick="reorder('.$ssel_id.');return false;">'._('Re-ordonner').'</div>
										</div>';
									}
									else
									{						
										$out .= '<div title="" class="context-menu-item">
											<div class="context-menu-item-inner" id="SSTTREMOVER_'.$ssel_id.'" onclick="checkDeleteThis(\'SSTT\',this);return false;">'._('action : supprimer').'</div>
										</div>';
										
										if($bask->valid != 'myvalid')
										{
											$out .= '<div title="" class="context-menu-item">
												<div class="context-menu-item-inner" onclick="reorder('.$ssel_id.');return false;">'._('Re-ordonner').'</div>
											</div>';
										}
									}
									$out .= '</div>
								</td>
							</tr>
						</tbody>
					</table>
					</div><div id="SSTT_content_'.$ssel_id.'" class="content '.$class.'" style="overflow:hidden;">';
				
				$firstBask = false;
				
				$out .='</div>';
				
			}
			
		}
	}
	$out .= '</div>';
	$out .= '</div>';
	
	return $out;
}



	
?>