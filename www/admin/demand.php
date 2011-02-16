<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("deny","accept","accept_hd","watermark","template");

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
else{
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


$allnames = array();

foreach($ph_session['bases'] as $base)
{

	foreach($base['collections'] as $coll)
	{
		$allnames[$coll['base_id']] = $coll['name'];
		
	}
}

$templates = array();
if(!is_null($parm['template']))
{
	foreach($parm['template'] as $tmp)
	{
		if(trim($tmp) != '')
		{
			$tmp = explode('_',$tmp);
			
			if(count($tmp) == 2)
			{
				$templates[$tmp[0]] = $tmp[1];
			}
		}
	}
}
$deny = $accept = $options = array();

if(!is_null($parm['deny']))
{
	foreach($parm['deny'] as $den)
	{
		$den = explode('_',$den);
		if(count($den) == 2 && !isset($templates[$den[0]]))
		{
			$deny[$den[0]][$den[1]]=$den[1];
		}
	}
}

if(!is_null($parm['accept']))
{
	foreach($parm['accept'] as $acc)
	{
		$acc = explode('_',$acc);
		if(count($acc) == 2 && !isset($templates[$acc[0]]))
		{
			$accept[$acc[0]][$acc[1]] = $acc[1];
			$options[$acc[0]][$acc[1]]=array('HD'=>false,'WM'=>false);
		}
	}
}

if(!is_null($parm['accept_hd']))
{
	foreach($parm['accept_hd'] as $accHD)
	{
		$accHD = explode('_',$accHD);
		if(count($accHD) == 2 && isset($accept[$accHD[0]]) && isset($options[$accHD[0]][$accHD[1]]))
		{
			$options[$accHD[0]][$accHD[1]]['HD'] = true;
		}
	}
}
if(!is_null($parm['watermark']))
{
	foreach($parm['watermark'] as $wm)
	{
		$wm = explode('_',$wm);
		if(count($wm) == 2 && isset($accept[$wm[0]]) && isset($options[$wm[0]][$wm[1]]))
		{
			$options[$wm[0]][$wm[1]]['WM'] = true;
		}
	}
}

if(!is_null($templates) || !is_null($parm['deny']) || !is_null($parm['accept']))
{
	$done = array();
	
	$cache_to_update = array();
	
	foreach($templates as $usr=>$template_id)
	{
		$cache_to_update[$usr] = true;
		
		$sql = "REPLACE INTO sbasusr (SELECT null as sbasusr_id, sbas_id, '".$conn->escape_string($usr)."' as usr_id, bas_manage, bas_modify_struct, bas_modif_th, bas_chupub FROM sbasusr WHERE usr_id='".$conn->escape_string($template_id)."')";
		$conn->query($sql);
		
		$sql = "REPLACE INTO basusr (SELECT null as id, base_id, '".$conn->escape_string($usr)."' as usr_id, canpreview, canhd, canputinalbum, candwnldhd, candwnldsubdef, candwnldpreview, cancmd, canadmin, actif, canreport, canpush, creationdate, basusr_infousr, mask_and, mask_xor, restrict_dwnld, month_dwnld_max, remain_dwnld, time_limited, limited_from, limited_to, canaddrecord, canmodifrecord, candeleterecord, chgstatus, lastconn, imgtools, manage, modify_struct, bas_manage, bas_modify_struct, needwatermark FROM basusr WHERE usr_id='".$conn->escape_string($template_id)."')";
		if($conn->query($sql))
		{
			if(!isset($done[$usr]))
				$done[$usr] = array();
				
			$sql = 'SELECT base_id FROM basusr WHERE usr_id = "'.$conn->escape_string($template_id).'" AND base_id NOT IN (SELECT base_id FROM basusr WHERE usr_id = "'.$conn->escape_string($usr).'")';
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					$done[$usr][$row['base_id']] = true;
				}
			}
		
			$sql = "DELETE FROM demand WHERE usr_id='".$conn->escape_string($usr)."'";
			$conn->query($sql);
			
			$sql = "SELECT usr_login as lastModel from usr where usr_id='".$conn->escape_string($template_id)."'";
			if($rs = $conn->query($sql))
			{
				if($row = $conn->fetch_assoc($rs))
				{
					$sql = "UPDATE usr SET lastModel = '".$conn->escape_string($row['lastModel'])."' WHERE usr_id = '".$conn->escape_string($usr)."' ";
					$conn->query($sql);
				}
			}
		}
	}
	foreach($deny as $usr=>$bases)
	{
		$cache_to_update[$usr] = true;
		foreach($bases as $bas)
		{
			$sql = "UPDATE demand SET en_cours=0,refuser=1,date_modif=now() WHERE usr_id='".$conn->escape_string($usr)."' AND base_id='".$conn->escape_string($bas)."'";
		
			if($conn->query($sql))
			{
			
				if(!isset($done[$usr]))
					$done[$usr] = array();
					
				$done[$usr][$bas] = false;
			}
		}
	}
	foreach($accept as $usr=>$bases)
	{
		$cache_to_update[$usr] = true;
		foreach($bases as $bas)
		{
			$sql = "INSERT INTO sbasusr (sbas_id,usr_id,bas_manage, bas_modify_struct) VALUES ('".$conn->escape_string(phrasea::sbasFromBas($bas))."','".$conn->escape_string($usr)."','0','0')";
			$conn->query($sql);
			
			$wm = $options[$usr][$bas]['WM'];
			$hd = $options[$usr][$bas]['HD'];
			$f = $v = '';
				$f.="base_id,";			$v.="$bas,";
				$f.="usr_id,";			$v.="$usr,";
				$f.="canpreview,";		$v.="1,";
				$f.="canhd,";			$v.="0,";
				$f.="canputinalbum,";	$v.="1,";
		
				if($hd)
				{	$f.="candwnldhd,";		$v.="1,"; }
				else
				{	$f.="candwnldhd,";		$v.="0,"; }
				
				if($wm)
				{	$f.="needwatermark,";		$v.="1,"; }
				else
				{	$f.="needwatermark,";		$v.="0,"; }
	
				$f.="candwnldsubdef,";	$v.="0,";
				$f.="candwnldpreview,";	$v.="1,";
				$f.="cancmd,";			$v.="0,";
				$f.="canadmin,";		$v.="0,";
				$f.="actif,";			$v.="1,";
				$f.="canreport,";		$v.="0,";
				$f.="canpush,";			$v.="0,";
				$f.="creationdate,";	$v.="now(),";
				$f.="basusr_infousr,";	$v.="'',";
				$f.="mask_and,";		$v.="0,";
				$f.="mask_xor,";		$v.="0,";
				$f.="restrict_dwnld,";	$v.="0,";
				$f.="month_dwnld_max,";	$v.="0,";
				$f.="remain_dwnld,";	$v.="0,";
				$f.="time_limited,";	$v.="0,";
				$f.="canaddrecord,";	$v.="0,";
				$f.="canmodifrecord,";	$v.="0,";
				$f.="candeleterecord";	$v.="0";
			
			$sql = "INSERT INTO basusr ( $f ) VALUES ( $v )";
			$conn->query($sql);
			
			if(!isset($done[$usr]))
				$done[$usr] = array();
				
			$done[$usr][$bas] = true;
			
			$sql = "DELETE FROM demand WHERE usr_id='".$conn->escape_string($usr)."' AND base_id='".$conn->escape_string($bas)."'";
			$conn->query($sql);
		}
	}
	
	$cache_user = cache_user::getInstance();
	foreach($cache_to_update as $usr_id=>$true)
		$cache_user->delete($usr_id);
	
	foreach($done as $usr=>$bases)
	{
		$sql = 'SELECT usr_mail FROM usr WHERE usr_id = "'.$conn->escape_string($usr).'"';
		$accept = $deny = '';
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				if(p4string::checkMail($row['usr_mail']))
				{
					foreach($bases as $bas=>$isok)
					{
						if(isset($allnames[$bas]))
						{
							if($isok === true)
								$accept .= '<li>'.$allnames[$bas]."</li>\n";
							if($isok === false)
								$deny .= '<li>'.$allnames[$bas]."</li>\n";
						}
					}
				}
				
				if(($accept != '' || $deny != ''))				
				{
					mail::register_confirm($row['usr_mail'], $accept, $deny);
				}
			}
		}
	}
}

phrasea::headers();
?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
		<script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js">
		</script>
		<script type="text/javascript" src="/include/minify/f=include/jquery.tooltip.js">
		</script>
		<script type="text/javascript">
		var bodySize = {x:100,y:100};
		function resize(){

			bodySize.y = $(window).height();
			bodySize.x = $(window).width();
			$('#tab_demandes').height(bodySize.y-80)
		}
		$(document).ready(function(){
			
			resize();
			$(window).resize(function(){
				resize();
			});
			
		});
		function checkDeny(el)
		{
			if($(el)[0].checked)
			{
				$('.disabler_'+$(el).attr('id').substring(5)).removeAttr('checked');
			}
			else
			{
			}
		}

		function checkAdd(el)
		{
			if($(el)[0].checked)
			{
				$('#accept_'+$(el).attr('id').substring(10))[0].checked = true;
				$('#deny_'+$(el).attr('id').substring(10))[0].checked = false;
			}
		}
		function checkRemove(el)
		{
			if(!$(el)[0].checked)
				$('.disabler_'+$(el).attr('id').substring(7)).each(function(){$(this)[0].checked = false;});
			else
				$('#deny_'+$(el).attr('id').substring(7))[0].checked = false;
		}

		function modelChecker(usr)
		{
			var val = $('#model_'+usr)[0].value;

			var bool = false;
			if(!isNaN(val) && val!='')
					bool = true;

			if(bool)
				$('#sub_usr_'+usr).slideToggle('slow');
			else
				$('#sub_usr_'+usr).slideToggle('slow');

			if(bool)
				$('.checker_'+usr).attr('disabled','disabled');
			else
				$('.checker_'+usr).removeAttr('disabled');
			
		}

		function checkAll(that)
		{
			var bool = true;
			var first = true;
			$('.'+that+'_checker:not(:disabled)').each(function(){
//				if(!$(this)[0].disabled)
//				{
					if(first && $(this)[0].checked)
							bool = false;
					$(this)[0].checked = bool;
					first = false;
					if(that == 'deny')
					{
								checkDeny($(this));
					}
					if(that == 'accept_hd')
						checkAdd(this)
					if(that == 'watermark')
						checkAdd(this)
					if(that == 'accept')
						checkRemove(this)
//				}
				});
		}
		</script>
		<style>
		#tooltip{
			background-color:black;
			color:white;
			position:absolute;
		}
		</style>

	</head>
	
	<body><form method='post' action='demand.php'>
	
<?php

$out = "";


	$lastMonth = time() - (3 * 4 * 7 * 24 * 60 * 60);

	$sql = "delete from demand where date_modif <'".date('Y-m-d', $lastMonth)."'";
	$conn->query($sql);


// on filtre les bases administrables
$sql = "SELECT base_id FROM basusr WHERE usr_id='".$conn->escape_string($session->usr_id)."' AND canadmin=1";


	$baslist = array();
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$baslist[] = $row["base_id"];
		}
		$conn->free_result($rs);
	}


	$models = '<option value="">aucun</option>';
	$sql = 'SELECT usr_id, usr_login FROM usr WHERE model_of = "'.$conn->escape_string($session->usr_id).'"';
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
			$models .= '<option value="%stemplate%s_'.$row['usr_id'].'">'.$row['usr_login'].'</option>';
	}
	
	$sql = "SELECT demand.date_modif,demand.base_id,usr.usr_id , usr.usr_login ,usr.usr_nom,usr.usr_prenom, usr.societe,CONCAT(usr.usr_nom,' ',usr.usr_prenom,'\n',fonction,' (',societe,')') AS info 
		FROM (demand INNER JOIN usr on demand.usr_id=usr.usr_id AND demand.en_cours=1) 
		WHERE (base_id='" . implode("' OR base_id='",$baslist) ."') ORDER BY demand.usr_id DESC,demand.base_id ASC";
	
	//$out .= '<div>'.$baslibs . "</div>";

	if($rs = $conn->query($sql))
	{

		$out .= "<div id=\"top_box\" style='height:40px;overflow:hidden;'>";
		$out .= "<div id=\"title\">"._('admin:: demandes en cours')."</div>";
		
		$out .= "<div>";
		$out .= "<table style='width:100%'>".
						"<tr>".
							"<td style='width:20px'><img onclick='checkAll(\"deny\")' style='cursor:pointer;' class='tipInfoUsr' title=\""._('admin:: refuser l\'acces')."\" src='/skins/icons/delete.gif'/></td>".
							"<td style='width:20px'><img onclick='checkAll(\"accept\")' style='cursor:pointer;' class='tipInfoUsr' title='"._('admin:: donner les droits de telechargement et consultation de previews')."' src='/skins/icons/cmdok.gif'/></td>".
							"<td style='width:20px'><span onclick='checkAll(\"accept_hd\")' style='cursor:pointer;' class='tipInfoUsr' title='"._('admin:: donner les droits de telechargements de preview et hd')."'>HD</span></td>".
							"<td style='width:20px'><span onclick='checkAll(\"watermark\")' style='cursor:pointer;' class='tipInfoUsr' title='"._('admin:: watermarquer les documents')."'>W</span></td>".
							"<td style='width:120px'>" . _('admin::compte-utilisateur identifiant') . "</td>".
							"<td style='width:auto'>" . _('admin::compte-utilisateur societe') . "</td>".
							"<td style='width:130px'>" . _('admin::compte-utilisateur date d\'inscription') . "</td>".
							"<td style='width:150px'>"._('admin::collection')."</td>".
						"</tr>".
					"</table>";
		
		
		$out .= "</div>";
		$out .= "</div><div  id=\"tab_demandes\" style='overflow-y:scroll;overflow-x:hidden'>";
		$out .= "<table style='width:100%' class='ulist' cellspacing='0' cellpading='0'>".
						"<tr>".
							"<td style='width:20px'></td>".
							"<td style='width:20px'></td>".
							"<td style='width:20px'></td>".
							"<td style='width:20px'></td>".
							"<td style='width:120px'></td>".
							"<td style='width:auto'></td>".
							"<td style='width:130px'></td>".
							"<td style='width:150px'></td>".
						"</tr>";
		$class = '';
		$currentUsr = null;
		while(($row = $conn->fetch_assoc($rs)))
		{
			if($row['usr_id'] != $currentUsr)
			{
				if($currentUsr !== null)
				{
					
					$out .= '</table></div></td></tr>';
				}
				
				$currentUsr = $row['usr_id'];
							$class = $class=='g'?'':'g';
			
				$info  = "" ;
				$sqlInfo = "SELECT * FROM usr WHERE usr_id='".$conn->escape_string($row['usr_id'])."'";
				if($rsInfo = $conn->query($sqlInfo))
				{
					if($rowInfo = $conn->fetch_assoc($rsInfo))
					{
						$info .= "<div><div>" . _('admin::compte-utilisateur identifiant') . " : " .  ($rowInfo["usr_login"]) ."</div>";
						
						$info .=  "<div>". _('admin::compte-utilisateur nom') . "/" . _('admin::compte-utilisateur prenom') . " : "  ;
						$info .= ($rowInfo["usr_nom"]) ." ";
						$info .= ($rowInfo["usr_prenom"]);
						$info .= "</div>";
						 
						$info .=  "<div>". _('admin::compte-utilisateur email') . " : "  ;
						$info .= ($rowInfo["usr_mail"])  ;
						$info .= "</div>";
						 
						$info .=  "<div>". _('admin::compte-utilisateur telephone') . " : "  ;
						$info .= ($rowInfo["tel"])  ;
						$info .= "</div>";
						 
						$info .=  "<div>". _('admin::compte-utilisateur poste') . " : "  ;
						$info .= ($rowInfo["fonction"])  ;
						$info .= "</div>";
						 
						$info .=  "<div>". _('admin::compte-utilisateur societe') . " : "  ;
						$info .= ($rowInfo["societe"])  ;
						$info .= "</div>";
						 
						$info .=  "<div>". _('admin::compte-utilisateur activite') . " : "  ;
						$info .= ($rowInfo["activite"])  ;
						$info .= "</div>";
						 
						$info .= "<div>" . _('admin::compte-utilisateur adresse'). " : ";	
						$info .= "". ($rowInfo["adresse"]);
						$info .= "</div>";
						 
						$info .= "<div>";
							
						$info .= ($rowInfo["cpostal"])." ";
								
						$info .= ($rowInfo["ville"]);
						$info .= "</div>". "</div>";
	 
					}
				}
				
				$info = "<div style='margin:5px;'>".$info."</div>";
				
				
				$out .= '<tr class="tipInfoUsr '.$class.'" title="'.str_replace('"','&quot;',$info).'"  id="USER_' . $row['usr_id'] .'"' . '>' ;
				$out .= "<td>";
				$out .= " ";
				$out .= "</td>";
				$out .= "<td>";
				$out .= " ";
				$out .= "</td>";
				$out .= "<td>";
				$out .= " ";
				$out .= "</td>";
				$out .= "<td>";
				$out .= " ";
				$out .= "</td>";
				$out .= '<td>';
				$out .= '' . ($row["usr_login"]) ;
				$out .= '</td>' ;
	
				$tmp = $row["usr_nom"]." ".$row["usr_prenom"].( $row["societe"]?" (".$row["societe"].")":"" );
				$out .= '<td>' . ( trim($tmp) ). '</td>' ;
	
				$out .= '<td colspan="2"> '._('admin:: appliquer le modele  ').'  <select name="template[]" id="model_'.$row['usr_id'].'" onchange="modelChecker('.$row['usr_id'].')">'.str_replace('%stemplate%s',$row['usr_id'],$models).'</select></td>';
					
				$out .= '</tr>';
				$out .= '<tr><td colspan="8"><div id="sub_usr_'.$row['usr_id'].'"><table cellspacing="0" cellpading="0" style="width:100%">'.
						"<tr style='height:0px;dispolay:none;'>".
							"<td style='width:20px'></td>".
							"<td style='width:20px'></td>".
							"<td style='width:20px'></td>".
							"<td style='width:20px'></td>".
							"<td style='width:120px'></td>".
							"<td style='width:auto'></td>".
							"<td style='width:130px'></td>".
							"<td style='width:150px'></td>".
						"</tr>";
				
			}

				$out .= '<tr class="'.$class.'">' ;
				$out .= "<td>";
				$out .= "<input name='deny[]' value='".$row['usr_id']."_".$row['base_id']."' onclick='checkDeny(this)' id='deny_".$row['usr_id']."_".$row['base_id']."' class='deny_checker tipInfoUsr checker_".$row['usr_id']."' title=\""._('admin:: refuser l\'acces')."\" class='' type=\"checkbox\"/>";
				$out .= "</td>";
				$out .= "<td>";
				$out .= "<input name='accept[]' value='".$row['usr_id']."_".$row['base_id']."' onclick='checkRemove(this)' id='accept_".$row['usr_id']."_".$row['base_id']."' class='disabler_".$row['usr_id']."_".$row['base_id']." accept_checker tipInfoUsr checker_".$row['usr_id']."' title='"._('admin:: donner les droits de telechargement et consultation de previews')."' class='checker_".$row['usr_id']."' type=\"checkbox\"/>";
				$out .= "</td>";
				$out .= "<td>";
				$out .= "<input name='accept_hd[]' value='".$row['usr_id']."_".$row['base_id']."' onclick='checkAdd(this)' id='accept_hd_".$row['usr_id']."_".$row['base_id']."' class='disabler_".$row['usr_id']."_".$row['base_id']." accept_hd_checker tipInfoUsr checker_".$row['usr_id']."' title='"._('admin:: donner les droits de telechargements de preview et hd')."' class='checker_".$row['usr_id']."' type=\"checkbox\"/>";
				$out .= "</td>";
				$out .= "<td>";
				$out .= "<input name='watermark[]' value='".$row['usr_id']."_".$row['base_id']."' onclick='checkAdd(this)' id='watermark_".$row['usr_id']."_".$row['base_id']."' class='disabler_".$row['usr_id']."_".$row['base_id']." watermark_checker tipInfoUsr checker_".$row['usr_id']."' title='"._('admin:: watermarquer les documents')."' class='checker_".$row['usr_id']."' type=\"checkbox\"/>";
				$out .= "</td>";
				$out .= "<td colspan='2'>";
				$out .= "</td>";
	
				$out .= '<td>' . ($row["date_modif"]) . '</td>' ;
	
				if(isset($allnames[$row["base_id"]]))
					$out .= '<td>' . $allnames[$row["base_id"]]. '</td>';
				else
					$out .= '<td>' . $row["base_id"] . '</td>';
					
				$out .= '</tr>';
		}
		
		$out .= "			</table><br />\n";
		$out .= "		</div>\n";
		
		$out .= 	"</table>";
		
		$out .= "</div>";

	}
	$conn->free_result($rs);

	$out .= "		<div id='bottom_box' style='height:40px;overflow:hidden;'>";
	$out .= "			<div id=\"divboutdemand\" style=\"text-align:center;\">";
	$out .= "				<input type='submit' value='"._('boutton::valider')."' />";
	$out .= "			</div>";
	$out .= "		</div></form>";
	$out .= "	</body>";
	$out .= "</html>";

print($out);

?>
<script>$('.tipInfoUsr').tooltip();</script>
	</body>
</html>
