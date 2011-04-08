<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
require(GV_RootPath."lib/index_utils2.php");

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("act",
									"p0",	// id de la base
									"sta",	// afficher les stats de base (1) ou non (0)
									"srt",	// trier les colonnes de stats par collection (col) ou objet (obj)
									"nvn",	// New ViewName ( lors de l'UPD
									"othcollsel",
									"coll_id",
									"base_id"
									 );

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
		
if(!$parm["srt"])
	$parm["srt"] = "col";


if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	phrasea::headers(403);	

$conn = connection::getInstance();
if(!$conn || !$conn->isok())
	phrasea::headers(500);	

	
phrasea::headers();

$usrRight=null;
$sbas_id = null;
	
$printLogoUploadMsg = "";

switch($parm["act"])
{
	case "SENDLOGOPDF":
		if(isset($_FILES['newLogoPdf']) && $_FILES['newLogoPdf']['error'] == UPLOAD_ERR_OK) 
		{
			if($_FILES['newLogoPdf']['size']<65536)
			{
				$filenameTemp =  $_FILES['newLogoPdf']["tmp_name"];
				$mimeExt = giveMimeExt($filenameTemp);
				if(mb_strtolower($mimeExt['mime']) == 'image/jpeg' || mb_strtolower($mimeExt['mime']) == 'image/jpg' || mb_strtolower($mimeExt['mime']) == 'image/pjpeg')
				{
					copy($filenameTemp, GV_RootPath.'config/minilogos/logopdf_'.$parm['p0'].'.jpg');
					$cache_data = cache_appbox::getInstance();
					$cache_data->delete('printLogo'.$parm['p0']);		
				}
				else
				{
					$printLogoUploadMsg = _('forms::erreur lors de l\'envoi du fichier');
				}
			}
			else
			{
				$printLogoUploadMsg = _('forms::erreur lors de l\'envoi du fichier');
			}
		}
		else
		{
			$printLogoUploadMsg = _('forms::erreur lors de l\'envoi du fichier');
		}
		break;
	case 'MOUNT':
		try
		{
			$base_id = collection::mount_collection($parm['p0'], $parm['coll_id']);
			if(!is_null($parm['othcollsel']))
			{
				collection::duplicate_right_from_bas($parm['othcollsel'], $base_id);
			}
		}
		catch(Exception $e)
		{

		}
		break;
	case 'ACTIVATE':
		try
		{
			$base_id = collection::activate_collection($parm['p0'], $parm['base_id']);
		}
		catch(Exception $e)
		{

		}
		break;
}	


$connbas = connection::getInstance($conn->escape_string($parm['p0']));


$sql = "SELECT sbas_id, bas_manage, bas_modify_struct
		FROM sbasusr 
		WHERE sbasusr.usr_id='".$conn->escape_string($usr_id)."' AND sbas_id='".$conn->escape_string($parm['p0'])."'";
			
if($rs = $conn->query($sql))
{
	if($row = $conn->fetch_assoc($rs))
	{			
		$usrRight["bas_manage"] = $row["bas_manage"];
		$usrRight["bas_modify_struct"] = $row["bas_modify_struct"];
		$sbas_id = $row["sbas_id"];
	}	
	$conn->free_result($rs);
}

// on liste l'ensemble des collections publiees sur notre site pour cette base
$tcoll = array("baslist"=>array(), "bases"=>array());

$sql = 'SELECT sbas.*, sbas.viewname, sbas.indexable, bas.active, bas.ord, bas.server_coll_id, bas.base_id FROM 
 sbas LEFT JOIN bas ON (bas.sbas_id = sbas.sbas_id) 
 WHERE sbas.sbas_id=\''.$conn->escape_string($parm['p0']).'\'';
$row = null;
if($rs = $conn->query($sql))
{
	while($r = $conn->fetch_assoc($rs))	// on a liste tts les colls sur la meme base, on se connecte a la base distante au premier record
	{
		$row = $r;
		if(!is_null($row["base_id"]))
			$tcoll["baslist"][] = $row["base_id"];
		$tcoll["bases"]["b".$row["sbas_id"]] = $row;
		$sbas_id = $row["sbas_id"];
		$is_indexable = $row["indexable"];
	}
	$conn->free_result($rs);
}

if(trim($row["viewname"])!="")
	$HTMLviewname = '<b>'.trim($row["viewname"]).'</b>';
else
	$HTMLviewname = '<i>'._('admin::base: aucun alias').'</i>';
	

?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />

		<script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js"></script>

		<script type="text/javascript">
		
		function sendLogopdf()
		{
			document.forms["flpdf"].target = "";
			document.forms["flpdf"].act.value = "SENDLOGOPDF";
			document.forms["flpdf"].submit();	
		}
		function deleteLogoPdf()
		{
			if(confirm("<?php echo _('admin::base: Supprimer le logo pour impression')?>"))
			{
				$.ajax({
					type: "POST",
					url: "index_infos.php",
					dataType: 'json',
					data: { ACTION:"DELLOGOPDF", p0:<?php echo($parm['p0'])?>},
					success: function(data){
						$("#printLogoDIV_OK").hide();
						$("#printLogoDIV_NONE").show();
					}
				});
			}
		}
		function reindex()
		{
			if(confirm('<?php echo str_replace("'","\'",_('Confirmez-vous la re-indexation de la base ?')); ?>'))
			{
				$.ajax({
					type: "POST",
					url: "index_infos.php",
					dataType: 'json',
					data: { ACTION:"REINDEX", p0:<?php echo($parm['p0'])?>},
					success: function(data){
					}
				});
			}
		}

		function makeIndexable(el)
		{
			$.ajax({
				type: "POST",
				url: "index_infos.php",
				dataType: 'json',
				data: { ACTION:"MAKEINDEXABLE", p0:<?php echo($parm['p0'])?>, INDEXABLE:(el.checked?'1':'')	},
				success: function(data){
				}
			});
		}

		var __viewname = "";		// global will be updated by refreshContent
		function chgViewName()
		{
			if( (newAlias = prompt("<?php echo(_('admin::base: Alias'))?> :", __viewname)) != null)
			{
				$.ajax({
					type: "POST",
					url: "index_infos.php",
					dataType: 'json',
					data: { ACTION:"CHGVIEWNAME", p0:<?php echo($parm['p0'])?>, viewname:newAlias},
					success: function(data){
					}
				});
			}
		}
		
		function emptyBase()
		{
			if(confirm("<?php echo _('admin::base: Confirmer le vidage complet de la base')?>"))
			{
				$.ajax({
					type: "POST",
					url: "emptybase.php",
					dataType: 'json',
					data: { sbid:<?php echo($parm['p0']) ?>	},
					success: function(data){
					}
				});
			}
		}
		
		function refreshContent()
		{
			$.ajax({
				type: "POST",
				url: "index_infos.php",
				dataType: 'json',
				data: { ACTION:"P_BAR_INFO", p0:"<?php echo($parm['p0']) ?>"},
				success: function(data){
					__viewname = data.viewname;	// global
					if(data.viewname == '')
						$("#viewname").html("<i><?php echo(_('admin::base: aucun alias'))?></i>");
					else
						$("#viewname").html("<b>"+data.viewname+"</b>");
					$("#nrecords").text(data.records);
					$("#is_indexable").attr('checked', data.indexable);
					$("#xml_indexed").text(data.xml_indexed);
					$("#thesaurus_indexed").text(data.thesaurus_indexed);
					if(data.records > 0)
					{
						var p;
						p = 100*data.xml_indexed/data.records;
						$("#xml_indexed_bar").width(Math.round(2*p));	// 0..200px
						$("#xml_indexed_percent").text((Math.round(p*100)/100)+" %");
						p = 100*data.thesaurus_indexed/data.records;
						$("#thesaurus_indexed_bar").width(Math.round(2*p));
						$("#thesaurus_indexed_percent").text((Math.round(p*100)/100)+" %");
					}
					if(data.printLogoURL)
					{
						$("#printLogo").attr("src", data.printLogoURL);
						$("#printLogoDIV_NONE").hide();
						$("#printLogoDIV_OK").show();
					}
					else
					{
						$("#printLogoDIV_OK").hide();
						$("#printLogoDIV_NONE").show();
					}
				}
			});
			setTimeout("refreshContent();", 6000);
		}
		
		function deleteBase()
		{
			$.ajax({
				type: "POST",
				url: "index_infos.php",
				dataType: 'json',
				data: { ACTION:"P_BAR_INFO", p0:<?php echo($parm['p0']) ?> },
				success: function(data){
					if(data.records > 0)
					{
						alert("<?php echo(_('admin::base: vider la base avant de la supprimer'))?>");
					}
					else
					{
						if(confirm("<?php echo _('admin::base: Confirmer la suppression de la base')?>"))
						{
							$.ajax({
								type: "POST",
								url: "index_infos.php",
								dataType: 'json',
								data: { ACTION:"DODELETEBASE", p0:<?php echo($parm['p0'])?> },
								success: function(data){
									if(data.err == 0)		// ok
									{
										parent.$("#TREE_DATABASES").trigger('click');
										parent.reloadTree("bases");
									}
									else
									{
										if(data.errmsg)
											alert(data.errmsg);
									}
								}
							});
						}
					}
				}
			});
		}
		function clearAllLog()
		{
			if(confirm("<?php echo _('admin::base: Confirmer la suppression de tous les logs')?>"))
			{
				$.ajax({
					type: "POST",
					url: "index_infos.php",
					dataType: 'json',
					data: { ACTION:"CLEARALLLOG", p0:<?php echo($parm['p0'])?>
					},
					success: function(data){
					}
				});
			}
		}

		function mountColl()
		{
			$('#mount_coll').toggle();
		}

		function activateColl()
		{
			$('#activate_coll').toggle();
		}
		
		function umountBase()
		{
			if(confirm("<?php echo _('admin::base: Confirmer vous l\'arret de la publication de la base')?>"))
			{
				$.ajax({
					type: "POST",
					url: "index_infos.php",
					dataType: 'json',
					data: { ACTION:"UMOUNTBASE", p0:<?php echo($parm['p0'])?>
					},
					success: function(data){
						parent.$("#TREE_DATABASES").trigger('click');
					}
				});
			}
		}
		
		function showDetails(sta)
		{
			document.forms["manageDatabase"].target = "";
			document.forms["manageDatabase"].act.value = "";
			document.forms["manageDatabase"].sta.value = sta;
			document.forms["manageDatabase"].submit();
		}
		function chgOrd(srt)
		{
			document.forms["manageDatabase"].target = "";
			document.forms["manageDatabase"].act.value = "";
			document.forms["manageDatabase"].sta.value = "1";
			document.forms["manageDatabase"].srt.value = srt;
			document.forms["manageDatabase"].submit();
		}
		$(document).ready(function(){
			refreshContent();
		});
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
<?php


$out = "";

$title = $row["dbname"] . '@' . $row["host"] . ':' . $row["port"];
if($connbas && $connbas->isok())
{
	$title .= ' (MySQL ' . $connbas->server_info() . ')';
}
else
{
	$title .= ' <span style="color:#ff0000">' . _('admin::base: erreur : le serveur de base de donnee n\'est pas joignable') . '</span>';
}

if(isset($tcoll["bases"]["b".$parm["p0"]]))	// ok si p0=base_id (local) ok
{
	$row = $tcoll["bases"]["b".$parm["p0"]];
?>	
	<div style='margin:3px 0 3px 10px;'>
		<h2>
		<?php echo($title);?>
		</h2>
	</div>
	<div style='margin:3px 0 3px 10px;'>
		ID : <?php echo($sbas_id)?>
	</div>
	
	<div style='margin:3px 0 3px 10px;'>
		<?php echo(_('admin::base: Alias'))?> : <span id="viewname"><?php echo($HTMLviewname)?></span>
		<img src='/skins/icons/edit_0.gif' onclick="chgViewName();return(false);" style='vertical-align:middle'/>
	</div>
<?php 
}

	$row = $tcoll["bases"]["b".$parm["p0"]];
	
	$connbas = connection::getInstance($row['sbas_id']);
	$nrecords = 0;
	if($connbas)
	{
		// stats sur la base distante
		$sql = "SELECT COUNT(record_id) AS n FROM record";
		if($rsbas = $connbas->query($sql))
		{
			if($rowbas = $connbas->fetch_assoc($rsbas))
				$nrecords = $rowbas["n"];
			$connbas->free_result($rsbas);
		}
		
	}
	
	if($usrRight["bas_manage"] && $connbas && $connbas->isok()) 
	{
		// stats sur la base distante
		$out .= "<div style='margin:3px 0 3px 10px;'>";
		$out .= _('admin::base: nombre d\'enregistrements sur la base :').'<span id="nrecords"></span> ';
		
		if((int)$parm["sta"] < 1)
		{
			$out .= " (<a href=\"javascript:void(0);\" onclick=\"showDetails(1);return(false);\">"._('phraseanet:: details')."</a>)";
		}
		else
		{
			//***************** stats detaillees ***************
			$sql = "SELECT COUNT(kword_id) AS n FROM kword";
			if($rsbas = $connbas->query($sql))
			{
				if($rowbas = $connbas->fetch_assoc($rsbas))
				{
					$out .= ", &nbsp;&nbsp;" ;
					$out .=  _('admin::base: nombre de mots uniques sur la base : ').' '.$rowbas['n'];
				}
				$connbas->free_result($rsbas);
			}
			
			$sql = "SELECT COUNT(idx_id) AS n FROM idx";
			if($rsbas = $connbas->query($sql))
			{
				if($rowbas = $connbas->fetch_assoc($rsbas))
				{
					$out .= ", &nbsp;&nbsp;" ;
					$out .=  _('admin::base: nombre de mots indexes sur la base').' '.$rowbas["n"];
				}
				$connbas->free_result($rsbas);
			}
			
			if(GV_thesaurus)
			{
				$sql = "SELECT COUNT(thit_id) AS n FROM thit";
				if($rsbas = $connbas->query($sql))
				{
					if($rowbas = $connbas->fetch_assoc($rsbas))
					{
						$out .= ", &nbsp;&nbsp;" ;
						$out .=  _('admin::base: nombre de termes de Thesaurus indexes :').' '.$rowbas['n'];
					}
					$connbas->free_result($rsbas);
				}
			}

			$out .= " (<a href=\"javascript:void(0);\" onclick=\"showDetails(0);return(false);\">"._('admin::base: masquer les details')."</a>)<br />\n";
			

			//**************************** tableau recap par collection/objet ******************
			$trows = array();
			// ts les records de la base, y compris les records 'orphelins' (dont le coll_id ne correspond a aucune collection de 'coll')
			$sql = "SELECT record.coll_id, ISNULL(coll.coll_id) AS lostcoll, 
						COALESCE(asciiname, CONCAT('_',record.coll_id)) AS asciiname, name, 
						SUM(1) AS n, SUM(size) AS siz FROM (record, subdef) 
					LEFT JOIN coll ON record.coll_id=coll.coll_id 
					WHERE record.record_id = subdef.record_id 
					GROUP BY record.coll_id, subdef.name";

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
					// $k = $rowbas[$sortk1] ? $rowbas[$sortk1] : ("_".$rowbas["coll_id"]);
					if(!isset($trows[$rowbas[$sortk1]]))
						$trows[$rowbas[$sortk1]] = array();
					$trows[$rowbas[$sortk1]][$rowbas[$sortk2]] = array("coll_id"=>$rowbas["coll_id"], "asciiname"=>$rowbas["asciiname"], "lostcoll"=>$rowbas["lostcoll"], "name"=>$rowbas["name"], "n"=>$rowbas["n"], "siz"=>$rowbas["siz"]);
				}
				$connbas->free_result($rsbas);
			}
			// les coll vides (sans records)
			$sql = "SELECT coll.coll_id, 0, asciiname, '_' AS name, 0 AS n, 0 AS siz FROM coll LEFT JOIN record ON record.coll_id=coll.coll_id WHERE ISNULL(record.coll_id) GROUP BY coll.coll_id";
			if($rsbas = $connbas->query($sql))
			{
				while($rowbas = $connbas->fetch_assoc($rsbas))
				{
					if(!isset($trows[$rowbas[$sortk1]]))
						$trows[$rowbas[$sortk1]] = array();
					$trows[$rowbas[$sortk1]][$rowbas[$sortk2]] = array("coll_id"=>$rowbas["coll_id"], "asciiname"=>$rowbas["asciiname"], "lostcoll"=>0, "name"=>"", "n"=>0, "siz"=>0);
				}
				$connbas->free_result($rsbas);
			}
			ksort($trows);
			foreach($trows as $kgrp=>$vgrp)
				ksort($trows[$kgrp]);
			
			
			$out .= "<table class=\"ulist\"><col width=180px><col width=100px><col width=60px><col width=80px><col width=70px>\n";
			$out .= "<thead> <tr>";
			$out .= "<th onClick=\"chgOrd('col');\">";
			if($parm["srt"]=="col")
				$out .= "<img src=\"/skins/icons/tsort_desc.gif\">&nbsp;";			
			$out .= _('phraseanet:: collection') . "</th>";
			
			$out .= "<th onClick=\"chgOrd('obj');\">";
			if($parm["srt"]=="obj")
				$out .= "<img src=\"/skins/icons/tsort_desc.gif\">&nbsp;";			
			$out .= _('admin::base: objet'). "</th>";
			
			$out .= "<th>"._('admin::base: nombre')."</th>";
			$out .= "<th>"._('admin::base: poids')." (Mo)</th>";
			$out .= "<th>"._('admin::base: poids')." (Go)</th>";
			$out .= "</tr> </thead><tbody>";
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
						$out .= "<tr>\n";
						if($last_k1 !== $vrow["coll_id"])
						{
							if((int)$vrow["lostcoll"] <= 0)
							{
								$out .= "<td>" . $vrow["asciiname"]. "</td>\n" ;
							}
							else
							{
								$out .= "<td style=\"color:red\"><i>"._('admin::base: enregistrements orphelins')." </i>" . sprintf("(coll_id=%s)", $vrow["coll_id"]) . "</td>";
							}
							$last_k1 = $vrow["coll_id"];
						}
						else
						{
							$out .= "<td></td>\n" ;
						}
						if($last_k2 !== $vrow["name"])
							$out .= "<td>" .($last_k2 = $vrow["name"]). "</td>\n" ;
						else
							$out .= "<td></td>\n" ;
						$out .= "<td style=\"text-align:right\">&nbsp;" . $vrow["n"] . "&nbsp;</td>\n" ;
						if(extension_loaded("bcmath"))
							$mega = bcdiv($vrow["siz"], 1024*1024, 5);
						else
							$mega = $vrow["siz"] / (1024*1024);
						if(extension_loaded("bcmath"))
							$giga = bcdiv($vrow["siz"], 1024*1024*1024, 5);
						else
							$giga = $vrow["siz"] / (1024*1024*1024);
						$out .= "<td style=\"text-align:right\">&nbsp;" . sprintf("%.2f", $mega) . "&nbsp;</td>\n" ;
						$out .= "<td style=\"text-align:right\">&nbsp;" . sprintf("%.2f", $giga) . "&nbsp;</td>\n" ;
						$out .= "</tr>\n";
					}
					// $last_k1 = null;
				}
				$totobj += $midobj;
				if(extension_loaded("bcmath"))
					$totsiz = bcadd($totsiz, $midsiz, 0);
				else
					$totsiz += $midsiz;
				$out .= "<tr>\n";
					$out .= "<td></td>\n" ;
				$out .= "<td style=\"text-align:right\"><i>"._('report:: total')."</i></td>\n" ;
				$out .= "<td style=\"text-align:right; TEXT-DECORATION:overline\">&nbsp;" . $midobj . "&nbsp;</td>\n" ;
				if(extension_loaded("bcmath"))
					$mega = bcdiv($midsiz, 1024*1024, 5);
				else
					$mega = $midsiz / (1024*1024);
					
				if(extension_loaded("bcmath"))
					$giga = bcdiv($midsiz, 1024*1024*1024, 5);
				else
					$giga = $midsiz / (1024*1024*1024);
				$out .= "<td style=\"text-align:right; TEXT-DECORATION:overline\">&nbsp;" . sprintf("%.2f", $mega) . "&nbsp;</td>\n" ;
				$out .= "<td style=\"text-align:right; TEXT-DECORATION:overline\">&nbsp;" . sprintf("%.2f", $giga) . "&nbsp;</td>\n" ;
				$out .= "</tr>\n";
				$out .= "<tr><td colspan=\"5\"><hr /></td></tr>\n";
			}			
			$out .= "<tr>\n";
			$out .= "<td colspan=\"2\" style=\"text-align:right\"><b>"._('report:: total')."</b></td>\n" ;
			$out .= "<td style=\"text-align:right;\">&nbsp;<b>" . $totobj . "</b>&nbsp;</td>\n" ;
			if(extension_loaded("bcmath"))
				$mega = bcdiv($totsiz, 1024*1024, 5);
			else
				$mega = $totsiz / (1024*1024);
			if(extension_loaded("bcmath"))
				$giga = bcdiv($totsiz, 1024*1024*1024, 5);
			else
				$giga = $totsiz / (1024*1024*1024);
			$out .= "<td style=\"text-align:right;\">&nbsp;<b>" . sprintf("%.2f", $mega) . "</b>&nbsp;</td>\n" ;
			$out .= "<td style=\"text-align:right;\">&nbsp;<b>" . sprintf("%.2f", $giga) . "</b>&nbsp;</td>\n" ;
			$out .= "</tr>\n";

			$out .= "</tbody></table>";
			
			
		}
		$out .= "</div>";
		
		print($out);
?>		
			
		<div style='margin:3px 0 3px 10px;'>
			<div id='INDEX_P_BAR'>
				<div style='height:30px;'>
					<div>
						<?php echo(_('admin::base: document indexes en utilisant la fiche xml'));?> :
						<span id='xml_indexed'></span>
					</div>
					<div id='xml_indexed_bar' style='position:absolute;width:0px;height:15px;background:#d4d0c9;z-index:6;'>
					</div>
					<div id='xml_indexed_percent' style='position:absolute;width:198px;height:13px;text-align:center;border:1px solid black;z-index:10;'>
					</div>
				</div>
				<div style='height:30px;'>
					<div>
						<?php echo(_('admin::base: document indexes en utilisant le thesaurus'));?> :
						<span id='thesaurus_indexed'></span>
					</div>
					<div id='thesaurus_indexed_bar' style='position:absolute;width:0px;height:15px;background:#d4d0c9;z-index:6;'>
					</div>
					<div id='thesaurus_indexed_percent' style='position:absolute;width:198px;height:13px;text-align:center;border:1px solid black;z-index:10;'>
					</div>
				</div>
			</div>
		
			<div style='margin:15px 5px 0px 0px;'>
				<input type='checkbox'  id='is_indexable' onclick='makeIndexable(this)'/>
				<label for='is_indexable<?php echo($parm["p0"]);?>'>
					<?php echo(_('admin::base: Cette base est indexable'));?>
				</label>
				<div style='display:none' id='make_indexable_ajax_status'>&nbsp;</div>
			</div>
				
			<div>
				<a href="javascript:void(0);return(false);" onclick="reindex();return(false);">
					<?php echo(_('base:: re-indexer'));?>
				</a>
			</div>
		</div>
		
<?php		
		if($usrRight["bas_manage"]>0 )
		{
?>
		<div style='margin:20px 0 3px 10px;'>
			<a href="newcoll.php?act=GETNAME&p0=<?php echo($parm["p0"]);?>">
				<img src='/skins/icons/create_coll.png' style='vertical-align:middle'/>
				<?php echo(_('admin::base:collection: Creer une collection'));?>
			</a>
		</div>
		<?php 
		
		$databox = new databox($parm['p0']);
		$mountable_colls = $databox->get_mountable_colls();

		if(count($mountable_colls) > 0)
		{
		?>
			<div style='margin:20px 0 3px 10px;'>
				<a href="#" onclick="mountColl();">
					<img src='/skins/icons/create_coll.png' style='vertical-align:middle'/>
					<?php echo(_('admin::base:collection: Monter une collection'));?>
				</a>
			</div>
			<div id="mount_coll" style="display:none;">
				<form method="post" action="database.php" target="_self">
					<select name="coll_id">
					<?php
					foreach($mountable_colls as $coll_id=>$name)
					{
					?>
						<option value="<?php echo $coll_id?>"><?php echo $name?></option>
					<?php
					}
					?>
					</select>
					<?php
					$colls = $databox->list_colls();
					if(count($colls) > 0)
					{
					?>
						<span>
							<?php echo _('admin::base:collection: Vous pouvez choisir une collection de reference pour donenr des acces ')?>
						</span>
						<select name="othcollsel" >
							<option><?php echo _('choisir')?></option>
							<?php
							foreach ($colls as $base_id=>$name)
								echo "<option value='".$base_id."'>".$name.'</option>';
							?>
						</select>
					<?php
					}
					?>
					<input type="hidden" name="p0" value="<?php echo $parm['p0'];?>"/>
					<input type="hidden" name="act" value="MOUNT"/>
					<button type="submit"><?php echo _('Monter');?></button>
				</form>
			</div>
		<?php
		}
		$activable_colls = $databox->get_activable_colls();

		if(count($activable_colls) > 0)
		{
		?>
			<div style='margin:20px 0 3px 10px;'>
				<a href="#" onclick="activateColl();">
					<img src='/skins/icons/create_coll.png' style='vertical-align:middle'/>
					<?php echo(_('Activer une collection'));?>
				</a>
			</div>
			<div id="activate_coll" style="display:none;">
				<form method="post" action="database.php" target="_self">
					<select name="base_id">
					<?php
					foreach($activable_colls as $base_id)
					{
					?>
            <option value="<?php echo $base_id?>"><?php echo phrasea::bas_names($base_id)?></option>
					<?php
					}
					?>
					</select>
					<input type="hidden" name="p0" value="<?php echo $parm['p0'];?>"/>
					<input type="hidden" name="act" value="ACTIVATE"/>
					<button type="submit"><?php echo _('Activer');?></button>
				</form>
			</div>
		<?php
		}
		?>
		<div style='margin:20px 0 3px 10px;'>
			<a href="javascript:void(0);return(false);" onclick="clearAllLog();return(false);">
				<img src='/skins/icons/clearLogs.png' style='vertical-align:middle'/>
				<?php echo(_('admin::base: supprimer tous les logs'));?>
			</a>
		</div>
		<div style='margin:20px 0 13px 10px;'>
			<a href="javascript:void(0);return(false);" onclick="umountBase();return(false);">
				<img src='/skins/icons/db-remove.png' style='vertical-align:middle'/> 
				<?php echo(_('admin::base: arreter la publication de la base'));?>
			</a>
		</div>
		<div style='margin:3px 0 3px 10px;'>
			<a href="javascript:void(0);return(false);" onclick="emptyBase();return(false);">
				<img src='/skins/icons/trash.png' style='vertical-align:middle'/>
				<?php echo(_('admin::base: vider la base'));?>
			</a>
		</div>
		<div style='margin:3px 0 3px 10px;'>
			<a href="javascript:void(0);return(false);" onclick="deleteBase();return(false);">
				<img src='/skins/icons/delete.gif' style='vertical-align:middle'/>
				<?php echo(_('admin::base: supprimer la base'));?>
			</a>
		</div>
<?php
		}
	}
?>

		<!-- minilogo pour print pdf -->
		<div class='logo_boxes'>
			<div style="font-size:11px;font-weight:bold;margin:0px 3px 10px 0px;">
				<?php echo(_('admin::base: logo impression PDF'))?>
			</div>

			<?php echo($printLogoUploadMsg)?>

			<div id='printLogoDIV_OK' style='margin:0 0 5px 0; display:none'>
				<img id='printLogo' src="/print/<?php echo $sbas_id?>" />
	
<?php if($usrRight["bas_manage"]=="1") { ?>
				<a href="javascript:void();return(false);" onclick="deleteLogoPdf();return(false);">
					<?php echo(_('admin::base:collection: supprimer le logo'))?>
				</a>
<?php } ?>
			</div>

			<div id='printLogoDIV_NONE' style='margin:0 0 5px 0; display:none'>
				<?php echo(_('admin::base:collection: aucun fichier (minilogo, watermark ...)'))?>
			
				<form method="post" name="flpdf" action="./database.php" target="???" onsubmit="return(false);" ENCTYPE="multipart/form-data">
					<input type="hidden" name="p0"  value="<?php echo($parm["p0"]);?>" />
					<input type="hidden" name="sta" value="\" />
					<input type="hidden" name="srt" value="" />
					<input type="hidden" name="act" value="" />
					<input type="hidden" name="tid" value="" />
<?php if($usrRight["bas_manage"]=="1") { ?>
					<input name="newLogoPdf" type="file" />
					<input type='button' value='<?php echo(_('boutton::envoyer'));?>' onclick='sendLogopdf();'/>
					<br/>
					<?php echo(_('admin::base: envoyer un logo (jpeg 35px de hauteur max)'));?>
<?php } ?>
				</form>
			</div>

		</div>
		<form method="post" name="manageDatabase" action="./database.php" target="???" onsubmit="return(false);">
			<input type="hidden" name="p0"  value="<?php echo($parm["p0"])?>" />
			<input type="hidden" name="sta" value="0" />
			<input type="hidden" name="srt" value="" />
			<input type="hidden" name="act" value="???" />
			<input type="hidden" name="tid" value="???" />
		</form>
	</body>
</html>
