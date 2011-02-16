<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
phrasea::headers();
require(GV_RootPath."lib/index_utils2.php");

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					'lst'
					,'ACT'
					, 'SSTTID'
					);
if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	if(!($ph_session = phrasea_open_session((int)$ses_id, $usr_id)))
	{
		header("Location: /login/?err=no-session");
		exit();
	}
}
else
{
	header("Location: /login/");
	exit();
}

if($parm['ACT'] === null)
{
?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<base target="_self">
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/jquery-ui.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/prodcolor.css" />
		<script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js,include/jslibs/jquery-ui-1.7.2.js,include/jquery.p4.modal.js"></script>
	</head>
<?php
		
	$usrRight = null;

	$nb_HD_Substit = 0;	
	$nb_Thumb_Substit = 0;	

	#########
	$speedAccesParm = NULL;		// selation sbas vers properties de connexion
	$sbasNames = NULL;
	foreach($ph_session['bases'] as $onebase)
	{					 
		$speedAccesParm[$onebase['sbas_id']] = $onebase;
		$sbasNames[$onebase['sbas_id']] = $onebase['dbname'];
	}
	
	$conn = connection::getInstance();

	$sql = 'SELECT bu.base_id,bu.imgtools FROM (usr u, basusr bu) WHERE u.usr_id="'.$conn->escape_string($usr_id).'" AND bu.usr_id = u.usr_id';
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$usrRight[$row["base_id"]] = $row["imgtools"];
		}			
		$conn->free_result($rs);
	}
	
	
	if($parm['SSTTID']!='' && ($parm['lst']==null || $parm['lst']==''))
	{
		// WARNIING : on demande le print d'un element de la zone temporaire
		$continu = true ;
		$parm['lst'] = ';';
		$sql = 'SELECT s.*, c.*, sb.*, u.mask_and, u.mask_xor' .
		' FROM ssel s, sselcont c, sbas sb, bas b, basusr u' .
		' WHERE (s.usr_id="'.$conn->escape_string($usr_id).'"' .
		' OR (s.public="1" AND s.pub_restrict="0")' .
		' OR (s.public="1" AND s.pub_restrict="1" AND c.base_id IN' .
		' (SELECT base_id FROM basusr WHERE usr_id = "'.$conn->escape_string($usr_id).'" AND actif = "1"))' .
		' ) AND s.ssel_id="'.$conn->escape_string($parm["SSTTID"]) .'" AND s.ssel_id = c.ssel_id' .
		' AND b.base_id = c.base_id AND b.sbas_id = sb.sbas_id AND u.usr_id = "'.$conn->escape_string($usr_id).'" AND u.base_id = b.base_id' .
		' ORDER BY c.ord asc, sselcont_id DESC';
		
		if($rs = $conn->query($sql))
		{
			while( ($row=$conn->fetch_assoc($rs)) && $continu )
			{ 
					$parm['lst'] .= $row['base_id'] . '_' . $row['record_id'].';' ;
			}
			$conn->free_result($rs);
		}
	}
	elseif($parm['SSTTID']!='' && $parm['lst']!=null && $parm['lst']!='')
	{

	}
	
	$seeOngChgDoc = FALSE ;
	
	$tmpbasrec = explode(';',$parm['lst'] );	
	
	$okbrec = array();
	
	//on enleve les reg et on prend les fils
	foreach($tmpbasrec as $k=>$basrec)
	{
		$basrec = explode('_',$basrec);
				
		if(count($basrec) == 2)
			if( function_exists("phrasea_isgrp") && phrasea_isgrp($ses_id, $basrec[0], $basrec[1]) )
			{
					unset($tmpbasrec[$k]);
					$allson = phrasea_grpchild($ses_id,$basrec[0],$basrec[1],GV_sit,$usr_id) ;				
					if($allson)
					foreach($allson as $oneson)
					{
							$tmpbasrec[] = $oneson[0].'_'.$oneson[1] ;
					}
			}
	}
	
	$okbrec = liste::filter($tmpbasrec);
	
	$parm['lst'] = implode(';',$okbrec);	
	$tmpbasrec = $okbrec;
	
	
	
	$tmpbasrec = liste::addtype($tmpbasrec);
	
	foreach($tmpbasrec as $rec)
	{
		$rec = explode('_',$rec);
		$tmpsd = phrasea_subdefs($session->ses_id,$rec[0], $rec[1]);
		
		if(isset($tmpsd['document']['substit'])&& $tmpsd['document']['substit']==1)
			$nb_HD_Substit++;
		
		if(isset($tmpsd['thumbnail']['substit'])&& $tmpsd['thumbnail']['substit']==1)
			$nb_Thumb_Substit++;	
	}
				

	if(count($tmpbasrec)==1)
	{
		$seeOngChgDoc = TRUE ; 
		foreach($tmpbasrec as $basrec)
			$basrec2 = explode('_',$basrec);
	}
	
?>
	<body class="bodyprofile">
		<div class="boxCloser" onclick="parent.hideDwnl();"><?php echo _('boutton::fermer')?></div>
		<div id="tabs">
			<ul>
				<li><a href="#subdefs"><?php echo _('prod::tools: regeneration de sous definitions')?></a></li>
				<li><a href="#image"><?php echo _('prod::tools: outils image')?></a></li>
				<?php if($seeOngChgDoc && GV_seeOngChgDoc)	{ ?>		
					<li><a href="#hdsub"><?php echo _('prod::tools: substitution HD')?></a></li>
				<?php }	if($seeOngChgDoc && GV_seeNewThumb)	{ ?>
					<li><a href="#sdsub"><?php echo _('prod::tools: substitution de sous definition')?></a></li>
				<?php }	if(GV_exiftool != "" && count($tmpbasrec)==1)	{ ?>
					<li><a href="#exiftool"><?php echo _('prod::tools: meta-datas')?></a></li>
				<?php } ?>	
			</ul>

			<div id="subdefs" class="tabBox">
				<form name="formsubdef" id="formsubdef" target="_self" action="newimg.php" method="post">	
					<?php
						if($nb_Thumb_Substit>0)	
						{
							?>
							<div style="color:#A00;"><?php echo _('prod::tools:regeneration: Attention, certain documents ont des sous-definitions substituees.'); ?></div> 
							<input type="checkbox" name="ForceThumbSubstit" id="FTS"><label for="FTS"><?php echo _('prod::tools:regeneration: Forcer la reconstruction sur les enregistrements ayant des thumbnails substituees.'); ?></label><br/>
						<?php
						}
						?>
						<div style="margin:5px 5px 5px 10px;"><h6 style="margin:0;"><?php echo _('prod::tools:regeneration: Reconstruire les sous definitions');?> :</h6>

							<select name="rebuild" style="border:1px solid black;">
								<option selected="selected" value="none"><?php echo _('prod::tools: option : recreer aucune les sous-definitions'); ?></option>
								<option value="all"><?php echo _('prod::tools: option : recreer toutes les sous-definitions'); ?></option>
							</select>
					
							<input type="hidden" name="ACT" value="SEND" />
							<input type="hidden" name="lst" value="<?php echo implode(';',$tmpbasrec);?>" />
							<div style="text-align:center;margin:10px 0;">
								<input type="submit" onclick="parent.hideDwnl();" class="input-button" value="<?php echo _('boutton::valider')?>" />
								<input type="button" class="input-button" value="<?php echo _('boutton::annuler')?>" onclick="parent.hideDwnl();" />
							</div>
						</div>
				</form>
			</div>		
			<div id="image" class="tabBox">
				<form name="formpushdoc" action="rotate.php" method="post">	
					<?php echo _('prod::tools::image: Cette action n\'a d\'effet que sur les images :');?><br/>
					<input type="radio" name="rotation" id="ROTA_90" value="90"><label for="ROTA_90"><?php echo _('prod::tools::image: rotation 90 degres horaire');?></label>
					<br />
					<input type="radio" name="rotation" id="ROTA_C90" value="-90"><label for="ROTA_C90"><?php echo _('prod::tools::image rotation 90 degres anti-horaires')?></label>
					
					<input type="hidden" name="ACT" value="SEND" />
					<input type="hidden" name="lst" value="<?php echo implode(';',$tmpbasrec);?>" />
					<input type="hidden" name="element" value="" />
					<input type="hidden" name="cchd" value="" />
					<div style="text-align:center;margin:10px 0;">
						<input type="button" class="input-button" value="<?php echo _('boutton::valider')?>" onclick="document.forms.formpushdoc.submit();" />
						<input type="button" class="input-button" value="<?php echo _('boutton::annuler')?>" onclick="parent.hideDwnl();" />
					</div>
				</form>
			</div>		
		
			<?php if($seeOngChgDoc && GV_seeOngChgDoc) { ?>	
		
			<div id="hdsub" class="tabBox">
				<br />
				<form name="formchgHD" action="./chghddocument.php" enctype="multipart/form-data" method="post">
					<input type="hidden" name="MAX_FILE_SIZE" value="20000000" />
					<input name="newHD" type="file" />
					<br /><br />
					<input type="checkbox" name="ccfilename" id="CCFNALP" value="1"><label for="CCFNALP"><?php echo _('prod::tools:substitution : mettre a jour le nom original de fichier apres substitution')?></label>
					
					<input type="hidden" name="ACT" value="SEND" />
					<input type="hidden" name="bid" value="<?php echo $basrec2[0]?>" />
					<input type="hidden" name="rid" value="<?php echo $basrec2[1]?>" />
					<div style="text-align:center;margin:10px 0;">
						<input type="button" class="input-button" value="<?php echo _('boutton::valider')?>" onclick="document.forms.formchgHD.submit();" />
						<input type="button" class="input-button" value="<?php echo _('boutton::annuler')?>" onclick="parent.hideDwnl();" />
					</div>
				</form>
			</div>		
	<?php } ?>
	
	<?php if($seeOngChgDoc && GV_seeNewThumb) { ?>
			<div id="sdsub"  class="tabBox">
				<br />
				<form name="formchgthumb" action="./chgthumb.php" enctype="multipart/form-data" method="post">
					<input type="hidden" name="MAX_FILE_SIZE" value="20000000" />
					<input name="newThumb" type="file" />
					
					<input type="hidden" name="ACT" value="SEND" />
					<input type="hidden" name="bid" value="<?php echo $basrec2[0]?>" />
					<input type="hidden" name="rid" value="<?php echo $basrec2[1]?>" />
					<input type="hidden" name="element" value="" />
					<div style="text-align:center;margin:10px 0;">
						<input type="button" class="input-button" value="<?php echo _('boutton::valider')?>" onclick="document.forms.formchgthumb.submit();" />
						<input type="button" class="input-button" value="<?php echo _('boutton::annuler')?>" onclick="parent.hideDwnl();" />
					</div>
				</form>
			</div>		
	<?php }
	if(GV_exiftool != "" && count($tmpbasrec)==1)
	{
	 ?>
			<div id="exiftool"  class="tabBox">
					
					<?php
					
					$list = explode(';',$parm['lst']);
					
					foreach($list as $rec)
					{
						unset($out);
						$rec2 = explode('_',$rec);
						if(sizeof($rec2)==2)
						{
							$tmpsd = phrasea_subdefs($ses_id, $rec2[0],$rec2[1]);
							if(isset($tmpsd['document']))
							{
								$file = $tmpsd['document']['path'].$tmpsd['document']['file']; 
								
								echo '<div style="with:100%;text-align:center;font-size:12px;font-weight:bold;">Record '.$rec2[1]."</div><br/><br/>";
								$thumbnail = answer::getThumbnail($ses_id, $rec2[0],$rec2[1]);
								echo '<img src="'.$thumbnail['thumbnail'].'" width="'.$thumbnail['w'].'" height="'.$thumbnail['h'].'" />';
								
								echo '<hr/>';
								print("<b>HTML</b><br/>\n");
								$cmd = GV_exiftool.' -h '.escapeshellarg($file).'';
								exec($cmd, $out);
								foreach($out as $liout)
								{
									if(strpos($liout,'<tr><td>Directory')===false)
										echo $liout;
								}
								echo '<hr/>';
								
								print("<b>XML</b><br/>\n");
								$out = "";
								$cmd = GV_exiftool.'  -X -n -fast '.escapeshellarg($file).'';
								exec($cmd, $out);
								foreach($out as $liout)
								{
									echo "<pre>".htmlentities($liout) . "\r\n</pre>";
								}
								echo '<hr/>';
								
								flush();
							}
						}
					}
					
					?>
					<br /><br />
					<br /><br />
			</div>		
			<?php
	}
			?>
		</div>
	</body>
</html>
<?php

}


############## END ACT STEP2 ######################
?>