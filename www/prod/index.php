<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
require(GV_RootPath."lib/prodUtils.php");

$request = httpRequest::getInstance();
$parm = $request->get_parms("res", 'nolog');

if(!is_null($parm['nolog']) && phrasea::guest_allowed())
{
	$logged = p4::signOnasGuest();
	if($logged['error'])
	{
		header("Location: /login/?app=prod&error=".$logged['error']);
		exit();
	}
	
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;

	header("Location: /index.php");
	exit();
}


if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
}
else{
	header("Location: /login/prod/");
	exit();
}
$user = user::getInstance($session->usr_id);

if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
{
	header("Location: /login/logout.php");
	exit();	
}
phrasea::headers(200, true);

user::updateClientInfos(1);

$rss_infos = user::getMyRss();

	




?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<meta http-equiv="X-UA-Compatible" content="chrome=1">
		<title><?php echo GV_homeTitle?> Production </title>
		<link rel="shortcut icon" type="image/x-icon" href="favicon.ico"/>
		<link href="<?php echo $rss_infos['url']?>" type="application/atom+xml" rel="alternate" title="<?php echo _('Flux Atom des publications internes')?>" />
		<style ID="MYS" type="text/css">
		#idFrameE .diapo
		{
		    WIDTH : 134px;
		    HEIGHT : 134px;
		}
		
		DIV.HDIV
		{
			visibility: hidden;
			BACKGROUND-COLOR:#00FF00;
			POSITION: relative;
		    LEFT: 0px;
			WIDTH: 100%;
			HEIGHT: 1px;
			overflow: hidden;
		}
		
		.noRepresent
		{ background-color:#A2F5F5; }
		
		
		.disable{
			display:none;
		}


		</style>
		
		<style>	/* NE PAS FUSIONER AVEC LE BLOC PRECEDENT */
		.MenuSG
		{
		}
		.MenuSG DIV
		{
			WIDTH: 220px;
		}
		.MenuSG A,.MenuSG A:link,.MenuSG A:visited,.MenuSG A:active,.MenuSG A:hover
		{
		}
		.MenuSG A:hover
		{
		}
		</style>
		
		<style type="text/css"> 
		.MenuSG
		{
		    BORDER-RIGHT: 2px outset;
		    BORDER-TOP: 2px outset;
		    DISPLAY: none;
		    FONT-SIZE: 8pt;
		    Z-INDEX: 100;
		    VISIBILITY: visible;
		    OVERFLOW: hidden;
		    BORDER-LEFT: 2px outset;
		    COLOR: #000000;
		    BORDER-BOTTOM: 2px outset;
		    FONT-FAMILY: Tahoma;
		    POSITION: absolute;
		    BACKGROUND-COLOR: #e6e4e0;
		    TEXT-DECORATION: none;
		    LEFT: 500px;
		    TOP: 300px;
		    WIDTH: 220px;
		    COLOR: #222222;
		    
		}
		.MenuSG DIV
		{
			WIDTH: 220px;
			overflow:scroll-y;		
			WHITE-SPACE: normal;
		    COLOR: #222222;
		    BORDER-bottom:#CCCCCC 1px solid;
		    margin-top:1px;
		    margin-bottom:1px;
		}
		.MenuSG A,.MenuSG A:link,.MenuSG A:visited,.MenuSG A:active,.MenuSG A:hover
		{
		
		    cursor: pointer;
		    DISPLAY: block;
		    PADDING-LEFT: 5px;
		    COLOR: #000000;
		    PADDING-TOP: 2px;
			WHITE-SPACE: normal;
		    BACKGROUND-COLOR: #e6e4e0;
		    POSITION: relative;
		    TOP: 0px;
		    LEFT: 0px;
		    TEXT-DECORATION: none;
		    COLOR: #222222;
		}
		.MenuSG A:hover
		{
		    COLOR: #ffffff;
		    BACKGROUND-COLOR: #191970;
		    TEXT-DECORATION: none;
		}
		
		.sugg_val{
			font-size : 10px;
			font-color:black;
			border-bottom : 1px solid black;
			padding:3px;
			height:16px;
			background:white;
		}
		.sugg_val_sel{
			background:#FBFFA8;
		}
		.indicator{
			color:green;
			float:right;
			font-size:9px;
			position:relative;
			font-style:italic;
		}
		
		<?php 
		//listage des css
		$css = array();
		$cssfile = false;
		
		$cssPath = GV_RootPath.'www/skins/prod/';
		
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
		
		$mod = user::getPrefs('view');
		$nppage = user::getPrefs('images_per_page');
		$th_size = user::getPrefs('images_size');
		$cssfile = user::getPrefs('css');
		$technical_display = user::getPrefs('technical_display');
		$doctype_display = user::getPrefs('doctype_display');
		$rollover_thumbnail = user::getPrefs('rollover_thumbnail');
		$basket_caption_display = user::getPrefs('basket_caption_display');
		$basket_status_display = user::getPrefs('basket_status_display');
		$basket_title_display = user::getPrefs('basket_title_display');
		$start_page = user::getPrefs('start_page');
		$start_page_query = user::getPrefs('start_page_query');
		$searchSet = json_decode(user::getPrefs('search'));
		
			
		if(!$cssfile && isset($css['000000']))
			$cssfile = '000000';
		
		?>
		</style>
		<script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js"></script>
<?php if(USE_MINIFY_CSS) { ?>		
		<link type="text/css" rel="stylesheet" href="/include/minify/f=include/jslibs/jquery.contextmenu.css,include/colorpicker/css/colorpicker.css,include/jquery-treeview/jquery.treeview.css" />
		<link id="skinCssUi" type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo $cssfile?>/jquery-ui.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css,login/geonames.css" />
		<link id="skinCss" type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo $cssfile?>/prodcolor.css" />

<?php } else { ?>
		<link type="text/css" rel="stylesheet" href="/skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/colorpicker/css/colorpicker.css" />
		<link id="skinCss" type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo $cssfile?>/jquery-ui.css,skins/prod/<?php echo $cssfile?>/prodcolor.css" />
<?php } ?>

<!--[if IE 7]>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/ie7.css" />
<![endif]-->
<!--[if IE 8]>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/ie8.css" />
<![endif]-->
		<style id="color_selection">
			
			.effectiveZone .diapo.selected, .effectiveZone .list.selected, .effectiveZone .list.selected .diapo
			{
			    COLOR: #<?php echo user::getPrefs('fontcolor-selection') != '' ? user::getPrefs('fontcolor-selection') : 'FFFFFF'; ?>;
			    BACKGROUND-COLOR: #<?php echo user::getPrefs('background-selection-disabled') != '' ? user::getPrefs('background-selection-disabled') : '333333'; ?>;
			}
			.activeZone .diapo.selected,#reorder_box .diapo.selected, #EDIT_ALL .diapo.selected, .activeZone .list.selected, .activeZone .list.selected .diapo
			{
			    COLOR: #<?php echo user::getPrefs('fontcolor-selection') != '' ? user::getPrefs('fontcolor-selection') : 'FFFFFF'; ?>;
			    BACKGROUND-COLOR: #<?php echo user::getPrefs('background-selection') != '' ? user::getPrefs('background-selection') : '404040'; ?>;
			}
		</style>

	</head>
	

	<body>
    		<div style="position:absolute;top:0;left:0;right:0;bottom:0;background-color:#1a1a1a;z-index:32766;">
				<div id="loader" style="top:200px;margin:0 auto;-moz-border-radius:5px;-webkit-border-radius:5px;background-color:#CCCCCC;position:relative;margin:0 auto;text-align:center;left:color:black;width:400px;height:100px;padding:20px;;z-index:32767;">
					<div style="margin:0 10px 10px;font-family:Helvetica,Arial,sans-serif;font-size:18px;color:#1A1A1A;text-align:left;"><?php echo _('phraseanet::Nom de l\'application');?></div>
					<div style="text-align:center;"><?php echo _('Chargement');?></div>
					<div style="width:220px;height:19px;margin:20px auto;">
						<div id="loader_bar" style="height:19px;width:10%;background-image:url(/skins/icons/main-loader.gif)"></div>
					</div>
				</div>
			</div>
		<div id="maincontainer" class="PNB">					
			<?php
	
	$events_mngr = eventsmanager::getInstance();
	
	$twig = new supertwig();
	$twig->display('common/menubar.twig', array('module'=>'prod','events'=>$events_mngr));
			?>
			<div id="headBlock" class="PNB" style="height:70px;bottom:auto;top:30px;">
				<div id="queryBox">
					<table style="width:100%;height:60px;padding:5px 0;" cellspacing="0" cellpadding="0">
						<tr valign="top">
							<td style="width:10px;">&nbsp;</td>
							<td style="width:375px">
								<div id="alternateTrigger" class="ui-corner-all" style="height: 60px;background-color:1a1a1a;">
									<table style="height:35px;">
										<tr style="height:20px;">
											<td colspan="2" style="text-align:right;" id="qry_buttons">
												<form style="margin:0;padding:0;" onsubmit="newSearch();return false;">
													<input autocomplete="off" id="EDIT_query" type="text" style="margin-right:0;padding-right:0;width:270px;" name="qry" value="<?php echo str_replace('"','&quot;',$start_page_query);?>">
													<input id="search_submit" type="submit" value="<?php echo _('boutton::rechercher');?>" class="input-button" />
												</form>
											</td>
										</tr>
										<tr style="height:15px;">
											<td style="text-align:left;white-space:nowrap;">
											<?php
												if(GV_multiAndReport)
												{
													$sel1= "";
													$sel2 = "";
													(GV_defaultQuery_type==0?$sel1=" checked='checked'":$sel2=" checked='checked'");
												
													echo '<input type="radio" value="0" class="mode_type_doc_reg checkbox" name="search_type" '.$sel1.' id="mode_type_doc"/><label for="mode_type_doc">'. _('phraseanet::type:: documents').'</label>'.
																'<input type="radio" value="1" class="mode_type_doc_reg checkbox" name="search_type" '.$sel2.' id="mode_type_reg"/><label for="mode_type_reg">'. _('phraseanet::type:: reportages').'</label>';
												
												}else{
													echo '<input type="hidden" value="0" name="search_type" />';
												}
											?>
												<select name="recordtype" id="recordtype_sel">
													<option value=""><?php echo _('Tout type');?></option>
													<option value="image">Image</option>
													<option value="video">Video</option>
													<option value="audio">Audio</option>
													<option value="document">Document</option>
													<option value="flash">Flash</option>
												</select>
											</td>
											<td style="text-align:right;width:auto;">
												<a href="#" onmousedown="advSearch(event);return false;"> &gt; <?php echo _('prod:: recherche avancee');?></a>
											</td>
										</tr>
									</table>
								</div>
							</td>
							<td>
								<div class="tools" style="width:100%;overflow-x:auto;overflow-y:hidden;">
									<table>
										<tr>
											<td>
												<div class="toolbutton" id="TOOL_disktt_btn">
													<div class="toolbuttonimg" id="TOOL_disktt">
														&nbsp;
													</div>
													<div class="toolbuttonlabel">
														<?php echo _('action : exporter')?>
													</div>
												</div>
											</td>
										<?php
										
										
										if($user->_global_rights['modifyrecord'] == true)
										{
										?>
											<td>
												<div class="toolbutton" id="TOOL_ppen_btn">
													<div class="toolbuttonimg" id="TOOL_ppen">
														&nbsp;
													</div>
													<div class="toolbuttonlabel">
														<?php echo _('action : editer');?>
													</div>
												</div>
											</td>
										<?php
										}
									
									
										if($user->_global_rights['changestatus'] == true)
										{
										?>
											<td>
												<div class="toolbutton" id="TOOL_chgstatus_btn">
													<div class="toolbuttonimg" id="TOOL_chgstatus">
														&nbsp;
													</div>
													<div class="toolbuttonlabel">
														<?php echo _('action : status');?>
													</div>
												</div>
											</td>
										<?php
										}

										if($user->_global_rights['deleterecord'] == true && $user->_global_rights['addrecord'] == true)
										{
										?>
											<td>
												<div class="toolbutton" id="TOOL_chgcoll_btn">
													<div class="toolbuttonimg" id="TOOL_chgcoll">
														&nbsp;
													</div>
													<div class="toolbuttonlabel">
														<?php echo _('action : collection');?>
													</div>
												</div>
											</td>
										<?php
										}
										?>
										
										<?php
						
										if($user->_global_rights['push'] == true)
										{
										?>
											<td>
												<div class="toolbutton" id="TOOL_pushdoc_btn">
													<div class="toolbuttonimg" id="TOOL_pushdoc">
														&nbsp;
													</div>
													<div class="toolbuttonlabel">
														<?php echo _('action : push');?>
													</div>
												</div>
											</td>
										<?php
										}
										?>
											<td>
												<div class="toolbutton" id="TOOL_print_btn">
													<div class="toolbuttonimg" id="TOOL_print">
														&nbsp;
													</div>
													<div class="toolbuttonlabel">
														<?php echo _('action : print');?>
													</div>
												</div>
											</td>
										<?php
				
										if($user->_global_rights['doctools'] == true)
										{
										?>
											<td>
												<div class="toolbutton" id="TOOL_imgtools_btn">
													<div class="toolbuttonimg" id="TOOL_imgtools">
														&nbsp;
													</div>
													<div class="toolbuttonlabel">
														<?php echo _('action : outils');?>
													</div>
												</div>
											</td>
										<?php
										}

										?>
											<td>
												<div class="toolbutton" id="TOOL_trash_btn">
													<div class="toolbuttonimg" id="TOOL_trash">
														&nbsp;
													</div>
													<div class="toolbuttonlabel">
														<?php echo _('action : supprimer');?>
													</div>
												</div>
											</td>
										</tr>
										</table>
									</div>
								</td>
							</tr>
						</table>
					</div>
				</div>
				<div id="desktop" class="PNB" style="top:100px;" >
					<?php
					$ratio = (float)user::getPrefs('search_window');
					$ratio = $ratio > 0 ? $ratio : 0.333;
					
					$w1 = round(100 * $ratio);
					$w2 = 100 - $w1;
					?>
					<div id="idFrameC" class="PNB" style="right:auto;width:<?php echo $w1?>%;">
						<div class="tabs ui-tabs">
							<ul class="PNB ui-tabs-nav ui-helper-reset" style="bottom:auto;height:30p;">
								<li class="ui-tabs-selected ui-corner-top"><a href="#baskets"><?php echo _('phraseanet:: panier');?><span id="basket_menu_trigger" style="cursor:pointer;padding:3px;font-size:12px;">&#9660;</span></a></li>
								<?php if(GV_thesaurus){ ?>
								<li>
									<a href="#proposals">
										<img class="activeproposals" src="/skins/icons/button-red.png" style="display:none;vertical-align:middle;margin:0 3px;" title="<?php echo _('phraseanet:: propositions');?>" /><?php echo _('phraseanet:: propositions')?>
									</a>
								</li>
								<li><a href="#thesaurus_tab"><?php echo _('phraseanet:: thesaurus');?></a></li>
								<?php } ?>
							</ul>
							<div id="baskets" class="PNB ui-tabs-panel ui-accordion effectiveZone" style="top:30px;">
								<?php echo baskets(null) ?>
							</div>
							<?php if(GV_thesaurus){ ?>
								<div id="proposals" class="PNB ui-tabs-panel ui-tabs-hide" style="top:30px;" ondblclick='return(thesau_dblclickThesaurus(event));' onclick='return(thesau_clickThesaurus(event));'></div>
								<div id="thesaurus_tab" class="PNB ui-tabs-panel ui-tabs-hide" style="top:30px;">
								
								<?php include 'thesaurus_tab.php';?>
								</div>
							<?php } ?>
						</div>
						
						<div id="basket_menu" class="context-menu context-menu-theme-vista" style="display:none;">
							<ul style="list-style-type:none;margin:0;padding:0">
								<li class="context-menu-item">
									<div class="context-menu-item-inner" onclick="newTemp();">
										<img style="cursor:pointer;" src="/skins/icons/mtadd_0.gif" title="<?php echo _('action:: nouveau panier')?>" /> 
										<?php echo _('action:: nouveau panier');?>
									</div>
								</li>
								<li class="context-menu-item">
									<div class="context-menu-item-inner" onclick="refreshBaskets('current','date');">
										<img style="cursor:pointer;" src="/skins/icons/cal.png" title="<?php echo  _('phraseanet:: tri par date');?>" /> 
										<?php echo  _('phraseanet:: tri par date');?>
									</div>
								</li>
								<li class="context-menu-item">
									<div class="context-menu-item-inner" onclick="refreshBaskets('current','name');">
										<img style="cursor:pointer;" src="/skins/icons/alpha.png" title="<?php echo  _('phraseanet:: tri par nom');?>" /> 
										<?php echo  _('phraseanet:: tri par nom');?>
									</div>
								</li>
								<li class="context-menu-item">
									<div class="context-menu-item-inner" onclick="basketPrefs();">
										<?php echo _('Preferences'); ?>
									</div>
								</li>
							</ul>
						</div>
					</div>
					<div class="PNB" id="rightFrame" style="left:auto;width:<?php echo $w2;?>%;">
						<div id="idFrameT" class="PNB" style="height:40px;bottom:auto;">
							<div class="bloc ui-corner-all">
								<table style="width:100%;" valign="middle" style="white-space:nowrap;">
									<tr style="height:16px;">
										<td style="text-align:left;">
											<a href="#" onclick="lookBox(this,event);return false;"><?php echo _('Preferences');?> </a>
											<span id="tool_results">
											
											</span>
										</td>
										<td style="min-width:200px;text-align:right;" rowspan="2" id="tool_navigate">
											
										</td>
									</tr>
									<tr style="height:16px;">
										<td>
											<div>
												<?php echo _('reponses:: selectionner');?>
												<a href="#" class="answer_selector all_selector"><?php echo _('reponses:: selectionner tout');?></a> 
												<a href="#" class="answer_selector none_selector"><?php echo _('reponses:: selectionner rien');?></a> 
	<!--											<a href="#" class="answer_selector starred_selector"><?php echo _('reponses:: selectionner etoile');?></a> -->
												<a href="#" class="answer_selector image_selector"><?php echo _('phraseanet::type:: images');?></a> 
												<a href="#" class="answer_selector document_selector"><?php echo _('phraseanet::type:: documents');?></a> 
												<a href="#" class="answer_selector video_selector"><?php echo _('phraseanet::type:: videos');?></a> 
												<a href="#" class="answer_selector audio_selector"><?php echo _('phraseanet::type:: audios');?></a> 
												
												<span id="tool_dyn">
												
												</span>
											</div>
										</td>
									</tr>
								</table>
								</div>
						</div>
						<div id="idFrameA" class="PNB" style="top:50px;">
							<div id="answers" class="<?php echo in_array($start_page, array('QUERY','LAST_QUERY')) ? 'loading' : ''; ?> PNB effectiveZone" style="overflow-x:hidden;overflow-y:auto;">
							<?php
							
							if(!in_array($start_page, array('QUERY','LAST_QUERY')))
							{
								echo phrasea::getHome($start_page, 'prod');
							}

							?>
							</div>
						</div>
					</div>
				
				</div>
			
				<div>
					<form style="visibility:hidden;display:none;" name="formDownload" action="/include/download.php" method="post" target="HFrameZ" >
						<input type="hidden" name="act" value="DOWNLOAD" />
						<input type="hidden" name="lst" value="" />
						<input type="hidden" name="fromchu" value="" />
						<input type="hidden" name="type" value="" />
						<input type="checkbox" name="obj[]" value="document" />
						<input type="checkbox" name="obj[]" value="preview" />
						<input type="checkbox" name="obj[]" value="caption" />
						<input type="hidden" name="SSTTID" value="" />
					</form>
					<form style="visibility:hidden;display:none;" name="formZ" action="???" method="post">
						<input type="hidden" name="act" value="???" />
						<input type="hidden" name="p0" value="?" />
						<input type="hidden" name="p1" value="?" />
					</form>
					<div id="idFrameW0">
						<div class="pbarBck">
							<div id="idProgressBar0" class="pbarFrt" style="width:0%;"></div>
						</div>
					</div>
				</div>
				<div id="MESSAGE"></div>
				<div id="MESSAGE-push"></div>
				<div id="MESSAGE-publi"></div>
				<div id="DIALOG"></div>
				<div id="keyboard-dialog" class="<?php echo user::getPrefs('keyboard_infos') != '0' ? 'auto' : ''?>" style="display:none;" title="<?php echo _('raccourci :: a propos des raccourcis claviers');?>">
			
				<div>
					<h1><?php echo _('Raccourcis claviers en cours de recherche : ');?></h1>
					<ul>
						<li><?php echo _('Raccourcis:: ctrl-a : tout selectionner ');?></li>
						<li><?php echo _('Raccourcis:: ctrl-p : imprimer la selection ');?></li>
						<li><?php echo _('Raccourcis:: ctrl-e : editer la selection ');?></li>
						<li><?php echo _('Raccourcis::fleche gauche : page precedente ');?></li>
						<li><?php echo _('Raccourcis::fleche droite : page suivante ');?></li>
						<li><?php echo _('Raccourcis::fleche haut : scroll vertical ');?></li>
						<li><?php echo _('Raccourcis::fleche bas : scroll vertical ');?></li>
					</ul>
				</div>
				<div>
					<h1><?php echo _('Raccourcis claviers de la zone des paniers : ');?></h1>
					<ul>
						<li><?php echo _('Raccourcis:: ctrl-a : tout selectionner ');?></li>
						<li><?php echo _('Raccourcis:: ctrl-p : imprimer la selection ');?></li>
						<li><?php echo _('Raccourcis:: ctrl-e : editer la selection ');?></li>
					</ul>
				</div>
						
				<div>
					<h1><?php echo _('Raccourcis claviers en cours de editing : ');?></h1>
					<ul>
						<li><?php echo _('Raccourcis::tab/shift-tab se ballade dans les champs ');?></li>
					</ul>
				</div>
						
				<div>
					<h1><?php echo _('Raccourcis claviers en cours de preview : ');?></h1>
					<ul>
						<li><?php echo _('Raccourcis::fleche gauche : en avant ');?></li>
						<li><?php echo _('Raccourcis::fleche gauche : en arriere ');?></li>
						<li><?php echo _('Raccourcis::espace : arreter/demarrer le diaporama ');?></li>
					</ul>
				</div>
						
				<div>
					<ul>
						<li><?php echo _('Vous pouvez quitter la plupart des fenetres survolantes via la touche echap ');?></li>
					</ul>
				</div>
				<div>
					<ul>
						<li><input id="keyboard-stop" type="checkbox"/><label for="keyboard-stop"><?php echo _('raccourcis :: ne plus montrer cette aide') ?></label></li>
					</ul>
				</div>
		
			</div>
		
			<?php 
			
			echo cgus::askAgreement();
			?>
			<div id="publish-dialog" style="display:none;">
					<?php //echo p4publi::getForm()?>
			</div>
			<div id="basket-dialog" style="display:none;">
					<form onsubmit="return false;">
						<div><label><?php echo _('panier:: nom');?></label></div><div><input style="width:100%;margin:10px 0;" type="text" name="name" value=""/></div>
						<div><label><?php echo _('panier:: description');?></label></div><div><textarea style="width:100%;height:200px;" id="basket-desc" name="description"></textarea></div>
						<div></div>
					</form>
			</div>
			<div style="display:none;position:relative;" id="look_box" title="<?php echo _('Preferences'); ?>" >
				<div class="tabs">
					<ul>
						<li><a href="#look_box_screen"><?php echo _('Affichage')?></a></li>
						<li><a href="#look_box_settings"><?php echo _('Configuration')?></a></li>
					</ul>
					<div id="look_box_screen">
						<div class="box">
							<div class="" style="float:left;width:49%;">
								<h1><?php echo _('Mode de presentation');?></h1>
								<input onchange="setPref('view',$(this).val())" name="view_type" type="radio" class="checkbox" value="thumbs" id="thumbs_view" <?php echo ($mod=='thumbs'?'checked="checked"':'')?>/><label for="thumbs_view"><?php echo _('reponses:: mode vignettes');?></label>
								<input onchange="setPref('view',$(this).val())" name="view_type" type="radio" class="checkbox" value="list" id="list_view" <?php echo ($mod=='list'?'checked="checked"':'')?>/><label for="list_view"><?php echo _('reponses:: mode liste');?></label>
							</div>
							<div style="float:left;width:49%;">
								<h1><?php echo _('Theme');?></h1>
								<?php
								if(count($css)>0)
								{
									foreach($css as $color=>$file)
									{
										echo '<div title="'._('Selecteur de theme').'" class="colorpicker_box" onclick="setCss(\''.$color.'\')" style="width:16px;height:16px;background-color:#'.$color.';">&nbsp;</div>';
									}
								}
								?>
							</div>
						</div>
						<div class="box">
							<h1><?php echo _('Presentation de vignettes');?></h1>
							<div>
								<input onchange="setPref('rollover_thumbnail',$(this).val())" name="rollover_thumbnail" type="radio" class="checkbox" value="caption" id="rollover_caption" <?php echo ($rollover_thumbnail=='caption'?'checked="checked"':'')?>/>
								<label for="rollover_caption"><?php echo _('Iconographe (description au rollover)');?></label>
							</div>
							<div>
								<input onchange="setPref('rollover_thumbnail',$(this).val())" name="rollover_thumbnail" type="radio" class="checkbox" value="preview" id="rollover_preview" <?php echo ($rollover_thumbnail=='preview'?'checked="checked"':'')?>/>
								<label for="rollover_preview"><?php echo _('Graphiste (preview au rollover)');?></label>
							</div>
						</div>
						<div class="box">
							<h1><?php echo _('Informations techniques');?></h1>
							<div>
								<input onchange="setPref('technical_display',$(this).val())" name="technical_display" type="radio" class="checkbox" value="1" id="technical_show" <?php echo ($technical_display=='1'?'checked="checked"':'')?>/>
								<label for="technical_show"><?php echo _('Afficher');?></label>
							</div>
							<div>
								<input onchange="setPref('technical_display',$(this).val())" name="technical_display" type="radio" class="checkbox" value="group" id="technical_group" <?php echo ($technical_display=='group'?'checked="checked"':'')?>/>
								<label for="technical_group"><?php echo _('Afficher dans la notice');?></label>
							</div>
							<div>
								<input onchange="setPref('technical_display',$(this).val())" name="technical_display" type="radio" class="checkbox" value="0" id="technical_hide" <?php echo ($technical_display=='0'?'checked="checked"':'')?>/>
								<label for="technical_hide"><?php echo _('Ne pas afficher');?></label>
							</div>
						</div>
						<div class="box">
							<h1><?php echo _('Type de documents');?></h1>
							<div>
								<input onchange="setPref('doctype_display',($(this).attr('checked') ? '1' :'0'))" name="doctype_display" type="checkbox" class="checkbox" value="1" id="doctype_display_show" <?php echo ($doctype_display=='0'?'':'checked="checked"')?>/>
								<label for="doctype_display_show"><?php echo _('Afficher une icone');?></label>
							</div>
						</div>
						<div class="box">
							<div class="" style="float:left;width:49%;">
								<h1><?php echo _('reponses:: images par pages : ');?></h1>
								<div class="box">
									<div id="nperpage_slider" class="ui-corner-all" style="width:100px;display:inline-block;"></div><input type="text" readonly style="width:35px;" value="<?php echo $nppage?>" id="nperpage_value"/>
								</div>
							</div>
							<div style="float:left;width:49%;">
								<h1><?php echo _('reponses:: taille des images : ');?></h1>
								<div class="box">
									<div id="sizeAns_slider" class="ui-corner-all" style="width:100px;display:inline-block;"></div><input type="hidden" value="<?php echo $th_size?>" id="sizeAns_value"/>
								</div>
							</div>
						</div>
						<div class="box">
							<h1><?php echo _('Couleur de selection');?></h1>
			                <div id="backcolorpickerHolder" class="colorpickerbox">
			                	<div class="submiter"><?php echo _('choisir') ?></div>
			                </div>
						</div>
					
					
					</div>
					<div id="look_box_settings">
						<div class="box">
							<div class="" style="float:left;width:100%;">
								<h1><?php echo _('Affichage au demarrage');?></h1>
								<?php 
								$start_page_pref = user::getPrefs('start_page');
								?>
								<div class="box" >
									<select style="width:150px;" name="start_page" onchange="start_page_selector();">
										<option value="LAST_QUERY" <?php echo $start_page_pref == 'LAST_QUERY' ? 'selected' : '';?>><?php echo _('Ma derniere question');?></option>
										<option value="QUERY" <?php echo $start_page_pref == 'QUERY' ? 'selected' : '';?>><?php echo _('Une question personnelle');?></option>
										<option value="PUBLI" <?php echo $start_page_pref == 'PUBLI' ? 'selected' : '';?>><?php echo _('Publications');?></option>
										<option value="HELP" <?php echo $start_page_pref == 'HELP' ? 'selected' : '';?>><?php echo _('Aide');?></option>
									</select>
									<input style="width:120px;display:<?php echo $start_page_pref == 'QUERY' ? 'inline' : 'none'?>;" type="text" name="start_page_value" value="<?php echo str_replace('"','&quot;',$start_page_query);?>" />
									<input onclick="set_start_page();" type="button" value="<?php echo str_replace('"','&quot;',_('boutton::valider'));?>" />
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		
	
			<script type="text/javascript">
			<?php include 'thesaurus.php';?>
			</script>
	<?php if(USE_MINIFY_JS) { ?>		
			<script type="text/javascript" src="/include/minify/g=prod"></script>
			<script type="text/javascript" src="/include/minify/f=include/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>
	<?php } else { ?>
			<script type="text/javascript" src="/include/swfobject/swfobject.js"></script>
			<script type="text/javascript" src="/include/jslibs/jquery-1.4.4.js"></script>
			<script type="text/javascript" src="/include/jslibs/jquery-ui-1.7.2.js"></script>
			<script type="text/javascript" src="/include/jslibs/jquery.cookie.js"></script>
			<script type="text/javascript" src="/include/jquery.common.js"></script>
			<script type="text/javascript" src="/include/jslibs/jquery.form.2.49.js"></script>
			<script type="text/javascript" src="/prod/page0.js"></script>
			<script type="text/javascript" src="/include/jslibs/json2.js"></script>
			<script type="text/javascript" src="/include/jslibs/jquery.sprintf.1.0.3.js"></script>
			<script type="text/javascript" src="/include/jquery.tooltip.js"></script>
			<script type="text/javascript" src="/include/jquery.p4.preview.js"></script>
			<script type="text/javascript" src="/prod/jquery.edit.js"></script>
			<script type="text/javascript" src="/include/jslibs/jquery.color.animation.js"></script>
			<script type="text/javascript" src="/include/jslibs/jquery.contextmenu_scroll.js"></script>
			<script type="text/javascript" src="/include/jquery-treeview/jquery.treeview.js"></script>
			<script type="text/javascript" src="/include/jquery-treeview/jquery.treeview.async.js"></script>

			<script type="text/javascript" src="/include/minify/f=include/tinymce/jscripts/tiny_mce/jquery.tinymce.js"></script>
	<?php } ?>
			<script type="text/javascript">


			$(document).ready(function(){
				<?php 
						$prefs = 'p4.reg_delete='.user::getPrefs('warning_on_delete_story')?'true':'false'.';';
						echo $prefs;
				?>
			});
			
			function sessionactive(){
				$.ajax({
					type: "POST",
					url: "/include/updses.php",
					dataType: 'json',
					data: {
						app : 1,
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
							manageSession(data, true);
						var t = 20000;
						if(data.apps && parseInt(data.apps)>1)
							t = Math.round((Math.sqrt(parseInt(data.apps)-1) * 1.3 * 20000));
						window.setTimeout("sessionactive();", t);
						return;
					}
				})
			};
			
			function setCss(color)
			{
				$('#skinCssUi').attr('href','/include/minify/f=skins/prod/'+color+'/jquery-ui.css');
				$('#skinCss').attr('href','/include/minify/f=skins/prod/'+color+'/prodcolor.css');
				$.post("prodFeedBack.php", {
					action: "CSS",
					color: color,
					t: Math.random()
				}, function(data){
					return;
				});
				if ($.browser.msie && $.browser.version == '6.0')
					$('select').hide().show();
			}


			<?php 
			if(trim(GV_bitly_user) !== '' && trim(GV_bitly_key) !== '')
			{
				?>
				$(document).ready(function(){
						$('#bitly_loader').attr("src","http://bit.ly/javascript-api.js?version=latest&login=<?php echo GV_bitly_user?>&apiKey=<?php echo GV_bitly_key?>");
				});
				
			<?php
			}
			?>
			
			
			</script>
			<script type="text/javascript" id="bitly_loader"></script>
			
	
			<?php 
			$browser = browser::getInstance();
			
			if($browser->isNewGeneration() !== true)
			{
				
				?>
				<div id="wrongBrowser" style="display:none;text-align:center;width:100%;background:#00a8FF;font-size:14px;font-weight:bold;">
					<div>
					<?php echo _('phraseanet::browser not compliant').',<br/>',_('phraseanet::recommend browser')?>
					</div>
					<div style="height:30px;text-align:center;margin-top:15px;width:950px;margin-right:auto;margin-left:auto;">
							<?php
							if(mb_strtolower(substr($browser->getPlatform(),0,7)) == 'windows')
							{
							?>
								<span style="margin:0 10px;padding:0;white-space:nowrap;height:25px;"><img style="vertical-align:middle;" src="/login/img/firefox.png"/> <a href="http://www.mozilla.com/firefox/" target="_blank">Mozilla Firefox 3</a></span>
								<span style="margin:0 10px;padding:0;white-space:nowrap;height:25px;"><img style="vertical-align:middle;" src="/login/img/safari.png"/> <a href="http://www.apple.com/safari/" target="_blank">Apple Safari 3</a></span>
								<span style="margin:0 10px;padding:0;white-space:nowrap;height:25px;"><img style="vertical-align:middle;" src="/login/img/chrome.png"/> <a href="http://www.google.com/chrome/" target="_blank">Google Chrome 1</a></span>
							<?php
							}
							elseif(mb_strtolower(substr($browser->getPlatform(),0,7)) == 'apple')
							{
							?>
								<span style="margin:0 10px;padding:0;white-space:nowrap;height:25px;"><img style="vertical-align:middle;" src="/login/img/firefox.png"/> <a href="http://www.mozilla.com/firefox/" target="_blank">Mozilla Firefox 3</a></span>
								<span style="margin:0 10px;padding:0;white-space:nowrap;height:25px;"><img style="vertical-align:middle;" src="/login/img/safari.png"/> <a href="http://www.apple.com/safari/" target="_blank">Apple Safari 3</a></span>
								<span style="margin:0 10px;padding:0;white-space:nowrap;height:25px;"><img style="vertical-align:middle;" src="/login/img/opera.png"/> <a href="http://www.opera.com/download/" target="_blank">Opera 9</a></span>
							<?php
							}
							else
							{
								?>
								<span style="margin:0 10px;padding:0;white-space:nowrap;height:25px;"><img style="vertical-align:middle;" src="/login/img/firefox.png"/> <a href="http://www.mozilla.com/firefox/" target="_blank">Mozilla Firefox 3</a></span>
								<?php
							}
							?>
					</div>
				</div>
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
		</div>
		<div id="PREVIEWBOX" class="PNB" style="overflow:hidden;">
			<div class="PNB" style="right:180px;">
				<div id="PREVIEWTITLE" style="height:55px;bottom:auto;" class="PNB">
					<div class="PNB10 ui-corner-top" id='PREVIEWTITLEWRAPPER'>
						<span id="SPANTITLE" class="PNB10"> </span>

					</div>
				</div>
				<div class="PNB" style="top:55px;">
					<div id="PREVIEWLEFT" class="preview_col PNB" style="width:60%;right:auto;overflow:hidden;">
						<div id="PREVIEWCURRENT" class="ui-corner-bottom PNB10" style="top:0;height:116px;bottom:auto;">
							<div id="PREVIEWCURRENTGLOB" style="">
							</div>
						</div>
						<div id="PREVIEWIMGCONT" class="dblclick preview_col_cont PNB10" style="overflow:hidden;top:146px;"></div>
					</div>
					<div class="gui_vsplitter gui_vsplitter2" style="left:60%;"></div> 
					
					<div id="PREVIEWRIGHT" class="preview_col PNB" style="top:7px;left:60%;overflow:hidden;">
						<div id="PREVIEWIMGDESC" class="PNB10">
							<ul class="PNB" style="height:30px;bottom:auto;">
								<li><a href="#PREVIEWIMGDESCINNER-BOX"><?php echo _('preview:: Description');?></a></li>
								<li><a href="#HISTORICOPS-BOX"><?php echo _('preview:: Historique');?></a></li>
								<?php 
								if(GV_google_api)
								{
								?>
								<li><a href="#popularity-BOX"><?php echo _('preview:: Popularite');?></a></li>
								<?php 
								}
								?>
							</ul>
							<div id="PREVIEWIMGDESCINNER-BOX" class="descBoxes PNB">
								<div id="PREVIEWIMGDESCINNER" class="PNB10">
								</div>
							</div>
							<div id="HISTORICOPS-BOX" class="descBoxes PNB">
								<div id="HISTORICOPS" class="PNB10">
								</div>
							</div>
							<?php 
							if(GV_google_api)
							{
							?>
							<div id="popularity-BOX" class="descBoxes PNB">
								<div id="popularity" class="PNB10">
								</div>
							</div>
							<?php 
							}
							?>
						</div>
					</div>
				</div>
			</div>
			<div class="PNB" style="width:180px;left:auto;">
				<div class="PNB10 ui-corner-all" style="height:20px;">
					<div onclick="closePreview();" style="cursor:pointer;color:#CCCCCC;font-size:14px;font-weight:bold;text-align:center;text-decoration:underline;">
						<?php echo _('boutton::fermer')?>
					</div>
				
				</div>
				<div id="PREVIEWOTHERS" style="top:40px;" class="PNB10 ui-corner-all">
					<div id="PREVIEWOTHERSINNER" style=""></div>
				</div>
			</div>
		</div>				
		<div id="EDITWINDOW" style="display:none;" class="PNB">
			<div id="idFrameE" style="position:absolute;top:10px;left:10px;right:10px;bottom:10px;">
			
			</div>
		</div>
		
		<div id="adv_search" style="overflow:hidden;">
							
						<?php
						$inc = 0;
						$outB = '<div class="sbasglob ui-corner-all">
						<div style="text-align:center;margin:0 10px;">
							<input class="input-button" type="button" value="'._('boutton:: selectionner toutes les bases').'" onclick="checkBases(true);"/>
							<input class="input-button" type="button" value="'._('boutton:: selectionner aucune base').'" onclick="checkBases(false);"/>
						</div>';
						$status = $fields = $dates = array();
						$first = true;
						
						$bas_order = phrasea::getBasesOrder();
						$sbase = array();
						foreach($ph_session["bases"] as $base)
						{
							
							$collections = array();
							
							$inc = $base['sbas_id'];
							
							$sbase[$inc] = '';
							
								
							$sbase[$inc] .= "<div class='sbas_list sbas_".$inc."'><input type='hidden' name='reference' value='".$inc."'/>
								<div class='clksbas' style='text-align:center;'>
									<input type='checkbox' checked style='display:none' id='sbasChkr_".$inc."__UNIQUEID__' class='sbasChkr_".$inc."' />
									<label for='sbasChkr_".$inc."__UNIQUEID__'  onclick='clksbas(\"".$inc."\", $(\"#sbasChkr_".$inc."__UNIQUEID__\"));return false;'>
										<span>".phrasea::sbas_names($base["sbas_id"])."</span>".
										"<span class='infos_sbas_".$inc."'>".count($base["collections"])."/".count($base["collections"])."</span>
									</label>
								</div>";
									
							$sbase[$inc] .= "<div class='sbascont sbascont_".$base['sbas_id']."'>
								<ul style='list-style-type:none;padding:0;margin:0;' class='basChild_".$inc."'>";
													
							foreach($base["collections"] as $coll)
							{
								$selected = ($searchSet && isset($searchSet->bases) && isset($searchSet->bases->$base['sbas_id'])) ?(in_array($coll["base_id"],$searchSet->bases->$base['sbas_id'])?true:false) : true;
								
								$collections[$coll['base_id']] = "<li style='margin:0 5px;' class='clkbas'>
										<input class='ck_".$coll["base_id"]." checkbas checkbox' onclick='cancelEvent(event);return false;' onmousedown='infoSbas(this, ".$inc.", false, event);return false;' id='ck_".$coll["base_id"]."__UNIQUEID__' type='checkbox' name='bas[]' value='".$coll["base_id"]."' ".($selected ? 'checked' : '')." />
										<label style='cursor:pointer;' onclick='infoSbas($(\"#ck_".$coll["base_id"]."__UNIQUEID__\"),".$inc.", false, event);return false;' for='ck_".$coll["base_id"]."__UNIQUEID__' class='ck_".$coll["base_id"]." ".($selected ? 'selected' : '')."'>".collection::getLogo($coll['base_id']).' '.$coll["name"]."</label>
									</li>";
							}
							
							foreach($bas_order as $coll)
							{
								if(isset($collections[$coll['base_id']]))
								{
									$sbase[$inc] .= $collections[$coll['base_id']];
								}
							}
								
														
							$sbase[$inc] .= "</ul></div>";
																				
							if(($sxe = databox::get_sxml_structure($inc)) != false)
							{
								if($sxe->description)
								{
									foreach($sxe->description->children() as $f=>$field)
									{
										if($field['type'] == 'date')
										{
											if(isset($dates[$f]))
												$dates[$f][] = $base['sbas_id'];
											else
												$dates[$f] = array($base['sbas_id']);
										}
										elseif($field['type'] != 'date')
										{
											if(isset($fields[$f]))
												$fields[$f][] = $base['sbas_id'];
											else
												$fields[$f] = array($base['sbas_id']);
										}
									}
									
								}
							}
							$sbase[$inc] .= '</div>';
						}
						if(isset($sbase[$inc]))
							$sbase[$inc] .= '</div>';
								
								
						$outF = '<div class="field_filter">';
						
						$outF .= '<div>'._('Les termes apparaissent dans le(s) champs').'</div>';
						
						$outF .= '<select size="8" multiple onchange="checkFilters(true);" name="fields[]" style="vertical-align:middle;width:100%;">
						<option value="phraseanet--all--fields">'._('rechercher dans tous les champs').'</option>';
						foreach($fields as $field=>$sbas)
						{
							$outF .= '<option class="field_switch field_'.implode(' field_',$sbas).'" value="'.$field.'">'.$field.'</option>';
						}	
						$outF .= '</select></div>';	
								
						
						$status = status::getSearchStatus();
						if(count($status) > 0)
						{
							$outF .= '<div style="margin:5px 0;"><hr/></div><div class="status_filter">';
							
							$outF .= '<div>'._('Status des documents a rechercher)').'</div><table>';
							foreach($status as $n=>$status)
							{
								foreach($status as $s)
								{
									$outF .= '<tr>
												<td> 
													'.(trim($s['imgoff']) != '' ? '<img src="'.$s['imgoff'].'" title="'.str_replace('"','&quot;',$s['labeloff']).'" />' : '').' <input onchange="checkFilters(true);" class="field_switch field_'.implode(' field_',$s['sbas']).'" type="checkbox" value="'.implode('_',$s['sbas']).'" n="'.$n.'" name="status['.$n.'][off][]"/><label>'.$s['labeloff'].'</label>
												</td>
												<td>
													'. (trim($s['imgon']) != '' ? '<img src="'.$s['imgon'].'" title="'.str_replace('"','&quot;',$s['labelon']).'" />' : '').' <input onchange="checkFilters(true);" class="field_switch field_'.implode(' field_',$s['sbas']).'" type="checkbox" value="'.implode('_',$s['sbas']).'" n="'.$n.'" name="status['.$n.'][on][]"/><label>'.$s['labelon'].'</label>
												</td>
											</tr>';
								}
							}
							$outF .= '</table></div>';
						}
						
						
						if(count($dates) > 0)
						{
							$outF .= '<div style="margin:5px 0;"><hr/></div><div class="date_filter">';
							
							$outF .= '<div>'._('Rechercher dans un champ date').'</div><table>';
							
							$outF .= '<tr><td colspan="2"><select name="datefield">';
							$field_list = array();
							foreach($dates as $field=>$sbas)
							{
								$field_list[] = $field;	
									$outF .= '<option onchange="checkFilters(true);" class="field_switch field_'.implode(' field_',$sbas).'" value="'.$field.'">'.$field.'</option>';
								
							}
							$outF .= '<option value="'.implode('|',$field_list).'" selected="selected">'._('rechercher dans tous les champs').'</option>';
							$outF .= '</select></td>
							</tr>';
							
							$outF .= '<tr>
										<td>'._('phraseanet::time:: de'). 
											'<input onchange="checkFilters(true);" class="datepicker" type="text" name="datemin">' .
										'</td>
										<td>' .
											_('phraseanet::time:: a'). 
											'<input onchange="checkFilters(true);" class="datepicker" type="text" name="datemax">
										</td>
									</tr>';
							$outF .= '</table></div>';
						}
								
						
						$blocDet = '<div>';
						$blocDet .= '<input type="hidden" name="ord" id="searchOrd" value="'.PHRASEA_ORDER_DESC.'" />';
								$blocDet .= $outF;
										?>
										
										
				<div class="tabs">
					<ul>
						<li><a href="#adv_filters"><?php echo _('advsearch::filtres') ?></a></li>
						<!--<li><a href="#adv_tech"><?php echo _('advsearch::technique') ?></a></li>-->
					</ul>
					<div id="adv_filters" style="position:relative;">
						<div class="PNB10" style="height:110px;width:120px;">
							<input onclick="reset_adv_search();" type="button" value="<?php echo _('Re-initialiser');?>" class="input-button"/>
						</div>
						<div class="PNB10" style="height:110px;left:100px;">
							<form class="adv_search_bind">
								<div class="search_box">
									<div style="float:left;width:230px;text-align:right;"><label>
										<?php echo _('Chercher tous les mots');?>
									</label></div>
									<input style="width:350px;" name="query_all" type="text" value="" />
								</div>
								<div class="search_box">
									<div style="float:left;width:230px;text-align:right;"><label>
										<?php echo _('Cette expression exacte');?>
									</label></div>
									<input style="width:350px;" name="query_exact" type="text" value="" />
								</div>
								<div class="search_box">
									<div style="float:left;width:230px;text-align:right;"><label>
										<?php echo _('Au moins un des mots suivants');?>
									</label></div>
									<input style="width:350px;" name="query_or" type="text" value="" />
								</div>
								<div class="search_box">
									<div style="float:left;width:230px;text-align:right;"><label>
										<?php echo _('Aucun des mots suivants');?>
									</label></div>
									<input style="width:350px;" name="query_none" type="text" value="" />
								</div>
							</form>
						</div>
						<div class="PNB10" style="top:120px;">
							<div class="innerBox" style="float:left;height:100%;overflow-y:auto;overflow-x:hidden;">
								<table class="colllist" cellspacing="0" cellpadding="0">
									<tr>
										<td valign="top">
										
										<?php echo $outB.str_replace('__UNIQUEID__',mt_rand(10000,99999),implode('<div><hr/></div>',$sbase));?>
										
										</td>
									</tr>
								</table>
							</div>
							<div class="innerBox" style="float:left;height:100%;overflow-y:auto;overflow-x:hidden;">
								<table class="filterlist" cellspacing="0" cellpadding="0">
									<tr>
										<td valign="top">
											<div id="sbasfiltercont" class="ui-corner-all">
											<?php echo $blocDet?>
											</div>
										</td>
								
									</tr>
								</table>
							</div>
						</div>
					</div>
					<!--<div id="adv_tech">
						<div style="margin:10px;">
							<a href="#" onclick="search_doubles();return false;"><?php echo _('recherche:: rechercher les doublons');?></a> <label><?php echo _('aide doublon:: trouve les documents ayant la meme signature numerique');?></label>
						</div>
					</div>-->
				</div>
			</div>
			<div id="alternateSearch" class="PNB" style="z-index:510;">
				<ul class="PNB" style="height:30px;">
					<li><a href="#bases-queries"><?php echo _('recherche :: Bases'); ?></a></li>
					<li><a href="#history-queries"><?php echo _('recherche :: Historique'); ?></a></li>
				<?php
					if(queries::topics_exists())
					{
				?>
					<li><a href="#choosen-topics"><?php echo _('recherche :: Themes'); ?></a></li>
				<?php
					}
				?>
<!--					<li><a href="#popular-queries">Popular</a></li>-->
				</ul>
				<?php
					if(queries::topics_exists())
					{
				?>
				<div id="choosen-topics" class="PNB" style="top:30px;overflow:hidden;">
					<div class="PNB10" style="overflow-y:auto;overflow-x:auto;">
						<?php
							if(GV_client_render_topics == 'popups')
								echo queries::dropdown_topics();
							elseif(GV_client_render_topics == 'tree')
								echo queries::tree_topics();
						?>
					</div>
				</div>
				<?php
					}
				?>
				<div id="bases-queries" class="PNB" style="top:30px;overflow:hidden;">
					<div class="PNB10" style="overflow-y:auto;overflow-x:auto;">
						
										<?php echo $outB.str_replace('__UNIQUEID__',mt_rand(10000,99999),implode('<div><hr/></div>',$sbase));?>
					</div>
				</div>
				<div id="history-queries" class="PNB" style="top:30px;overflow:hidden;">
					<div class="PNB10" style="overflow-y:auto;overflow-x:auto;">
						<?php echo queries::history(); ?>
					</div>
				</div>
<!--				<div id="popular-queries" class="PNB" style="top:30px;overflow:hidden;">-->
<!--					<div class="PNB10" style="overflow-y:auto;overflow-x:auto;">-->
<!---->
<!--					</div>-->
<!--				</div>-->
			</div>
			<div id="basket_preferences" style="display:none;">
				<div class="box">
					<h1><?php echo _('Presentation de vignettes de panier');?></h1>
					<div>
						<input onchange="setPref('basket_status_display',($(this).attr('checked') ? '1' :'0'))" 
								name="basket_status_display" type="checkbox" class="checkbox" value="1" 
								id="basket_status_display" <?php echo ($basket_status_display=='1'?'checked="checked"':'')?>/>
						<label for="basket_status_display"><?php echo _('Afficher les status');?></label>
					</div>
					<div>
						<input onchange="setPref('basket_caption_display',($(this).attr('checked') ? '1' :'0'))" 
								name="basket_caption_display" type="checkbox" class="checkbox" value="1" 
								id="basket_caption_display" <?php echo ($basket_caption_display=='1'?'checked="checked"':'')?>/>
						<label for="basket_caption_display"><?php echo _('Afficher la fiche descriptive');?></label>
					</div>
					<div>
						<input onchange="setPref('basket_title_display',($(this).attr('checked') ? '1' :'0'))" 
								name="basket_title_display" type="checkbox" class="checkbox" value="1" 
								id="basket_title_display" <?php echo ($basket_title_display=='1'?'checked="checked"':'')?>/>
						<label for="basket_title_display"><?php echo _('Afficher le titre');?></label>
					</div>
				</div>
			</div>
			<div id="dialog_dwnl" title="<?php echo _('action : exporter')?>" style="display:none;"></div>
			<div title="<?php echo _('Re-ordonner');?>" id="reorder_dialog" style="position:relative;overflow:hidden;">
				<div id="reorder_options" class="PNB" style="height:30px;bottom:auto;">
				<span><?php echo _('Reordonner automatiquement');?></span> 
				<select id="auto_order">
					<option value=""><?php echo _('Choisir');?></option>
					<option value="default"><?php echo _('Re-initialiser');?></option>
					<option value="title"><?php echo _('Titre');?></option>
				</select>
				<input type="button" onclick="autoorder();return false;" value="<?php echo _('Re-ordonner');?>"/>
				<input type="button" onclick="reverse_order();return false;" value="<?php echo _('Inverser');?>" style="float:right;"/></div>
				<div style="top:30px;overflow:auto;" id="reorder_box" class="PNB loading"></div>
			</div>
			<form id="searchForm" name="search" action="answer.php" method="post" style="display:none;">
				<input type="hidden" name="nba" value="">
				<input type="hidden" name="pag" id="formAnswerPage" value="">
				<input type="hidden" name="sel" value="">
				<input type="hidden" name="qry" value="" />
				<input type="hidden" name="datemin" value="" />
				<input type="hidden" name="datemax" value="" />
				<input type="hidden" name="datefield" value="" />
				<input type="hidden" name="search_type" value="" />
				<input type="hidden" name="recordtype" value="" />
				<div class="status"></div>
				<div class="bases"></div>
				<div class="fields"></div>					
			</form>
			<?php 
				if(in_array($start_page, array('QUERY','LAST_QUERY')))
				{
					?>
					<script type="text/javascript">
						$(document).ready(function(){
							newSearch();
						});
					</script>
					<?php 
				}
			?>
	</body>
	
</html>
<?php
