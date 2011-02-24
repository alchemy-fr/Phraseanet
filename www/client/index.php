<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();

$lng = isset($session->locale)?$session->locale:GV_default_lng;
		
$conn = connection::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms('nolog');


if(phrasea::guest_allowed() && isset($parm['nolog']))
{
	$logged = p4::signOnasGuest();
	if($logged['error'])
	{
		header("Location: /login/?app=prod&error=".$logged['error']);
		exit();
	}
	
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;

	
	header("Location: ./index.php");
	exit();
}
$isInvite = false;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
		$sql = 'SELECT invite FROM usr WHERE usr_id="'.$conn->escape_string($usr_id).'"';
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				if($row['invite'] == '1')
					$isInvite = true;
			}
		}
	
}
else{
	header("Location: /login/client/");
	exit();
}

if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
{
	header('Location: /login/logout.php?app=client');
	exit();
}
phrasea::headers();

user::updateClientInfos(2);

				


?>
<html lang="<?php echo $session->usr_i18n;?>">
<head>
<title><?php echo GV_homeTitle?> Client</title>
<meta http-equiv="X-UA-Compatible" content="chrome=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
<style ID="MYS" type="text/css">
IMG.hthbimg
{
    WIDTH: 108px;
}
IMG.vthbimg
{
    HEIGHT: 108px;
}
.w160px
{
	WIDTH: 128px;
}
.h160px
{
	HEIGHT: 128px;
}



</style>
<script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js"></script>
<script type="text/javascript" src="/include/minify/f=include/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>
<link type="text/css" rel="stylesheet" href="/include/jslibs/jquery.contextmenu.css" />
<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
<?php

//listage des css
$css = array();
$cssPath = GV_RootPath.'www/skins/client/';

if($hdir = opendir($cssPath))
{
	while(false !== ($file = readdir($hdir)))
	{
		
		if(substr($file,0,1)=="." || mb_strtolower($file)=="cvs")
			continue;
		if(is_dir($cssPath.$file))
		{
			$css[$file]=$file;
		}
	}
	closedir($hdir);
} 

$cssfile = false;
$baskStatus = '1';
$mode_pres = '';
	
$cssfile = user::getPrefs('client_css');
$baskStatus = user::getPrefs('client_basket_status');
$mode_pres = user::getPrefs('client_view');
$start_page = user::getPrefs('start_page');
$start_page_query = user::getPrefs('start_page_query');

if(!$cssfile && isset($css['000000']))
	$cssfile = '000000';

	$cssfile = 'skins/prod/000000/jquery-ui.css,skins/client/000000/clientcolor.css';
?>
<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/client/clientcolor.css" />
<?php
if($cssfile)
{
?>
<link id="skinCss" type="text/css" rel="stylesheet" href="/include/minify/f=<?php echo $cssfile?>" />
<?php
}
?>
</head>
<body style="overflow:hidden;">
	<div id="container" style="position:absolute;top:0;left:0;overflow:hidden;width:100%;height:100%;">
		
			<?php
	
	$events_mngr = eventsmanager::getInstance();
	
	$twig = new supertwig();
	$twig->display('common/menubar.twig', array('module'=>'client', 'events'=>$events_mngr));
			?>
			<div style="top:30px;position:relative;float:left;">
		<div id="left" style="height:100%;width:265px;position:relative;float:left;">
			<div style="overflow:hidden;border:none;padding:0;margin:0;position:relative;top:0px;height:0px;width:265px;" id="search">
			<?php
			
		$i = 1;
			
			
		$tong = array(GV_ong_search=>1, GV_ong_advsearch=>2, GV_ong_topics=>3);
		unset($tong[0]);
		if(count($tong) == 0)
			$tong = array(1=>1);
		
		ksort($tong);
		?>
		
		
		<div class="bodyLeft" style="top:3px;">
			<div id="bigTabsBckg">
				<table align="center" border="0" style="table-layout:fixed; top:1px; left:2px;height:22px; width:253px;" cellpadding="0" cellspacing="0">
					<tr>
					<?php
						$activeTab = '';
						foreach($tong as $kong=>$ong)
						{
							if($kong==0)
								continue;
							$k = $kong==GV_ong_actif ? 'actif':'inactif';
							switch($ong)
							{
								case 1:
									if($k=='actif')
										$activeTab = 'ongSearch';
						?>
									<td class="bigTabs <?php echo $k?>" id="ongSearch" onclick="chgOngSearch('ongSearch');"><?php echo _('client:: recherche')?></td>
						<?php
									break;
								case 2:
									if($k=='actif')
										$activeTab = 'ongAdvSearch';
						?>
									<td class="bigTabs <?php echo $k?>" id="ongAdvSearch" onclick="chgOngSearch('ongAdvSearch');return(false);"><?php echo _('client:: recherche avancee')?></td>
						<?php
									break;
								case 3:
									if($k=='actif')
										$activeTab = 'ongTopic';
						?>
									<td class="bigTabs <?php echo $k?>" id="ongTopic" onclick="chgOngSearch('ongTopic');return(false);"><?php echo _('client:: topics')?></td>
						<?php
									break;
							}
						}
						?>
					</tr>
				</table>
			</div>
		<form  style="margin:0px; padding:0px;" name="search" id="searchForm" action="answer.php" onkeypress="if(event.keyCode==13){ doSearch();return false;}" method="post">
	
			<div id="idongSearch">
				
				<div id="mainSearch" style="overflow:hidden;">			 
				<div>
						<div>
									<input type="text" name="qry" value="<?php echo p4string::MakeString($start_page_query, "form")?>" id="qry" style="width:245px;">
						</div>
							<div id="idongAdvSearch" style="display:none;">
								
									<div>
										<select name="opAdv[]" style="width:54px">
											<option value="<?php echo _('phraseanet::technique:: et')?>"><?php echo _('phraseanet::technique:: et')?></option>
											<option value="<?php echo _('phraseanet::technique:: or')?>"><?php echo _('phraseanet::technique:: or')?></option>
											<option value="<?php echo _('phraseanet::technique:: except')?>"><?php echo _('phraseanet::technique:: except')?></option>
										</select>
										<input type="text" name="qryAdv[]" id="qryAdv1" style="width:185px">
									</div>
									<div>
										<select name="opAdv[]" style="width:54px">
											<option value="<?php echo _('phraseanet::technique:: et')?>"><?php echo _('phraseanet::technique:: et')?></option>
											<option value="<?php echo _('phraseanet::technique:: or')?>"><?php echo _('phraseanet::technique:: or')?></option>
											<option value="<?php echo _('phraseanet::technique:: except')?>"><?php echo _('phraseanet::technique:: except')?></option>
										</select>
										<input type="text" name="qryAdv[]" id="qryAdv2" style="width:185px">
									</div>
								
							</div>
							<?php
							
							if(GV_client_coll_ckbox === 'popup')
							{
								// liste des collections : popup
							?>
							<div>
									<?php echo _('client::recherche: rechercher dans les bases :')?>
								
									
									<?php
										$allbases = array();
										$showbases = (count($ph_session["bases"]) > 0);
										$options = '';
										
							
										
										foreach($ph_session["bases"] as $base)
										{
											if($showbases)
											{
												$options .= '<optgroup label="'.htmlentities($base["viewname"],ENT_COMPAT,'UTF-8',false).'">';
												$allbcol = array();
												$n_allbcol = 0;
												$colls = array();
												foreach($base["collections"] as $coll)
												{
													$allbcol[] = $coll["base_id"];
													$n_allbcol++;
													
													echo '<input style="display:none;" checked="checked" type="checkbox" class="basItem checkbox basItem'.$base['sbas_id'].'" name="bas[]" value="'.$coll["base_id"].'"  id="basChk'.$coll["base_id"].'" />';
													
													$colls[$coll['base_id']] = '<option value="'.$coll["base_id"].'" checked="checked" >'.htmlentities($coll["name"],ENT_COMPAT,'UTF-8',false).'</option>';
													
													$allbases[] = $coll["base_id"];
												}
												if($n_allbcol > 1)
												{
													$options .= '<option value="'.implode(';',$allbcol).'">`'.htmlentities($base["viewname"],ENT_COMPAT,'UTF-8',false).'`'.'</option>';
												}
											
												$bas_order = phrasea::getBasesOrder();
												foreach($bas_order as $coll)
												{
													if(isset($colls[$coll['base_id']]))
													{
														$options .= $colls[$coll['base_id']];
													}
												}
											}
											foreach($base["collections"] as $coll)
											{
											}
											if($showbases)
											{
												$options .= "</optgroup>\n";
											}
										}
										echo '<select id="basSelector" onchange="beforeAnswer();" style="width:245px;"><option value="'.implode(';',$allbases).'">'._('client::recherche: rechercher dans toutes les bases').'</option>'.$options.'</select>';
									
									?>
							</div>
							<?php
							}
							?>
							<div>
									<select title="<?php echo _('phraseanet:: presentation des resultats')?>" name="mod" id="mod" onChange="changeModCol();" >
									<?php
										$vmf = array(
											array('w'=>'3',	'h'=>'2',	'name'=>'3*2',		'selected'=>'0'),
											array('w'=>'5',	'h'=>'4',	'name'=>'5*4',		'selected'=>'0'),
											array('w'=>'4',	'h'=>'10',	'name'=>'4*10',		'selected'=>'0'),
											array('w'=>'6',	'h'=>'3',	'name'=>'6*3',		'selected'=>'1'),
											array('w'=>'8',	'h'=>'4',	'name'=>'8*4',		'selected'=>'0'),
											array('w'=>'1',	'h'=>'10',	'name'=>'list*10',	'selected'=>'0'),
											array('w'=>'1',	'h'=>'100',	'name'=>'list*100',	'selected'=>'0')
										);
										foreach($vmf as $vm)
										{
											$w = $vm["w"];
											$h = $vm["h"];
											
											$sel = '';
											if($mode_pres == '')
											{
												if($vm['selected'] == '1')
													$sel = 'selected';
											}
											else
												if($mode_pres == $h.'X'.$w)
													$sel = "selected";
												
											echo '<option '.$sel.' value="'.$h.'X'.$w.'">'.(string)$vm['name'].'</option>';
										}
									?>
									</select>
									<?php
									
										
									$sel1= "";
									$sel2 = "";
									(GV_defaultQuery_type==0?$sel1=" checked='checked'":$sel2=" checked='checked'")
									?>
									
									<input type="radio" value="0" class="checkbox" <?php echo $sel1?> id="search_type_docs" name="search_type" /><label for="search_type_docs"><?php echo _('phraseanet::type:: documents')?></label>
									<input type="radio" value="1" class="checkbox" <?php echo $sel2?> id="search_type_group" name="search_type" /><label for="search_type_group"><?php echo _('phraseanet::type:: reportages')?></label>
									<input type="hidden" name="ord" id="searchOrd" value="<?php echo PHRASEA_ORDER_DESC?>" />
								</div>
								<div>
								
									 <div style="text-align:center;"><input class="pointer" type="button" onclick="doSearch();" value="<?php echo _('boutton::rechercher')?>" /></div>
								</div>
							</div>
					<input type="hidden" name="pag" id="formAnswerPage" value="">
					<input type="hidden" name="nba" value="">
	


				<div class="onglets" style="white-space: nowrap; margin-left: 5px; width: 227px;">
				<?php
				if(GV_client_coll_ckbox == 'checkbox')
				{
				?>
					<span id="idOnglet1" class="actif actives" onclick="chgOng(1);">
						<?php echo _('phraseanet:: collections')?> <img onclick="removeFilters();" id="filter_danger" src="/skins/icons/alert.png" title="<?php echo _('client::recherche: cliquez ici pour desactiver tous les filtres de toutes base')?>" style="vertical-align:bottom;width:12px;height:12px;display:none;"/>
					</span>
					<?php
				}
				if(GV_thesaurus)
				{
				?>
					<span id="idOnglet4" class="<?php echo (GV_client_coll_ckbox == 'checkbox')?"inactif":"actif"?> actives" onclick="chgOng(4);">
						<?php echo _('phraseanet:: propositions')?>
					</span>
				<?php
				}
				?>
					<span id="idOnglet5" class="<?php echo (!(GV_client_coll_ckbox == 'checkbox') && !GV_thesaurus)?'actif':'inactif'?> actives" onclick="chgOng(5);">
						<?php echo _('phraseanet:: historique')?>
					</span>
				</div>
			</div>
			<div id="searchMiddle" style="">
				<?php
				if(GV_client_coll_ckbox == 'checkbox')
				{
				?>
				<div id="onglet1" style="display:block;height:100%;overflow-x: hidden; overflow-y: auto;" class="searchZone" >
					<div>	
						<div style="text-align:center;margin:5px;">
							<input id="bases_all" class="actives" type="button" value="<?php echo _('boutton:: selectionner toutes les bases')?>" onclick="checkBases(true);"/>
							<input id="bases_none" class="actives" type="button" value="<?php echo _('boutton:: selectionner aucune base')?>" onclick="checkBases(false);"/>
						</div>
						
					</div>
					<div>
							<div class="basesContainer">
							<?php
								foreach($ph_session["bases"] as $nbbas=>$base)
								{
						
									if(GV_view_bas_and_coll )
									{
										?>
										<div class="basContainer">
												<div class="basContTitle">
													<div class="basTitle">
														<input class="basChecker checkbox" id="basChecker<?php echo $base['sbas_id']?>" type="checkbox" onclick="chkSbas(<?php echo $base['sbas_id']?>,this)" /> 
														<label for="basChecker<?php echo $base['sbas_id']?>"><?php echo $base["viewname"]?></label> 
														<img onclick="removeFilters(<?php echo $base['sbas_id']?>);" id="filter_danger<?php echo $base['sbas_id']?>" class="filter_danger" src="/skins/icons/alert.png" title="<?php echo _('client::recherche: cliquez ici pour desactiver tous les filtres de cette base')?>" style="vertical-align:bottom;width:12px;height:12px;display:none;"/>
													</div>
													<?php
													if($sxe = simplexml_load_string($base['xmlstruct']))
													{
															$sbFilters = '';
															$dateFilters = $fieldsFilters = '';
														if($sxe->statbits->bit)
														{
															foreach($sxe->statbits->bit as $sb)
															{
																if($sb['searchclient'] && $sb['searchclient']=='1')
																{
																	
													
																	$sb_pathOff	= '/status/'.$base["host"]."-".$base["port"]."-".$base["dbname"].'-' . 'stat_'.$sb['n'].'_0.gif';
																	$sb_pathOn	= '/status/'.$base["host"]."-".$base["port"]."-".$base["dbname"].'-' . 'stat_'.$sb['n'].'_1.gif';
																	
																	$imgoff = '';
																	$imgon = '';
																	
																	if(is_file(GV_RootPath . 'config/' . $sb_pathOff))
																		$imgoff = '<img src="'. $sb_pathOff.'" title="'.$labeloff.'" style="width:16px;height:16px;vertical-align:bottom" />';
																	
																		
																	if(is_file(GV_RootPath. 'config/' . $sb_pathOn))
																		$imgon = '<img src="'. $sb_pathOn.'" title="'.$labelon.'" style="width:16px;height:16px;vertical-align:bottom" />';
																		
																	$labeloff = $sb['labelOff'];
																	$labelon = $sb['labelOn'];
																	
																	$sbFilters .= '<div style="text-align:center;overflow:hidden;">' .
																			'<table style="table-layout:fixed;width:90%;text-align:left;" cellspacing="0" cellpadding="0">' .
																				'<tr>'.
																					'<td style="width:50%" nowrap>' .
																						'<input class="checkbox" db="'.$base['sbas_id'].'" onchange="checkFilters();" type="checkbox" name="status[]" id="statusfil_'.$base['sbas_id'].'_off'.$sb['n'].'" value="'.$base['sbas_id'].'_of'.$sb['n'].'"/>' .
																						'<label title="'.$labeloff.'" for="statusfil_'.$base['sbas_id'].'_off'.$sb['n'].'">'.$imgoff.$labeloff.'</label>' .
																					'</td>'.
																					'<td style="width:50%" nowrap>' .
																						'<input class="checkbox" db="'.$base['sbas_id'].'" onchange="checkFilters();" type="checkbox" name="status[]" id="statusfil_'.$base['sbas_id'].'_on'.$sb['n'].'" value="'.$base['sbas_id'].'_on'.$sb['n'].'"/>' .
																						'<label title="'.$labelon.'" for="statusfil_'.$base['sbas_id'].'_on'.$sb['n'].'">'.$imgon.$labelon.'</label>' .
																					'</td>'.
																				'</tr>' .
																			'</table>' .
																		'</div>';
																}
															}
														}
														if($sxe->description)
														{
															foreach($sxe->description->children() as $f=>$field)
															{
																if($field['type'] == 'date' && $field['searchclient']=='1')
																{
																	$dateFilters .= '<div><table style="width:98%;text-align:left;" cellspacing="0" cellpadding="0"><tr><td colspan="2">' .
																			$f.'</td></tr>'.
																			'<tr><td style="width:50%;">'._('phraseanet::time:: de'). 
																			'</td><td style="width:50%;">' .
																			_('phraseanet::time:: a').
																			'</td></tr>'.
																			'<tr><td style="width:50%;">'.
																			'<input type="hidden" name="dateminfield[]" value="'.$base['sbas_id'].'_'.$f.'">'.
																			' <input db="'.$base['sbas_id'].'" onchange="checkFilters();" class="datepicker" type="text" name="datemin[]"></td><td style="width:50%;">' .
																			'<input type="hidden" name="datemaxfield[]" value="'.$base['sbas_id'].'_'.$f.'">' .
																			' <input db="'.$base['sbas_id'].'" onchange="checkFilters();" class="datepicker" type="text" name="datemax[]"></td></tr>'.
																			'</table>' .
																			'</div>';
																}
																elseif($field['type'] != 'date')
																{
																	$fieldsFilters .= '<option value="'.$base['sbas_id'].'_'.$f.'">'.$f.'</option>';
																}
															}
															if($dateFilters != '' || $sbFilters !='' || $fieldsFilters != '')
															{
																echo '<div class="filter"><span class="actives" onclick="toggleFilter(\'Filters'.$base['sbas_id'].'\',this);">'._('client::recherche: filter sur').'</span></div>' .
																		'<div id="Filters'.$base['sbas_id'].'" class="base_filter" style="display:none;">';
																if($dateFilters != '')
																	echo '<div class="filterTitle">- '._('client::recherche: filtrer par dates').'</div>' .$dateFilters;
																if($sbFilters != '')
																	echo '<div class="filterTitle">- '._('client::recherche: filtrer par status').'</div>'.$sbFilters;
																if($fieldsFilters != '')
																	echo '<div class="filterTitle">- '._('client::recherche: filtrer par champs').'</div><div><select db="'.$base['sbas_id'].'" onchange="checkFilters();" name="infield[]" style="width:165px;"><option value="" selected="selected">'._('client::recherche: filtrer par champs : tous les champs').'</option>'.$fieldsFilters.'</select></div>';
																echo '</div><div style="height:4px;">&nbsp;</div>';
															}
														}
													}
													
													?>
												</div>

										<?php
									}
									?><div class="basGrp"><?php
									$colls = array();
									foreach($base["collections"] as $coll)
									{
										$s = "checked";
										$colls[$coll['base_id']] = '<div><input type="checkbox" class="checkbox basItem basItem'.$base['sbas_id'].'" '.$s.' name="bas[]"  id="basChk'.$coll["base_id"].'" value="'.$coll["base_id"].'"><label for="basChk'.$coll["base_id"].'">'.$coll["name"].'</label></div>';
									}
									$bas_order = phrasea::getBasesOrder();
									foreach($bas_order as $coll)
									{
										if(isset($colls[$coll['base_id']]))
										{
											echo $colls[$coll['base_id']];
										}
									}
									?></div><?php
									if(GV_view_bas_and_coll )
										echo '</div>';
								}
							?>
							</div>
				</div>
			</div>

			<?php
			} 
			if(GV_thesaurus)
			{
			?>

			<div id="onglet4" style="display:<?php echo (GV_client_coll_ckbox == 'checkbox')?'none':'block'?>;height:100%;overflow-x: hidden; overflow-y: auto;" class="searchZone" >
				<div>
					<div id="proposals" style="width:235px; overflow:hidden">
					
					</div>
				</div>
			</div>
			<?php
			}
			?>
			<div id="onglet5" style="display:<?php echo (!(GV_client_coll_ckbox == 'checkbox') && !GV_thesaurus)?'block':'none'?>;height:100%;overflow-x: hidden; overflow-y: auto;" class="searchZone" >
				<div id="history">
				</div>
			</div>




		</div>
</div>
				</form>
	
		<div id="idongTopic" style="overflow-x:hidden;overflow-y:auto;">
		
		<?php
		if(GV_client_render_topics == 'popups')
			echo queries::dropdown_topics();
		elseif(GV_client_render_topics == 'tree')
			echo queries::tree_topics();
		
		?>
		
		</div>
			<div class="bodySearchBottom">


			</div>
		</div>			
			</div>
			<div id="baskets" class="loading" style="overflow:hidden;border:none;padding:0;margin:0;position:relative;bottom:0;width:265px;height:320px;">

			</div>
		</div>
		<div id="right" style="position:relative;top:0;height:100%;float:right;">
			<div id="nb_answersEXT">
					<div id="nb_answers"></div>
			</div>
			<div id="answers" style="overflow-x:auto;overflow-y:auto;border:none;padding:0;margin:0;position:relative;left:0;top:0;margin:10px 0;">
				<?php
				echo phrasea::getHome($start_page, 'client');
				?>
			</div>
			<div class="divNavig" id="navigation"></div>	
		</div>
	</div>
	</div>
	<div id="OVERLAY" style="display:none;">
	
	</div><div id="PREVIEWBOX" style="overflow:hidden;">
				<div id="PREVIEWTITLE" style="height:50px;">
					<div style="margin:0 20px 8px;height:34px;">
						<span id="SPANTITLE" style="font-size:16px;font-weight:bold;"> </span>
						<div style="position:absolute;right:0;top:0;"><div onclick="closePreview();" style="cursor:pointer;color:#CCCCCC;font-size:12px;font-weight:bold;text-align:right;text-decoration:underline;"><?php echo _('boutton::fermer')?></div></div>
					</div>
				</div>
				<div id="PREVIEWLEFT" class="preview_col" style="width:49%;position:relative;float:left;overflow:hidden;">
					<div id="PREVIEWCURRENT" class="debug preview_col_film" style="margin-left:20px;">
						<div id="PREVIEWCURRENTGLOB" style="position:relative;float:left;">
						</div>
					</div>
					<div id="PREVIEWIMGCONT" class="preview_col_cont" style="overflow-x:hidden;overflow-y:hidden;text-align:left;"></div>
				</div>
				<div id="PREVIEWRIGHT" class="preview_col" style="width:49%;position:relative;float:right;overflow:hidden;">
					<div style="margin-right:10px;">
						<div id="PREVIEWIMGDESC" class="preview_col_cont" style="overflow-x:hidden;overflow-y:auto;">
							<ul style="height:30px;">
								<li><a href="#PREVIEWIMGDESCINNER-BOX"><?php echo _('preview:: Description');?></a></li>
								<li><a href="#HISTORICOPS-BOX"><?php echo _('preview:: Historique');?></a></li>
								<li><a href="#popularity-BOX"><?php echo _('preview:: Popularite');?></a></li>
							</ul>
							<div id="PREVIEWIMGDESCINNER-BOX" class="descBoxes">
								<div id="PREVIEWIMGDESCINNER" style="margin:10px;overflow-x:hidden;overflow-y:auto;">
								</div>
							</div>
							<div id="HISTORICOPS-BOX" class="descBoxes">
								<div id="HISTORICOPS" style="margin:10px;overflow-x:hidden;overflow-y:auto;">
								</div>
							</div>
							<div id="popularity-BOX" class="descBoxes">
								<div id="popularity" style="margin:10px;overflow-x:hidden;overflow-y:auto;">
								</div>
							</div>
						</div>
						<div id="PREVIEWOTHERS" class="preview_col_film" style="overflow-x:hidden;overflow-y:auto;">
							<div id="PREVIEWOTHERSINNER" style="margin:0 0 0 20px;position:relative;float:left;width:100%;"></div>
						</div>
					</div>
				</div>
			</div>
	<div id="PREVIEWHD"></div>
	<!-- BOITE MODALE DIALOG -->
	<div id="DIALOG"></div>
	<!-- BOITE MODALE DIALOG -->
	<div id="MESSAGE"></div>
	
		<?php 
		echo cgus::askAgreement();
		?>
	<div id="OVERLAY2" style="display:none;">
	
	</div>
	<iframe id="MODALDL" class="modalbox" src="" name="download" frameborder="0">
	</iframe>
<!--<iframe style="display:none;" id="download" name="download"></iframe>-->
	<form style="display:none;" action="./index.php" target="_self" id="mainForm">
	</form>
	<div id="dialog_dwnl" title="<?php echo _('action : exporter')?>" style="display:none;"></div>
	<form name="formChu" id="formChu" action="./baskets.php" method="post" style="visibility:hidden; display:none" >
		<input type="hidden" name="bas" id="formChubas" value="">
		<input type="hidden" name="act" id="formChuact" value="">
		<input type="hidden" name="p0"  id="formChup0" value="">
		<input type="hidden" name="ssel_id" value="">
		<input type="hidden" name="courChuId" id="formChuBaskId" value="">
	</form>	
	<form name="formPrintPage" action="printpage.php" method="post" style="visibility:hidden; display:none" >
		<input type="hidden" name="lst" value="">
	</form>		 
	<form name="validatorEject" target="wVal<?php echo $ses_id?>" id="validatorEject" action="/lightbox/index.php" method="post" style="visibility:hidden; display:none" >
		<input type="hidden" name="ssel_id" id="ssel2val" value="">
		<input type="hidden" name="mode" value="0">
	</form>	
	<form name="logout" target="_self" id="logout" action="/login/logout.php" method="post" style="visibility:hidden; display:none" >
		<input type="hidden" name="app" value="client">
	</form>	
	<script type="text/javascript" src="/include/minify/g=client"></script>
	<script type="text/javascript" src="/include/flowplayer/flowplayer-3.2.6.min.js"></script>
<script type="text/javascript"> 


function reModCol()
{
		var mod_col = $('#mod')[0].options[$('#mod')[0].selectedIndex].value.split('X');
		if(mod_col[0])
			mod_col = mod_col[1]
		var w = Math.round((bodyW - 16) / (mod_col==1?4:mod_col)) - 12;

		if(w < 128)
			w = 128;
		var propname = document.styleSheets[0].cssRules ? "cssRules":"rules";  // firefox=cssRules ; safari,ie=rules
		document.styleSheets[0][propname][0].style.width  = (w-20)+"px";  // IMG.hthbimg
		document.styleSheets[0][propname][1].style.height = (w-20)+"px";  // IMG.vthbimg
		document.styleSheets[0][propname][2].style.width  = (w)+"px";  // .w160px
		document.styleSheets[0][propname][3].style.height = (w)+"px";  // .h160px
}



function sessionactive(){
	$.ajax({
		type: "POST",
		url: "/include/updses.php",
		dataType: 'json',
		data: {
			app : 2,
			usr : <?php echo $usr_id?>
		},
		error: function(){
			window.setTimeout("sessionactive();", 10000);
		},
		timeout: function(){
			window.setTimeout("sessionactive();", 10000);
		},
		success: function(data){
			if(data)
				manageSession(data);
			var t = 20000;
			if(data.apps && parseInt(data.apps)>1)
				t = Math.round((Math.sqrt(parseInt(data.apps)-1) * 1.3 * 20000));
			window.setTimeout("sessionactive();", t);
			return;
		}
	})
};
window.onbeforeunload = function(){
	var xhr_object = null;      
	if(window.XMLHttpRequest) // Firefox   
	   xhr_object = new XMLHttpRequest();   
	else if(window.ActiveXObject) // Internet Explorer
	   xhr_object = new ActiveXObject("Microsoft.XMLHTTP");   
	else  // XMLHttpRequest non supporte par le navigateur   
	  return;   
	url= "/include/delses.php?app=2&t="+Math.random();
	xhr_object.open("GET", url, false);     
	xhr_object.send(null); 
}
</script>
	<script type="text/javascript" language="javascript">
		var lastAct = null;
		var baskDisplay = true;
		$(document).ready(function(){
			
			chgOngSearch('<?php echo $activeTab?>');
			checkBases(true)
			
			<?php 
			if(!$isInvite && $session->isset_cookie('last_act'))
			{
				?>
				lastAct = $.parseJSON('<?php echo $session->get_cookie('last_act')?>');
				execLastAct(lastAct);
				<?php
			}
			if($baskStatus == '0')
			{
				?>
				baskDisplay = false;
				<?php
			}
			else
			{
				?>
				baskDisplay = true;
				<?php
			}
			?>
			setBaskStatus();
			
			
		});
	</script>
	<?php if(trim(GV_bitly_user) !== '' && trim(GV_bitly_key) !== '')
	{
	?>	
	<script type="text/javascript" src="http://bit.ly/javascript-api.js?version=latest&login=<?php echo GV_bitly_user?>&apiKey=<?php echo GV_bitly_key?>"></script>
	<?php
	}
	?>
		<?php
		if(trim(GV_googleAnalytics) != '')
		{
			?>
			<script type="text/javascript">
				var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
				document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
			</script>
			<script type="text/javascript">
				try {
				var pageTracker = _gat._getTracker("<?php echo GV_googleAnalytics?>");
				pageTracker._setDomainName("none");
				pageTracker._setAllowLinker(true);
				pageTracker._trackPageview();
				} catch(err) {}
			</script>
			<?php
		}
		?>
</body>
</html>