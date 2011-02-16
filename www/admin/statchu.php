<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("act", "p0","p1","v" , "i" );
$parm_i = (int)($parm["i"]);

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
		<style type="text/css">
		body
		{
		
		}
		A,  A:link, A:visited, A:active
		{
			
			color : #000000; 
			FONT-SIZE: 12px;
			padding-bottom : 15px;
			text-decoration:none;
		}
		
		A:hover
		{ 
			COLOR : #ba36bf;
			text-decoration:underline;
		}
		</style>
	</head>
	<body>

<center><div style="text-decoration:underline;font-size:14px"><?php echo _('admin::paniers: edition des status des paniers')?></div></center>
<?php


if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
{
	die();
}


$conn = connection::getInstance();
if(!$conn)
{
	die();
}

if($parm_i>7)
{
	die();
}

// verif superU
$sql = "SELECT issuperu FROM usr WHERE usr_id='" . $usr_id."'";
if($rs = $conn->query($sql))
{
	if( $conn->num_rows($rs)!=1)
	{
		die();
	}
}
$sitepreff = "";
$version   = "";
$lastmaj   = ""; 
$sql = "SELECT * FROM sitepreff WHERE id='1'";
if($rs = $conn->query($sql))
{
	if($row = $conn->fetch_assoc($rs))
	{
		$sitepreff = $row["preffs"]; 
	}
}

$bits = NULL ; 

$bits[$parm_i]["label"] = "";
$bits[$parm_i]["order"] = "0";
$bits[$parm_i]["link"]  = "0";
$bits[$parm_i]["view"]  = "0";
$bits[$parm_i]["wmprev"]= "0";
$bits[$parm_i]["thumbLimit"]= "0";

$sxe = simplexml_load_string($sitepreff);
if($sxe)
{
	if($sxe->statuschu->bit)
	{
		foreach($sxe->statuschu->bit as $sb)
		{
			$num = (int)($sb["n"]);
			$bits[$num]["label"]= (string)($sb["label"]);
			$bits[$num]["order"]= (string)($sb["order"]);			
			$bits[$num]["link"] = (string)($sb["link"]);			
			$bits[$num]["view"] = (string)($sb["view"]);			
			$bits[$num]["wmprev"] = (string)($sb["wmprev"]);			
			$bits[$num]["thumbLimit"] = (string)($sb["thumbLimit"]);			
		}
	}
}

?>
<br>
<br>

<script type="text/javascript">
function valid(boo)
{
	if(boo)
		document.forms[0].act.value = "UPD" ;	
	document.forms[0].submit();
}
</script>

<form method="post" action="./paramchu.php"  target="_self">
	<input type="hidden" name="p0"  value="<?php echo $parm["p1"]?>" />
	<input type="hidden" name="p1"  value="<?php echo $parm["p0"]?>" />
	<input type="hidden" name="i"   value="<?php echo $parm["i"]?>" />
	<input type="hidden" name="act" value=""  />	
	
	
	<center>
	<table style="table-layout:fixed;border:#cccccc 1px solid">
		<tr>
			<td style="width:140px;text-align:right" />
			<td style="width:500px;text-align:left" />
		</tr>
		
		<tr>
			<?php
			if($parm_i == -1)
			{
			?>
				<td colspan="2" style="text-align:center;font-size:14px;font-weight:700;background-color:#cccccc"><?php echo _('admin::paniers: parametres de publications des paniers de page d\'accueil')?></td>
			<?php
			}
			else
			{
			?>
				<td colspan="2" style="text-align:center;font-size:14px;font-weight:700;background-color:#cccccc"><?php echo _('admin::paniers: edition du status').' '.$parm_i?></td>
			<?php
			}
			?>
		</tr>
		
		<tr>
			<td colspan="2" style="height:20px" />
		</tr>
		<?php
		if($parm_i != -1){
		?>
		<tr>
			<td style="text-align:right"><?php echo _('admin::paniers: label status : ')?></td>
			<td style="text-align:left"><input type="text" name="label"   value="<?php echo $bits[$parm_i]["label"]?>"  /></td>
		</tr>
		<?php
		}
		?>
		<tr>
			<td colspan="2" style="height:10px" />
		</tr>
<?php
if($parm_i == -1){
?>		
		
		
		<tr>
			<td style="text-align:right" ><?php echo _('admin::paniers: ordre de presentation : ')?></td>
			<td style="text-align:left" >
				<select name="order" >
					<option  <?php echo $bits[$parm_i]["order"]=="0"?"selected":""?> value="0"><?php echo _('admin::paniers: ordre par date d\'ajout')?></option>
					<option  <?php echo $bits[$parm_i]["order"]=="1"?"selected":""?> value="1"><?php echo _('admin::paniers: ordre aleatoire')?></option>
				</select>
			</td>
		</tr>
		
		<tr>
			<td colspan="2" style="height:10px" />
		</tr>
		
		
		<tr>
			<td style="text-align:right" ><?php echo _('phraseanet::watermark')?></td>
			<td style="text-align:left" >
				
				<select name="wmprev" >
					<option <?php echo $bits[$parm_i]["wmprev"]=="1"?"selected":""?> value="1"><?php echo _('phraseanet::oui')?></option>
					<option <?php echo $bits[$parm_i]["wmprev"]!="1"?"selected":""?> value="0"><?php echo _('phraseanet::non')?></option>
				</select>
			</td>
		</tr>
		
		
		<tr>
			<td colspan="2" style="height:10px" />
		</tr>
		
		<tr>
			<td style="text-align:right" ><?php echo _('admin::paniers: limite du nombre d\'images')?></td>
			<td style="text-align:left" >
				
				<select name="thumbLimit" >
					<option <?php echo $bits[$parm_i]["thumbLimit"]=="5"?"selected":""?> value="5">4</option>
					<option <?php echo $bits[$parm_i]["thumbLimit"]=="0"?"selected":""?> value="0"><?php echo _('admin::paniers: pas de limite du nombre d\'images')?></option>
				</select>
			</td>
		</tr>
		
		
		<tr>
			<td colspan="2" style="height:10px" />
		</tr>
		
		<tr>
			<td style="valign:top;text-align:right" valign="top">View :</td>
			<td style="text-align:left" >
				<input type="radio" <?php echo $bits[$parm_i]["view"]=="0"?"checked":""?> name="view" value="0"><?php echo _('admin::paniers: affichage avec page intermediaire listant le nom des chutiers')?>
				<br> 
				<input type="radio" <?php echo $bits[$parm_i]["view"]=="1"?"checked":""?> name="view" value="1" ><?php echo _('admin::paniers: affichage direct avec contenu des paniers les uns a la suite des autres')?>
			</td>
		</tr>		
		
		
		<tr>
			<td colspan="2" style="height:15px" />
		</tr>

<?php
}
?>		
		
		<tr>
			<td colspan="2" style="text-align:center" >
			<a href="javascript:void(); return(false);" onclick="valid(true);return(false);"><?php echo _('boutton::valider')?></a>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="javascript:void(); return(false);" onclick="valid(false);return(false);"><?php echo _('boutton::annuler')?></a>
			</td>
		</tr>
		
		<tr>
			<td colspan="2" style="height:15px" />
		</tr>
		
	</table>	 
	</center>

</form>
<br>
<br>
</body>
</html>
