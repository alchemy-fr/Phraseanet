<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("newrest" , "newmax" , "newlimit" , "act" , "ul", "b", "bl", "type"); 

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

phrasea::headers();


?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
<script type="text/javascript">	
self.resizeTo(430,200);
function valid()
{

	document.send.act.value = 'UPD';
	document.send.submit();
}

function editmode()
{
	if(document.getElementById('boutreplacedroit'))
		document.getElementById('boutreplacedroit').style.visibility = "hidden";
	if(document.getElementById('spandroit'))
		document.getElementById('spandroit').style.visibility = "visible";
}
function chckRestrict(val)
{	
	// boutreplacedroit
	// spandroit
	if(document.getElementById('boutreplacedroit'))
		document.getElementById('boutreplacedroit').style.visibility = "hidden";
	if(document.getElementById('spandroit'))
		document.getElementById('spandroit').style.visibility = "visible";
	
	if(val==false)
	{
		document.getElementById('cc_restrict_y').src = "/skins/icons/ccoch1.gif";
		document.getElementById('cc_restrict_y').chk ="1";
		
		document.getElementById('cc_restrict_n').src = "/skins/icons/ccoch0.gif";
		document.getElementById('cc_restrict_n').chk ="0";
		//chg[collid+'_restrict']='1';
		document.send.newlimit.value = '1';
	}
	else
	{
		document.getElementById('cc_restrict_y').src = "/skins/icons/ccoch0.gif";
		document.getElementById('cc_restrict_y').chk ="0";
		
		document.getElementById('cc_restrict_n').src = "/skins/icons/ccoch1.gif";
		document.getElementById('cc_restrict_n').chk ="1";
		//chg[collid+'_restrict']='0';
		document.send.newlimit.value = '0';
	}
	document.getElementById('restrict_droit').disabled = val;
	document.getElementById('restrict_rest').disabled = val;
	
}
function chgrest(obj)
{
	if(obj.value>=0)
		document.send.newrest.value = obj.value; // "newrest" , "newmax"
	else
	{	
		alert('<?php echo _('admin::user: erreur dans les restrictions de telechargement');?>');
		obj.value = 0;
		obj.select();
	}
	
}
function chgmax(obj)
{
	if(obj.value>=0)
		document.send.newmax.value = obj.value;
	else
	{
	
		alert('<?php echo _('admin::user: erreur dans les restrictions de telechargement')?>');
		obj.value = 0;
		obj.select();
	}
	
}
</script>
</head>
	<body>
<?php

$myuser = null;
$list = "";
$usrInfo = null;
$servercollid = null;

$month_dwnld_max = null;
$remain_dwnld = null;


$conn = connection::getInstance();
if(!$conn)
{
	die();
}
else 
{		

	$sql = "SELECT b.sbas_id, b.base_id,b.active,s.host,s.port,s.dbname,s.sqlengine,s.user,s.pwd,b.server_coll_id 
				FROM bas b, sbas s where b.base_id='".$conn->escape_string($parm["b"])."' AND b.sbas_id = s.sbas_id";
	$distcaract = null;			
	if($rs = $conn->query($sql))
	{
		if($row = $conn->fetch_assoc($rs) )
			$distcaract = $row;			
		$conn->free_result($rs);
	}
	
	if($parm["type"]=="C")
	{	
		## on se connect a la base distante
		$conn2 = connection::getInstance($distcaract['sbas_id']);
		if($conn2)		
		{	
			$sql2 = "SELECT coll_id,htmlname from coll";
			if($rs2 = $conn2->query($sql2))
			{
				while(($row2 = $conn2->fetch_assoc($rs2)) )
				{
					
					if($row2["coll_id"]==$distcaract["server_coll_id"])
						$servercollid[$distcaract["base_id"]] = $row2["htmlname"];
				}
				$conn2->free_result($rs2);
			}
		}	
	}		
		
	$list="";
		/// je verif j'ai le droit d'admin les coll precedement citees
		$sql = "SELECT base_id 
				FROM (usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id)
   				WHERE usr.usr_id='".$conn->escape_string($usr_id)."' AND basusr.canadmin=1 and basusr.base_id IN (".$parm["bl"].")";
		if($parm["type"]=="C")
		{
			$sql = "SELECT base_id 
				FROM (usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id)
   				WHERE usr.usr_id='".$conn->escape_string($usr_id)."' AND basusr.canadmin=1 and basusr.base_id IN (".$parm["b"].")";
		
		}	
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs) )
			{				
				if($list!="")
					$list.= ",";
				$list.= $row["base_id"];
			}		
			$conn->free_result($rs);
		}	
	
	
	if($parm["act"]=="UPD")
	{
		if($parm["newlimit"]!="???" || $parm["newrest"]!="???" || $parm["newmax"]!="???" )
		{
			$sql = "";
			if($parm["newlimit"]!="???") // restrict_dwnld
			{			
				if($sql!="")$sql.=",";
				$sql .= "restrict_dwnld='".$conn->escape_string($parm["newlimit"])."'";
			}		
			if($parm["newrest"]!="???") // remain_dwnld
			{
				if($sql!="")$sql.=",";
				$sql .= "remain_dwnld='".$conn->escape_string($parm["newrest"])."'";
			}
			
			if($parm["newmax"]!="???") // month_dwnld_max
			{
				if($sql!="")$sql.=",";
				$sql .= "month_dwnld_max='".$conn->escape_string($parm["newmax"])."'";
			}			
			$sql = "UPDATE basusr SET ". $sql ;
			switch($parm["type"])
			{
				case 'B':
					$sql.= " WHERE usr_id='". $conn->escape_string($parm["ul"]) . "' AND base_id IN (".$parm["bl"].")";
				break;
				case 'C':
					$sql.= " WHERE usr_id='". $conn->escape_string($parm["ul"]) . "' AND base_id='".$conn->escape_string($parm["b"])."'";
				break;
			}
			//echo $sql;
			$rs = $conn->query($sql);
			if($rs)
			{
				echo "<br><br><br><center>"._('forms::modifications enregistrees')."</center>";
			}
			else
			{
				echo "<br><br><br><center>"._('forms::erreurs lors de l\'enregistrement des modifications')."</center>";
			}	
		}
		else
		{
			echo "<br><br><br><center>"._('forms::aucune modification a enregistrer')."</center>";
		
		}
?>		
	<br><br><br>
	<center>
		<span class="bout"  onClick="javascript:self.close();" ><?php echo _('boutton::fermer')?></span>
	</center>
<?php
		exit();
	}
	
	
	$sql = "SELECT usr_login,sum(1) as nb,restrict_dwnld
		FROM (usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id) WHERE usr.usr_id='".$conn->escape_string($parm["ul"])."' AND basusr.base_id IN (".$list.") group by restrict_dwnld";
	
	$idtval = true;
	$idtvalvalue = "0";	
	if($rs = $conn->query($sql))
	{			
		$rep = $conn->num_rows($rs);	
		if($row = $conn->fetch_assoc($rs)) 
		{
			$myuser = $row;
			if($rep==1)
			{
				## pour le moment on a tjs la meme entree de restriction suivant les coll de cette base
				## on doit verif si on vient pas de clicker sur acceder ce qui modifierai la valeur finale
				if($row["restrict_dwnld"]=="0")
				{
					$idtval = true;
					$idtvalvalue = "0";
				}
				else
				{
					if( count(explode(",",$list))==$row["nb"])
					{
						$idtval = true;
						$idtvalvalue = "1";
						
						$sql = "SELECT distinct(remain_dwnld)
								FROM (usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id) 
								WHERE usr.usr_id='".$conn->escape_string($parm["ul"])."' AND basusr.base_id IN (".$list.")";
						if($rs2 = $conn->query($sql))
						{	
							if($conn->num_rows($rs2)==1)
							{
								if($row3 = $conn->fetch_assoc($rs2)) 
									$remain_dwnld = $row3["remain_dwnld"];
							
							}
							$conn->free_result($rs2);
						}
						$sql = "SELECT distinct(month_dwnld_max)
								FROM (usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id) 
								WHERE usr.usr_id='".$conn->escape_string($parm["ul"])."' AND basusr.base_id IN (".$list.")";
						if($rs2 = $conn->query($sql))
						{	
							if($conn->num_rows($rs2)==1)
							{
								$month_dwnld_max="";
								if($row3 = $conn->fetch_assoc($rs2)) 
									$month_dwnld_max = $row3["month_dwnld_max"];
									
							}
							$conn->free_result($rs2);
						}
						
					}
					else
					{
						$idtval = false;
						$idtvalvalue = "2";
					}
				}			
			
			} 
			else
			{
				$idtval = false;
				$idtvalvalue = "2";
			}
			$conn->free_result($rs);
		}
		else
		{		
			$sql = "SELECT usr_login FROM usr WHERE usr.usr_id='".$conn->escape_string($parm["ul"])."'";
			if($rs = $conn->query($sql))
			{	
				if($row = $conn->fetch_assoc($rs)) 
				{
					$myuser = $row;
				}
				$conn->free_result($rs);
			}
		}
	}
	
	
?>
		<span class="basename">
			<?php echo _('phraseanet:: base')?> "<?php echo $distcaract["dbname"]?>"
<?php
	if($parm["type"]=="C")
	{
		if(isset($servercollid[$parm["b"]]))
			echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;Collection : ". $servercollid[$parm["b"]];
	}

?>			
		</span>
		
		<span class="loginspan">
			<?php echo _('admin::compte-utilisateur identifiant')?> : <b><?php echo $myuser["usr_login"]?></b>
		</span>
		<br><br><br>
		<center>
		<br>
		</center>
		<div class="FT" style="left:10px; clear:left"><?php echo _('admin::user: restrictions de telechargement')?></div>
			<span class="FIELD" style="left:10px;height:65px; width:390px;">	
			  <center>
<?php
		$collid="";
		
		if($idtvalvalue=="0")	 // non - non 
		{
?>		
				 <?php echo _('phraseanet::oui')?> <img id="cc_restrict_y" src="ccoch0.gif" border="0" onClick="chckRestrict(false);" chk="0">
				 <?php echo _('phraseanet::non')?> <img id="cc_restrict_n" src="ccoch1.gif" border="0" onClick="chckRestrict(true);" chk="1">
				  </center>				
				<br>
				 <?php echo _('admin::user:quota: droit')?>&nbsp;:&nbsp;<input type="text"  name="restrict_droit" id="restrict_droit" value="" style="width:55px" disabled onChange="javascript:chgmax(this);" >&nbsp;<?php echo _('admin::user:quota: par mois')?>
				 <?php echo _('admin::user:quota: reste')?>&nbsp;:&nbsp;<input type="text"   name="restrict_rest"  id="restrict_rest" value="" style="width:55px" disabled onChange="javascript:chgrest(this);">
<?php
		}
		elseif($idtvalvalue=="1")	// oui - oui
		{
				// val idt
				if( $month_dwnld_max!=null && $remain_dwnld!=null )
				{
?>				 <?php echo _('phraseanet::oui')?> <img id="cc_restrict_y" src="ccoch1.gif" border="0" onClick="chckRestrict(false);" chk="1">
				 <?php echo _('phraseanet::non')?> <img id="cc_restrict_n" src="ccoch0.gif" border="0" onClick="chckRestrict(true);" chk="0">
				  </center>				
				<br>
				 <?php echo _('admin::user:quota: droit')?> <input type="text" name="restrict_droit" id="restrict_droit" value="<?php if($month_dwnld_max)echo $month_dwnld_max;?>" style="width:55px"  onChange="javascript:chgmax(this);" >&nbsp;<?php echo _('admin::user:quota: par mois')?>
				 <?php echo _('admin::user:quota: reste')?> <input type="text"  name="restrict_rest"  id="restrict_rest" value="<?php if($remain_dwnld)echo $remain_dwnld; ?>" style="width:55px"   onChange="javascript:chgrest(this);">
<?php
				}
				else
				{	// val non idt
				
?>				
				
				 <?php echo _('phraseanet::oui')?> <img id="cc_restrict_y" src="ccoch1.gif" border="0" onClick="chckRestrict(false);" chk="1">
				  <?php echo _('phraseanet::non')?> <img id="cc_restrict_n" src="ccoch0.gif" border="0" onClick="chckRestrict(true);" chk="0">
				  </center>				
				<br>
				<span id="spandroit" style="visibility:hidden">
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo _('admin::user:quota: droit')?>&nbsp;:&nbsp;<input type="text" name="restrict_droit" id="restrict_droit" value="<?php if($month_dwnld_max)echo $month_dwnld_max;?>" style="width:55px"  onChange="javascript:chgmax(this);" >&nbsp;<?php echo _('admin::user:quota: par mois')?>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo _('admin::user:quota: reste')?>&nbsp;:&nbsp;<input type="text"  name="restrict_rest"  id="restrict_rest" value="<?php if($remain_dwnld)echo $remain_dwnld; ?>" style="width:55px"   onChange="javascript:chgrest(this);">
				</span>
				<center><a href="javascript:void('');" onClick="editmode();" ><span  id="boutreplacedroit" style="width:350px;position:absolute;left:8px;top:30px;background-color:#DDDDDD; color:#000000;TEXT-DECORATION:none;cursor:hand"  ><b><?php echo _('admin::user:quota: les valeurs des quotas sont differentes entre les collections et ne peuvent etre affichees')?>.</b><BR><?php echo _('admin::user:quota: forcer l\'edition')?></span></a></center>
				
<?php
				}	
		}
		else // non - opui
		{
?>				 <?php echo _('phraseanet::oui')?> <img id="cc_restrict_y" src="ccoch2.gif" border="0" onClick="chckRestrict(false);" chk="1">
				 <?php echo _('phraseanet::non')?> <img id="cc_restrict_n" src="ccoch2.gif" border="0" onClick="chckRestrict(true);" chk="0">
				  </center>				
				<br>
				<span id="spandroit" style="visibility:hidden;">
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo _('admin::user:quota: droit')?>&nbsp;:&nbsp;<input type="text"  name="restrict_droit" id="restrict_droit" value="<?php if($month_dwnld_max)echo $month_dwnld_max; ?>" style="width:55px;" disabled onChange="javascript:chgmax(this);" >&nbsp;<?php echo _('admin::user:quota: par mois')?>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo _('admin::user:quota: reste')?>&nbsp;:&nbsp;<input type="text"  name="restrict_rest"  id="restrict_rest" value="<?php if($remain_dwnld)echo $remain_dwnld; ?>" style="width:55px;" disabled  onChange="javascript:chgrest(this);">
				</span>
				<center><span  id="boutreplacedroit" style="width:350px;position:absolute;left:8px;top:35px;" ><b><?php echo _('admin::user:quota: les valeurs des quotas sont differentes entre les collections et ne peuvent etre affichees')?>.</b></span></center>
<?php
		}
		if($parm["type"]=="B")
		{
?>			  
				<center><small><br>* <?php echo _('admin::user:quota: les quotas par base seront appliques uniformement a toutes les collections')?>.</small></center>
<?php
		}
?>					
			</span>		
			
		</div>	
			
		<table style="position:absolute;top:145px;left:10px; table-layout:fixed; width:375px" width="375px"  cellspacing=0 scrollleft=0 scrolltop=0 cellpadding=0 border=0>
			<tr>
				<td class="noborder" width="70px"></td>
				<td class="noborder" width="80px">
					<span class="bout"  onClick="javascript:valid();" ><?php echo _('boutton::valider')?></span>
				</td>
				<td class="noborder" width="40px"></td>
				<td class="noborder" width="80px" >
					<span class="bout"  onClick="javascript:self.close();" ><?php echo _('boutton::annuler')?></span>
				</td>
				<td class="noborder" width="65px"></td>				
			</tr>		
		</table>	
		
		<form action="./openquota.php" method="post" name="send">		
			<input type="hidden" name="act" value="">
			<input type="hidden" name="chg" value="">
			<input type="hidden" name="b" value="<?php echo $parm["b"]?>">
			<input type="hidden" name="bl" value="<?php echo $parm["bl"]?>">
			<input type="hidden" name="ul" value="<?php echo $parm["ul"]?>">
			<input type="hidden" name="type" value="<?php echo $parm["type"]?>">
			
			<input type="hidden" name="newlimit" value="???">
			<input type="hidden" name="newmax" value="???">
			<input type="hidden" name="newrest" value="???"> 
		</form>

<?php
}
?>	
	</body>
</html>
