<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms(
						 "act",
						 "p0",
						 "send"
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


if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	phrasea::headers(403);	
	
$conn = connection::getInstance();
if(!$conn)
	phrasea::headers(500);	
	
	

if(is_null($parm['p0']))
	phrasea::headers(400);

$user = user::getInstance($usr_id);
if(!isset($user->_rights_sbas[$parm['p0']]) || !$user->_rights_sbas[$parm['p0']]['bas_modify_struct'])
{
	phrasea::headers(403);
}

phrasea::headers();
?>

<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />

<?php 
$update = false;
echo "<H4>",sprintf(_('admin::base: reglage des ordres des collection de la base %s'),phrasea::sbas_names($parm['p0'])),"</h4>";

if( $parm["act"]=="APPLY")
{
	$newOrder = NULL ;
	$change = "<change>" . $parm["send"] . "</change>";
	$xml = simplexml_load_string($change);
	foreach ($xml->children() as $name=>$val)
	{
	//  echo "<br>".(string)$name." --> ".(string)$val ;
		$nodename = (string)$name;
		$nodeval = (string)$val;
		if(substr($nodename,0,3)=="ord")
		{
			$idx = substr($nodename,3) *10;
			$newOrder[$idx]=$nodeval;
		}
	}
	foreach($newOrder as $ord=>$base_id)
	{
		$sqlupd = "UPDATE bas SET ord='".$conn->escape_string($ord)."' WHERE base_id='".$conn->escape_string($base_id)."'";
		$conn->query($sqlupd);
	}
	if( $conn->query($sqlupd) )
	{
		$update = true;
	}
}
else
	echo "<br><br>";

$sbas_id = $parm['p0'];

$appboxcoll =NULL;
$sessioncoll=array();

if($sbas_id!=NULL)
{
	$sql = "SELECT * FROM bas WHERE sbas_id='" . $conn->escape_string($sbas_id) . "' order by ord";
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
			 $appboxcoll[] = $row["base_id"];
	}
	foreach($ph_session["bases"] as $onebase)
	{
		if($sbas_id==$onebase["sbas_id"])
		{
			foreach($onebase["collections"] as $oneColl)
		 		$sessioncoll[$oneColl["base_id"]]=$oneColl["name"];
		}
	}
}
?>

<script type="text/javascript">
function activeButtons()
{
	if( document.getElementById("coll_ord")!=null && document.getElementById("coll_ord").selectedIndex!=-1)
	{
		if(document.getElementById("coll_ord").selectedIndex==0)
			document.getElementById("upbutton").disabled = true;
		else
			document.getElementById("upbutton").disabled = false;

		if( (document.getElementById("coll_ord").selectedIndex+1)==document.getElementById("coll_ord").length)
			document.getElementById("downbutton").disabled = true;
		else
			document.getElementById("downbutton").disabled = false;
	}
}
function upcoll()
{
	if( document.getElementById("coll_ord")!=null && document.getElementById("coll_ord").selectedIndex!=-1 )
	{
		var old_idx   = document.getElementById("coll_ord").selectedIndex;
		var old_value = document.getElementById("coll_ord")[old_idx].value;
		var old_html  = document.getElementById("coll_ord")[old_idx].innerHTML;

		var new_idx   = old_idx-1;

		document.getElementById("coll_ord")[old_idx].value     = document.getElementById("coll_ord")[new_idx].value;
		document.getElementById("coll_ord")[old_idx].innerHTML = document.getElementById("coll_ord")[new_idx].innerHTML;

		document.getElementById("coll_ord")[new_idx].value     = old_value;
		document.getElementById("coll_ord")[new_idx].innerHTML = old_html;

		document.getElementById("coll_ord").selectedIndex = new_idx;
		activeButtons();
	}
}

function downcoll()
{
	if( document.getElementById("coll_ord")!=null && document.getElementById("coll_ord").selectedIndex!=-1 )
	{
		var old_idx   = document.getElementById("coll_ord").selectedIndex;
		var old_value = document.getElementById("coll_ord")[old_idx].value;
		var old_html  = document.getElementById("coll_ord")[old_idx].innerHTML;

		var new_idx   = old_idx+1;

		document.getElementById("coll_ord")[old_idx].value     = document.getElementById("coll_ord")[new_idx].value;
		document.getElementById("coll_ord")[old_idx].innerHTML = document.getElementById("coll_ord")[new_idx].innerHTML;

		document.getElementById("coll_ord")[new_idx].value     = old_value;
		document.getElementById("coll_ord")[new_idx].innerHTML = old_html;

		document.getElementById("coll_ord").selectedIndex = new_idx;
		activeButtons();
	}
}
function applychange()
{
	var send = "";
	if( document.getElementById("coll_ord")!=null )
	{
		for(i=0; i<document.getElementById("coll_ord").length;i++)
		{
			send += "<ord" + i + ">" + document.getElementById("coll_ord")[i].value + "</ord" + i + ">";
		}
	}
	document.forms["formcollorder"].act.value = "APPLY";
	document.forms["formcollorder"].send.value = send;
	document.forms["formcollorder"].submit();
}
function alphaOrder()
{
	if( document.getElementById("coll_ord")!=null )
	{
		document.getElementById("coll_ord").selectedIndex =-1 ;		
<?php
		$temp = $sessioncoll;
		natcasesort($temp);
		$i =0;
		foreach($temp as $basid=>$neword)
		{			
			echo "\t\tdocument.getElementById(\"coll_ord\")[".$i."].value     = \"".$basid."\";\n";
			echo "\t\tdocument.getElementById(\"coll_ord\")[".$i."].innerHTML =  \"".$neword."\";\n";
			$i++;
		}
?>
	}
}
</script>
</head>
<body>
<?php 
	if($update)
	{
		?>
		<span style="color:#00BB00"><?php echo _('admin::base: mise a jour de l\'ordre des collections OK');?></span>
		<script type="text/javascript">
		
		parent.reloadTree('base:<?php echo $parm['p0'];?>');
		</script>
		<?php
		
	}
?>
	<table style="position:relative; left:10px;">
	<tr>
		<td>
			<select size=16 name="coll_ord" id="coll_ord" style="width:140px" onclick="activeButtons();">
<?php
	foreach($appboxcoll as $orderapp)
	{
		if(isset( $sessioncoll[$orderapp] ) )
		{
			echo "\t\t\t\t<option value='$orderapp' >" . $sessioncoll[$orderapp]."\n";
		}
	}
?>
			</select>
		</td>
		<td>
			<input type="submit" value="<?php echo _('admin::base:collorder: monter')?>" disabled style="width:120px" onclick="upcoll();"   id="upbutton"   name="upbutton">
			<br>
			<input type="submit" value="<?php echo _('admin::base:collorder: descendre')?>" disabled style="width:120px" onclick="downcoll();" id="downbutton" name="downbutton">
			<br>
			<br>
			<center><a href="javascript:void();" onclick="alphaOrder();return(false);" style="color:#000000; text-decoration:none" ><b><?php echo _('admin::base:collorder: reinitialiser en ordre alphabetique')?></b></a></center>
		</td>
	</tr>
	
	<tr>
		<td colspan="2" style="height:20px" />
	</tr>
	<tr>
		<td colspan="2" style="text-align:center"><a href="javascript:void();" onclick="applychange();return(false);" style="color:#000000; text-decoration:none" ><b><?php echo _('boutton::valider')?></b></a></td>
	</tr>
</table>
<form method="post" name="formcollorder" id="formcollorder" action="./collorder.php" onsubmit="return(false);">
	<input type="hidden" name="act" value="" />
	<input type="hidden" name="send" value="" />
	<input type="hidden" name="p0" value="<?php echo $parm["p0"]?>" />
</form>
</body>
</html>