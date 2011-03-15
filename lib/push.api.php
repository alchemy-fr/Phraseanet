<?php

function newUserCheckMail($usr,$ses,$lng,$mail,$usr_id,$out='HTML')
{
	$conn = connection::getInstance();
	
	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return;

	$sql = sqlFromFilters($usr,$ses,'');
	
	$sql .= ' AND usr.usr_mail = "'.$conn->escape_string($mail).'"';
	
	if($rs = $conn->query($sql))
	{
		if($n = $conn->num_rows($rs)>0)
		{
			
			return '<div>'.sprintf(_('push:: %d utilisateurs accessible via le formulaire de recherche ont ete trouves. Vous ne pouvez pas ajouter d\'utilisateur portant cette adresse email'),$n).'</div>';
			
			
		}
	}
	
	$ret = array();

	$sql="SELECT usr_id, usr_mail, usr_login, usr_nom, usr_prenom, activite, societe, fonction, pays, usr_sexe" .
			" FROM usr" .
			" WHERE usr_mail='".$conn->escape_string($mail)."'" .
			" AND usr_login" .
			" NOT LIKE '(#deleted_%)#%' AND invite='0'";	

	$bases = implode(',',array_keys(whatCanIAdmin($usr,$ses)));
	
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$row['base'] = $row['watermark'] = $row['canpreview'] = array();
			$sql = 'SELECT base_id, needwatermark, canpreview FROM basusr WHERE usr_id="'.$conn->escape_string($row['usr_id']).'" AND base_id IN ('.$bases.') AND actif="1"';
			
			if($rsR = $conn->query($sql))
			{
				while($raw = $conn->fetch_assoc($rsR))
				{
					$row['base'][$raw['base_id']] = '1';
					$row['watermark'][$raw['base_id']] = $raw['needwatermark'];
					$row['canpreview'][$raw['base_id']] = $raw['canpreview'];
				}
			}
			$ret[$row['usr_id']] = $row;
		}
		$conn->free_result($rs);
	}
	
	
	if($out=='HTML')
		$ret = formatUsrForm($usr,$ses,$lng,$usr_id,$ret);
	
	return $ret;
}

function formatUsrForm($usr,$ses,$lng,$usr_id,$datas)
{
	
	require_once(GV_RootPath.'lib/countries.php');
	 	
	$ctry = getCountries($lng);
	
	$canAdmin = whatCanIAdmin($usr,$ses);	

	$out = '<form id="ADD_USR_FORM" name="add_usr_form" action="push.feedback.php">
				  <div style="margin: 0pt 0pt 0pt 40px; width: 400px;">';
	
	if(count($datas)>1)
	{
				  $out .= '<div>'._('push :: Plusieurs utilisateurs correspondant a cette addresse email ont ete trouves dans la base.')._('push:: Ces utilisateurs ne sont pas presentes car ils n\'ont pas encore acces a une des collections que vous administrez ou parce qu\'ils sont fantomes.')._('push:: Trouvez le profil correspondant a la personne que vous recherchez et donner lui acces a au moin l\'une de vos collection pour lui transmettre des documents').'</div>
				  <select onchange="adduserDisp(this)">';
	
		$out .= '<option value="">'._('choisir').'</option>';
		foreach($datas as $data)
		{
			$sel = $data['usr_id']==$usr_id?'selected':'';;
			$out .= '<option '.$sel.' value="'.$data['usr_id'].'">'.$data['usr_login'].'</option>';
		}
		$out .= '</select>';
	}
	if(count($datas)==1)
	{
		$usr_id=implode('',array_keys($datas));
		$out .= '<div>'._('push :: Cet utilisateur a ete trouve dans la base, il correspond a l\'adresse email que vous avez renseigne').'</div>';
				  
	}
				 $out .= '</div>';
	if($usr_id != '' && isset($datas[$usr_id]))
	{
		$part = $datas[$usr_id];	
	}
	else
	{
		$part = array(
			'usr_login'=>''
			,'usr_nom'=>''
			,'usr_prenom'=>''
			,'usr_sexe'=>''
			,'activite'=>''
			,'fonction'=>''
			,'pays'=>''
			,'usr_mail'=>''
			,'watermark'=>''
			,'canpreview'=>''
			,'base'=>''
			,'societe'=>''
			,'usr_id'=>''
		);
	}
	if((count($datas)>1 && $usr_id!='') || count($datas)<=1)
	{
		$out .= '<table style="margin: 40px 0pt 0pt 40px;">
						<tr>
							<td>
							  	<label for="add_ident">'._('admin::compte-utilisateur identifiant').' :</label>
							</td>
							<td colspan="3">
								<input value="'.$part['usr_login'].'" type="text" name="add_ident" id="add_ident" size="20"/>
								<input value="'.$part['usr_id'].'" type="hidden" name="add_id" id="add_id"/>
							</td>
						</tr>
						<tr>
							<td>
								<label for="nothing">'._('admin::compte-utilisateur sexe').' :</label>
							</td>
							<td colspan="3">
								<input '.($part['usr_sexe']=='0'?'checked':'').' style="float:left;width:auto;" id="CIV_0" name="CIV" value="0" checked="checked" type="radio"/>
								<label style="float:left;width:auto;" for="CIV_0">'._('admin::compte-utilisateur:sexe: mademoiselle').'</label>
								<input '.($part['usr_sexe']=='1'?'checked':'').' style="float:left;width:auto;" id="CIV_1" value="1" name="CIV"" type="radio"/>
								<label style="float:left;width:auto;" for="CIV_1">'._('admin::compte-utilisateur:sexe: madame').'</label>
								<input '.($part['usr_sexe']=='2'?'checked':'').' style="float:left;width:auto;" id="CIV_2"" value="2" name="CIV" type="radio"/>
								<label style="float:left;width:auto;" for="CIV_2">'._('admin::compte-utilisateur:sexe: monsieur').'</label>
							</td>
						</tr>
						<tr>
							<td>
								<label for="add_nom">'._('admin::compte-utilisateur nom').' : </label>
							</td>
							<td colspan="3">
								<input value="'.$part['usr_nom'].'" type="text" name="add_nom" id="add_nom" size="20"/>
							</td>
						</tr>
						<tr>
							<td>
								<label for="add_prenom">'._('admin::compte-utilisateur prenom').' : </label>
							</td>
							<td colspan="3">
								<input value="'.$part['usr_prenom'].'" type="text" name="add_prenom" id="add_prenom" size="20"/>
							</td>
						</tr>
						<tr>
							<td>
								<label for="add_societe">'._('admin::compte-utilisateur societe').' : </label>
							</td>
							<td colspan="3">
								<input value="'.$part['societe'].'" type="text" name="add_societe" id="add_societe" size="20"/>
							</td>
						</tr>
						<tr>
							<td>
								<label for="add_fonction">'._('admin::compte-utilisateur poste').' : </label>
							</td>
							<td colspan="3">
								<input value="'.$part['fonction'].'" type="text" name="add_fonction" id="add_fonction" size="20"/>
							</td>
						</tr>
						<tr>
							<td>
								<label for="add_activite">'._('admin::compte-utilisateur activite').' : </label>
							</td>
							<td colspan="3">
								<input value="'.$part['activite'].'" type="text" name="add_activite" id="add_activite" size="20"/>
							</td>
						</tr>
						<tr>
							<td>
								<label for="add_pays">'._('admin::compte-utilisateur pays').' : </label>
							</td>
							<td colspan="3">';
								$out .= '<select id="add_pays" name="add_pays" style="width:150px;">
												<option class="pays_switch" value="">'._('choisir').'</option>';
								foreach($ctry as $k=>$c)
								{
									$sel = $part['pays']==$k?'selected':'';
									$out .= '<option '.$sel.' class="pays_switch" value="'.$k.'">'.$c.'</option>';
								}
									$out .= '</select>
							</td>
						</tr>
						<tr>
							<td colspan="4">
								<span>'._('push::L\'utilisateur cree doit pouvoir acceder a au moins l\'une de ces bases').'</span>
							</td>
						</tr>
						';
									
						$out.= '
								<tr><td> </td><td>'._('push::Acces').'</td>
								<td>'._('push::preview').'</td>
								<td>'._('push::watermark').'</td></tr>';

						foreach($canAdmin as $base=>$basename)
							$out.= '
								<tr><td><span>'.$basename.'"</span></td><td><input '.((isset($part['base'][$base]) && $part['base'][$base]==1)?'checked':'').' type="checkbox" value="'.$base.'" class="baseinsc" name="baseinsc[]" /></td>
								<td><input '.((isset($part['canpreview'][$base]) && $part['canpreview'][$base]==1)?'checked':'').' type="checkbox" value="'.$base.'" class="basepreview" name="basepreview[]" /></td>
								<td><input '.((isset($part['watermark'][$base]) && $part['watermark'][$base]==1)?'checked':'').' type="checkbox" value="'.$base.'" class="basewm" name="basewm[]" /></td></tr>
										';
				
			$out .= '
						
						<tr>
							<td><input type="button" value="'._('boutton::valider').'" onclick="addNewUser();" size="20"/></td>
							<td colspan="3"><input type="button" value="'._('boutton::annuler').'" onclick="cancelAddUser();" size="20"/></td>
						</tr>
						</table></div>
						
					  
				  	';
	}
			return $out;
}

function sendHdOk($usr,$ses,$lst)
{
	
	
	$conn = connection::getInstance();

	if(!(phrasea_open_session($ses,$usr)))
		return;
		
	$ret = array();
	
	$bases = array();
	foreach($lst as $basrec)
	{
		$basrec = explode('_',$basrec);
		if(count($basrec)==2)
		{
			$bases[] = $basrec[0];
		}
	}
	
	$bases = implode(',',array_unique($bases));
	if($bases != '')
	{
		$sql = 'SELECT base_id, candwnldhd FROM basusr WHERE usr_id = "'.$conn->escape_string($usr).'" AND base_id IN ('.$bases.') AND actif="1" AND candwnldhd="1" ';

		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$ret[] = $row['base_id'];
			}
			$conn->free_result($rs);
		}
	}
	
	return $ret;
}

function whatCanIAdmin($usr,$ses)
{
	
	
	$conn = connection::getInstance();

	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return;

	$baseNames = array();
	foreach($ph_session['bases'] as $base)
		foreach($base['collections'] as $coll)
			$baseNames[$coll['base_id']] = $coll['name'];		
			
	$canAdmin = array();	
	
	$sql = "SELECT bu.canAdmin,bu.base_id FROM basusr bu, bas b WHERE bu.usr_id='".$conn->escape_string($usr)."' AND b.base_id=bu.base_id AND b.active='1'";
	
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs) )
		{
			if($row["canAdmin"]=="1" && isset($baseNames[$row['base_id']]))
				$canAdmin[$row['base_id']] = $baseNames[$row['base_id']];
		}
		$conn->free_result($rs);
	}
	return $canAdmin;
}

function getPushLanguage($usr,$ses,$lng)
{
	
	

	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return;
	
	$ret = array();
	$str = array(
					"selNameEmptyVal"
					,"notInList"
					,"userssel"
					,"wrongmail"
					,"noUsersSel"
					,"selNameEmpty"
					);
	
	
	$ret["selNameEmptyVal"]	= _('push::alertjs: un panier doit etre cree pour votre envoi, merci de specifier un nom');
	$ret["notInList"]		= _('push::alertjs: vous n\'etes pas dans la liste des personne validant, voulez vous etre ajoute ?');
	$ret["userssel"]		= _('phraseanet::utilisateurs selectionnes');
	$ret["wrongmail"]		= _('phraseanet:: email invalide');
	$ret["noUsersSel"]		= _('push::alertjs: aucun utilisateur n\'est selectionne');
	$ret["selNameEmpty"]	= _('push::alertjs: vous devez specifier un nom de panier');
	$ret['removeIlist']		= _('push:: supprimer la recherche');
	$ret['removeList']		= _('push:: supprimer la(es) liste(s) selectionnee(s)');
	
	return p4string::jsonencode($ret);
}

function createUserOnFly($usr,$ses,$arrayUsr,$arrayBases,$arrayPrev=array(),$arrayWm=array())
{
	
	
		
	$id = trim(stripslashes(urldecode($arrayUsr['ID'])));
	$ident = trim(urldecode($arrayUsr['IDENT']));
	$mail = trim(urldecode($arrayUsr['MAIL']));
	$nom = trim(urldecode($arrayUsr['NOM']));
	$prenom = trim(urldecode($arrayUsr['PREN']));
	$societe = trim(urldecode($arrayUsr['SOCIE']));
	$fonction = trim(urldecode($arrayUsr['FUNC']));
	$activite = trim(urldecode($arrayUsr['ACTI']));
	$country = trim(urldecode($arrayUsr['COUNTRY']));
	$dateEnd = trim(urldecode($arrayUsr['DATE_END']));
	$sexe=$arrayUsr['CIV'];
		
	$conn = connection::getInstance();

	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return;
	
	$n = 1;
	if($ident == "" && !is_numeric($id)){
		if($nom == ""){
			$ident = explode('@',$mail);
			$ident = $ident[0];
		}else
			$ident = $nom;
	}else
		$n = 0;
	while($n != 0){
		$sql = "SELECT USR_ID FROM usr WHERE usr_login='".$conn->escape_string($ident)."'";
		$rs = $conn->query($sql);
		$n = $conn->num_rows($rs);
		if($n != 0)
			$ident.=rand(0,9);
	}
	
	if(is_numeric($id))
	{
		$sql = 'SELECT usr_id FROM usr WHERE usr_id="'.$conn->escape_string($id).'" AND usr_mail="'.$conn->escape_string($mail).'" AND usr_login="'.$conn->escape_string($ident).'"';
		if($rs = $conn->query($sql))
		{
			if(($conn->num_rows($rs))==0)
				return '-23';
			else
				$id = $id;
				
			$conn->free_result($rs);
		}
		// verifier que jai bien le droit dediter ce mec
	}
	else
	{
		//verifier que ya tjrs pas d'user avec le meme mail
		
		if(count(newUserCheckMail($usr,$ses,'',$mail,'','PHP'))!=0)
		{
			return '-24';
		}
		$newid= $conn->getId("usr");
		
		$pass = random::generatePassword();
				
		$sql = 'INSERT INTO usr' .
			' (usr_id, usr_login, usr_password, usr_mail, usr_nom, usr_prenom, usr_sexe, societe, fonction,' .
					' activite, pays, usr_creationdate, usr_modificationdate, code8, model_of)' .
			' values ' .
			'("'.$conn->escape_string($newid).'","'.$conn->escape_string($ident).'","'.$conn->escape_string($pass).'","'.$conn->escape_string($mail).'","'.$conn->escape_string($nom).'","'.$conn->escape_string($prenom).'","'.$conn->escape_string($sexe).'","'.$conn->escape_string($societe).'","'.$conn->escape_string($fonction).'",' .
				'"'.$conn->escape_string($activite).'","'.$conn->escape_string($country).'", now(), now(), "0", "0" )';
		if(!$conn->query($sql))
			return '-2';
		else
			$id = $newid;
	}
		
	foreach($arrayBases as $base){
		if(is_numeric($base)){
			$timeLimit = '0';
			$limitedTo = '0000-00-00 00:00:00';
			if($dateEnd != '')
			{
				$timeLimit='1';
				$limitedTo = $dateEnd;
			}
			$sql = "INSERT INTO basusr" .
				" (base_id, usr_id, actif, creationdate,time_limited,limited_to )" .
				" VALUES ('".$conn->escape_string($base)."', '".$conn->escape_string($id)."', '1',now(),'".$conn->escape_string($timeLimit)."','".$conn->escape_string($limitedTo)."')";
			$conn->query($sql);
			$sql = "INSERT INTO sbasusr" .
				" (sbas_id, usr_id)" .
				" VALUES ('".phrasea::sbasFromBas($base)."', '".$conn->escape_string($id)."')";
			$conn->query($sql);
		}
	}
	foreach($arrayPrev as $base){
		if(is_numeric($base) && in_array($base,$arrayBases)){
			$sql = "UPDATE basusr" .
				" SET canpreview='1' WHERE usr_id='".$conn->escape_string($id)."' AND base_id='".$conn->escape_string($base)."'";
			$conn->query($sql);
		}
	}
	foreach($arrayWm as $base){
		if(is_numeric($base) && in_array($base,$arrayBases)){
			$sql = "UPDATE basusr" .
				" SET needwatermark='1' WHERE usr_id='".$conn->escape_string($id)."' AND base_id='".$conn->escape_string($base)."'";
			$conn->query($sql);
		}
	}
	return $id;
}

function whatCanIPush($usr,$ses,$lst)
{
	$newlst = array();
	
	$user = user::getInstance($usr);
	
	foreach($lst as $basrec)
	{
		$basrec = explode('_',$basrec);
		if(count($basrec) != 2)
			continue;
			
		if(!isset($user->_rights_bas[$basrec[0]]) || !$user->_rights_bas[$basrec[0]]['canpush'])
			continue;
		
		$newlst[] = implode('_',$basrec);
	}
	
	return $newlst;
}


function loadUsers($usr,$ses,$token,$filters)
{
	$session = session::getInstance();
	
	require_once(GV_RootPath.'lib/countries.php');
	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return;

	$conn = connection::getInstance();
	$out = array();
	
	$sql = sqlFromFilters($usr,$ses,$filters);
	$ret = 0;
	if($rs = $conn->query($sql))
	{
		if(isset($session->prod['push'][$token]))
		{
			$push_datas = $session->prod;
			while($row = $conn->fetch_assoc($rs))
			{
				$push_datas['push'][$token]['usrs'][$row['usr_id']] = array('HD'=>0);
			}
			$session->prod = $push_datas;
			$ret = count($session->prod['push'][$token]['usrs']);
		}
	}
	return $ret;
}

function unloadUsers($usr,$ses,$token,$filters)
{
	$session = session::getInstance();
	
	require_once(GV_RootPath.'lib/countries.php');
	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return;

	$conn = connection::getInstance();
	$out = array();
	
	$ret = -1;
	if(isset($session->prod['push'][$token]))
	{
		$push_datas = $session->prod;
		$push_datas['push'][$token]['usrs'] = array();
		$session->prod = $push_datas;
		$ret = count($session->prod['push'][$token]['usrs']);
	}
	return $ret;
}


function addUser($usr,$ses,$token,$usr_ids)
{
	$session = session::getInstance();
	
	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return;
	 	
	
	$ret = array('result'=>array(),'selected'=>0);
	
	$conn = connection::getInstance();
		
	$sql = sqlFromFilters($usr,$ses,'');
	
	if(isset($session->prod['push'][$token]))
	{
		$push_datas = $session->prod;
		$usr_ids = json_decode(stripslashes($usr_ids));
		
		$result = array();
		foreach($usr_ids as $usr_id=>$add)
		{
			$zsql = $sql.' AND usr.usr_id = "'.$usr_id.'"';
		
			if($rs = $conn->query($zsql))
			{
				if($conn->num_rows($rs) == 1)
				{
					//on peut ajouter
					if($add->sel == '0')
					{
						unset($push_datas['push'][$token]['usrs'][$usr_id]); 	
						$result[$usr_id] = 0;
					}
					if($add->sel == '1')
					{
						$hd_value = '0';
						if($add->hd=='1')
							$hd_value = '1'; 
						$push_datas['push'][$token]['usrs'][$usr_id] = array('HD'=>$hd_value);
						$result[$usr_id] = 1;
					}
				}
			}
		}
		$session->prod = $push_datas;
		$ret = array('result'=>$result, 'selected'=>count($session->prod['push'][$token]['usrs']));
	}
	return p4string::jsonencode($ret);
}

function sqlFromFilters($usr,$ses,$filters)
{
	
	
	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return;

		
	$conn = connection::getInstance();
	
	$baslist = array(); 
	$sql = 'SELECT DISTINCT(b.base_id) FROM (bas b, basusr u)' .
			' WHERE u.usr_id="'.$conn->escape_string($usr).'"' .
			' AND b.base_id =u.base_id' .
			' AND u.canpush="1"' .
			' AND u.actif="1"' .
			' AND b.active="1"';

	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs) )
		{
			$baslist[] = $row['base_id'];
		}
		$baslist = implode(',',$baslist);
		$conn->free_result($rs);
	}
	$precise ='';
	$filters = $filters!=''?json_decode(urldecode($filters)):false;
	if($filters)
	{
		foreach($filters->strings as $filter)
		{
			if(trim($filter->fieldsearch) == '')
				continue;
			$like = ' LIKE ';
			
			switch($filter->operator)
			{
				case 'and':
					$precise .= ' AND ';
					break;
				case 'or':
					$precise .= ' OR ';
					break;		
				case 'except':
					$precise .= ' AND ';
					$like = ' NOT LIKE ';
					break;
			} 
			switch($filter->fieldlike)
			{
				case 'BEGIN':
					$start='';$end='%';
					break;
				case 'CONT':
					$start='%';$end='%';
					break;		
				case 'END':
					$start='%';$end='';
					break;
			} 
			switch($filter->field)
			{
				case "LOGIN" :
					$precise.=" (usr_login ".$like." '$start".$conn->escape_string($filter->fieldsearch)."$end' COLLATE utf8_general_ci )";
					break;
				case "NAME" :
					$precise.="  ((usr_nom ".$like." '$start".$conn->escape_string($filter->fieldsearch)."$end' OR usr_prenom like '$start".$conn->escape_string($filter->fieldsearch)."$end' ) )";
					break;
				case "COMPANY" :
					$precise.=" (usr.societe ".$like." '$start".$conn->escape_string($filter->fieldsearch)."$end' )";
					break;
				case "MAIL" :
					$precise.=" (usr.usr_mail ".$like." '$start".$conn->escape_string($filter->fieldsearch)."$end' )";
					break;
				case "FCT" :
					$precise.=" (usr.fonction ".$like." '$start".$conn->escape_string($filter->fieldsearch)."$end' )";
					break;
				case "ACT" :
					$precise.=" (usr.activite ".$like." '$start".$conn->escape_string($filter->fieldsearch)."$end' )";
					break;
				case "LASTMODEL" :
					$precise.=" (usr.lastModel ".$like." '$start".$conn->escape_string($filter->fieldsearch)."$end' )";
					break;
			} 
		}
		if(count($filters->lists)>0 && trim($filters->lists[0])!='')
		{
				$precise.=' AND usr.usr_id IN (SELECT ulu.usr_id FROM usrlistusers ulu, usrlist ul WHERE ul.usr_id="'.$conn->escape_string($usr).'" AND ul.list_id IN ('.implode(',',$filters->lists).') AND ul.list_id = ulu.list_id) '; 
		}
		if(count($filters->countries)>0 && trim($filters->countries[0])!='')
		{
			$precise.=" AND usr.pays IN ('".implode("','",str_replace("'","''",$filters->countries))."')";
		}
		if(count($filters->activite)>0 && trim($filters->activite[0])!='')
		{
			$precise.=" AND usr.activite IN ('".implode("','",str_replace("'","''",$filters->activite))."')";
		}
		if(count($filters->fonction)>0 && trim($filters->fonction[0])!='')
		{
			$precise.=" AND usr.fonction IN ('".implode("','",str_replace("'","''",$filters->fonction))."')";
		}
		if(count($filters->societe)>0 && trim($filters->societe[0])!='')
		{
			$precise.=" AND usr.societe IN ('".implode("','",str_replace("'","''",$filters->societe))."')";
		}
		if(count($filters->template)>0 && trim($filters->template[0])!='')
		{
			$precise.=" AND usr.lastModel IN ('".implode("','",str_replace("'","''",$filters->template))."')";
		}
	}	
	$sqlGhost = '';
	if(count(whatCanIAdmin($usr,$ses))>0)
		$sqlGhost = ' OR (isnull(b.base_id)) ';
		
	$sql = 'SELECT DISTINCT usr.usr_id,usr_login, usr_mail,CONCAT_WS(" ",usr_nom,usr_prenom) as usr_nomprenom,societe,fonction,activite,pays,lastModel' .
		' FROM usr' .
		' LEFT JOIN basusr b ON b.usr_id=usr.usr_id' .
//		' left join demand on usr.usr_id=demand.usr_id' .
		' WHERE (b.base_id IN ('.$baslist.') '.$sqlGhost.' )' .
		' AND usr_login not like "(#deleted_%" '.// AND isnull(demand.base_id)' .
		' AND usr.model_of=0 '.$precise.' AND invite="0" AND usr_login!="invite" AND usr_login!="autoregister"' ;
	
	return $sql;
}


function hd_user($usr,$ses,$token,$usrs,$value)
{
	$session = session::getInstance();
	
	if(isset($session->prod['push'][$token]))
	{
		$push_datas = $session->prod;
		foreach($usrs as $u)
		{
			if(isset($push_datas['push'][$token]['usrs'][$u]))
			{
				$push_datas['push'][$token]['usrs'][$u]['HD'] = $value;
			}
		}
		$session->prod = $push_datas;
	}
}

function whoCanIPush($usr,$ses,$lng,$token,$view,$filters,$page=1,$sort='LA',$perPage='')
{
	$session = session::getInstance();
	
	require_once(GV_RootPath.'lib/countries.php');
	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return;
	 	
	$ctry = getCountries($lng);
	
	$conn = connection::getInstance();
	
	$out = '';
	
	if($view == 'current')
		$filters = '';
	$sql = sqlFromFilters($usr,$ses,$filters);
	

	if($view == 'search' && count($session->prod['push'][$token]['usrs']))
		$sql .= ' AND usr.usr_id NOT IN ('.implode(',',array_keys($session->prod['push'][$token]['usrs'])).') ';

	if($view == 'current')
		$sql .= ' AND usr.usr_id IN ('.implode(',',array_keys($session->prod['push'][$token]['usrs'])).') ';
						
	
	$nPage = $nresult = 0;
	if($rs = $conn->query($sql))
	{
		$nPage = ceil(($nresult = $conn->num_rows($rs))/$perPage);
	}
	if($page>$nPage)
		$page = $nPage;
	
	if(!isset($session->prod['push'][$token]))
		return;

	$orderBy = array();
	
	$sort = $sort!=''?json_decode(urldecode($sort)):array();
	$lact = $lsort = $nact = $nsort = $mact = $msort = $sact = $ssort = $jact = $jsort = $aact = $asort = $cact = $csort = $tact = $tsort = '';

	
	foreach($sort as $s)
	{
		switch($s)
		{
			case 'MA';
				$orderBy[] = 'usr_mail ASC';
				$mact = 'active';
				$msort = 'SortUp';
				break;
			case 'MD';
				$orderBy[] = 'usr_mail DESC';
				$mact = 'active';
				$msort = 'SortDown';
				break;
			case 'NA';
				$orderBy[] = 'usr_nomprenom ASC';
				$nact = 'active';
				$nsort = 'SortUp';
				break;
			case 'ND';
				$orderBy[] = 'usr_nomprenom DESC';
				$nact = 'active';
				$nlsort = 'SortDown';
				break;
			case 'LA';
				$orderBy[] = 'usr_login ASC';
				$lact = 'active';
				$lsort = 'SortUp';
				break;
			case 'LD';
				$orderBy[] = 'usr_login DESC';
				$lact = 'active';
				$lsort = 'SortDown';
				break;
			case 'SA';
				$orderBy[] = 'societe ASC';
				$sact = 'active';
				$ssort = 'SortUp';
				break;
			case 'SD';
				$orderBy[] = 'societe DESC';
				$sact = 'active';
				$ssort = 'SortDown';
				break;
			case 'JA';
				$orderBy[] = 'fonction ASC';
				$jact = 'active';
				$jsort = 'SortUp';
				break;
			case 'JD';
				$orderBy[] = 'fonction DESC';
				$jact = 'active';
				$jsort = 'SortDown';
				break;
			case 'AA';
				$orderBy[] = 'activite ASC';
				$aact = 'active';
				$asort = 'SortUp';
				break;
			case 'AD';
				$orderBy[] = 'activite DESC';
				$aact = 'active';
				$asort = 'SortDown';
				break;
			case 'CA';
				$orderBy[] = 'pays ASC';
				$cact = 'active';
				$csort = 'SortUp';
				break;
			case 'CD';
				$orderBy[] = 'pays DESC';
				$cact = 'active';
				$csort = 'SortDown';
				break;
			case 'TA';
				$orderBy[] = 'lastModel ASC';
				$tact = 'active';
				$tsort = 'SortUp';
				break;
			case 'TD';
				$orderBy[] = 'lastModel DESC';
				$tact = 'active';
				$tsort = 'SortDown';
				break;
		}
	}
	
	if(count($orderBy)>0)
		$sql .= ' ORDER BY '.implode(', ',$orderBy).'';
		
	
	
	$sql .= ' LIMIT '.(($page-1)*$perPage).', '.$perPage.'';

			$out .= '<div class="pager" id="pager" style="margin: 12px auto 3px; text-align: center;">
	<form>
		<img class="first" onclick="specialsearch(false,1)" src="/skins/icons/first.png"/>
		<img class="prev" '.(($page-1)>0?("onclick='specialsearch(false,".($page-1).")'"):"").' src="/skins/icons/prev.png"/>
		<input type="text" class="pagedisplay" value="'.$page.'/'.$nPage.'"/>
		<img class="next" '.(($page+1)>$nPage?"":"onclick='specialsearch(false,".($page+1).")'").' src="/skins/icons/next.png"/>
		<img class="last" onclick="specialsearch(false,'.($nPage).')" src="/skins/icons/last.png"/>
	
';
		$out .= '<select class="pagesize" onclick="setPerPage();" id="pagesizer">
			<option '.($perPage==10?'selected':'').' value="10">10</option>
			<option '.($perPage==20?'selected':'').' value="20">20</option>
			<option '.($perPage==30?'selected':'').' value="30">30</option>
			<option '.($perPage==40?'selected':'').' value="40">40</option>
		</select></form></div>';	
		$out .= "<div id='search_list' style='width:100%'>";
		
		$out .= "<table cellspacing='1' border='0' id='BLABLA' class=\"pushlist tablesorter\">";
		$out .= "<colgroup>";
		$out .= "<col width='11%'>";
		$out .= "<col width='12%'>";
		$out .= "<col width='20%'>";
		$out .= "<col width='12%'>";
		$out .= "<col width='12%'>";
		$out .= "<col width='12%'>";
		$out .= "<col width='10%'>";
		$out .= "<col width='11%'>";
		$out .= "<col width='20px'>";
		$out .= "</colgroup>";
		$out .= "<thead><tr><th colspan='8'  style='background-image:none;text-align:center;'>".sprintf(_('push:: %d resultats'),$nresult)." - 
				<a href='#' onclick='loadUsers();return false;'>"._('push:: tous les ajouter')."</a>  ---  
				".sprintf(_("push:: %s selectionnes"),"<span id='alert_nbuser'>".count($session->prod['push'][$token]['usrs'])."</span>")." - 
				<a href='#' onclick='$(\"#saveList, #saveListButton\").toggle();' id='saveListButton'> "._('push:: enregistrer cette liste')." </a><span id='saveList' style='display:none;'><input  type='text' id='NEW_LST'/> <img onclick='saveList();return false;' src='/skins/icons/save.png' /> <img src='/skins/icons/delete.gif' onclick='$(\"#saveList, #saveListButton\").toggle();'/> </span> / 
				<a href='#' onclick='unloadUsers();'>"._('push:: tout deselectionner')."</a> --- 
										<span ".($view != 'all'?'style="background-color:red;"':'').">"._('push:: afficher :')."</span><select  style='width:60px;' onchange='toggleView(this)'>
											<option ".($view == 'all'?'selected':'')." value='all'>"._('push:: afficher la recherche')."</option>
											<option ".($view == 'current'?'selected':'')." value='current'>"._('push:: afficher la selection')."</option>
										</select>
				</th></tr><tr>";
		$out .= "<th class='REFL ".$lact." ".$lsort."' id='TREFL'>"._('admin::compte-utilisateur identifiant')."</th>"; 				
		$out .= "<th class='REFN ".$nact." ".$nsort."' id='TREFN'>"._('admin::compte-utilisateur nom').'/'._('admin::compte-utilisateur prenom')."</th>";		
		$out .= "<th class='REFM ".$mact." ".$msort."' id='TREFM'>"._('admin::compte-utilisateur email'). "</th>";
		$out .= "<th class='REFS ".$sact." ".$ssort."' id='TREFS'>"._('admin::compte-utilisateur societe')."</th>";	
		$out .= "<th class='REFJ ".$jact." ".$jsort."' id='TREFJ'>"._('admin::compte-utilisateur poste')."</th>";	
		$out .= "<th class='REFA ".$aact." ".$asort."' id='TREFA'>"._('admin::compte-utilisateur activite')."</th>";				
		$out .= "<th class='REFC ".$cact." ".$csort."' id='TREFC'>"._('admin::compte-utilisateur pays')."</th>";		
		$out .= "<th class='REFT ".$tact." ".$tsort."' id='TREFT'>"._('admin::compte-utilisateur dernier modele applique')."</th>";	
		$out .= "<th><img src='/skins/icons/HD-down.png' title=\"".str_replace('"','&quot;',_('push:: donner les droits de telechargement HD'))."\"/></th>";

		
		$out .= "</tr></thead>";	
		$out .= "<tbody>";
		$out .= "";
		
		
		$out .= "";			
		$ilig=0;			
		
	if($rs = $conn->query($sql))
	{
		while(($row = $conn->fetch_assoc($rs)))
		{
//			if((($page-1)*300)<=$ilig && $ilig<($page*300))
//			{
				
				$sel = $hd_checked = '';
				if(array_key_exists($row["usr_id"],$session->prod['push'][$token]['usrs']))
				{
					$sel = 'selected';
					if($session->prod['push'][$token]['usrs'][$row["usr_id"]]['HD'] == '1')
						$hd_checked = 'checked';
					if($view == 'search')
						continue;
				}
				else
				{
					if($view == 'current')
						continue;
				}
				$out .= "<tr class='".$sel."' onclick=\"addUser(event,'".$row["usr_id"]."',this);\" s='0' id='USER_".$row["usr_id"]."'>";
				$out .= "<td>" . $row["usr_login"]."</td>";
				
				$out .= "<td>" . $row["usr_nomprenom"]."</td>";
				$out .= "<td>" . $row["usr_mail"]. "</td>";
				$out .= "<td>" . $row["societe"] . "</td>";
				$out .= "<td>" . $row["fonction"] . "</td>";
				$out .= "<td>" . $row["activite"] . "</td>";
				
				$pays = "";
				if(isset($ctry[trim($row["pays"])]))
					$pays = $ctry[trim($row["pays"])];
				
				$out .= "<td>" . $pays . "</td>";
				$out .= "<td>" . $row["lastModel"] . "</td>";
				$out .= "<td><input ".$hd_checked." type='checkbox' name='hd_box' value='1' onchange='checkHD(event,this,".$row["usr_id"].")'/></td>";
				$out .= "</tr>";
//			}
			$ilig++;
		}
		$conn->free_result($rs);
	}
	if($ilig >11)
	{
		$out .= "<tfoot><tr>";
		$out .= "<th class='REFL ".$lact." ".$lsort."' id='BREFL'>"._('admin::compte-utilisateur identifiant')."</th>"; 				
		$out .= "<th class='REFN ".$nact." ".$nsort."' id='BREFN'>"._('admin::compte-utilisateur nom').'/'._('admin::compte-utilisateur prenom')."</th>";		
		$out .= "<th class='REFM ".$mact." ".$msort."' id='BREFM'>"._('admin::compte-utilisateur email'). "</th>";
		$out .= "<th class='REFS ".$sact." ".$ssort."' id='BREFS'>"._('admin::compte-utilisateur societe')."</th>";	
		$out .= "<th class='REFJ ".$jact." ".$jsort."' id='BREFJ'>"._('admin::compte-utilisateur poste')."</th>";	
		$out .= "<th class='REFA ".$aact." ".$asort."' id='BREFA'>"._('admin::compte-utilisateur activite')."</th>";				
		$out .= "<th class='REFC ".$cact." ".$csort."' id='BREFC'>"._('admin::compte-utilisateur pays')."</th>";		
		$out .= "<th class='REFT ".$tact." ".$tsort."' id='BREFT'>"._('admin::compte-utilisateur dernier modele applique')."</th>";		
		$out .= "<th></th>";		
		$out .= "</tr></tfoot>";
	}
	$out .= "</tbody>";
		$out .= "</table></div>".
		"";
	
		$out .= "";
	
	return $out;
}



function saveiList($usr,$ses,$lng,$name,$token,$filters)
{
	
	
	require_once(GV_RootPath.'lib/countries.php');
	
	$ret = -1;
	
	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return $ret;
	 	
	$conn = connection::getInstance();
		
	$ilists = new stdClass();
	
	$sql = 'SELECT push_list FROM usr WHERE usr_id="'.$conn->escape_string($usr).'"';
	if($rs = $conn->query($sql))
	{
		if($row = $conn->fetch_assoc($rs))
		{
			if($row['push_list'] != '')
				$ilists = json_decode($row['push_list']);
		}
		$conn->free_result($rs);
	}	
	
	if(($filters = json_decode($filters)) !== false)
	{
		$label = $name;
		$n = 2;
		while(isset($ilists->$label))
		{
			$label = $name.'#'.$n;
			$n++;
		}
		$ilists->$label = $filters;
	
		$sql = 'UPDATE usr SET push_list="'.$conn->escape_string(p4string::jsonencode($ilists)).'" WHERE usr_id="'.$conn->escape_string($usr).'"';
		if($conn->query($sql))
		{
			$ret = loadILists($usr,$ses,$lng,$label);
		}
	}
		
	return $ret;
}

function loadILists($usr,$ses,$lng,$name='')
{

	
	
	
	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return;
	
	$conn = connection::getInstance();
				
	$lists = array();
	
	$html = '<option value="" >'._('choisir').'</option>';
	$sql = 'SELECT push_list FROM usr WHERE usr_id = "'.$conn->escape_string($usr).'"';
	
	
	
	if($rs = $conn->query($sql))
	{
		if($row = $conn->fetch_assoc($rs))
		{
			if($ilists = json_decode($row['push_list']))
			{
				foreach($ilists as $k=>$v)
				{
					$sel = "";
					if($k == $name)
						$sel = 'selected="selected"';
					$html .= "<option ".$sel." value='$k'>".$k."</option>";
				}
			}
		}
		$conn->free_result($rs);
	}

	return $html;

}

function loadIList($name)
{
	$session = session::getInstance();
	$ses = $session->ses_id;
	$usr = $session->usr_id;
	
	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return;
	
	$conn = connection::getInstance();
				
	
	$sql = 'SELECT push_list FROM usr WHERE usr_id = "'.$conn->escape_string($usr).'"';
	
	if($rs = $conn->query($sql))
	{
		if($row = $conn->fetch_assoc($rs))
		{
			if($ilists = json_decode($row['push_list']))
			{
				if(isset($ilists->$name))
					$ret = $ilists->$name;
				else
					$ret = array(
						'strings'	=> array()
						,'countries'=> array()
						,'fonction'	=> array()
						,'activite'	=> array()
						,'lists'	=> array()
						,'societe'	=> array()
						,'template'	=> array()
					);
			}
		}
		$conn->free_result($rs);
	}

	return p4string::jsonencode($ret);

}

function saveList($usr,$ses,$lng,$name,$token)
{
	$session = session::getInstance();
	
	require_once(GV_RootPath.'lib/countries.php');
	
	$ret = '-1'.'ses';
	
	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return $ret;
	 	
	$conn = connection::getInstance();
		
	$label = $name;
	$sql = 'SELECT label FROM usrlist WHERE usr_id="'.$conn->escape_string($usr).'" AND label = "'.$conn->escape_string($label).'"';
	if($rs = $conn->query($sql))
	{
		$n =2;
		while($conn->num_rows($rs)>0)
		{
			$label = $name.'#'.$n;	
			$sql = 'SELECT label FROM usrlist WHERE usr_id="'.$conn->escape_string($usr).'" AND label = "'.$conn->escape_string($label).'"';
			$rs = $conn->query($sql);
			$n++;
		}
	}	
	
	$ret = '-1';
	
	if(isset($session->prod['push'][$token]) && count($session->prod['push'][$token]['usrs'])>0)
	{
		$sql = 'INSERT into usrlist (list_id, usr_id, label) VALUES (null, "'.$conn->escape_string($usr).'", "'.$conn->escape_string($label).'")';

		if($conn->query($sql))
		{
			$sql = 'SELECT LAST_INSERT_ID() as list_id FROM usrlist';
			if($rs = $conn->query($sql))
			{
				if($row = $conn->fetch_assoc($rs))
				{
					$list_id = $row['list_id'];
					foreach($session->prod['push'][$token]['usrs'] as $usr_id=>$cool)
					{
						
						$sql = 'INSERT INTO usrlistusers (list_id, usr_id) VALUES ("'.$conn->escape_string($list_id).'","'.$conn->escape_string($usr_id).'")';

						$conn->query($sql);
					}
					$ret = loadLists($usr,$ses,$lng);
				}
				
			}
		}
	}
	
	return $ret;
}
function loadLists($usr,$ses,$lng,$name='')
{
	
	
	require_once(GV_RootPath.'lib/countries.php');
	
	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return;
	
	$conn = connection::getInstance();
				
	$lists = array();
	
	$html = '<option value="" >Toutes</option>';
	$sql = 'SELECT l.label, l.list_id, COUNT(u.usr_id) as nusr FROM (usr s, usrlist l) LEFT JOIN  usrlistusers u ON (l.list_id = u.list_id AND u.usr_id = s.usr_id) WHERE l.usr_id = "'.$conn->escape_string($usr).'" AND s.usr_login NOT LIKE "(#deleted_%" GROUP BY l.label ORDER BY l.label ASC';
	
	
	
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$sel = "";
			if($name != '' && $row['label'] == $name)
				$sel = "selected='selected'";
				
			$html .= "<option ".$sel." value='".$row['list_id']."'>".$row['label']." (".$row['nusr']." users)</option>";
		}
	}

	return $html;
}

function deleteList($usr,$ses,$lists,$lng)
{
	
	require_once(GV_RootPath.'lib/countries.php');
	
	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return;
	
	$conn = connection::getInstance();
	$lists = json_decode($lists);
	foreach($lists as $list)
	{
		$sql = "DELETE FROM usrlist WHERE list_id='".$conn->escape_string($list)."' AND usr_id='".$conn->escape_string($usr)."'";
		if($conn->query($sql))
		{
			$sql = 'DELETE FROM usrlistusers WHERE list_id="'.$conn->escape_string($list).'"';
			$conn->query($sql);
		}
	}
	
	return loadLists($usr,$ses, $lng);

	return $html;
}

function deleteiList($usr,$ses,$name,$lng)
{
	
	
	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return;
		
	$conn = connection::getInstance();

	$sql = sprintf("SELECT push_list FROM usr WHERE usr_id = '%d'", $conn->escape_string($usr));

	if($rs = $conn->query($sql))
	{
		if($row = $conn->fetch_assoc($rs))
		{
			$lists = json_decode($row['push_list']);
			if(isset($lists->$name))
			{
				unset($lists->$name);
			}
				
			$sql = 'UPDATE usr SET push_list="'.$conn->escape_string(p4string::jsonencode($lists)).'" WHERE usr_id="'.$conn->escape_string($usr).'"';
			$conn->query($sql);
		}
		$conn->free_result($rs);
	}
	
	$ret = loadiLists($usr,$ses,$lng);
		
	return $ret;
}


function getUsrInfos($usr,$ses,$arrayUsrs)
{
	
	
	
	if(!($ph_session = phrasea_open_session($ses,$usr)))
		return;

	$conn = connection::getInstance();
	
	$usrs = array();
	
	$sql = 'SELECT usr_id,usr_mail, usr_login, usr_password, usr_nom, usr_prenom FROM usr WHERE usr_id IN ('.implode(',',$arrayUsrs).')';
	
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
			$usrs[$row['usr_id']] = $row ;
		$conn->free_result($rs);
	}
	return $usrs;
}

function pushIt($usr,$ses,$newBask,$parmLST,$users,$mail_content,$lng,$accuse)
{
	
	$session = session::getInstance();
	$finalUsers = array();
	
	$conn = connection::getInstance();

	$nbMail = 0;
	$nbchu = 0;
	$my_link="";

	$usrs = getUsrInfos($usr,$ses,array_merge(array_keys($users),array($usr)));

	$me = user::getInstance($session->usr_id);
		
	$reading_confirm_to = false;
	if($accuse == '1')
	{
		$reading_confirm_to = $me->email;
	}
	
	foreach($users as $oneuser=>$rights)
	{
		$new_basket = null;
		
		try {
			$user = user::getInstance($oneuser);
			
			if($new_basket = new basket())
			{
				$new_basket->name = $newBask;
				$new_basket->pusher = $usr;
				$new_basket->usr_id = $user->id;
				$new_basket->save();
	
				$nbchu++;
				
				$new_basket->push_list($parmLST, false);
				
				$finalUsers[] = $user->id;
				
				$canSendHD = sendHdOk($usr,$ses,$parmLST);
				if($canSendHD && $rights['canHD'])
				{
					$canSendHD = implode(',',$canSendHD);
					$sql = 'UPDATE sselcont SET canHD="1" WHERE ssel_id="'.$new_basket->ssel_id.'" AND base_id IN ('.$canSendHD.')';
					$conn->query($sql);
				}	
				
				set_time_limit(60);
				
				$from = trim($me->email) != "" ? $me->email : false;
				
				
				$url = GV_ServerName.'lightbox/index.php?LOG='.random::getUrlToken('view',$user->id,false,$new_basket->ssel_id);
				
				if($me->id == $user->id)
					$my_link = $url;
				
				$name = user::getInfos($user->id);
			
				$params = array(
					'from'			=> $session->usr_id
					,'from_email' 	=> $from
					,'to'			=> $user->id
					,'to_email'		=> $user->email
					,'to_name'		=> $name
					,'url'			=> $url
					,'accuse'		=> $reading_confirm_to
					,'message'		=> $mail_content
					,'ssel_id'		=> $new_basket->ssel_id
				);
				
				
				$evt_mngr = eventsmanager::getInstance();
				$evt_mngr->trigger('__PUSH_DATAS__', $params);
				
			}	
		}
		catch(Exception $e)
		{
		
		}
	}
	return array('nbchu'=>$nbchu,'mylink'=>$my_link, 'users'=>$finalUsers);
	
}

function pushValidation($usr,$ses,$ssel_id,$listUsrs,$time,$mail_content, $accuse)
{
	$session = session::getInstance();
	$finalUsers = array();
	
	$my_link = '';
	
	$me = user::getInstance($session->usr_id);
		
	$reading_confirm_to = false;
	if($accuse == '1')
	{
		$reading_confirm_to = $me->email;
	}
	
	if($time != 0)
	{
		$expires_obj = new DateTime('+'.(int)$time.' day'. ((int)$time>1 ? 's':''));
		$expires = phraseadate::format_mysql($expires_obj);
		
		if($time > 1)
			$mail_content .= '<br/><br/><div>'.sprintf(_('Vous avez %d jours pour confirmer votre validation'),$time).'<div><br/><br/>';
		else
			$mail_content .= '<br/><br/><div>'._('Vous avez une journee pour confirmer votre validation').'<div><br/><br/>';
	}
	else
	{
		$expires = null;
	}
		
	
	
	$basket = basket::getInstance($ssel_id);
	
	foreach($listUsrs as $oneuser=>$rights)
	{
		$user = user::getInstance($oneuser);
		
		if(!$user->id)
			continue;
			
		$from = trim($me->email) != "" ? $me->email : false;
		
		$message  = $mail_content."<br/>\n<br/>\n";				
		
		$url = GV_ServerName.'lightbox/index.php?LOG='.random::getUrlToken('validate',$user->id,$expires, $ssel_id);
		
		$name = user::getInfos($user->id);
			
		$params = array(
			'from'			=> $session->usr_id
			,'from_email' 	=> $from
			,'to'			=> $user->id
			,'to_email'		=> $user->email
			,'to_name'		=> $name
			,'message'		=> $mail_content
			,'url'			=> $url
			,'ssel_id'		=> $ssel_id
			,'accuse'		=> $reading_confirm_to
		);
		
		$evt_mngr = eventsmanager::getInstance();
		$evt_mngr->trigger('__PUSH_VALIDATION__', $params);
			
		if($me->id == $user->id)
			$my_link = $url;
		 
		if($time != 0)
			$message .= '<br/>\n<br/>\n'.sprintf(_('push:: %d jours restent pour finir cette validation'),(int)$time)."<br/>\n";
				
		$basket->validation_to_users($expires, $oneuser, $rights['canAgree'], $rights['canSeeOther'], $rights['canHD']);
		$finalUsers[] = $oneuser;
	}
	
	return array('mylink'=>$my_link, 'users'=>$finalUsers);
}

?>