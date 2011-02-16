<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("srt", "ord", "act", "p0", "p1","p2", "sta", 'admins', 'pub_wm'); 

$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
	if(!$session->admin)
	{
		phrasea::headers(403);
	}
}
else
{
	phrasea::headers(403);
}


if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
{
	phrasea::headers(403);
}

$conn = connection::getInstance();
if(!$conn)
{
	phrasea::headers(500);
}
	
$base_id = (int)$parm['p1'];
	

$usrRight=null;
$sql = "SELECT canaddrecord,canmodifrecord,canadmin,manage,modify_struct FROM basusr" .
		" WHERE usr_id='".$conn->escape_string($usr_id)."' AND base_id = '".$conn->escape_string($base_id)."'";
if($rs = $conn->query($sql))
{
	$usrRight = $conn->fetch_assoc($rs);
	$conn->free_result($rs);
}

if($usrRight == null)
{
	phrasea::headers(403);
}


$row = null;
$sbas_id = null;
$distant_coll_id = null;
$addr = '';

$sql = 'SELECT s.sbas_id, s.dbname, s.host, s.port, s.sqlengine, b.server_coll_id, b.active, b.base_id 
		FROM sbas s, bas b WHERE b.base_id=\''.$conn->escape_string($base_id).'\' AND s.sbas_id = b.sbas_id';
if($rs = $conn->query($sql))
{
	if($row = $conn->fetch_assoc($rs))
	{
		$sbas_id         = $row['sbas_id'];
		$distant_coll_id = $row['server_coll_id'];
		$addr = $row["dbname"]."@".$row["host"].":".$row["port"]." (".$row["sqlengine"].")";
	}
	$conn->free_result($rs);
}

if(!$row)
{
	// unknown (local) base
	phrasea::headers(403);
}




$connbas = connection::getInstance($sbas_id);

$collection = collection::get($base_id);

$msg = array();

$refreshFinder = false;
$cache_coll = cache_collection::getInstance();


if(is_array($parm['admins']))
{
	$admins = array();
	
	foreach($parm['admins'] as $a)
	{
		if(trim($a) == '')
			continue;
		
		$admins[] = $a;
	}
	
	if($admins > 0)
	{
		exportorder::set_order_admins($admins, $base_id);
	}
}


switch($parm['act'])
{
	case 'ENABLED':
		$sql = 'UPDATE bas SET active=1 WHERE base_id=\''.$conn->escape_string($base_id).'\'';
		$conn->query($sql);
		break;
		
	case 'DISABLED';
		$sql = 'UPDATE bas SET active=0 WHERE base_id=\''.$conn->escape_string($base_id).'\'';
		$conn->query($sql);
		break;
		
	case 'pub_wm':
		if($connbas && $connbas->isok())
		{
			if($usrRight['canadmin'] == 1 && in_array($parm['pub_wm'],array('none','wm','stamp')))
			{
				$sql = 'UPDATE coll SET pub_wm="'.$connbas->escape_string($parm['pub_wm']).'" WHERE coll_id="'.$connbas->escape_string($distant_coll_id).'"';
				$connbas->query($sql);
			}
		}
		break;
		
	case 'APPLYNEWNAMECOLL':
		if($connbas && $connbas->isok())
		{
			$sql = "UPDATE coll SET htmlname='".$connbas->escape_string($parm["p2"])."', asciiname='".$connbas->escape_string($parm["p2"])."' WHERE coll_id='".$connbas->escape_string($distant_coll_id)."'";
			if($connbas->query($sql))
			{	
				$cache = cache_appbox::getInstance();
				$cache->delete('list_bases');	
				cache_databox::update($sbas_id,'structure');			
			}
			$refreshFinder = true;
		}
		break;
		
	case 'UMOUNTCOLL':
		$sql = "DELETE FROM basusr WHERE base_id='" . $conn->escape_string($base_id)."'" ;
		$conn->query($sql);
		$sql = "DELETE FROM sselcont WHERE base_id='" .$conn->escape_string($base_id)."'" ;
		$conn->query($sql);
		$sql = "DELETE FROM bas WHERE base_id='" . $conn->escape_string($base_id)."'" ;
		$conn->query($sql);
		
		$msg['ACTDONE'] = $htmlname.' '._('forms::operation effectuee OK');
		
		$refreshFinder = true;
		
		break;
		
	case 'DODELETECOLL':
		if($connbas && $connbas->isok())
		{
			$sql = "SELECT COUNT(record_id) AS n FROM record WHERE coll_id='" . $connbas->escape_string($distant_coll_id)."'";
			if($rsbas = $connbas->query($sql))
			{
				if($rowbas = $connbas->fetch_assoc($rsbas))
				{
					if($rowbas['n'] > 0)
					{
						$msg['ACTDONE'] = _('admin::base:collection: vider la collection avant de la supprimer');
					}
					else
					{
						$sql = "DELETE FROM basusr WHERE base_id='" . $conn->escape_string($base_id)."'" ;
						$conn->query($sql);
						$sql = "DELETE FROM sselcont WHERE base_id='" . $conn->escape_string($base_id)."'" ;
						$conn->query($sql);
						$sql = "DELETE FROM bas WHERE base_id='" . $conn->escape_string($base_id)."'" ;
						$conn->query($sql);
						$sql = "DELETE FROM order_masters WHERE base_id='" . $conn->escape_string($base_id)."'" ;
						$conn->query($sql);
						
						$sql = "DELETE FROM coll WHERE coll_id='" . $connbas->escape_string($distant_coll_id) . "'" ;
						if($connbas->query($sql))
						{
							$msg['ACTDONE'] = _('forms::operation effectuee OK');
							// $connbas = null;
						}
						$refreshFinder = true;
					}
				}
				$connbas->free_result($rsbas);
			}
		}			
		break;
		
	case 'SENDMINILOGO':
		if(isset($_FILES['newLogo']))
		{   
			if($_FILES['newLogo']['size']>65535)
			{
				$msg['SENDMINILOGO'] = '<div style="color:#FF0000">' . _('admin::base:collection le fichier envoye est trop volumineux.'). ' 64Ko </div>';
			}
			elseif ($_FILES['newLogo']['error']) 
			{
				$msg['SENDMINILOGO'] = '<div style="color:#FF0000">' . _('forms::erreur lors de l\'envoi du fichier') . '</div>'; // par le serveur (fichier php.ini) 
			}
			elseif( ( $_FILES['newLogo']['error'] == UPLOAD_ERR_OK  )   ) 
			{
				$file = GV_RootPath . 'config/minilogos/'.$base_id ;
				if( (@rename($_FILES['newLogo']['tmp_name'], $file)) )
				{
					$cache_coll->delete($base_id,'logo');
					p4::chmod($file);
					$sql = "UPDATE coll SET logo='".$connbas->escape_string(file_get_contents($file))."', majLogo=NOW() WHERE coll_id='".$connbas->escape_string($distant_coll_id)."'";
					$connbas->query($sql);
				}
			}
		}
		break;				
		
	case 'DELMINILOGO':
		if($connbas && $connbas->isok())
		{
			$cache_coll->delete($base_id,'logo');
			@unlink(GV_RootPath.'config/minilogos/'.$base_id);
			$sql = "UPDATE coll SET logo='',majLogo=NOW() WHERE coll_id='".$connbas->escape_string($distant_coll_id)."'";
			$connbas->query($sql);
		}
		break;
	
	case 'SENDWM':
	case 'DELWM':
		if($connbas && $connbas->isok())
		{
			// delete pre-watermarked (cache) files
			$sql = 'SELECT path, file FROM record r INNER JOIN subdef s USING(record_id) WHERE r.coll_id="'.$connbas->escape_string($distant_coll_id).'" AND r.type="image" AND s.name="preview"';
			if($rs2 = $connbas->query($sql))
			{
				while($row2 = $connbas->fetch_assoc($rs2))
				{
					@unlink(p4string::addEndSlash($row2['path']).'watermark_'.$row2['file']);
				}
			}
			
			if($parm['act']=='SENDWM' && isset($_FILES['newWm']))
			{   
				if($_FILES['newWm']['size']>65535)
				{
					$msg['SENDWM'] = '<div style="color:#FF0000">' . _('admin::base:collection le fichier envoye est trop volumineux.'). " 64Ko" . "</div>";
				}
				elseif($_FILES['newWm']['error'])
				{ 
					$msg['SENDWM'] .= '<div style="color:#FF0000">' ._('forms::erreur lors de l\'envoi du fichier'). "</div>"; // par le serveur (fichier php.ini)
				} 
				elseif( ($_FILES['newWm']['error'] == UPLOAD_ERR_OK) )
				{
					$file = GV_RootPath.'config/wm/'.$base_id;
					@rename($_FILES['newWm']["tmp_name"], $file);
					p4::chmod($file);
				}	
			}				
			elseif($parm['act']=="DELWM")
			{
				@unlink(GV_RootPath.'config/wm/'.$base_id);
			}
			$cache_coll->delete($base_id,'watermark');
		}		
		break;

	case 'SENDSTAMPLOGO':
		if(isset($_FILES['newStampLogo']))
		{   
			if($_FILES['newStampLogo']['size']>1024*1024)
			{
				$msg['SENDSTAMPLOGO'] = '<div style="color:#FF0000">' . _('admin::base:collection le fichier envoye est trop volumineux.') . ' 1Mo </div>';
			}
			elseif ($_FILES['newStampLogo']['error']) 
			{
	          	$msg['SENDSTAMPLOGO'] = '<div style="color:#FF0000">' . _('forms::erreur lors de l\'envoi du fichier') . '</div>'; // par le serveur (fichier php.ini) 
			}
			elseif( ( $_FILES['newStampLogo']['error'] == UPLOAD_ERR_OK) ) 
			{
				$file = GV_RootPath . 'config/stamp/'.$base_id ;
				@rename($_FILES['newStampLogo']['tmp_name'], $file);
				p4::chmod($file);
			}
			$cache_coll->delete($base_id,'stamp');
		}
		break;
		
	case 'DELSTAMPLOGO':
		$cache_coll->delete($base_id,'stamp');
		@unlink(GV_RootPath.'config/stamp/'.$base_id);
		// $collection->deleteStamp($base_id);
		break;
		
	case 'SENDPRESENTPICT':
		if(isset($_FILES['newPresentPict']) )
		{							   
			if($_FILES['newPresentPict']['size']>1024*1024*2)
			{
				$msg['SENDPRESENTPICT'] = '<div style="color:#FF0000">' . _('admin::base:collection le fichier envoye est trop volumineux.'). ' 2Mo </div>';
			}
			elseif($_FILES['newPresentPict']['error']) 
			{
				$msg['SENDPRESENTPICT'] = '<div style="color:#FF0000">' . _('forms::erreur lors de l\'envoi du fichier') . '</div>'; // par le serveur (fichier php.ini) 
			}
			elseif($_FILES['newPresentPict']['error'] == UPLOAD_ERR_OK  ) 
			{
				$cache_coll->delete($base_id,'presentation');
				$file = GV_RootPath . 'config/presentation/'.$base_id;
				@rename($_FILES['newPresentPict']["tmp_name"], $file );
				p4::chmod($file);
			}
		}
		break;
		
	case 'DELPRESENTPICT':
		$cache_coll->delete($base_id,'presentation');
		@unlink(GV_RootPath . 'config/presentation/'.$base_id);
		//$collection->deletePresentation($base_id);
		break;
	
}


$asciiname = $htmlname = '';

if($connbas && $connbas->isok())
{
	$sql = 'SELECT asciiname, htmlname FROM coll WHERE coll_id=\''.$connbas->escape_string($distant_coll_id).'\'';
	if($rsbas = $connbas->query($sql))
	{
		if($rowbas = $connbas->fetch_assoc($rsbas))
		{
			$asciiname = $rowbas['asciiname'];
			$htmlname  = $rowbas['htmlname'];
		}
		$connbas->free_result($rsbas);
	}
}

function showMsg($k)
{
  global $msg;
	if(isset($msg[$k]))
		echo($msg[$k]);
}



$sql = 'SELECT * FROM bas WHERE base_id=\''.$conn->escape_string($base_id).'\'';
if($rs = $conn->query($sql))
{
	if(!$conn->fetch_assoc($rs))
	{
		$conn->free_result($rs);
?>
<html lang="<?php echo $session->usr_i18?>">
	<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
	</head>
	<body>
		<script>

		<?php
		if($parm["act"]=="DODELETECOLL")
		{
			print("parent.reloadTree('base:".$parm['p0']."');");
		}
		?>
		</script>
		<?php showMsg('ACTDONE') ?>
	</body>
</html>
<?php
		die();
	}
	else
	{
		$conn->free_result($rs);
	}
}

phrasea::headers();


?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
		<script type="text/javascript" src="/include/minify/?f=include/jslibs/jquery-1.4.4.js"></script>
		<script type="text/javascript">
			var ntask = 0 ;
			
			function sendForm(act)
			{
				document.forms["manageColl"].target = "";
				document.forms["manageColl"].act.value = act;
				document.forms["manageColl"].submit();
			}
			
			function emptyColl(collname)
			{
				if(confirm("<?php echo _('admin::base:collection: etes vous sur de vider la collection ?')?>"))
				{
					$.ajax({
						type: "POST",
						url: "emptybase.php",
						dataType: 'json',
						data: {
								sbid:<?php echo $sbas_id ?>,
								collid:<?php echo $distant_coll_id ?>
								
						},
						success: function(data){
							return;
						}
					});
					// url = "progress0.php?page=emptycoll.php&p0=<?php echo $parm["p0"]?>&p1=<?php echo $sbas_id?>&type=ec&htmlname="+collname ;
					// parent.ec<?php echo $parm["p0"]?><?php echo $base_id?> = window.open( url,"ec<?php echo $parm["p0"]?><?php echo $base_id?>","menubar=no, status=no, scrollbars=no, menubar=no, width=420, height=610")
				}
			}

			function askUnmountColl()
			{
				if(confirm("<?php echo _('admin::base:collection: etes vous sur de demonter cette collection ?')?>"))
					sendForm('UMOUNTCOLL');
			}
			
			function showDetails(sta)
			{
				document.forms["manageColl"].sta.value = sta;
				sendForm('');
			}
			
			function enabledPublication(bool)
			{
				if(bool)
				{
					if(confirm("<?php echo _('admin::base:collection: etes vous sur de publier cette collection ?')?>"))
						sendForm('ENABLED');
				}
				else
				{
					if(confirm("<?php echo _('admin::base:collection: etes vous sur darreter la publication de cette collection')?>"))
						sendForm('DISABLED');
				}
			}
<?php
if($refreshFinder)
{
	print("			parent.reloadTree('base:".$sbas_id."');\n");
}
?>
		</script>
		<style>
			.logo_boxes
			{
				margin:5px 5px 5px 10px;
				padding-top:5px;
				border-top:2px solid black;
			}
			a:link,a:visited{
				text-decoration:none;
				color:#666;
			}
			a:hover{
				text-decoration:underline;
				color:black;
			}
		</style>
	</head>
	<body>
		<h4>
			<?php echo _('phraseanet:: collection');?> <b><?php echo $asciiname;?></b>
		</h4>
		<div style='margin:3px 0 3px 10px;'>
			<?php echo _('phraseanet:: adresse');?> : <?php echo $addr;?>&nbsp;
		</div>
		<?php showMsg('ACTDONE') ?>
		<div style='margin:3px 0 3px 10px;'>
			<?php echo _('admin::base:collection: numero de collection distante');?> : <?php echo $distant_coll_id;?>&nbsp;
		</div>

<?php
//foreach($msg as $m)
//{
//	print('		<div>' . $m . '</div>'."\n");
//}

if(!$connbas || !$connbas->isok())
{
?>
		<div style="color:red">
			<?php echo _('admin::base: erreur : le serveur de base de donnee n\'est pas joignable')?>
			<br/>
		</div>
<?php
}
else
{
?>
		<div style="margin:3px 0 3px 10px;">
			<?php echo _('admin::base:collection: etat de la collection') . " : " . ( $row["active"]==1 ? _('admin::base:collection: activer la collection'):_('admin::base:collection: descativer la collection') )?>&nbsp;
		</div>
				
		<div style="margin:3px 0 3px 10px;">
<?php
	$sql = "SELECT COUNT(record_id) AS n FROM record WHERE coll_id='" . $connbas->escape_string($distant_coll_id)."'";
	if($rsbas = $connbas->query($sql))
	{
		if($rowbas = $connbas->fetch_assoc($rsbas))
			echo '			'. $rowbas["n"] . ' records'."\n";					
		$connbas->free_result($rsbas);
	}
				
	if($parm["sta"]=="" || $parm["sta"]==NULL || $parm["sta"]==0 )
	{
?>
			(<a href="javascript:void(0);" onclick="showDetails(1);return(false);">
				<?php echo _('phraseanet:: details')?> 
			</a>)
			<br />
<?php
	}
	else
	{
			
		$trows = array();					
		$sql = "SELECT record.coll_id,name,COALESCE(asciiname, CONCAT('_',record.coll_id)) AS asciiname, SUM(1) AS n, SUM(size) AS siz FROM record NATURAL JOIN subdef inner JOIN coll ON record.coll_id=coll.coll_id AND coll.coll_id='".$connbas->escape_string($distant_coll_id)."' GROUP BY record.coll_id, subdef.name";
							
		if($parm["srt"]=="obj")
		{
			$sortk1 = "name";
			$sortk2 = "asciiname";
		}
		else
		{
			$sortk1 = "asciiname";
			$sortk2 = "name";
		}
		if($rsbas = $connbas->query($sql))
		{
			while($rowbas = $connbas->fetch_assoc($rsbas))
			{
				if(!isset($trows[$rowbas[$sortk1]]))
					$trows[$rowbas[$sortk1]] = array();
				$trows[$rowbas[$sortk1]][$rowbas[$sortk2]] = array("coll_id"=>$rowbas["coll_id"], "asciiname"=>$rowbas["asciiname"],/* "lostcoll"=>$rowbas["lostcoll"],*/ "name"=>$rowbas["name"], "n"=>$rowbas["n"], "siz"=>$rowbas["siz"]);
			}
			$connbas->free_result($rsbas);
		}
?>					
			(<a href="javascript:void(0);" onclick="showDetails(0);return(false);">
				<?php echo _('admin::base: masquer les details')?>
			</a>)
			<br />
			<br />
			<table class="ulist">
				<col width=180px>
				<col width=100px>
				<col width=60px>
				<col width=80px>
				<col width=70px>
				<thead>
					<tr>
						<th>
<?php
		if($parm["srt"]=="col")
			print('<img src="/skins/icons/tsort_desc.gif">&nbsp;');
		print(_('phraseanet:: collection'));
?>			
						</th>
						<th>
<?php
		if($parm["srt"]=="obj")
			print('<img src="/skins/icons/tsort_desc.gif">&nbsp;');
		print(_('admin::base: objet'));
?>			
						</th>
						<th>
							<?php echo _('admin::base: nombre')?>
						</th>
						<th>
							<?php echo _('admin::base: poids')?> (Mo)
						</th>
						<th>
							<?php echo _('admin::base: poids')?> (Go)
						</th>
					</tr>
				</thead>
				<tbody>
<?php
		$totobj = 0;
		$totsiz = "0";		// les tailles de fichiers sont calculees avec bcmath

		foreach($trows as $kgrp=>$vgrp)
		{
			// ksort($vgrp);
			$midobj = 0;
			$midsiz = "0";
			$last_k1 = $last_k2 = null;
			foreach($vgrp as $krow=>$vrow)
			{
				if($last_k1 !== $vrow["coll_id"])
				{
				}
				if($vrow["n"] > 0 || $last_k1 !== $vrow["coll_id"])
				{
					$midobj += $vrow["n"];
					if(extension_loaded("bcmath"))
						$midsiz = bcadd($midsiz, $vrow["siz"], 0);
					else
						$midsiz += $vrow["siz"];
					if(extension_loaded("bcmath"))
						$mega = bcdiv($vrow["siz"], 1024*1024, 5);
					else
						$mega = $vrow["siz"] / (1024*1024);
					if(extension_loaded("bcmath"))
						$giga = bcdiv($vrow["siz"], 1024*1024*1024, 5);
					else
						$giga = $vrow["siz"] / (1024*1024*1024);
?>
					<tr>
						<td>
<?php
					if($last_k1 !== $vrow["coll_id"])
					{
						print($vrow["asciiname"]) ;
						$last_k1 = $vrow["coll_id"];
					}
?>
						</td>
						<td>
<?php 
					if($last_k2 !== $vrow["name"])
					{
						print($last_k2 = $vrow["name"]);
					}
?>
						</td>
						<td style="text-align:right">
							&nbsp;
							<?php echo $vrow["n"]?>
							&nbsp;
						</td>
						<td style="text-align:right">
							&nbsp;
							<?php printf("%.2f", $mega)?>
							&nbsp;
						</td>
						<td style="text-align:right">
							&nbsp;
							<?php sprintf("%.2f", $giga)?>
							&nbsp;
						</td>
					</tr>
<?php
				}
			}
			$totobj += $midobj;
			if(extension_loaded("bcmath"))
				$totsiz = bcadd($totsiz, $midsiz, 0);
			else
				$totsiz += $midsiz;
			if(extension_loaded("bcmath"))
				$mega = bcdiv($midsiz, 1024*1024, 5);
			else
				$mega = $midsiz / (1024*1024);
				
			if(extension_loaded("bcmath"))
				$giga = bcdiv($midsiz, 1024*1024*1024, 5);
			else
				$giga = $midsiz / (1024*1024*1024);
?>
					<tr>
						<td>
						</td>
						<td style="text-align:right">
							<i>total</i>
						</td>
						<td style="text-align:right; TEXT-DECORATION:overline">
							&nbsp;
							<?php echo $midobj?>
							&nbsp;
						</td>
						<td style="text-align:right; TEXT-DECORATION:overline">
							&nbsp;
							<?php printf("%.2f", $mega)?>
							&nbsp;
						</td>
						<td style="text-align:right; TEXT-DECORATION:overline">
							&nbsp;
							<?php printf("%.2f", $giga)?>
							&nbsp;
						</td>
					</tr>
					<tr>
						<td colspan="5">
							<hr />
						</td>
					</tr>
<?php 
		}
		if(extension_loaded("bcmath"))
			$mega = bcdiv($totsiz, 1024*1024, 5);
		else
			$mega = $totsiz / (1024*1024);
		if(extension_loaded("bcmath"))
			$giga = bcdiv($totsiz, 1024*1024*1024, 5);
		else
			$giga = $totsiz / (1024*1024*1024);
?>			
					<tr>
						<td colspan="2" style="text-align:right">
							<b>total</b>
						</td>
						<td style="text-align:right;">
							&nbsp;
							<b><?php echo $totobj ?></b>
							&nbsp;
						</td>
						<td style="text-align:right;">
							&nbsp;
							<b><?php printf("%.2f", $mega)?></b>
							&nbsp;
						</td>
						<td style="text-align:right;">
							&nbsp;
							<b><?php printf("%.2f", $giga)?></b>
							&nbsp;
						</td>
					</tr>
				</tbody>
			</table>
<?php		
	}
?>	
		</div>
<?php 	
	if($usrRight["manage"]==1)	
	{
		$pub_wm = 'none';
		$connsbas = connection::getInstance($row['sbas_id']);
		$sql2 = 'SELECT pub_wm FROM coll WHERE coll_id="'.$connsbas->escape_string($distant_coll_id).'"';
		if($rs2 = $connsbas->query($sql2))
		{
			if($row2 = $connsbas->fetch_assoc($rs2))
			{
				$pub_wm = $row2['pub_wm'];
			}
		}	
?>		
		<form action="/admin/collection.php" method="post">
			<input type="hidden" name="p0"  value="<?php echo $sbas_id?>" />
			<input type="hidden" name="p1"  value="<?php echo $base_id?>" />
			<?php echo _('admin::collection:: Gestionnaires des commandes')?>
			<div>
				<?php 
				
				$admins = exportorder::get_order_admins($base_id);
				
				foreach($admins as $usr_id=>$usr_login)
				{
					?>
					<div><input name="admins[]" type="checkbox" value="<?php echo $usr_id?>" id="adm_<?php echo $usr_id?>" checked /><label for="adm_<?php echo $usr_id?>"><?php echo $usr_login;?></label></div>
					<?php
				}
				?>
				<div><?php echo _('setup:: ajouter un administrateur des commandes') ?></div>
			
				<?php 
				
				$elligible = exportorder::get_simple_users_list($base_id);
				
				?>
				<select name="admins[]">
					<option value=""><?php echo _('choisir');?></option>
					<?php
					foreach($elligible as $usr_id=>$usr_login)
					{
						?>
						<option value="<?php echo $usr_id?>"><?php echo $usr_login;?></option>
						<?php
					}
					?>
				</select>
				<input type="submit" value="<?php echo _('boutton::valider') ?>" />
			</div>
		</form>
			
		<form method="post" name="manageColl" action="./collection.php" target="???" onsubmit="return(false);" ENCTYPE="multipart/form-data" >
			<input type="hidden" name="srt" value="<?php echo $parm["srt"]?>" />
			<input type="hidden" name="ord" value="<?php echo $parm["ord"]?>" />			
			<input type="hidden" name="act" value="???" />
			<input type="hidden" name="p0"  value="<?php echo $sbas_id?>" />
			<input type="hidden" name="p1"  value="<?php echo $base_id?>" />
			<input type="hidden" name="sta" value="<?php echo $parm["sta"]?>" />
			
			
			
			<?php echo _('admin::collection:: presentation des elements lors de la diffusion aux utilisateurs externes (publications)')?>
			<div>
				<input type='radio' name='pub_wm' onchange="sendForm('pub_wm');return(false);" <?php echo ($pub_wm == 'none'  ? 'checked' : '')?> value='none'  /> <?php echo _('admin::colelction::presentation des elements : rien')?>
				<input type='radio' name='pub_wm' onchange="sendForm('pub_wm');return(false);" <?php echo ($pub_wm == 'wm'    ? 'checked' : '')?> value='wm'    /> <?php echo _('admin::colelction::presentation des elements : watermark')?>
				<input type='radio' name='pub_wm' onchange="sendForm('pub_wm');return(false);" <?php echo ($pub_wm == 'stamp' ? 'checked' : '')?> value='stamp' /> <?php echo _('admin::colelction::presentation des elements : stamp')?>
			</div>

			<div style='margin:13px 0 3px 10px;'>
				<a href="javascript:void();return(false);" onclick="sendForm('ASKRENAMECOLL');return(false);">
					<img src="/skins/icons/edit_0.gif" style='vertical-align:middle'/>
					<?php echo _('admin::base:collection: renommer la collection')?>
				</a>
<?php 
		if($parm['act']=="ASKRENAMECOLL")
		{
?>
				<div style='margin:13px 0 3px 10px;'>
					<?php echo _('admin::base:collection: Nom de la nouvelle collection : ')?>
					<input type="text"   name="p2" id="p2" value="<?php echo $asciiname?>" /> 
					<input type="button" value="<?php echo _('boutton::envoyer')?>" onclick="sendForm('APPLYNEWNAMECOLL');"/>
					<input type="button" value="<?php echo _('boutton::annuler')?>" onclick="sendForm('');"/>
				</div>
<?php 
		}
		else
		{
?>
				<input type="hidden" name="p2" value="<?php echo $parm["p2"]?>" />
<?php 
		}
?>
			</div>
		
			<div style='margin:13px 0 3px 10px;'>
				<a href="javascript:void();return(false);" onclick="enabledPublication(<?php echo($row["active"]==1 ? "false":"true")?>);return(false);">
					<img src='/skins/icons/db-remove.png' style='vertical-align:middle'/>
					<?php echo( $row["active"]==1 ? _('admin::base:collection: descativer la collection'):_('admin::base:collection: activer la collection'))?>
				</a>
			</div>
			<div style='margin:3px 0 3px 10px;'>
				<a href="javascript:void();return(false);" onclick="emptyColl('<?php p4string::MakeString($htmlname,"js")?>');return(false);">
					<img src='/skins/icons/trash.png' style='vertical-align:middle'/>
					<?php echo _('admin::base:collection: vider la collection')?>
				</a>
			</div>
			<div style='margin:3px 0 3px 10px;'>
				<a href="javascript:void();return(false);" onclick="sendForm('ASKDELETECOLL');return(false);">
					<img src='/skins/icons/delete.gif' style='vertical-align:middle'/>
					<?php echo _('boutton::supprimer')?>
				</a>
			</div>
<?php 					
		if($parm['act']=="ASKDELETECOLL")
		{
?>
			<div style='margin:13px 0 3px 10px;'>
				<?php echo _('admin::collection: Confirmez vous la suppression de cette collection ?')?><br/>
				<div style='margin:5px 0;'>
					<input type="button" value="<?php echo _('boutton::valider')?>" onclick="sendForm('DODELETECOLL');"/>
					<input type="button" value="<?php echo _('boutton::annuler')?>" onclick="sendForm('');"/>
				</div>
			</div>
<?php 	
		}				
	} //$usrRight["manage"]==1
	
	
	// ------- image for minilogo -------
	//
	$sql = "SELECT logo, majLogo, UNIX_TIMESTAMP(majLogo) AS d FROM coll WHERE coll_id='".$connbas->escape_string($distant_coll_id)."'";
	if($rsbas = $connbas->query($sql))
	{
		$rowbas = $connbas->fetch_assoc($rsbas);
		$connbas->free_result($rsbas);
	}				
	$filename = GV_RootPath.'config/minilogos/'.$base_id;
	if($rowbas && $rowbas['logo']!=null && $rowbas['logo']!='')
	{				
		if( !file_exists($filename) || (fileatime($filename) < $rowbas['d']) )
			file_put_contents($filename, $rowbas['logo']);
	}
	else
	{
		@unlink($filename);
	}
?>
			<div class='logo_boxes'>
				<div style="font-size:11px;font-weight:bold;margin:0px 3px 10px 0px;">
					<?php echo _('admin::base:collection: minilogo actuel')?> :
					<?php showMsg('SENDMINILOGO') ?>
				</div>
<?php
 	if($usrRight["manage"]==1)	
	{
		if( file_exists(GV_RootPath.'config/minilogos/'.$base_id) )
		{
?>
				<div style='margin:0 0 5px 0;'>
					<?php echo $collection->getLogo($base_id)?>
					<a href="javascript:void();return(false);" onclick="sendForm('DELMINILOGO');return(false);">
						<?php echo _('boutton::supprimer')?>
					</a>
				</div>
<?php 
		}
		else
		{
?>
				<!-- <?php echo _('admin::base:collection: aucun fichier (minilogo, watermark ...)')?><br /><br /> -->				
				<input name="newLogo" type="file" />
				<input type="button" value="<?php echo _('boutton::envoyer')?>" onclick="sendForm('SENDMINILOGO');"/>
<?php 
		}
	}
?>
			</div>
<?php 
	// -----------------------------------
	
	
					
	// ------- image for watermark -------
	//
?>
			<div class='logo_boxes'> 
				<div style="font-size:11px;font-weight:bold;margin:0px 3px 10px 0px;">
					Watermark :
					<?php showMsg('SENDWM') ?>
				</div>
<?php 
	if($usrRight["manage"]==1)	
	{
		if( file_exists(GV_RootPath.'config/wm/'.$row["base_id"]) )
		{
?>
				<div style='margin:0 0 5px 0;'>
					<?php echo $collection->getWatermark($base_id)?>
					<a href="javascript:void();return(false);" onclick="sendForm('DELWM');return(false);">
						<?php echo _('boutton::supprimer')?>
					</a>
				</div>
<?php 
		}	
		else
		{
?>
				<!--  <?php echo _('admin::base:collection: aucun fichier (minilogo, watermark ...)')?><br /><br /> -->	
				<input name="newWm" type="file" />		
				<input type="button" value="<?php echo _('boutton::envoyer')?>" onclick="sendForm('SENDWM');"/>
<?php 
		}	
	}
?>
			</div>
<?php 
	// -------------------------------
	
	
	
	// ------- image for stamp -------
	//
?>
			<div class='logo_boxes'>
				<div style="font-size:11px;font-weight:bold;margin:0px 3px 10px 0px;">
					StampLogo :
					<?php showMsg('SENDSTAMPLOGO') ?>
				</div>
<?php 
	if($usrRight["manage"]==1)
	{
		if(file_exists(GV_RootPath . 'config/stamp/'.$base_id))
		{
?>	
				<div style='margin:0 0 5px 0;'>
					<?php echo $collection->getStamp($base_id)?>
					<a href="javascript:void();return(false);" onclick="sendForm('DELSTAMPLOGO');return(false);">
						<?php echo _('boutton::supprimer')?>
					</a>
				</div>
<?php 
		}
		else
		{
?>
				<input name="newStampLogo" type="file" />		
				<input type='button' value="<?php echo _('boutton::envoyer')?>" onclick="sendForm('SENDSTAMPLOGO');"/>
<?php 
		}
	}
?>
			</div>
<?php 
	// -------------------------------



	// ------- presentation pict (???) -------
	//
?>
			<div class='logo_boxes'>
				<div style="font-size:11px;font-weight:bold;margin:0px 3px 10px 0px;">
					<?php echo _('admin::base:collection: image de presentation : ')?>
					<?php showMsg('SENDPRESENTPICT') ?>
				</div>
<?php 
	if($usrRight["manage"]==1)
	{
		if(file_exists(GV_RootPath . 'config/presentation/'.$base_id))
		{
?>		
				<div style='margin:0 0 5px 0;'>
					<?php echo $collection->getPresentation($base_id)?>
					<a href="javascript:void();return(false);" onclick="sendForm('DELPRESENTPICT');return(false);">
						<?php echo _('boutton::supprimer')?>
					</a>
				</div>
<?php 
		}
		else
		{
?>
				 <input name="newPresentPict" type="file" />		
				 <input type="button" value="<?php echo _('boutton::envoyer')?>" onclick="sendForm('SENDPRESENTPICT');return(false);"/>					
				 <br/>( max : 650x200 )
<?php 
		}
	}
?>
			</div>

		</form>
<?php
}
?>
	</body>
				
</html>
