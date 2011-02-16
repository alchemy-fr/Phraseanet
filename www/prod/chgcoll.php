<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
phrasea::headers();

$request = httpRequest::getInstance();
$parm = $request->get_parms("act"
					, "base_id"
					, "lst"
					, "SSTTID"
					, "chg_coll_son"
					);

$lng = isset($session->locale)?$session->locale:GV_default_lng;

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

$user = user::getInstance($session->usr_id);
	
$samebase = true;
$ndocs = 0 ;
$ndocsMovable = 0 ;
$nCollDest = 0 ;
$lstrid = array();
		
$conn = connection::getInstance();

$base_dest = (isset($user->_rights_bas[$parm['base_id']]) && $user->_rights_bas[$parm['base_id']]['canaddrecord']) ? $parm['base_id'] : false; 

$lst = explode(";",$parm["lst"] );
unset($parm['lst']);

if($parm['act'] == null )
{
	if($parm['SSTTID'] != '')
	{
		$basket = basket::getInstance($parm['SSTTID']);
		
		
		if($basket->is_grouping)
		{
			
			$lst = array($basket->base_id.'_'.$basket->record_id);
		}
		else
		{
			$lst = array();
			foreach($basket->elements as $basket_element)
				$lst[]=$basket_element->base_id.'_'.$basket_element->record_id;
		}
	}
}

$ndocs = count($lst);
$lst = liste::filter($lst);
$sbas_id = false;

$okbrec = array();

foreach($lst as $basrec)
{
	$basrec = explode("_", $basrec);
	if($basrec && count($basrec)==2)
	{
		if($samebase && $sbas_id!==false && phrasea::sbasFromBas($basrec[0]) !== $sbas_id)
		{
			$samebase = false;
			break;
		}
		if($user->_rights_bas[$basrec[0]]['candeleterecord'])
		{
			$okbrec[] = implode('_', $basrec);
		}
		$sbas_id = phrasea::sbasFromBas($basrec[0]);
	}
}

$lst = $okbrec;
$ndocsMovable = count($lst);

$done = false;
if($parm['act']=="WORK" && (int)$base_dest > 0 && count($lst) > 0)
{
	if($parm["chg_coll_son"]=="1")
	{
		foreach($lst as $rec)
		{
			$rec = explode('_',$rec);
			$allson = phrasea_grpchild($ses_id,$rec[0], $rec[1],GV_sit,$usr_id) ;				
			foreach($allson as $oneson)
			{
				if( $user->_rights_bas[$oneson[0]]['candeleterecord'])
				{
					$lst[] = implode('_',$oneson) ;
				}
			}
		}	
	}
	
	$sbas_id = phrasea::sbasFromBas($base_dest);
	
	$connbas = connection::getInstance($sbas_id);
	
	$coll_dest = phrasea::collFromBas($base_dest) ? phrasea::collFromBas($base_dest) : false;

	if($connbas && $coll_dest !== false)
	{
		$recs = array();
		foreach($lst as $basrec)
		{
			$basrec = explode('_',$basrec);
			if(count($basrec) == 2)
				$recs[] = $conn->escape_string($basrec[1]);
		}
		
		$cache_basket = cache_basket::getInstance();
		
		$sqlM = 'UPDATE sselcont set base_id="'.$conn->escape_string($base_dest).'" WHERE (record_id="'.implode('" OR record_id="', $recs).'") AND base_id IN (SELECT base_id FROM bas WHERE sbas_id="'.$conn->escape_string($sbas_id).'")';
		if($conn->query($sqlM))
		{
			$cache_basket->revoke_baskets_record($recs);
		}
	
		$sql = "UPDATE record SET coll_id='" . $connbas->escape_string($coll_dest) . "' WHERE record_id IN (" . implode(',', $recs) . ")";
		$connbas->query($sql);
		
		foreach($recs as $record_id)
		{
			answer::logEvent($sbas_id,$record_id,'collection',$coll_dest,'');
		}
		$done = true;
		
	}
	
}

?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/jquery-ui.css" />
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/prodcolor.css" />
		<base target="_self">

<script type="text/javascript">

<?php if($parm['act'] == "WORK" && $done) { ?>
	parent.hideDwnl();
	parent.refreshBaskets('current');
<?php } ?>


<?php if($parm['act']== null ) { ?>

function doMove(do_it)
{
	if(do_it)
	{
	
		document.forms[0].submit();
	}
	else
		parent.hideDwnl();
}

<?php } ?>
</script>
<script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js,include/jslibs/jquery-ui-1.7.2.js,include/jquery.p4.modal.js"></script>
	</head>
	
	<body id="body" class="bodyprofile" style="overflow:hidden; padding:0px; margin:0px;">
		<div class="boxCloser" onclick="parent.hideDwnl();"><?php echo _('boutton::fermer')?></div>
		<div id="tabs">
			<ul>
				<li><a href="#colls"><?php echo _('prod::collection::Changer de collection')?></a></li>
			</ul>
			<div id="colls" class="tabBox">
				<div style="text-align:center;margin:30px 0px;">
			
			
<?php
	if($done == true)
	{
		?>
		
		
					<input class="input-button" type="button" value="<?php echo _('boutton::fermer');?>" onclick="parent.hideDwnl();" />
		</div>
		 	</div>
		 	</div>
	</body>
</html>
		<?php 
		exit();
	}
	elseif(!$samebase) // les records sel ne sont pas de la meme base
	{
		echo _('prod::Les enregistrements ne provienent pas tous de la meme base et ne peuvent donc etre traites ensemble');
	}
	elseif($ndocsMovable==0) // on a le droits de deplacer aucun records
	{
		echo _('prod::Vous n\'avez le droit d\'effectuer l\'operation sur aucun document');
	}
	elseif(!$sbas_id)
	{
		echo _('erreur : Vous n\'avez pas les droits');
	}
	else
	{			
	?>		
		<form action="/prod/chgcoll.php" method="post">
			<div>
		<?php
		
			if($ndocsMovable<$ndocs) // on a pas les droits de deplacer tt les records, seulements qqes uns
			{
				echo sprintf(_('prod::collection %d documents ne pouvant etres mofiies'),($ndocs-$ndocsMovable));
			}
			?>
			</div>
			<?php 	
		if($parm['act']== null  && $samebase)
		{
			## verif si grouping ou non pour proposer choix de changer les fils aussi
			$parm['act'] = "WORK";
			$nbgrouping = 0 ;	
			$nrecs = 0;
			
			foreach($lst as $basrec)
			{
				$basrec = explode("_", $basrec);
				if($basrec && count($basrec)==2)
				{
					$nrecs++;
					if( function_exists("phrasea_isgrp") && phrasea_isgrp($ses_id, $basrec[0], $basrec[1]))
					{
						$nbgrouping++;
					}
				}
			}	
			?>
			
			
			<?php
			echo sprintf(_('prod::collection %d documents a deplacer'),$ndocsMovable);
			
		
			print("<br />\n");
			print("<br />\n");
			
			$b = 0;
			printf("<select name=\"base_id\">\n");
			
			$colls = array();
			
			foreach($ph_session['bases'] as $base)
			{
				if($base['sbas_id'] == $sbas_id)
				{
					foreach($base['collections'] as $coll)
					{ 
						if($user->_rights_bas[$coll['base_id']]['canaddrecord'])
						{
							$colls[$coll['base_id']] = sprintf("<option %s  value=\"%s\">%s<br/>\n", $b==0?"checked":"", $coll['base_id'], $coll["name"]);
							$b++;
						}
					}
				}
			}
			
			$bas_order = phrasea::getBasesOrder();
		
							
			foreach($bas_order as $coll)
			{
				if(isset($colls[$coll['base_id']]))
				{
					echo $colls[$coll['base_id']];
				}
			}
			
			printf("</select>\n");
			print("<br />\n");
			print("<br />\n");
			print("<br />\n");
			$nCollDest = $b ;
			
			if( $nrecs==$nbgrouping  )
			{			 
				?>
				<table style="border:#ff0000 1px solid;">
					<tr>
						<td style="width:25px;"><input type="checkbox" value="1" name="chg_coll_son"/>
						</td>	
						<td style="width:250px; text-align:left">
							<?php echo _('prod::collection deplacer egalement les documents rattaches a ce(s) regroupement(s)');?>
						</td>
					</tr>
				</table>
				<?php 
			}
		}
		?>
			<input type="hidden" name="act" value="<?php echo $parm['act']?>">
			<input type="hidden" name="lst" value="<?php echo implode(';',$lst)?>">
	
		</form>
		<?php
	}
	?>
				<div style="text-align:center;">
					<input class="input-button" type="button" value="<?php echo _('boutton::valider')?>" onclick="doMove(true);" /> 
					<input class="input-button" type="button" value="<?php echo _('boutton::annuler');?>" onclick="parent.hideDwnl();" />
				</div>
			</div>
		</div>
		 	
	</body>
</html>