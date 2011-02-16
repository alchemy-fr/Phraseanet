<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("act", "p0" );

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
		function umountBase()
		{
			if(confirm("<?php echo _('admin::base: etes vous surs de vouloir demonter cette collection ?')?>"))
			{
				document.forms["manageDatabase"].target = "";
				document.forms["manageDatabase"].act.value = "UMOUNTBASE";
				document.forms["manageDatabase"].submit();
				
			}
		}
		</script> 
	</head>
	<body>
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

$refreshFinder = false;

### droits user
$usrRight=null;
$sbas = null;
$totcoll = 0 ;
	$sql = "select bas_manage from sbasusr usr.usr_id='".$conn->escape_string($usr_id) ."' AND sbas_id='".$conn->escape_string($parm["p0"])."'";
				
	if($rs = $conn->query($sql))
	{
		if($row = $conn->fetch_assoc($rs))
		{			
			$usrRight["bas_manage"] = $row["bas_manage"];
		}	
		$conn->free_result($rs);
	}


$sql = "SELECT * FROM sbas WHERE sbas_id='".$conn->escape_string($parm["p0"])."'";
if($rs = $conn->query($sql))
{
	if($row = $conn->fetch_assoc($rs) )
		$sbas = $row ;
}
$baslist ="";

$sql = "SELECT base_id FROM bas WHERE sbas_id='".$conn->escape_string($parm["p0"])."'";
if($rs = $conn->query($sql))
{
	while($row = $conn->fetch_assoc($rs) )
	{
		$totcoll++;
		if($baslist!='')
			$baslist.=',';
		$baslist.= $row["base_id"];
	}
}
$usrOncoll = 0 ;
$sql = "select usr.usr_id from usr inner join basusr on basusr.usr_id=usr.usr_id and base_id in($baslist) group by usr.usr_id" ;
 
if($rs = $conn->query($sql))
	$usrOncoll = $conn->num_rows($rs);

$out  = "<h4>"._('admin::base: A propos')."</H4><br />\n";
$out .= '<div style="padding-left:10px">';
$out .= _('phraseanet:: adresse') ." : ".$sbas["dbname"]."@". $sbas["host"].":".$sbas["port"]." (".$sbas["sqlengine"].")<br/>";
$out .= "sbas_id : " . $parm["p0"] . "<br />\n";


if($parm["act"]=="UMOUNTBASE")
{
	
	// on supprime le sql local
	$sql = "DELETE FROM basusr WHERE base_id IN (" . $baslist . ")";
	$conn->query($sql);
	$sql = "DELETE FROM sselcont WHERE base_id IN (" .$baslist . ")";
	$conn->query($sql);
	$sql = "DELETE FROM bas WHERE base_id IN (" . $baslist . ")";
	$conn->query($sql);
	$sql = "DELETE FROM order_masters WHERE base_id IN (" . $baslist . ")";
	$conn->query($sql);
	$sql = "DELETE FROM demand WHERE base_id IN (" . $baslist . ")";
	$conn->query($sql);
	$sql = "DELETE FROM sbas WHERE sbas_id='".$conn->escape_string($parm["p0"])."'";
	$conn->query($sql);
	$sql = "DELETE FROM sbasusr WHERE sbas_id='".$conn->escape_string($parm["p0"])."'";
	$conn->query($sql);
  
 
	$out .= '<br><br><h5><font color="#DD0000">'.sprintf(_('admin::base: base %s fermee'),$sbas['dbname']). "</font></h5><br/>";
	$refreshFinder = true;
}
else 
{
	$out .= "<br /><br />".sprintf(_('admin::base: %d collection montees'),$totcoll)."<br />\n";
	$out .= sprintf(_('admin::base: %d utilisteurs rattaches a cette base'),$usrOncoll)."<br />\n";
	if($usrRight["bas_manage"]=='1')
		$out .= "<br/><br/><br/>&nbsp;-&nbsp;<a href=\"javascript:void(0);return(false);\" onclick=\"umountBase();return(false);\">"._('admin::base: arreter la publication de la base')."</a>\n";
	$out .= "</div>\n";
	
	$out .= "<form method=\"post\" name=\"manageDatabase\" action=\"./unmount_off_db.php\" target=\"\" onsubmit=\"return(false);\">\n";
	$out .= "	<input type=\"hidden\" name=\"p0\"  value=\"" . $parm["p0"] . "\" />\n";
	$out .= "	<input type=\"hidden\" name=\"act\" value=\"\" />\n";
	$out .= "</form>\n";	
}
echo $out ;

if($refreshFinder)
{
	print('<script type="text/javascript">parent.reloadTree(base:'.$parm['p0'].') </script>');
}


?>
</body>
</html>