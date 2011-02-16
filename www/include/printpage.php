<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
require(GV_RootPath.'lib/fpdf/fpdf.php');



###########################
###########################
# Pour Affichage du viewname dans le bandeau en haut a gauche
$printViewName	= FALSE ; // viewname = base
$printlogosite	= TRUE ;

###########################

$presentationpage = false;

$request = httpRequest::getInstance();
$parm = $request->get_parms("lst"
					, "ACT"
					, "lay"
					, "callclient"
					, "SSTTID"
					);

$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	{
		header('Location: /include/logout.php');
		exit();
	}
}
else{
	header("Location: /login/");
	exit();
}

if($parm["ACT"] != "PRINT")
	phrasea::headers();

// les variables
$tot_record = 0;
$tot_hd = 0;
$tot_prev = 0;	



$regid = NULL;
$printReg = FALSE ;
$child=0;
							
############## ACT STEP2 ######################
if($parm["ACT"] === null)
{
	global $ph_session;
	#########
	$basdst2baslocal = NULL;	// relation basDistante (en fct du sbas) vers bas locale 
	foreach($ph_session["bases"] as $onebase)
	{					 
		foreach($onebase["collections"] as $oneColl)
		{
			$basdst2baslocal[$onebase["sbas_id"]][$oneColl["coll_id"]] = $oneColl["base_id"];			
		}
	}
	##################### 

	
	
	$usrRight = null;
	
		
	$conn = connection::getInstance();

	$sql = "SELECT base_id,actif,candwnldpreview,candwnldhd FROM (usr natural join basusr ) WHERE usr.usr_id='" . $conn->escape_string($usr_id)."'" ;
	 
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$usrRight[$row["base_id"]] = $row;
		}	
		$conn->free_result($rs);
	}
		
		
	if($parm["SSTTID"]!="")
	{
		// WARNIING : on demande le print d'un element de la zone temporaire
		$continu = true ;
		$parm["lst"] = ";";
		$sql = "select * from (ssel left join sselcont on ( ssel.ssel_id=sselcont.ssel_id AND ssel.ssel_id=".$conn->escape_string($parm["SSTTID"]).") ) " .
				"where ssel.ssel_id=".$conn->escape_string($parm["SSTTID"])." AND usr_id=" . $conn->escape_string($usr_id) ;
		$sql = 'SELECT s.*, c.*, sb.*, u.mask_and, u.mask_xor FROM (ssel s, sselcont c, sbas sb, bas b, basusr u) ' .
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
				if( $row["temporaryType"]=="1" )
				{
					
					$continu = false ;
					$oneSbasid = $row["sbas_id"];

					$conn2 = connection::getInstance($oneSbasid);
					
					if( $conn2 )
					{
						
						$sql2 ="SELECT * FROM record where record_id='".$conn2->escape_string($row["rid"])."' and parent_record_id='".$conn2->escape_string($row["rid"])."'";
						if(($rs2 = $conn2->query($sql2)))
						{
							// il faut avoir ajouter auparavant, la fiche regroupement en premiere
							if(($row2 = $conn2->fetch_assoc($rs2)) )	
							{
								$parm["lst"] = $basdst2baslocal[$oneSbasid][$row2["coll_id"]] . "_" . $row2["record_id"].";" ;	
								$conn2->free_result($rs2);	
								
								// puis les fils
								$sql2 = "SELECT record.coll_id,regroup.* ,recordchild.coll_id as coll_idchild FROM
									(( regroup inner join record on record.record_id=regroup.rid_parent AND rid_parent='".$conn2->escape_string($row["rid"])."')
									inner join record as recordchild on recordchild.record_id=rid_child)
									 ORDER BY rid_parent,ord,dateadd,rid_child";
								if(($rs2 = $conn2->query($sql2)))
								{	
									while(($row2 = $conn2->fetch_assoc($rs2)) )	
										$parm["lst"] .= $basdst2baslocal[$oneSbasid][$row2["coll_idchild"]] . "_" . $row2["rid_child"].";" ;						
									$conn2->free_result($rs2);	
								}					
							}
						}
					}	
				}
				else 
				{
					$parm["lst"] .= $row["base_id"] . "_" . $row["record_id"].";" ;
				}
			}
			$conn->free_result($rs);
		}
		
	}
		
	$lstTable = explode(";",$parm["lst"] );
	
	$unsets = array();
	foreach($lstTable as $k=>$br)
	{
		$br = explode('_',$br);
		if(count($br) == 2)
		{
			if(phrasea_isgrp($ses_id, $br[0], $br[1]))
			{
				$children = phrasea_grpchild($ses_id, $br[0], $br[1], GV_sit, $usr_id );
				if($children)
				{
					foreach($children as $child)
					{
						$lstTable[] = implode('_',$child);
					}
				}
				$unsets[] = $k;
			}
		}
	}
	
	foreach($unsets as $u)
		unset($lstTable[$u]);
		
	$okbrec = liste::filter($lstTable);
	
	$lstTable = $okbrec;
	
	$parm['lst'] = implode(';',$lstTable);
	
	
	
	foreach($lstTable as $basrec)
	{
		$basrec = explode("_", $basrec);
		if($basrec && count($basrec)==2)
		{
			$tot_record++;
			$sd = phrasea_subdefs($ses_id, $basrec[0], $basrec[1]);
			// $basrec[0]=> coll id
			// $basrec[1]=> record id
			
  			if((isset($usrRight[$basrec[0]]) && $usrRight[$basrec[0]]["candwnldhd"]=="1") )
			{
				if(isset($sd["document"]))
					$tot_hd++;
			}
			if((isset($usrRight[$basrec[0]]) && $usrRight[$basrec[0]]["candwnldpreview"]=="1"))
			{
				if(isset($sd["preview"]))
					$tot_prev++;
			}				
		}
	}
	
?>

<html lang="<?php echo $session->usr_i18n;?>">
	<head>

		<base target="_self">
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/jquery-ui.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/prodcolor.css" />
		<script type="text/javascript">
		function doPrint()
		{
			<?php
			$zurl = 'printpage_pdf.php?ACT=LOAD&form=formprintpage&callclient='.$parm['callclient'] ;
			?>
			window.open("<?php echo $zurl?>", "_blank", "width=600, height=500, directories=no, location=no, menubar=no, toolbar=no, help=no, status=no, resizable=yes, scrollbars=no");
			
				parent.hideDwnl();
			
		}
		</script>
		<script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js,include/jslibs/jquery-ui-1.7.2.js,include/jquery.p4.modal.js"></script>
	</head>
	<body class="bodyprofile">
		<div class="boxCloser" onclick="parent.hideDwnl();"><?php echo _('boutton::fermer')?></div>
		<div id="tabs">
			<ul>
				<li><a href="#print"><?php echo _('action : print')?></a></li>
			</ul>
			<div id="print" class="tabBox" >

<?php
	if($printReg)
	{
		echo sprintf(_('export:: export du regroupement : %d fichiers'),$child);?><br/><br/><?php 
	}
	?>
				<form name="formprintpage" action="" onsubmit="return(false);">
	<?php
		if($tot_record>0)
		{
			if($tot_prev>0)
			{
	?>
					<u><?php echo _('phraseanet:: preview')?></u><br/>
	<?php
				if($tot_record>1)
				{
	?>
					<input type="radio" name="lay" value="preview" id="RADI_PRE_LAB" /><label for="RADI_PRE_LAB"><?php echo _('print:: image de choix seulement')?></label><br/>
	<?php
				}
	?>
	
					<input type="radio" name="lay" value="previewCaption" id="RADI_PRE_CAP" /><label for="RADI_PRE_CAP"><?php echo _('print:: image de choix et description')?></label><br/>
	<?php
				if($tot_record>1)
				{
	?>
					<input type="radio" name="lay" value="previewCaptionTdm" id="RADI_PRE_TDM" /><label for="RADI_PRE_TDM"><?php echo _('print:: image de choix et description avec planche contact');?></label><br/>
	<?php
				}
			
				if($tot_prev!=$tot_record)
					printf ("&nbsp;<small>*( %s&nbsp;preview(s)&nbsp;/&nbsp;%s&nbsp;)</small>",$tot_prev,$tot_record);		
			}
		
	?>
		<br /><br /><u><?php echo  _('print:: imagette');?></u>	<br/>
	<?php
			if($tot_record>1)
			{
	?>
					<input type="radio" name="lay" value="thumbnailList" id="RADI_PRE_THUM" /><label for="RADI_PRE_THUM"><?php echo  _('print:: liste d\'imagettes');?></label><br/>
	<?php
			}
	?>
					<input type="radio" name="lay" checked value="thumbnailGrid" id="RADI_PRE_THUMGRI" /><label for="RADI_PRE_THUMGRI"><?php echo _('print:: planche contact (mosaique)');?></label><br/>
	<?php
				
		}
		else
		{
	?>
		<?php echo _('export:: erreur : aucun document selectionne')?>	
	<?php
		}
	?>
					<input type="hidden" name="lst" value="<?php echo $parm["lst"]?>" />
				</form>
				<div style="text-align:center;margin-top : 10px;">
					<input type="button" class="input-button" value="<?php echo _('boutton::imprimer');?>" onclick="doPrint();" /> 
					<input type="button" class="input-button" value="<?php echo _('boutton::annuler');?>" onclick="parent.hideDwnl();" />
				</div>
			</div>
		</div>
	</body>
</html>
<?php
}
