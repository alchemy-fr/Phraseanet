<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("newlimit" , "newdatefrom"  , "newdateto"  , "act" , "ul", "b", "bl", "type"); 

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
<html lang="<?php echo $session->usr_i18?>">
	<head>
	<title><?php echo _('admin::user:time: duree de vie')?></title>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
<script type="text/javascript">	
self.resizeTo(400,200);

function editmode()
{
	if(document.getElementById('boutreplacetime'))
		document.getElementById('boutreplacetime').style.visibility = "hidden";
		
	if(document.getElementById('spantimeto'))
		document.getElementById('spantimeto').style.visibility = "visible";
	if(document.getElementById('spantimefrom'))
		document.getElementById('spantimefrom').style.visibility = "visible";

}

function valid()
{
	if(document.getElementById('cc_timelimit_y'))
	{
		if(document.getElementById('cc_timelimit_y').chk=="1")
		{
			var vardeb = "";
			var varfin = "";	
			if(document.getElementById('timeRestyearfrom'))
				vardeb += document.getElementById('timeRestyearfrom').value;
			if(document.getElementById('timeRestmonthfrom'))
				vardeb += document.getElementById('timeRestmonthfrom').value;
			if(document.getElementById('timeRestdayfrom'))
				vardeb += document.getElementById('timeRestdayfrom').value;		
			if(document.getElementById('timeRestyearto'))
				varfin += document.getElementById('timeRestyearto').value;
			if(document.getElementById('timeRestmonthto'))
				varfin += document.getElementById('timeRestmonthto').value;
			if(document.getElementById('timeRestdayto'))
				varfin += document.getElementById('timeRestdayto').value;		
			if(	varfin < vardeb )
			{
				alert("<?php echo _('admin::user:time: erreur : la date de fin doit etre posterieur a celle de debut')?>");
				return; 
			}	
			else
			{
				var newval = "";
				if(document.getElementById('timeRestyearfrom'))
					newval += document.getElementById('timeRestyearfrom').value;
				if(document.getElementById('timeRestmonthfrom'))
					newval += document.getElementById('timeRestmonthfrom').value;
				if(document.getElementById('timeRestdayfrom'))
					newval += document.getElementById('timeRestdayfrom').value;				
				document.send.newdatefrom.value = newval;
				
				newval = "";
				if(document.getElementById('timeRestyearto'))
					newval += document.getElementById('timeRestyearto').value;
				if(document.getElementById('timeRestmonthto'))
					newval += document.getElementById('timeRestmonthto').value;
				if(document.getElementById('timeRestdayto'))
					newval += document.getElementById('timeRestdayto').value;	
				
				document.send.newdateto.value = (newval+"235959");				
			}
		}
		else
		{
			document.send.newdatefrom.value = "???";
			document.send.newdateto.value = "???";
		}
	}		
	document.send.act.value = 'UPD';
	document.send.submit();
}

function chgdatefrom()
{	
	var newval = "";
	if(document.getElementById('timeRestyearfrom'))
		newval += document.getElementById('timeRestyearfrom').value;
	if(document.getElementById('timeRestmonthfrom'))
		newval += document.getElementById('timeRestmonthfrom').value;
	if(document.getElementById('timeRestdayfrom'))
		newval += document.getElementById('timeRestdayfrom').value;	
	
	document.send.newdatefrom.value = newval;
}
function chgdateto()
{	
	var newval = "";
	if(document.getElementById('timeRestyearto'))
		newval += document.getElementById('timeRestyearto').value;
	if(document.getElementById('timeRestmonthto'))
		newval += document.getElementById('timeRestmonthto').value;
	if(document.getElementById('timeRestdayto'))
		newval += document.getElementById('timeRestdayto').value;	
	
	document.send.newdateto.value = newval;	
}
function chckTimeLimited(val,collid)
{	
	if(document.getElementById('boutreplacetime'))
		document.getElementById('boutreplacetime').style.visibility = "hidden";
		
	if(document.getElementById('spantimeto'))
		document.getElementById('spantimeto').style.visibility = "visible";
	if(document.getElementById('spantimefrom'))
		document.getElementById('spantimefrom').style.visibility = "visible";
	
	
	if(val==false)
	{
		document.getElementById('cc_timelimit_y').src = "/skins/icons/ccoch1.gif";
		document.getElementById('cc_timelimit_y').chk ="1";
		
		document.getElementById('cc_timelimit_n').src = "/skins/icons/ccoch0.gif";
		document.getElementById('cc_timelimit_n').chk ="0";
		// chg[collid+'_tlim']='1';
		document.send.newlimit.value = '1';
	}
	else
	{
		document.getElementById('cc_timelimit_y').src = "/skins/icons/ccoch0.gif";
		document.getElementById('cc_timelimit_y').chk ="0";
		
		document.getElementById('cc_timelimit_n').src = "/skins/icons/ccoch1.gif";
		document.getElementById('cc_timelimit_n').chk ="1";
	//	chg[collid+'_tlim']='0';
		document.send.newlimit.value = '0';
	}	
	document.getElementById('timeRestdayfrom').disabled = val;
	document.getElementById('timeRestmonthfrom').disabled = val;
	document.getElementById('timeRestyearfrom').disabled = val;
	document.getElementById('timeRestdayto').disabled = val;
	document.getElementById('timeRestmonthto').disabled = val;
	document.getElementById('timeRestyearto').disabled = val;
}
</script>
</head>
	<body>
<?php
$myuser = null;
$list = "";
$usrInfo = null;
$servercollid = null;

$limited_from=null;
$limited_to=null;

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
		if($parm["newlimit"]!="???" || $parm["newdatefrom"]!="???" || $parm["newdateto"]!="???" )
		{
			$sql = "";
			if($parm["newlimit"]!="???") // 
			{			
				if($sql!="")$sql.=",";
				$sql .= "time_limited='".$conn->escape_string($parm["newlimit"])."'";
			}		
			if($parm["newdatefrom"]!="???") // 
			{
				if($sql!="")$sql.=",";
				$sql .= "limited_from='".$conn->escape_string($parm["newdatefrom"])."'";
			}
			if($parm["newdateto"]!="???") // 
			{
				if($sql!="")$sql.=",";
				$sql .= "limited_to='".$conn->escape_string($parm["newdateto"])."'";
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
			$rs = $conn->query($sql);
			if($rs)
			{
				echo "<br><br><br><center>"._('forms::modifications enregistrees').".</center>";
			}
			else
			{
				echo "<br><br><br><center>"._('forms::erreurs lors de l\'enregistrement des modifications').".</center>";
			}	
		}
		else
		{
			echo "<br><br><br><center>"._('forms::aucune modification a enregistrer').".</center>";
		
		}
?>		
	<br><br><br>
	<center>
		<span class="bout"  onClick="javascript:self.close();" ><?php echo _('boutton::fermer')?></span>
	</center>
<?php
		exit();
	}
	
	
	###
	$sql = "SELECT usr_login,sum(1) as nb,time_limited
		FROM (usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id) WHERE usr.usr_id='".$conn->escape_string($parm["ul"])."' AND basusr.base_id IN (".$list.") group by time_limited";
	
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
				if($row["time_limited"]=="0")
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
						
						$sql = "SELECT distinct(limited_from)
								FROM (usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id) 
								WHERE usr.usr_id='".$conn->escape_string($parm["ul"])."' AND basusr.base_id IN (".$list.")";
						
						if($rs2 = $conn->query($sql))
						{	
							if($conn->num_rows($rs2)==1)
							{
								if($row3 = $conn->fetch_assoc($rs2)) 
									$limited_from = $row3["limited_from"];
							
							}
							$conn->free_result($rs2);
						}
						$sql = "SELECT distinct(limited_to)
								FROM (usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id) 
								WHERE usr.usr_id='".$conn->escape_string($parm["ul"])."' AND basusr.base_id IN (".$list.")";
						if($rs2 = $conn->query($sql))
						{	
							if($conn->num_rows($rs2)==1)
							{
								$month_dwnld_max="";
								if($row3 = $conn->fetch_assoc($rs2)) 
									$limited_to = $row3["limited_to"];
									
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
			echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;"._('phraseanet:: collection')." : ". $servercollid[$parm["b"]];
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
		
			<div class="FT" style="left:10px;clear:left; width:104px"><?php echo _('admin::user:quota: limite temporelle')?></div>
			<span class="FIELD" style="left:10px;height:74px;width:360px">				
				<center>
<?php
	$collid="";
	
	if($idtvalvalue=="0")
	{
?>		
				&nbsp;<?php echo _('phraseanet::oui')?>&nbsp;<img id="cc_timelimit_y" src="/skins/icons/ccoch0.gif" border="0" onClick="chckTimeLimited(false);" chk="0">
				&nbsp;&nbsp;<?php echo _('phraseanet::oui')?>&nbsp;<img id="cc_timelimit_n" src="/skins/icons/ccoch1.gif" border="0" onClick="chckTimeLimited(true);" chk="1">				
				<br><br>
				</center>				
				<span style="position:relative;left:10px;top:0px;">
					&nbsp;<?php echo _('admin::user:time: de (date)')?>&nbsp;&nbsp;
					<select id="timeRestdayfrom" style="FONT-SIZE: 9px;" disabled onChange="javascript:chgdatefrom();" >			              
<?php			for($i=1;$i<=31;$i++)
			{
				if($i<10)	print("<option value='0$i'>0$i</option>\n");
				else		print("<option value='$i'>$i</option>\n");
			} 
?>		             		              
		            </select>
					<select id="timeRestmonthfrom" style="FONT-SIZE: 9px;" disabled onChange="javascript:chgdatefrom();" >			              
<?php			for($i=1;$i<=12;$i++)
			{
				if($i<10)	print("<option value='0$i'>0$i</option>\n");
				else		print("<option value='$i'>$i</option>\n");
			}					 
?>
		            </select>
					<select id="timeRestyearfrom" style="FONT-SIZE: 9px;" disabled onChange="javascript:chgdatefrom();" >	
		              <?php for($ptmi=-2;$ptmi<5;$ptmi++)
					  		echo " <option value='".(date("Y")+$ptmi)."'>".(date("Y")+$ptmi)."</option>\n";
					  ?>
		            </select>	
					</span>
				<br>
				<span style="position:relative;left:180px;top:0px;" >
					&nbsp;<?php echo _('phraseanet::time:: a')?>&nbsp;
					<select id="timeRestdayto" style="FONT-SIZE: 9px;" disabled onChange="javascript:chgdateto();" >			              
<?php			for($i=1;$i<=31;$i++)
			{	if($i<10)	print("<option value='0$i'>0$i</option>\n");
				else		print("<option value='$i'>$i</option>\n");
			}	 
?>		            </select>
					<select id="timeRestmonthto" style="FONT-SIZE: 9px;" disabled onChange="javascript:chgdateto();" >			              
<?php			for($i=1;$i<=12;$i++)
			{
				if($i<10)	print("<option value='0$i'>0$i</option>\n");
				else		print("<option value='$i'>$i</option>\n");
			}
?>		            </select>
					<select id="timeRestyearto" style="FONT-SIZE: 9px;" disabled onChange="javascript:chgdateto();" >	
		              <?php for($ptmi=-2;$ptmi<5;$ptmi++)
					  		echo " <option value='".(date("Y")+$ptmi)."'>".(date("Y")+$ptmi)."</option>\n";
					  ?>
		            </select>
				</span>	
<?php
	}
	elseif($idtvalvalue=="1")	
	{
		$vis="";					
		if( $limited_from==null || $limited_to==null )
			$vis="visibility:hidden;";
?>		
				&nbsp;<?php echo _('phraseanet::oui')?>&nbsp;<img id="cc_timelimit_y" src="/skins/icons/ccoch1.gif" border="0" onClick="chckTimeLimited(false);" chk="1">
				&nbsp;&nbsp;<?php echo _('phraseanet::non')?>&nbsp;<img id="cc_timelimit_n" src="/skins/icons/ccoch0.gif" border="0" onClick="chckTimeLimited(true);" chk="0">
				<br><br>
				</center>
<?php
if(isset($limited_from))
{
	$limited_from = explode(' ',$limited_from);
	$limited_from = explode('-',$limited_from[0]);
}

?>
				<span  id="spantimefrom" style="<?php echo $vis?>position:relative;left:10px;top:0px;">
					&nbsp;<?php echo _('admin::user:time: de (date)')?>&nbsp;&nbsp;
					<select id="timeRestdayfrom" style="FONT-SIZE: 9px;" onChange="javascript:chgdatefrom();" >			              
<?php			for($i=1;$i<=31;$i++)
			{	
				$en= "";
				if(isset($limited_from))
					if($limited_from[2]==$i)$en= " selected ";
				if($i<10)	print("<option value='0$i'$en>0$i</option>\n");
				else		print("<option value='$i'$en>$i</option>\n");
			}	 
?>		             		              
		            </select>
					<select id="timeRestmonthfrom" style="FONT-SIZE: 9px;" onChange="javascript:chgdatefrom();" >			              
<?php			for($i=1;$i<=12;$i++)
			{
				$en= "";
				if(isset($limited_from))
					if($limited_from[1]==$i)$en= " selected ";
				if($i<10)	print("<option value='0$i'$en>0$i</option>\n");
				else		print("<option value='$i'$en>$i</option>\n");
			}	 
?>
		            </select>
					<select id="timeRestyearfrom" style="FONT-SIZE: 9px;" onChange="javascript:chgdatefrom();" >	
		              
<?php
				// <option value='2004'>2004</option>
		        // <option value='2005'>2005</option>
				
				for($ptmi=-2;$ptmi<5;$ptmi++)
				{	  		
					$en= "";
					if(isset($limited_from))
						if($limited_from[0]==(date("Y")+$ptmi))$en= " selected ";
					echo " <option value='".(date("Y")+$ptmi)."'$en>".(date("Y")+$ptmi)."</option>\n";
				
				}	
				 
?>	           		</select>
				</span>
				<br>
<?php

if(isset($limited_to))
{
	$limited_to = explode(' ',$limited_to);
	$limited_to = explode('-',$limited_to[0]);
}

?>
				<span  id="spantimeto" style="<?php echo $vis?>position:relative;left:180px;top:0px;" >
					&nbsp;<?php echo _('phraseanet::time:: a')?>&nbsp;
					<select id="timeRestdayto" style="FONT-SIZE: 9px;" onChange="javascript:chgdateto();" >			              
<?php
			for($i=1;$i<=31;$i++)
			{
				$en= "";
				if(isset($limited_to))
					if($limited_to[2]==$i)$en= " selected ";
				if($i<10)
					print("<option value='0$i'$en>0$i</option>\n");
				else
					print("<option value='$i'$en>$i</option>\n");
			}
					 
?>		            </select>
					<select id="timeRestmonthto" style="FONT-SIZE: 9px;" onChange="javascript:chgdateto();" >			              
<?php
			for($i=1;$i<=12;$i++)
			{
				$en= "";
				if(isset($limited_to))
					if($limited_to[1]==$i)$en= " selected ";
				if($i<10)
					print("<option value='0$i'$en>0$i</option>\n");
				else
					print("<option value='$i'$en>$i</option>\n");
			}
			 
?>		            </select>
					<select id="timeRestyearto" style="FONT-SIZE: 9px;" onChange="javascript:chgdateto();" >	
<?php
			for($ptmi=-2;$ptmi<5;$ptmi++)
			{	  		
				$en= "";
				if(isset($limited_to))
					if($limited_to[0]==(date("Y")+$ptmi))$en= " selected ";
				echo " <option value='".(date("Y")+$ptmi)."'$en>".(date("Y")+$ptmi)."</option>\n";
			
			}	  
?>			            </select>
				</span>	
				
	
<?php
		if($vis!="")
		{
?>
<center><a href="javascript:void('');" onClick="editmode();" ><span  id="boutreplacetime" style="width:350px;position:absolute;left:8px;top:30px;background-color:#DDDDDD; color:#000000;TEXT-DECORATION:none;cursor:hand" ><b><?php echo _('admin::user:quota: les valeurs des quotas sont differentes entre les collections et ne peuvent etre affichees')?>.</b><BR><?php echo _('admin::user:quota: forcer l\'edition')?></span></a></center>		
<?php
		}
	}
	else	
	{
?>		
				&nbsp;<?php echo _('phraseanet::oui')?>&nbsp;<img id="cc_timelimit_y" src="/skins/icons/ccoch2.gif" border="0" onClick="chckTimeLimited(false);" chk="2">
				&nbsp;&nbsp;<?php echo _('phraseanet::non')?>&nbsp;<img id="cc_timelimit_n" src="/skins/icons/ccoch2.gif" border="0" onClick="chckTimeLimited(true);" chk="2">
				<br><br>
				</center>				
				
				<span id="spantimefrom" style="visibility:hidden;position:relative;left:10px;top:0px;">
					&nbsp;Du&nbsp;&nbsp;
					<select id="timeRestdayfrom" style="FONT-SIZE: 9px;" onChange="javascript:chgdatefrom();" >			              
<?php			for($i=1;$i<=31;$i++)
			{
				if($i<10)	print("<option value='0$i'>0$i</option>\n");
				else		print("<option value='$i'>$i</option>\n");
			}					 
?>		             		              
		            </select>
					<select id="timeRestmonthfrom" style="FONT-SIZE: 9px;" onChange="javascript:chgdatefrom();" >			              
<?php			for($i=1;$i<=12;$i++)
			{
				if($i<10)	print("<option value='0$i'>0$i</option>\n");
				else		print("<option value='$i'>$i</option>\n");
			}					 
?>
		            </select>
					<select id="timeRestyearfrom" style="FONT-SIZE: 9px;" onChange="javascript:chgdatefrom();" >	
		              <?php for($ptmi=-2;$ptmi<5;$ptmi++)
					  		echo " <option value='".(date("Y")+$ptmi)."'>".(date("Y")+$ptmi)."</option>\n";
					  ?>
		            </select>
					</span>
				<br>
				<span  id="spantimeto" style="visibility:hidden; position:relative;left:180px;top:0px;" >
					&nbsp;Au&nbsp;
					<select id="timeRestdayto" style="FONT-SIZE: 9px;" onChange="javascript:chgdateto();" >			              
<?php			for($i=1;$i<=31;$i++)
			{
				if($i<10)	print("<option value='0$i'>0$i</option>\n");
				else		print("<option value='$i'>$i</option>\n");
			}					 
?>		            </select>
					<select id="timeRestmonthto" style="FONT-SIZE: 9px;" onChange="javascript:chgdateto();" >			              
<?php
			for($i=1;$i<=12;$i++)
			{
				if($i<10)	print("<option value='0$i'>0$i</option>\n");
				else		print("<option value='$i'>$i</option>\n");
			}
			 
?>		            </select>
					<select id="timeRestyearto" style="FONT-SIZE: 9px;" onChange="javascript:chgdateto();" >	
		              <?php for($ptmi=-2;$ptmi<5;$ptmi++)
					  		echo " <option value='".(date("Y")+$ptmi)."'>".(date("Y")+$ptmi)."</option>\n";
					  ?>
		            </select>
				</span>	
				<center><span  id="boutreplacetime" style="width:350px;position:absolute;left:8px;top:30px;background-color:#DDDDDD; color:#000000;TEXT-DECORATION:none;" ><BR><b><?php echo _('admin::user:quota: les valeurs des quotas sont differentes entre les collections et ne peuvent etre affichees')?>.</b><BR><BR></span></center>
<?php
	}
?>		
	  
		<table style="position:relative;top:20px; table-layout:fixed; width:365px;" width="365px"  cellspacing=0 scrollleft=0 scrolltop=0 cellpadding=0 border=0>
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
		
		<form action="./opentime.php" method="post" name="send">		
			<input type="hidden" name="act" value="">
			<input type="hidden" name="chg" value="">
			<input type="hidden" name="b" value="<?php echo $parm["b"]?>">
			<input type="hidden" name="bl" value="<?php echo $parm["bl"]?>">
			<input type="hidden" name="ul" value="<?php echo $parm["ul"]?>">
			<input type="hidden" name="type" value="<?php echo $parm["type"]?>">
			
			<input type="hidden" name="newlimit" value="???">
			<input type="hidden" name="newdatefrom" value="???">
			<input type="hidden" name="newdateto" value="???">
		</form>

<?php
}

?>	
	</body>
</html>