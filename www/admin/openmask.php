<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("newxor" , "newand" , "act" , "ul", "b", "bl", "type",
					"vand_and", "vand_or", "vxor_and", "vxor_or"
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
else{
		phrasea::headers(403);
}

phrasea::headers();

?>
<html lang="<?php echo $session->usr_i18n;?>">
<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
	<title>Mask</title>
<script type="text/javascript">	
<?php
if($parm["act"]=="UPD")
	echo "self.resizeTo(320,180);";
else
	echo "self.resizeTo(320,500);";
?>
function validmask()
{
	document.send.act.value = 'UPD';
	document.send.submit();
}

function chgstate(obj,bitnum)
{
	var act = obj.src.substr(obj.src.length-5,1);
	switch(obj.chk)
	{
		case "2": // grise
			// on coche les 2 !
			obj.src = "/skins/icons/ccoch1.gif";
			obj.chk = "1";
			chgmsk(bitnum);
			if(obj = document.getElementById(obj.comp))
			{
				obj.src = "/skins/icons/ccoch1.gif";
				obj.chk = "1";
				chgmsk(bitnum);
			}
			break;
		case "1": // coche
			// on decoche
			// attention on verifie que son contraire n'est pas decoche
			if(document.getElementById(obj.comp).chk=="1")
			{
				obj.src = "/skins/icons/ccoch0.gif";
				obj.chk = "0";
				chgmsk(bitnum);
			}
			else
			{
				alert("<?php echo _('admin::user:mask: vous devez cocher au moins une case pour chaque status')?>");			
				return;
			}
			break;
		case "0":	// decoche
			// on coche	
			obj.src = "/skins/icons/ccoch1.gif";
			obj.chk = "1";	
			chgmsk(bitnum);
			break;
	}
}

function chgmsk(bitnum)
{
	vand_and = vand_or = vxor_and = vxor_or = "";
	for(i=0; i<64; i++)
	{
		if( (l=document.getElementById("bitnum"+i+"-1")) && (r=document.getElementById("bitnum"+i+"-2")) )
		{
			if(l.chk=="2" || r.chk=="2")
			{
				vand_and = "1" + vand_and;
				vand_or  = "0" + vand_or;
				vxor_and = "1" + vxor_and;
				vxor_or  = "0" + vxor_or;
			}
			else
			{
				b = (l.chk=="1" ^ r.chk=="1") ? "1":"0";
				vand_and = b + vand_and;
				vand_or  = b + vand_or;
				b = (l.chk=="1") ? "0" : "1" ;
				vxor_and = b + vxor_and;
				vxor_or  = b + vxor_or;
			}
		}
		else
		{
			vand_and = "0" + vand_and;
			vand_or  = "0" + vand_or;
			vxor_and = "0" + vxor_and;
			vxor_or  = "0" + vxor_or;
		}
	}
	document.send.vand_and.value = vand_and;
	document.send.vand_or.value  = vand_or;
	document.send.vxor_and.value = vxor_and;
	document.send.vxor_or.value  = vxor_or;
}


function tooheight()
{	
	if( document.getElementById("centretab") )
	{
		document.getElementById("centretab").style.height = "430px";
		document.getElementById("centretab").style.overflow ="scroll";
	}			
}
</script>	
</head>
	<body>
<?php

$myuser = null;
$list = "";
$usrInfo = null;
$structBase = null;

	$conn = connection::getInstance();

	// je recup les caract de la BASE Mysql
	$sql = "SELECT b.sbas_id, b.base_id,b.active,s.host,s.port,s.dbname,s.sqlengine,s.user,s.pwd,b.server_coll_id 
				FROM bas b, sbas s where b.base_id='".$conn->escape_string($parm["b"])."' AND b.sbas_id = s.sbas_id";
	$distcaract = null;			
	if($rs = $conn->query($sql))
	{
		if($row = $conn->fetch_assoc($rs) )
			$distcaract = $row;			
		$conn->free_result($rs);
	}
	$servercollid = null;
	
	
	// connection a la base pour sa structure
	$conn2 = connection::getInstance($distcaract['sbas_id']);
	if($conn2)		
	{
		$structBase = databox::get_structure($distcaract['sbas_id']);
		if($parm["type"]=="C")
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
	$list = "";
	$sql = "SELECT  base_id,server_coll_id FROM bas where sbas_id='".$conn->escape_string(phrasea::sbasFromBas($parm["b"]))."'";
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs) )
		{
				if($list!="")$list.= ",";
				$list.= $row["base_id"];
		}		
		$conn->free_result($rs);
	}	
	
	// ici j'ai recup ttes les base_id de collection de la "BASE" edite
	// cad si edition de FR5 : $list="300,301"
	if( $parm["act"]=="UPD" )
	{	
		if(1) // $parm["newand"]!="???" && $parm["newxor"]!="???" )
		{
				
			#########
			$vhex = array();
			foreach(array("vand_and", "vand_or", "vxor_and", "vxor_or") as $f)
			{
				$vhex[$f] = "0x";
				while(strlen($parm[$f])<64)
					$parm[$f] = "0".$parm[$f];
			}
				
			foreach(array("vand_and", "vand_or", "vxor_and", "vxor_or") as $f)
			{
				while(strlen($parm[$f])>0)
				{
					$valtmp = substr($parm[$f], 0, 4);
					$parm[$f] = substr($parm[$f], 4);
					$vhex[$f] .= dechex(bindec($valtmp));	
				}
			}
			###################		
			
			$sql = "UPDATE basusr SET mask_and=((mask_and & ".$vhex["vand_and"].") | ".$vhex["vand_or"].") , mask_xor=((mask_xor & ".$vhex["vxor_and"].") | ".$vhex["vxor_or"].") WHERE usr_id IN (".$parm["ul"].") AND " ;
			switch($parm["type"])
			{
				case 'B':
					$sql .= "base_id IN ($list) " ;
					break;
				case 'C':
					$sql .= "base_id='" . $conn->escape_string($parm["b"]) ."'";
				break;
			}
			$rs = $conn->query($sql);
			
			if($rs)
				echo "<br><br><br><center>" . _('forms::modifications enregistrees')."</center>";
			else
				echo "<br><br><br><center>"._('forms::erreurs lors de l\'enregistrement des modifications')."</center>";
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
	
	
	
	
	$lst =explode(",",$list );	
	// les coordonees utilisateur	
	$sql = "SELECT usr.usr_id,usr_login,usr.last_conn,creationdate
		FROM (usr INNER JOIN basusr ON usr.usr_id=basusr.usr_id) WHERE usr.usr_id IN (".$parm["ul"].") AND basusr.base_id IN (".$list.")";
	if($rs = $conn->query($sql))
	{			
		$rep = $conn->num_rows($rs);		
		if(($row = $conn->fetch_assoc($rs)) )
		{				
			$myuser = $row ;
		}		
		if ($rep==0) 
		{
		    $sql = "SELECT usr.usr_id,usr_login,usr.last_conn
					FROM usr WHERE usr.usr_id='".$conn->escape_string($parm["ul"])."'";
			if($rs2 = $conn->query($sql))
			{			
				if($row = $conn->fetch_assoc($rs2)) 
				{
					$usrInfo = $row;
					
				}
			}
		}
		$conn->free_result($rs);
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
<?php
	switch($parm["type"])
	{
		case 'B':
			$sql = "SELECT BIN(mask_and) AS mask_and, BIN(mask_xor) AS mask_xor FROM basusr WHERE usr_id IN (".$parm["ul"].") AND base_id IN (".$list.")";
			break;
		case 'C':
			$sql = "SELECT BIN(mask_and) AS mask_and, BIN(mask_xor) AS mask_xor FROM basusr WHERE usr_id IN (".$parm["ul"].") AND base_id='" .$conn->escape_string($parm["b"])."'" ;
		break;
	}

	$msk_and=null;
	$msk_xor=null;
	$tbits_and = array();
	$tbits_xor = array();
	$nrows = 0;
	for($bit=0; $bit<64; $bit++)
		$tbits_and[$bit] = $tbits_xor[$bit] = array( "nset"=>0 );
	if($rs = $conn->query($sql))
	{			
		$rep = $conn->num_rows($rs);		
		while($row = $conn->fetch_assoc($rs)) 
		{			
			$sta_xor = strrev( $row["mask_xor"]  );
			for($bit=0; $bit<strlen($sta_xor); $bit++)
				$tbits_xor[$bit]["nset"] += substr($sta_xor, $bit, 1)!="0" ? 1 : 0;		
		 	
			$sta_and = strrev( $row["mask_and"] );
			for($bit=0; $bit<strlen($sta_and); $bit++)
				$tbits_and[$bit]["nset"] += substr($sta_and, $bit, 1)!="0" ? 1 : 0;
				
			$nrows++;	
		}
		$conn->free_result($rs);
	}
	
	$tbits_left = array();
	$tbits_right = array();
	for($bit=0; $bit<64; $bit++)
	{
		$tbits_left[$bit]  = array("name"=>null, "nset"=>0);
		$tbits_right[$bit] = array("name"=>null, "nset"=>0);
	}
	$vand_and = $vand_or = $vxor_and = $vxor_or = "";
	for($bit=0; $bit<64; $bit++)
	{
		if(($tbits_and[$bit]["nset"]!=0 && $tbits_and[$bit]["nset"]!=$nrows) || ($tbits_xor[$bit]["nset"]!=0 && $tbits_xor[$bit]["nset"]!=$nrows))
		{
			$tbits_left[$bit]["nset"] = $tbits_right[$bit]["nset"] = 2;
			$vand_and = "1" . $vand_and;
			$vand_or  = "0" . $vand_or;
			$vxor_and = "1" . $vxor_and;
			$vxor_or  = "0" . $vxor_or;
		}
		else
		{
			$tbits_left[$bit]["nset"] = (($tbits_and[$bit]["nset"]==$nrows && $tbits_xor[$bit]["nset"]==0)      || $tbits_and[$bit]["nset"]==0 )? 1 : 0 ;
			$tbits_right[$bit]["nset"]= (($tbits_and[$bit]["nset"]==$nrows && $tbits_xor[$bit]["nset"]==$nrows) || $tbits_and[$bit]["nset"]==0 )? 1 : 0 ;	
			$vand_and = ($tbits_and[$bit]["nset"]==0?"0":"1") . $vand_and;
			$vand_or  = ($tbits_and[$bit]["nset"]==$nrows?"1":"0") . $vand_or;
			$vxor_and = ($tbits_xor[$bit]["nset"]==0?"0":"1") . $vxor_and;
			$vxor_or  = ($tbits_xor[$bit]["nset"]==$nrows?"1":"0") . $vxor_or;
		}
	}	
	// on recup les noms
	if($sxe = simplexml_load_string($structBase) )
	{
		if($sxe->statbits->bit)
		{
			foreach($sxe->statbits->bit as $sb)
			{
				$bit = (int)($sb["n"]);
				if($bit>=0 && $bit<=63)
				{
					if(isset( $sb["labelOff"] ) && trim($sb["labelOff"])!="")
						$tbits_left[$bit]["name"]  = (string)($sb["labelOff"]);
					else
						$tbits_left[$bit]["name"]  = "non-".(string)($sb);
					
					if(isset( $sb["labelOn"] ) && trim($sb["labelOn"])!="" )
						$tbits_right[$bit]["name"]  = (string)($sb["labelOn"]);
					else
						$tbits_right[$bit]["name"] = (string)($sb);
						
				}					
			}
		}
	}
?>
	&nbsp;<?php echo _('admin::user: l\'utilisateur peut voir les documents')?> :<br><br>

<div id="centretab" style="position:relative">

	<table style="table-layout:fixed; width:280px;position:relative;left:10px;" width="280px">
<?php
	$nbok=1;
	
	printf("\n");
	printf("<tr>\n");
	printf("<td width=\"15px\">\n");
	printf("<img src=\"/skins/icons/ccoch%s.gif\" onClick=\"javascript:chgstate(this,%s);\" chk=\"%s\" id=\"bitnum%s-1\" comp=\"bitnum%s-2\" >\n", $tbits_left[0]["nset"], 0, $tbits_left[0]["nset"], 0, 0);
	printf("</td>\n");
	printf("<td width=\"125px\">\n");
	printf(_('admin::user:mask : non-indexes'));
	printf("</td>\n");
	printf("<td width=\"15px\">\n");
	printf("<img src=\"/skins/icons/ccoch%s.gif\"  onClick=\"javascript:chgstate(this,%s);\" chk=\"%s\" id=\"bitnum%s-2\" comp=\"bitnum%s-1\" >\n",$tbits_right[0]["nset"], 0, $tbits_right[0]["nset"], 0, 0);
	printf("</td>\n");
	printf("<td width=\"125px\">\n");
	printf(_('admin::user:mask : indexes'));
	printf("</td>\n");
	printf("</tr>\n");

	for($bit=1; $bit<64; $bit++)
	{
		if($tbits_left[$bit]["name"]!==null)
		{			
			$nbok++;
			printf("\n\t\t<tr>\n");
			printf("\t\t\t<td width=\"15px\">\n");
			printf("\t\t\t\t<img src=\"/skins/icons/ccoch%s.gif\" onClick=\"javascript:chgstate(this,%s);\" chk=\"%s\" id=\"bitnum%s-1\" comp=\"bitnum%s-2\" >\n", $tbits_left[$bit]["nset"], $bit, $tbits_left[$bit]["nset"], $bit, $bit);
			printf("\t\t\t</td>\n");
			printf("\t\t\t<td width=\"125px\">\n");
			printf("\t\t\t\t%s\n", $tbits_left[$bit]["name"]);
			printf("\t\t\t</td>\n");
			printf("\t\t\t<td width=\"15px\">\n");
			printf("\t\t\t\t<img src=\"/skins/icons/ccoch%s.gif\"  onClick=\"javascript:chgstate(this,%s);\" chk=\"%s\" id=\"bitnum%s-2\" comp=\"bitnum%s-1\" >\n", $tbits_right[$bit]["nset"], $bit, $tbits_right[$bit]["nset"], $bit, $bit);
			printf("\t\t\t</td>\n");
			printf("\t\t\t<td width=\"125px\">\n");
			printf("\t\t\t\t%s\n", $tbits_right[$bit]["name"]);
			printf("\t\t\t</td>\n");
			printf("\t\t</tr>\n");
		}		
	}	

	$js = "";		
	$resize = $nbok*16 +  150; // $resize+= 150;
	if ($resize>600)
	{
		$resize=600;
		$js = "tooheight();\n";
	}
?>
	</table>
</div>
<script type="text/javascript">	
<?php
echo $js;
?>
self.resizeTo(320,<?php echo $resize?>);
</script>
<br><br>
	<table style="position:relative;left:10px; table-layout:fixed; width:280px" width="280px"  cellspacing=0 scrollleft=0 scrolltop=0 cellpadding=0 border=0>
		<tr>
			<td class="noborder" width="20px"></td>
			<td class="noborder" width="80px">
				<span class="bout"  onClick="javascript:validmask();" ><?php echo _('boutton::valider')?></span>
			</td>
			<td class="noborder" width="20px"></td>
			<td class="noborder" width="80px" >
				<span class="bout"  onClick="javascript:self.close();" ><?php echo _('boutton::annuler')?></span>
			</td>
			<td class="noborder" width="20px"></td>				
		</tr>		
	</table>	
	<br>
		<form action="./openmask.php" method="post" name="send">		
			<input type="hidden" name="act" value="">
			<input type="hidden" name="b" value="<?php echo $parm["b"]?>">
			<input type="hidden" name="bl" value="<?php echo $parm["bl"]?>">
			<input type="hidden" name="ul" value="<?php echo $parm["ul"]?>">
			<input type="hidden" name="type" value="<?php echo $parm["type"]?>">
						
			<!-- vand_and<br>  -->
			<input type="hidden" name="vand_and" value="<?php echo $vand_and?>">			
			<!-- vand_or<br>  -->
			<input type="hidden" name="vand_or" value="<?php echo $vand_or?>">
					
			<!-- vxor_and<br>  -->
			<input type="hidden" name="vxor_and" value="<?php echo $vxor_and?>">			
			<!-- vxor_or<br>  -->
			<input type="hidden" name="vxor_or" value="<?php echo $vxor_or?>">			
		</form>
	</body>
</html>
