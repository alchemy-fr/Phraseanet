<?php

function giveMeBases($usr=null)
{
	
	
	$conn = connection::getInstance();
	if(!$conn)
	{
		return null;
	}
	
	$inscriptions = null;
	
	$usrerRegis = null;
	
	if($usr != null)
	{
		
		$sqlU = 'SELECT sbas.dbname, time_limited, UNIX_TIMESTAMP( limited_from ) AS limited_from,' .
				' UNIX_TIMESTAMP( limited_to ) AS limited_to, bas.server_coll_id, usr.usr_id, basusr.actif, demand.en_cours, demand.refuser' .
				' FROM (usr, bas, sbas)' .
				' LEFT JOIN basusr ON ( usr.usr_id = basusr.usr_id' .
				' AND bas.base_id = basusr.base_id )' .
				' LEFT JOIN demand ON ( demand.usr_id = usr.usr_id' .
				' AND bas.base_id = demand.base_id )' .
				' WHERE bas.active >0 AND bas.sbas_id = sbas.sbas_id' .
				' AND usr.usr_id ="'.$conn->escape_string($usr).'"' .
				' AND model_of =0';

		$rsU = $conn->query($sqlU);
	
		if($conn->num_rows($rsU) == 0)
			return null;
	
		while($rowU = $conn->fetch_assoc($rsU))
		{
			if(!isset($usrerRegis[$rowU['dbname']]))
				$usrerRegis[$rowU['dbname']] = null;

			if(!is_null($rowU['actif']) || !is_null($rowU['en_cours']))
			{
				
				$usrerRegis[$rowU['dbname']][$rowU['server_coll_id']] = true;
				if($rowU['actif'] == '0')
					$usrerRegis[$rowU['dbname']][$rowU['server_coll_id']] = 'NONACTIF';
				elseif($rowU['time_limited'] == '1' && !($rowU['limited_from']>=time() && $rowU['limited_to']<=time()))
					$usrerRegis[$rowU['dbname']][$rowU['server_coll_id']] = 'OUTTIME';
				elseif($rowU['time_limited'] == '1' && ($rowU['limited_from']>time() && $rowU['limited_to']<time()))
					$usrerRegis[$rowU['dbname']][$rowU['server_coll_id']] = 'INTIME';
				elseif($rowU['en_cours'] == '1')
					$usrerRegis[$rowU['dbname']][$rowU['server_coll_id']] = 'WAIT';
				elseif($rowU['refuser'] == '1')
					$usrerRegis[$rowU['dbname']][$rowU['server_coll_id']] = 'REFUSE';
			}
		}
	
			
	}

	$lb = phrasea::bases();
	
	if(is_array($lb))
		foreach($lb["bases"] as $nbbas=>$oneBase)
		{
			$collname = $basname = null ; 
			$inscriptions[$oneBase['sbas_id']] = array();
			$inscriptions[$oneBase['sbas_id']]['CGU']=false;
			$inscriptions[$oneBase['sbas_id']]['CGUrelease']=false;
			$inscriptions[$oneBase['sbas_id']]['inscript']=false;
			$inscriptions[$oneBase['sbas_id']]['CollsCGU']=null;
			$inscriptions[$oneBase['sbas_id']]['Colls']=null;
			$inscriptions[$oneBase['sbas_id']]['CollsRegistered']=null;
			$inscriptions[$oneBase['sbas_id']]['CollsWait']=null;
			$inscriptions[$oneBase['sbas_id']]['CollsRefuse']=null;
			$inscriptions[$oneBase['sbas_id']]['CollsIntime']=null;
			$inscriptions[$oneBase['sbas_id']]['CollsOuttime']=null;
			$inscriptions[$oneBase['sbas_id']]['CollsNonactif']=null;
			
			foreach ($oneBase["collections"] as $key=>$row) 
			{
				$collname[$key] = $row['name'];
				$basname[$key]  = $row['coll_id'];
			}
			if( is_array($collname) )
			{
				array_multisort($collname, SORT_ASC, $basname, SORT_ASC, $oneBase["collections"]);						
				$lb["bases"][$nbbas]["collections"] = $oneBase["collections"];	
		 	}
			$sbpcgu = '';
			# chaque base a son  ["xmlstruct"]
			if( isset($oneBase["xmlstruct"]) )
			{
				if( $xml = simplexml_load_string( $oneBase["xmlstruct"] ) ){
					foreach ($xml->xpath('/record/caninscript') as $caninscript ) 
					{
					   if($inscriptions[$oneBase['sbas_id']]['inscript'] === false)
					   	 $inscriptions[$oneBase['sbas_id']]['inscript'] = ( (string)$caninscript =="1" ? true : false ) ;
					}	
					foreach($xml->xpath('/record/cgu') as $sbpcgu )
					{
						foreach($sbpcgu->attributes() as $a=>$b){
							if($a == "release")
								$inscriptions[$oneBase['sbas_id']]['CGUrelease'] = (string)$b;
						}
						$inscriptions[$oneBase['sbas_id']]['CGU'] = (string)$sbpcgu->saveXML();
					}
				}
			}
			$baseInscript = $inscriptions[$oneBase['sbas_id']]['inscript'];
			foreach( $oneBase["collections"] as $tmp=>$oneColl)
			{
				$cguColl = false;
	
				$collInscript = $baseInscript;
				$defined = false;
				$cguSpec = false;
				if( isset($oneColl["prefs"]) )
				{
					if($xml = simplexml_load_string($oneColl["prefs"]) )
					{
						$defined = true;
						foreach ($xml->xpath('/baseprefs/caninscript') as $caninscript ) 
						{
							$tmp = (string)$caninscript;
							if($tmp==="1")
								$collInscript = true ;
							elseif($tmp==="0")
								$collInscript = false ;
						}
						if($collInscript)
						{
							$cguCollRelease = false;
							
							if($inscriptions[$oneBase['sbas_id']]['inscript'] === false)
								$inscriptions[$oneBase['sbas_id']]['inscript'] = ($collInscript ===true ? true : false ) ;
							
							foreach($xml->xpath('/baseprefs/cgu') as $bpcgu )
							{
								foreach($bpcgu->attributes() as $a=>$b){
									if($a == "release")
										$cguCollRelease = (string)$b;
								}
								$cguColl = (string)$bpcgu->saveXML();
							}
							if($cguColl)
							{
								$cguSpec = true;
							}
							else
							{
								if(!isset($usrerRegis[$oneBase['dbname']][$oneColl['coll_id']]))
									$inscriptions[$oneBase['sbas_id']]['Colls'][$oneColl['coll_id']] = $oneColl['name'];
							}
						}
					}
				}
				$lacgu = $cguColl?$cguColl:(string)$sbpcgu;
				
				if(isset($usrerRegis[$oneBase['dbname']]) && isset($usrerRegis[$oneBase['dbname']][$oneColl['coll_id']]))
				{
					if($usrerRegis[$oneBase['dbname']][$oneColl['coll_id']] === "WAIT")
							$inscriptions[$oneBase['sbas_id']]['CollsWait'][$oneColl['coll_id']] = $lacgu;
					elseif($usrerRegis[$oneBase['dbname']][$oneColl['coll_id']] === "REFUSE")
						$inscriptions[$oneBase['sbas_id']]['CollsRefuse'][$oneColl['coll_id']] = $lacgu;
					elseif($usrerRegis[$oneBase['dbname']][$oneColl['coll_id']] === "INTIME")
						$inscriptions[$oneBase['sbas_id']]['CollsIntime'][$oneColl['coll_id']] = $lacgu;
					elseif($usrerRegis[$oneBase['dbname']][$oneColl['coll_id']] === "OUTTIME")
						$inscriptions[$oneBase['sbas_id']]['CollsOuttime'][$oneColl['coll_id']] = $lacgu;
					elseif($usrerRegis[$oneBase['dbname']][$oneColl['coll_id']] === "NONACTIF")
						$inscriptions[$oneBase['sbas_id']]['CollsNonactif'][$oneColl['coll_id']] = $lacgu;
					elseif($usrerRegis[$oneBase['dbname']][$oneColl['coll_id']] === true)
						$inscriptions[$oneBase['sbas_id']]['CollsRegistered'][$oneColl['coll_id']] = $lacgu;
				}
				elseif(!$cguSpec && $collInscript)//ne va pas.. si l'inscriptio na la coll est explicitement non autorise, je refuse'
				{
						$inscriptions[$oneBase['sbas_id']]['Colls'][$oneColl['coll_id']] = $oneColl['name'];
				}
				elseif($cguSpec)
				{
						$inscriptions[$oneBase['sbas_id']]['CollsCGU'][$oneColl['coll_id']]['name'] = $oneColl['name'];
						$inscriptions[$oneBase['sbas_id']]['CollsCGU'][$oneColl['coll_id']]['CGU'] = $cguColl;
						$inscriptions[$oneBase['sbas_id']]['CollsCGU'][$oneColl['coll_id']]['CGUrelease'] = $cguCollRelease;
				}
			}
		}
		
	return $inscriptions;
}

function giveMeBaseUsr($usr,$lng)
{

	$noDemand = true;
	$sbas2name = null;
	$coll2bas = null;
	$coll2basname = null;
	$lb = phrasea::bases();
	foreach($lb["bases"] as $nbbas=>$oneBase)
	{	
		$tmpMail = null ;
		$coll2bas[$oneBase['sbas_id']] = null;
		
		$sbas2name[$oneBase['sbas_id']] = $oneBase['viewname'];
		$coll2bas[$oneBase['sbas_id']] = null;
		$coll2basname[$oneBase['sbas_id']] = null;
		foreach($oneBase["collections"] as $oneColl){
			$coll2bas[$oneBase['sbas_id']][$oneColl["coll_id"]] = $oneColl["base_id"];
			$coll2basname[$oneBase['sbas_id']][$oneColl["coll_id"]] = $oneColl["name"];
		}
	}	

	$out = '<table border="0" style="table-layout:fixed;font-size:11px;" cellspacing=0 width="375">' .
			'<tr>' .
			'<td  style="width:180px; text-align:right">&nbsp;</td>' .
			'<td  width="15px" style="width:15px">&nbsp;</td>' .
			'<td  style="width:180px;">&nbsp;</td>' .
			'</tr>';	
		
	$inscriptions = giveMeBases($usr);
	foreach($inscriptions as $sbasId=>$baseInsc)
	{
			//je pr�sente la base
		if(($baseInsc['CollsRegistered'] || $baseInsc['CollsRefuse'] || $baseInsc['CollsWait'] || $baseInsc['CollsIntime'] || $baseInsc['CollsOuttime'] || $baseInsc['CollsNonactif'] || $baseInsc['CollsCGU'] || $baseInsc['Colls']) )//&& $baseInsc['inscript'])
		$out .= '<tr><td colspan="3" style="text-align:center;"><h3>'.$sbas2name[$sbasId].'</h3></td></tr>';
		
		if($baseInsc['CollsRegistered'])
		{
			foreach($baseInsc['CollsRegistered'] as $collId=>$isTrue){
				$out .= '<tr><td colspan="3" style="text-align:center;">'._('login::register: acces authorise sur la collection ').$coll2basname[$sbasId][$collId];
				if(trim($isTrue) != '')
					$out .= ' <a class="inscriptlink" href="/include/cguUtils.php?action=PRINT&bas='.$sbasId.'&col='.$collId.'">'._('login::register::CGU: lire les CGU').'</a>';
				$out .= '</td></tr>';
			}
			$out .= '<tr style="height:5px;"><td></td></tr>';
		}
		if($baseInsc['CollsRefuse'])
		{
			foreach($baseInsc['CollsRefuse'] as $collId=>$isTrue){
				$out .= '<tr><td colspan="3" style="text-align:center;"><span style="color:red;">'._('login::register: acces refuse sur la collection ').$coll2basname[$sbasId][$collId].'</span>';
				if(trim($isTrue) != '')
					$out .= ' <a class="inscriptlink" href="/include/cguUtils.php?action=PRINT&bas='.$sbasId.'&col='.$collId.'">'._('login::register::CGU: lire les CGU').'</a>';
				$out .= '</td></tr>';
			}
			$out .= '<tr style="height:5px;"><td></td></tr>';
		}
		if($baseInsc['CollsWait'])
		{
			foreach($baseInsc['CollsWait'] as $collId=>$isTrue){
				$out .= '<tr><td colspan="3" style="text-align:center;"><span style="color:orange;">'._('login::register: en attente d\'acces sur').$coll2basname[$sbasId][$collId].'</span>';
				if(trim($isTrue) != '')
					$out .= ' <a class="inscriptlink" href="/include/cguUtils.php?action=PRINT&bas='.$sbasId.'&col='.$collId.'">'._('login::register::CGU: lire les CGU').'</a>';
				$out .= '</td></tr>';
			}
			$out .= '<tr style="height:5px;"><td></td></tr>';
		}
		if($baseInsc['CollsIntime'])
		{
			foreach($baseInsc['CollsIntime'] as $collId=>$isTrue){
				$out .= '<tr><td colspan="3" style="text-align:center;">'._('login::register: acces temporaire sur').$coll2basname[$sbasId][$collId].'</span>';
				if(trim($isTrue) != '')
					$out .= ' <a class="inscriptlink" href="/include/cguUtils.php?action=PRINT&bas='.$sbasId.'&col='.$collId.'">'._('login::register::CGU: lire les CGU').'</a>';
				$out .= '</td></tr>';
			}
			$out .= '<tr style="height:5px;"><td></td></tr>';
		}
		if($baseInsc['CollsOuttime'])
		{
			foreach($baseInsc['CollsOuttime'] as $collId=>$isTrue){
				$out .= '<tr><td colspan="3" style="text-align:center;"><span style="color:red;">'._('login::register: acces temporaire termine sur ').$coll2basname[$sbasId][$collId].'</span>';
				if(trim($isTrue) != '')
					$out .= ' <a class="inscriptlink" href="/include/cguUtils.php?action=PRINT&bas='.$sbasId.'&col='.$collId.'">'._('login::register::CGU: lire les CGU').'</a>';
				$out .= '</td></tr>';
			}
			$out .= '<tr style="height:5px;"><td></td></tr>';
		}
		if($baseInsc['CollsNonactif'])
		{
			foreach($baseInsc['CollsNonactif'] as $collId=>$isTrue){
				$out .= '<tr><td colspan="3" style="text-align:center;"><span style="color:red;">'._('login::register: acces supendu sur').$coll2basname[$sbasId][$collId].'</span>';
				if(trim($isTrue) != '')
					$out .= ' <a class="inscriptlink" href="/include/cguUtils.php?action=PRINT&bas='.$sbasId.'&col='.$collId.'">'._('login::register::CGU: lire les CGU').'</a>';
				$out .= '</td></tr>';
			}
			$out .= '<tr style="height:5px;"><td></td></tr>';
		}
		
		$out .= '<tr style="height:5px;"><td></td></tr>';
		if(($baseInsc['CollsCGU'] || $baseInsc['Colls']) && $baseInsc['inscript'])// il y a des coll ou s'inscrire !
		{
			$noDemand = false;
			
			if($baseInsc['Colls'])//des coll ou on peut s'inscrire sans cgu specifiques
			{
				//je check si ya des cgu pour la base
				if($baseInsc['CGU'])
				{
					$out .= '<tr><td colspan="3" style="text-align:center;">'._('login::register: L\'acces aux bases ci-dessous implique l\'acceptation des Conditions Generales d\'Utilisation (CGU) suivantes').'</td></tr>';
					$out .= '<tr><td colspan="3" style="text-align:center;"><div style="width:90%;height:120px;text-align:left;overflow:auto;">'.$baseInsc['CGU'].'</div></td></tr>';
					
				}
				foreach($baseInsc['Colls'] as $collId=>$collName)
				{
					$out .= '<tr>' .
							'<td style="text-align:right;">'.$collName.'</td>' .
							'<td></td>' .
							'<td class="TD_R" style="width:200px;">' .
							'<input style="width:15px;" class="checkbox" type="checkbox" name="demand[]" value="'.$coll2bas[$sbasId][$collId].'" >' .
							'<span>'._('login::register: Faire une demande d\'acces').'</span>' .
							'</td>' .
							'</tr>';
				}
			}
			if($baseInsc['CollsCGU'])
			{
				foreach($baseInsc['CollsCGU'] as $collId=>$collDesc)
				{
					
					$out .= '<tr><td colspan="3" style="text-align:center;"><hr style="width:80%"/></td></tr>' .
							'<tr><td colspan="3" style="text-align:center;">'._('login::register: L\'acces aux bases ci-dessous implique l\'acceptation des Conditions Generales d\'Utilisation (CGU) suivantes').'</td></tr>' .
							'<tr>' .
							'<td colspan="3" style="text-align:center;">' .
							'<div style="width:90%;height:120px;text-align:left;overflow:auto;">'.$collDesc['CGU'].'</div>' .
							'</td>' .
							'</tr>' .
							'<tr >' .
							'<td style="text-align:right;">'.$collDesc['name'].'</td>' .
							'<td></td>' .
							'<td class="TD_R" style="width:200px;">' .
							'<input style="width:15px;" class="checkbox" type="checkbox" name="demand[]" value="'.$coll2bas[$sbasId][$collId].'" >' .
							'<span>'._('login::register: Faire une demande d\'acces').'</span>' .
							'</td>' .
							'</tr>';
				}
			}
			
		}
	}	

	$out .= '</table>';
	
	return array('tab'=>$out,'demandes'=>$noDemand);
}

function giveModInscript($usr,$lng)
{
	$session = session::getInstance();
	
	$out = '<html lang="'.$session->usr_i18n.'">' .
			'<head>' .
			'</head>' .
			'<body>' .
			'<div style="width:600px;height:20px;text-align:center;margin:0">'._('admin::compte-utilisateur actuellement, acces aux bases suivantes : ').' :</div>' .
			'<form id="conf_mod" target="_self" action="mod_inscript.php" method="post">' .
			'<div style="width:400px;center;margin:0 100px;">';
 
	$demandes = giveMeBaseUsr($usr,$lng);
	
	$out .=  $demandes['tab'];
	
	$noDemand = $demandes['demandes'];
	$out .= '</div>' .
			'<input type="hidden" value="'.$lng.'" name="lng">' .
			'<input type="hidden" value="SEND" name="act">' .
			'<input type="hidden" value="'.$usr.'" name="usrid">' .
			'</form>';

	if($noDemand)
	{
		$out .= '<div style="margin:10px 0;width:600px;text-align:center;">'._('login::register: Vous avez acces a toutes les collections de toutes les bases').'</div>';
	}
	else
	{
		$out .= '<div style="margin:10px 0;width:600px;text-align:center;"><input type="button" value="'._('login::register: confirmer la demande').'" onclick="document.getElementById(\'conf_mod\').submit();" /></a></div>';
	}
	
	$out .= '</div>' .
			'</body>' .
			'</html>';
	return $out;
}

function giveInscript($lng,$demandes=null)
{
	$lb = phrasea::bases();
	$sbas2name = null;
	$coll2bas = null;
	foreach($lb["bases"] as $nbbas=>$oneBase)
	{	
		$sbas2name[$oneBase['sbas_id']] = $oneBase['viewname'];
		$coll2bas[$oneBase['sbas_id']] = null;
		
		foreach($oneBase["collections"] as $oneColl )
			$coll2bas[$oneBase['sbas_id']][$oneColl["coll_id"]] = $oneColl["base_id"];
	}
	
	$out = '<table  border="0" style="table-layout:fixed" cellspacing=0 width="590">' .
			'<tr>' .
			'<td  style="width:240px; text-align:right">&nbsp;</td>' .
			'<td  width="25px" style="width:25px">&nbsp;</td>' .
			'<td  style="width:325px;">&nbsp;</td>' .
			'</tr>';	
	
	$inscriptions = giveMeBases();
	
	foreach($inscriptions as $sbasId=>$baseInsc)
	{

		if(($baseInsc['CollsCGU'] || $baseInsc['Colls']) && $baseInsc['inscript'])// il y a des coll ou s'inscrire !
		{
			//je pr�sente la base
			$out .= '<tr><td colspan="3" style="text-align:center;"><h3 style="margin: 15px 0pt 2px;" class="inscriptbase">'.$sbas2name[$sbasId].'</h3></td></tr>';
	
			if($baseInsc['Colls'])//des coll ou on peut s'inscrire sans cgu specifiques
			{
				//je check si ya des cgu pour la base
				if($baseInsc['CGU'])
				{
					$out .= '<tr><td colspan="3" style="text-align:center;">'._('login::register: L\'acces aux bases ci-dessous implique l\'acceptation des Conditions Generales d\'Utilisation (CGU) suivantes').'<br/><a class="inscriptlink" href="/include/cguUtils.php?action=PRINT&bas='.$sbasId.'">'._('login::register::CGU: ouvrir dans une nouvelle fenetre').'</a></td></tr>';
					//$out .= '<tr><td colspan="3" style="text-align:center;"><div id="CGUTXT'.$sbasId.'" style="width:90%;height:120px;text-align:left;overflow:auto;">'.(string)$baseInsc['CGU'].'</div></td></tr>';
				}
				foreach($baseInsc['Colls'] as $collId=>$collName)
				{

					$baseId = $coll2bas[$sbasId][$collId];
						$ch="checked";
					if((!is_null($demandes) &&  !isset( $demandes[$baseId] ) ))  
						$ch="";
					$out .= '<tr>' .
							'<td style="text-align:right;">'.$collName.'</td>' .
							'<td></td>' .
							'<td class="TD_R" style="width:200px;">' .
							'<input style="width:15px;" class="checkbox" type="checkbox" '.$ch.' name="demand[]" value="'.$baseId.'" >' .
							'<span>'._('login::register: Faire une demande d\'acces').'</span>' .
							'</td>' .
							'</tr>';

				}
			}
			if($baseInsc['CollsCGU'])
			{
				foreach($baseInsc['CollsCGU'] as $collId=>$collDesc)
				{
					
					$baseId = $coll2bas[$sbasId][$collId];
					
						$ch="checked";
					if(!is_null($demandes) &&  !isset($demandes[$baseId] ) )  
						$ch="";
					$out .= '<tr><td colspan="3" style="text-align:center;"><hr style="width:80%"/></td></tr>' .
							'<tr><td colspan="3" style="text-align:center;">'._('login::register: L\'acces aux bases ci-dessous implique l\'acceptation des Conditions Generales d\'Utilisation (CGU) suivantes').
							'<br/><a class="inscriptlink" href="/include/cguUtils.php?action=PRINT&bas='.$sbasId.'&col='.$collId.'">'._('login::register::CGU: ouvrir dans une nouvelle fenetre').'</a></td></tr>' .
						//	'<tr >' .
						//	'<td colspan="3" style="text-align:center;"><div style="height:120px;text-align:left;overflow:auto;">' .
						//	''.(string)$collDesc['CGU'].'' .
						//	'</div></td>' .
						//	'</tr>' .
							'<tr >' .
							'<td style="text-align:right;">'.$collDesc['name'].'</td>' .
							'<td></td>' .
							'<td class="TD_R" style="width:200px;">' .
							'<input style="width:15px;" class="checkbox" type="checkbox" '.$ch.' name="demand[]" value="'.$baseId.'" >' .
							'<span>'._('login::register: Faire une demande d\'acces').'</span>' .
							'</td>' .
							'</tr>';

				}
			}
		}
	}			
	$out .= '</table>'; 
	return $out;
}
?>