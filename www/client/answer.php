<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
require_once(GV_RootPath."lib/clientUtils.php");
require_once( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
$session = session::getInstance();


$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
}
else{
	header("Location: /login/client/");
	exit();
}


if(!in_array(mb_strtolower(GV_client_showTitle), array("top", "bottom", "none")))
	define("GV_client_showTitle","top");

if(!isset($parm))
{

	$request = httpRequest::getInstance();
	$parm = $request->get_parms("mod", "bas"
						, "pag"
						, "qry", "search_type"
						, "qryAdv", 'opAdv', 'status', 'datemin', 'datemax', 'dateminfield', 'datemaxfield', 'infield'
						, "nba" 
						, "regroup" // si rech par doc, regroup ,ou pizza
						, "ord" 
						);
}
$qry = '';

if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	die();

if(trim($parm['qry']) != '')
{
	$qry .= trim($parm['qry']);
}
if(count($parm['opAdv'])>0 && count($parm['opAdv']) == count($parm['qryAdv']))
{
	foreach($parm['opAdv'] as $opId=>$op)
	{
		if(trim($parm['qryAdv'][$opId])!= '')
		{
			if($qry == trim($parm['qry']))
				$qry = '('.trim($parm['qry']).')';
			$qry .= ' '.$op.' ('.trim($parm['qryAdv'][$opId]).')';
		}		
	}
}
if($qry == '')
	$qry = GV_defaultQuery;

$parm['qry'] = $qry;

$qrySbas = array();
if(is_null($parm['bas']))
{
	exit('vous devez selectionner des collections dans lesquelles chercher');
}


foreach($ph_session['bases'] as $base)
{
	foreach($base['collections'] as $coll)
	{
		if(in_array($coll['base_id'],$parm['bas']))
			$qrySbas[$base['sbas_id']] = $qry;
	}
}

/********************* STATUS***************/
$searchStatus = array();
if($parm['status'])
{
	foreach($parm['status'] as $stat)
	{
			$stat = explode('_',$stat);
			$sbas = $stat[0];
			$stat = $stat[1];
			if(isset($searchStatus[$sbas][substr($stat,2)]))
				unset($searchStatus[$sbas][substr($stat,2)]);
			else
				$searchStatus[$sbas][substr($stat,2)] = substr($stat,0,2);
	}
}

$requestStat = array();
foreach($searchStatus as $sbas=>$searchStat)
{
	if(isset($qrySbas[$sbas]))
	{
		for($i=4;($i<=64 && count($searchStat)>0);$i++)
		{
			if(!isset($requestStat[$sbas]))
				$requestStat[$sbas] = 'xxxx';
			if(isset($searchStat[$i]))
			{
				if($searchStat[$i] == 'on')
					$requestStat[$sbas] = '1'.$requestStat[$sbas];
				else
					$requestStat[$sbas] = '0'.$requestStat[$sbas];
					
				unset($searchStat[$i]);
			}
			else
				$requestStat[$sbas] = 'x'.$requestStat[$sbas];
		}
	}
}
foreach($requestStat as $sbas=>$status)
{
	if($status != 'xxxx' && isset($qrySbas[$sbas]))
	{
		if($qrySbas[$sbas] == trim($parm['qry']))
			$qrySbas[$sbas] = '('.trim($parm['qry']).')';
		$qrySbas[$sbas] .= ' and (recordstatus='.$status.')';
	}
}
/********************* CHAMP***************/
$searchField = array();
if($parm['infield'])
{
	foreach($parm['infield'] as $field)
	{
			if($field == "")
				continue;
			$field = explode('_',$field);
			$sbas = $field[0];
			$field = $field[1];
			
			$searchField[$sbas] = $field;
			if(isset($qrySbas[$sbas]))
			{
				if($qrySbas[$sbas] == trim($parm['qry']))
					$qrySbas[$sbas] = '('.trim($parm['qry']).')';
				$qrySbas[$sbas] .= ' dans '.$field;
			}
	}
}


/********************* DATE***************/
if(count($parm['datemin'])>0)
{
	foreach($parm['dateminfield'] as $opId=>$op)
	{
		$op = explode('_',$op);
		if(trim($parm['datemin'][$opId])!= '' && isset($qrySbas[$op[0]]))
		{
			if($qrySbas[$op[0]] == trim($parm['qry']))
				$qrySbas[$op[0]] = '('.trim($parm['qry']).')';
			$qrySbas[$op[0]] .= ' AND ('.$op[1].'>='.trim($parm['datemin'][$opId]).')';
		}		
	}
}
if(count($parm['datemax'])>0)
{
	foreach($parm['datemaxfield'] as $opId=>$op)
	{
		$op = explode('_',$op);
		if(trim($parm['datemax'][$opId])!= '' && isset($qrySbas[$op[0]]))
		{
			if($qrySbas[$op[0]] == trim($parm['qry']))
				$qrySbas[$op[0]] = '('.trim($parm['qry']).')';
			$qrySbas[$op[0]] .= ' AND ('.$op[1].'<='.trim($parm['datemax'][$opId]).')';
		}		
	}
}

if($parm["ord"]===NULL)
	$parm["ord"] = PHRASEA_ORDER_DESC;
else
	$parm["ord"] = (int)$parm["ord"];


	
if(!$lng)
{
	$lng = GV_default_lng ; 
}

if(!$parm['bas'])
	$parm['bas'] = array();
	
if($parm["pag"]===NULL)
	$parm["pag"] = "";

// le mode d'affichage
if(!$parm["mod"])
	$parm["mod"] = "3X6";
	

$mod = explode("X", $parm["mod"]);
if(count($mod) == 1)
{
	$mod_row = (int)($mod[0]);
	$mod_col = 1;
}
else
{
	$mod_row = (int)($mod[0]);
	$mod_col = (int)($mod[1]);
}
$mod_xy = $mod_col*$mod_row;
	
$conn = connection::getInstance();
if(!$conn)
	die();	

	
$tbases = array();
if($parm['qry'] !== NULL)
{	
	
			 
	$page = $npages = $nbanswers = $rqtime = null;
	$usrRight = array();

	$dateLog = date("Y-m-d H:i:s"); 
	
	if($parm["pag"] === "") // pas de page : c'est une recherche
	{
		$qp = $simple_treeq = $needthesaurus = $indep_treeq = $treeq = array();
		$nbanswers = $courcahnum = 0;
		$time_start = getthemicrotime();
		
		$qp['main'] = new qparser($lng);
		$qp['main']->debug = false;
		$simple_treeq['main'] = $qp['main']->parsequery($qry);
		$qp['main']->priority_opk($simple_treeq['main']);
		$indep_treeq['main'] = $qp['main']->extendThesaurusOnTerms($simple_treeq['main'], true, true, false);
		$needthesaurus['main'] = $qp['main']->containsColonOperator($indep_treeq['main']);
		
		foreach($qrySbas as $sbas=>$qryBas)
		{
			$qp[$sbas] = new qparser($lng);
			$qp[$sbas]->debug = false;
			
			$simple_treeq[$sbas] = $qp[$sbas]->parsequery($qryBas);
	
			$qp[$sbas]->priority_opk($simple_treeq[$sbas]);
			
			$indep_treeq[$sbas] = $qp[$sbas]->extendThesaurusOnTerms($simple_treeq[$sbas], true, true, false);
			
			$needthesaurus[$sbas] = $qp[$sbas]->containsColonOperator($indep_treeq[$sbas]);
		}
		foreach($ph_session["bases"] as $phbase)
		{
			$tcoll = array();
			foreach($phbase["collections"] as $coll)
			{
				if(in_array($coll["base_id"],$parm['bas']))
					$tcoll[] = (int)$coll["base_id"];	// le tableau de colls doit contenir des int
			}
			if(sizeof($tcoll) > 0 && isset($qrySbas[$phbase['sbas_id']]))	// au - une coll de la base ?tait coch?e
			{
	 			$connbas = connection::getInstance($phbase['sbas_id']);
	 			if($connbas)
	 			{
					$kbase = $phbase["sbas_id"];
					$tbases[$kbase] = array();
					$tbases[$kbase]["searchcoll"] = $tcoll;
					$tbases[$kbase]["domthesaurus"] = null;
					
					if($needthesaurus[$kbase])
					{
						$domthesaurus = databox::get_dom_thesaurus($phbase['sbas_id']);

						if($domthesaurus)
						{
							$qp[$kbase]->thesaurus2($indep_treeq[$kbase], $phbase["sbas_id"], phrasea::sbas_names($kbase), $domthesaurus, true);
							$qp['main']->thesaurus2($indep_treeq['main'], $phbase["sbas_id"], phrasea::sbas_names($kbase), $domthesaurus, true);
						}

						$tbases[$kbase]["domthesaurus"] = $domthesaurus;
					}
					
					if($qp[$kbase]->errmsg != "")
					{
						
			?>
			<script type="text/javascript">
			alert("<?php echo $qp->errmsg?>");
			</script>
			<?php
			exit();
					}
		
			$treeq[$kbase] = $indep_treeq[$kbase];
			
			$emptyw = false;
			
			$qp[$kbase]->set_default($treeq[$kbase], $emptyw);
			
			// on simplifie
			$qp[$kbase]->factor_or($treeq[$kbase]);
			$qp[$kbase]->distrib_in($treeq[$kbase]);

			$qp[$kbase]->setNumValue($treeq[$kbase],$phbase["xmlstruct"]);

			$qp[$kbase]->thesaurus2_apply($treeq[$kbase], $kbase);

			$tbases[$kbase]["arrayq"] = $qp[$kbase]->makequery($treeq[$kbase]);

			$tbases[$kbase]["results"] = NULL;
				
				$nocache = FALSE;


	 			}
			}
		}
		if(GV_thesaurus)
		{
		?>
		<script language="javascr<??>ipt">
			document.getElementById('proposals').innerHTML = "<div style='height:0px; overflow:hidden'>\n<?php echo p4string::MakeString($qp['main']->proposals["QRY"],"JS")?>\n</div>\n<?php echo p4string::MakeString(proposalsToHTML($qp['main']->proposals),"JS")?>";
			<?php
			if(GV_clientAutoShowProposals)
			{
			?>
				if("<?php echo p4string::MakeString(proposalsToHTML($qp['main']->proposals),"JS")?>" != "<div class=\"proposals\"></div>")
					chgOng(4);
			<?php
			}
			?>
		</script>
		<?php
		
		}
		
		
		phrasea_clear_cache($ph_session["session_id"]);
		
		foreach($tbases as $kb=>$base)
		{
			if($parm['search_type'] == '1')
 			{
 				$tbases[$kb]["results"] = phrasea_query2($ph_session["session_id"], $kb, $base["searchcoll"], $base["arrayq"], GV_sit, (string)($usr_id) , $nocache , PHRASEA_MULTIDOC_REGONLY );

 				if($tbases[$kb]["results"])
					$nbanswers += $tbases[$kb]["results"]["nbanswers"];
 			}
 			else 
 			{

 				$tbases[$kb]["results"] = phrasea_query2($ph_session["session_id"], $kb, $base["searchcoll"], $base["arrayq"], GV_sit, (string)($usr_id) , $nocache , PHRASEA_MULTIDOC_DOCONLY  );

 				if($tbases[$kb]["results"])
					$nbanswers += $tbases[$kb]["results"]["nbanswers"];
 			}
			 
			$dst_logid= array();
			
			$sql = 'SELECT dist_logid FROM cache WHERE session_id="'.$conn->escape_string($ph_session["session_id"]).'"';
			if($rs = $conn->query($sql))
			{
				if( $row2 = $conn->fetch_assoc($rs) )
				{
					$dst_logid = unserialize($row2["dist_logid"]);									
				}				
				$conn->free_result($rs);
			}
		
			$conn2 = connection::getInstance($kb);
			
			if($conn2 && isset($dst_logid[$kb])===true)		
			{
				 
				$newid = $conn2->getId("QUEST");

//				$sql  = "INSERT INTO quest (id, logid, date, askquest, nbrep, coll_id ) VALUES " ;
//				$sql .= " ('".$conn2->escape_string($newid)."', '".$conn2->escape_string($dst_logid[$kb])."','" . $conn2->escape_string($dateLog) . "', '".$conn2->escape_string($parm['qry'])."', '".$conn2->escape_string($tbases[$kb]["results"]["nbanswers"])."', '".$conn2->escape_string(implode(',',$base["searchcoll"]))."')";
//				$conn2->query($sql);	
				
				$sql3  = "INSERT INTO log_search (id, log_id, date, search, results, coll_id ) VALUES " ;
				$sql3 .= "(null, '".$conn2->escape_string($dst_logid[$kb])."','" . $conn2->escape_string($dateLog) . "', '".$conn2->escape_string($parm['qry'])."', ".$conn2->escape_string($tbases[$kb]["results"]["nbanswers"]).", '".$conn2->escape_string(implode(',',$base["searchcoll"]))."')" ;
				$conn2->query($sql3);
				
			}	
		}
		
		
		$sql = 'SELECT * from dsel where usr_id="'.$conn->escape_string($usr_id).'" ORDER BY id ASC';
		if($rs = $conn->query($sql))
		{
			if($conn->num_rows($rs) >= 80 )
			{
				if($row = $conn->fetch_assoc($rs))
				{
					$sql = 'DELETE from dsel where usr_id="'. $conn->escape_string($usr_id).'" AND id="'.$conn->escape_string($row["id"]).'"' ;
					$conn->query($sql);
				}
			}
			$conn->free_result($rs);
							
			$id = $conn->getId("DSEL");				
				
			$sql = "INSERT INTO dsel (id, name, usr_id, query) VALUES ('".$conn->escape_string($id)."','".$conn->escape_string($parm['qry'])."', '". $conn->escape_string($usr_id)."', '".$conn->escape_string($parm['qry'])."')";
			$conn->query($sql);
			
		}

		$history = queries::history();
		
		echo '<script language="javascript" type="text/javascript">$("#history").empty().append("'.str_replace('"','\"',$history).'")</script>';
		
		if(function_exists('phrasea_save_cache'))
			phrasea_save_cache($ph_session["session_id"]);
		
		$rqtime = getthemicrotime() - $time_start;	
		$page = 0;
	
	}
	else
	{
		$nbanswers = $parm["nba"];
		$page = 0+$parm["pag"];
	}
	$npages = ceil($nbanswers / $mod_xy);
	
	$sql = 'SELECT base_id,canpreview,canhd,canputinalbum,candwnldhd,candwnldpreview,
			cancmd,restrict_dwnld,remain_dwnld FROM (usr NATURAL JOIN basusr ) WHERE usr.usr_id="'.$conn->escape_string($usr_id).'" ORDER BY base_id';
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$usrRight[$row["base_id"]] = $row;		
		}
		$conn->free_result($rs);
	}				
		
	$timeAsk = ""	;
	if($rqtime !== NULL)
		$timeAsk = sprintf("&nbsp; <i>(%s s.)</i>", ((int)($rqtime*100))/100);
		

//	if($parm["pag"] === "") // pas de page : c'est une recherche
//	{
		$courcahnum = 0;
		$longueur = strlen($parm['qry']);
		
		$qrys = '<div>'._('client::answers: rapport de questions par bases').'</div>';

		foreach($qrySbas as $sbas=>$qryBas)
			$qrys .= '<div style="font-weight:bold;">'.phrasea::sbas_names($sbas).'</div><div>'.$qryBas.'</div>';
		
		$txt = "<b>".substr($parm['qry'],0,36).($longueur>36?"...":"")."</b>" . sprintf(_('client::answers: %d reponses'),(int)$nbanswers)." <a style=\"float:none;display:inline-block;padding:2px 3px\" class=\"infoTips\" title=\"".str_replace('"',"'",$qrys)."\">&nbsp;</a>".(GV_debug?$timeAsk:'') ;
		?>
		<script type="text/javascript">
		$(document).ready(function(){
					p4.tot = <?php echo ($nbanswers>0)?$nbanswers:'0'?>;
					document.getElementById("nb_answers").innerHTML = "<?php echo p4string::JSstring($txt)?>";
		});
		</script>
		<?php

	$pages = '';
	$ecart = 3;
	$max   = (2*$ecart)+3 ;

	if( $npages > $max )
	{
		for($p=0; $p<$npages; $p++)
		{
			if($p == $page)
				$pages .= '<span class="naviButton sel">'.($p+1).'</span>';	
			elseif( ( $p>= ($page-$ecart) ) && ( $p<= ($page+$ecart) ))		
				$pages .= '<span onclick="gotopage('.$p.');" class="naviButton">'.($p+1).'</span>';
			elseif(  ($page<($ecart+2)) && ($p < ($max-$ecart+2) ) )		        // si je suis dans les premieres pages ...
				$pages .= '<span onclick="gotopage('.$p.');" class="naviButton">'.($p+1).'</span>';
			elseif(  ($page>=($npages-$ecart-2)) && ($p >= ($npages-(2*$ecart)-2) ) ) 	// si je suis dans les dernieres pages ...
				$pages .= '<span onclick="gotopage('.$p.');" class="naviButton">'.($p+1).'</span>';
			elseif( $p==($npages-1))	// c"est la derniere	
				$pages .= '<span onclick="gotopage('.$p.');" class="naviButton">...'.($p+1).'</span>';
			elseif( $p==0)				// c"est la premiere
				$pages .= '<span onclick="gotopage('.$p.');" class="naviButton">'.($p+1).'...</span>';
				
			if( ($p == $page) 
						|| 	( ( $p>= ($page-$ecart) ) && ( $p<= ($page+$ecart) ))
						|| 	(  ($page<($ecart+2)) && ($p < ($max-$ecart+2) ) )	
						|| 	(  ($page>=($npages-$ecart-2)) && ($p >= ($npages-(2*$ecart)-2) ) )
						|| 	( $p==0)
						)
			$pages .= '<span class="naviButton" style="cursor:default;"> - </span>';
		}
	}
	else
	{
		for($p=0; $p<$npages; $p++)
		{
			if($p == $page)
				$pages .= '<span class="naviButton sel">'.($p+1).'</span>';
			else
				$pages .= '<span onclick="gotopage('.$p.');" class="naviButton">'.($p+1).'</span>'; 
			if($p+1<$npages)	
				$pages .= '<span class="naviButton" style="cursor:default;"> - </span>';
		}	
	}
	
	$string2 = $pages.'<div class="navigButtons">';
	$string2.= '<div id="PREV_PAGE" class="PREV_PAGE"></div>';
	$string2.= '<div id="NEXT_PAGE" class="NEXT_PAGE"></div>';
	$string2.= '</div>';

	?>
	<script type="text/javascript">
	$(document).ready(function(){
		$("#navigation").empty().append("<?php echo p4string::JSstring($string2)?>");

		<?php if($page!=0 && $nbanswers){ ?> 
		$("#PREV_PAGE").bind('click',function(){gotopage(<?php echo ($page-1)?>)});
		<?php }else{?>
		$("#PREV_PAGE").unbind('click');
		<?php }
		 if($page!=$npages-1 && $nbanswers){ ?>
		$("#NEXT_PAGE").bind('click',function(){gotopage(<?php echo ($page+1)?>)});
		<?php }else{?>
		$("#NEXT_PAGE").unbind('click');
		<?php }?>
	});
	</script>
	<?php

	$all_bas_name = null ;
	$all_coll_name = null ;
		
	$layoutmode = "grid";
	if($mod_col == 1)
		$layoutmode = "list";
	else
		$layoutmode = "grid";
			
	foreach($ph_session["bases"] as $base)
	{
		$all_bas_name[$base["base_id"]]=$base["dbname"];
		foreach($base["collections"] as $coll)
			$all_coll_name[$coll["base_id"]]=$coll["name"];
	}

	$query = phrasea_fetch_results($ses_id, ($page*$mod_xy)+1, $mod_xy, true, "[[em]]", "[[/em]]");
	$rs = array();
	if(isset($query['results']) && is_array($query['results']))
		$rs = $query['results'];
	
	$courcahnum = ($page*$mod_xy) ;
	$count = sizeof($rs);

	$i = 0;
	
	if(is_array($rs))
	{
		?><div><table id="grid" cellpadding="0" cellspacing="0" border="0" style="xwidth:95%;"><?php
		
		if($mod_col == 1) // MODE LISTE
		{
			?><tr style="visibility:hidden"><td class="w160px" /><td /></tr><?php
		}
		else // MODE GRILLE
		{	
			?><tr style="visibility:hidden"><?php
			for($ii = 0; $ii<$mod_col; $ii++)
			{  
				?><td class="w160px"></td><?php
			}
			?></tr><?php
		}
		// load the xml file and stylesheet as domdocuments 
		$xml = new DomDocument(); 
		

		
			
		foreach($rs as $occu)
		{
			
			$base_id = $occu["base_id"]; //le base_id local d'une reponses est un 'coll_id' local
			$sbas_id = phrasea::sbasFromBas($base_id);	// la base de cette collection

			$thumbnail = answer::getThumbnail( $ses_id, $occu["base_id"], $occu["record_id"],GV_zommPrev_rollover_clientAnswer);
			
			$extcur = $thumbnail['extension']; 
			$mimecur = $thumbnail['mime'];
			$docType = $thumbnail['type'];
			
			$title = answer::format_title($sbas_id, $occu["record_id"], $occu["xml"]);
			$light_info = answer::format_infos($occu["xml"],$sbas_id, $occu["record_id"],$thumbnail['type']);
			$caption = answer::format_caption($base_id, $occu["record_id"],$occu['xml']);
			
			
			if($i == 0)
			{
				?><tr><?php
			}
			if(($i%$mod_col==0 && $i!=0))
			{
				?></tr><tr><?php
			}
			if($mod_col == 1 && $i!=0)
			{
				?></tr><tr style="height:20px;">
				<td colspan="2" class="td_mod_lst_img"><hr></td>
				</tr><tr><?php
			}

			if($mod_col == 1)
			{
				?><td valign="top" class="td_mod_lst_desc"><?php
			}
			else
			{
				?><td class="w160px"><?php
			}
			?><div class="diapo w160px" style="margin-bottom:0;border-bottom:none;"><?php
			
			if(GV_client_showTitle == "top") { 
				?><div class="title"><?php echo $title?></div><?php
			}

		
			$status = '';
			$status .= '<div class="status">';
			
			$dstatus = status::getDisplayStatus();
			
			$user = user::getInstance($session->usr_id);

			if(isset($dstatus[$sbas_id]))
			{	
				foreach($dstatus[$sbas_id] as $n=>$statbit)
				{
					$d = ((int)$n)>>2;
					$m = 1<<((int)$n & 0x03);
					if($d>=0 && $d<=15)
					{
						if($statbit['printable'] == '0' && (!isset($user->_rights_bas[$occu['base_id']]) || $user->_rights_bas[$occu['base_id']]['chgstatus'] === false))
							continue;
								
							
						$x = hexdec(substr($occu["status"], 15-$d, 1));
						
						if($x & $m)
						{
								$style1 = "visibility:auto;display:inline;";
						}
						else
						{
								$style0 = "visibility:auto;display:inline;";
						}
						
						if($x & $m)
						{
							if(trim($statbit["img_on"]) != '')	
								$status .= "<img style=\"margin:1px;".$style1."\" id=\"STAT_".$base_id.'_'.$occu["record_id"]."_".$n."_1\" src=\"".$statbit["img_on"]."\" title=\"".$statbit["labelon"]."\"/>";
						}
						else
						{
							if(trim($statbit["img_off"]) != '')	
								$status .= "<img style=\"margin:1px;".$style0."\" id=\"STAT_".$base_id.'_'.$occu["record_id"]."_".$n."_0\" src=\"".$statbit["img_off"]."\" title=\"".$statbit["labeloff"]."\"/>";
						}
					}
				}
			}


			$status .= '</div>';

			echo $status;
							
			$isVideo = $docType == 'video' ? true:false;
			$isAudio = $docType == 'audio' ? true:false;
			$isImage = $docType == 'image' ? true:false;
			$isDocument = $docType == 'document' ? true:false;
			
			$prevTips = '';
			
			if(GV_zommPrev_rollover_clientAnswer)
			{			
			$sd = $thumbnail['preview'];		
			
			$isImage = false;
			$isDocument = false;
			if(!$isVideo && !$isAudio)
				$isImage = true;
			
			
			if($isImage)
			{
				if(isset($sd["preview"]["width"]) && $usrRight[$occu["base_id"]]['canpreview']=='1')
				{
					$prev = "directprev.php?bas=".$occu["base_id"]."&rec=".$occu["record_id"];							
					$prevTips = "<div class='prevLoading'><img class='imgTips' onload='setVisible(this)' src='/include/".$prev."' style='visibility:hidden;z-index:99;width:".$sd["preview"]["width"]."px;height:".$sd["preview"]["height"]."px' /></div>";
				}
				elseif(isset($sd["thumbnail"]))
				{
					$sd["preview"] = $sd["thumbnail"];
					$prev = p4string::addEndSlash($sd["thumbnail"]["baseurl"]).$sd["thumbnail"]["file"];
					$prevTips = "";//<div class='prevLoading'><img class='imgTips' onload='setVisible(this)' src='/".$prev."' style='visibility:hidden;z-index:99;width:".round($sd["preview"]["width"]*1)."px;height:".round($sd["preview"]["height"]*1)."px'></div></div>";
				}
			}
			elseif($isVideo)
			{
				if(isset($sd["thumbnailGIF"]["width"]) && $usrRight[$occu["base_id"]]['canpreview']=='1')
				{
					$prev = "directprev.php?type=thumbnailGIF&bas=".$occu["base_id"]."&rec=".$occu["record_id"];						
					$prevTips = "<div class='prevLoading'><img onload='setVisible(this)' class='imgTips' src='/include/".$prev."' style='z-index:99; visibility:hidden;width:".round($sd["thumbnailGIF"]["width"]*1)."px;height:".round($sd["thumbnailGIF"]["height"]*1)."px' /></div>";
				}
			}
			elseif($isDocument)
			{
				
			}
			elseif($isAudio)
			{
				if(isset($sd["preview"]["width"]) && $usrRight[$occu["base_id"]]['canpreview']=='1')
				{
					$prev = "/include/directprev.php%3Ftype%3Dpreview%26bas%3D".$occu["base_id"]."%26rec%3D".$occu["record_id"];							
					$prevTips = "<div class='prevLoading' style='position:relative;height:24px;width:290px;'><object class='playerTips' width='290' height='24' id='audioplayer1' data='/include/audio-player/player.swf' type='application/x-shockwave-flash'><param value='/include/audio-player/player.swf' name='movie'/><param value='playerID=1&amp;autostart=yes&amp;soundFile=".$prev."' name='FlashVars'/><param value='high' name='quality'/><param value='false' name='menu'/><param value='#FFFFFF' name='bgcolor'/></object></div>";
				}
			}
			}

			?><table cellpadding="0" cellspacing="0" style="margin: 0pt auto;"><?php
				?><tr class="h160px"><?php
					?><td class="image w160px h160px"><?php

						if($isVideo){
							$duration = answer::get_duration($occu["xml"]);
							if($duration != '00:00')
								echo '<div class="dmco_text duration">'.$duration.'</div>';
						}
						if($isAudio){
							$duration = answer::get_duration($occu["xml"]);
							if($duration != '00:00')
								echo '<div class="dmco_text duration">'.$duration.'</div>';
						}

						$onclick = "";
						
						if(phrasea_isgrp($ses_id, $occu["base_id"], $occu["record_id"]))
						{
							$onclick='openPreview(\'REG\',0,\''.$occu["base_id"].'_'.$occu["record_id"].'\');';
						}
						else
						{
							$onclick='openPreview(\'RESULT\','.$courcahnum.');';
						}
						
						if($mod_col == '1')
							$pic_roll = $prevTips;
						else
							$pic_roll = $caption;
							
						$pic_roll = str_replace(array('&','"'),array('&amp;','&quot;'),$pic_roll);
						
						?><img onclick="<?php echo $onclick?>" class="<?php echo $thumbnail["imgclass"];?> captionTips"	id="IMG<?php echo $occu["base_id"]?>_<?php echo $occu["record_id"]?>"	src="<?php echo $thumbnail["thumbnail"]?>"	title="<?php echo ($pic_roll)?>" /><?php
					?></td><?php
				?></tr><?php
			?></table><?php


			if(GV_client_showTitle == "bottom")
			{
				?><div class="title"><?php echo $title?></div><?php
			} 
			?></div><?php
				?><div class="diapo w160px" style="border-top:none;"><?php
					?><div class="buttons"><?php

			$minilogos ="";				

			$minilogos .= '<div class="minilogos">'.collection::getLogo($occu['base_id']);
			$minilogos .= '</div>';
			
			echo $minilogos;

			if( ($usrRight[$occu["base_id"]]["candwnldpreview"] || $usrRight[$occu["base_id"]]["candwnldhd"] || $usrRight[$occu["base_id"]]["cancmd"]) )
			{
				?><div class="downloader" title="<?php echo _('action : exporter')?>" onclick="evt_dwnl('<?php echo $occu["base_id"]?>_<?php echo $occu["record_id"]?>');"></div><?php
			}
			?>
			<div class="printer" title="<?php echo _('action : print')?>" onClick="evt_print('<?php echo $occu["base_id"]?>_<?php echo $occu["record_id"]?>');"></div>
			<?php
			if($usrRight[$occu["base_id"]]["canputinalbum"])
			{
				?><div class="baskAdder" title="<?php echo _('action : ajouter au panier')?>" onClick="evt_add_in_chutier('<?php echo $occu["base_id"]?>', '<?php echo $occu["record_id"]?>');"></div><?php
			}
			if($mod_col != '1')
			{
				?>
				<div style="margin-right:3px;" class="infoTips" id="INFO<?php echo $occu["base_id"]?>_<?php echo $occu["record_id"]?>" title="<?php echo str_replace(array("\n",'"'),array("<br>",'&quot;'),$light_info)?>"></div>
				<?php
				
				if($prevTips != '')
				{
				?>
				<div class="previewTips" title="<?php echo $prevTips?>" id="ZOOM<?php echo $occu["base_id"]?>_<?php echo $occu["record_id"]?>">&nbsp;</div>
				<?php
				}
			}
			?></div><?php
		?></div><?php
	?></td><?php

		if($mod_col == 1) // 1X10 ou 1X100
		{
			?><td valign="top"><?php
				?><div class="desc1"><?php
					?><div class="caption" class="desc2"><?php echo ($caption.'<hr/>'.$light_info)?></div><?php
				?></div><?php
			?></td><?php
		}
		
		$courcahnum++;
		$i++;
		
		}
		?></tr><?php
	?></table></div><?php
	}else
	{
		?><div><?php echo _('reponses:: Votre recherche ne retourne aucun resultat'); ?></div><?php
		phrasea::getHome('HELP','client');
	}
}
else // ici parm["qry"]===NULL : pas de recherche
{
	$nbanswers = "";
}



	

function proposalsToHTML(&$proposals)
{

	$html = '<div class="proposals">';
	$b = true;
	foreach($proposals["BASES"] as $zbase)
	{
		if((int)(count($proposals["BASES"]) > 1) && count($zbase["TERMS"])>0)
		{
			$style = $b? 'style="margin-top:0px;"':'';
			$b = false;
			$html .= "<h1 $style>" . sprintf(_('reponses::propositions pour la base %s'), htmlentities($zbase["NAME"])) . "</h1>";
		}
		$t = true;
		foreach($zbase["TERMS"] as $path=>$props)
		{
			$style = $t? 'style="margin-top:0px;"':'';
			$t = false;
			$html .= "<h2 $style>" . sprintf(_('reponses::propositions pour le terme %s'), htmlentities($props["TERM"])) . "</h2>";
			$html .= $props["HTML"];
		}
	}
	$html .= '</div>';
	return($html);
}

