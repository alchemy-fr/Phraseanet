<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("act",
										 "p0",		// base_id
										 "cnm",		// si act=NEWCOLL, nom de la collection a creer
										 "othcollsel",
										 "ccusrothercoll"
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

if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
{
		phrasea::headers(403);
}	
$msg = "";

$conn = connection::getInstance();
if(!$conn)
{
		phrasea::headers(500);
}

$rowbas = null;
$sql = "SELECT * FROM sbas WHERE sbas_id='" . $conn->escape_string($parm["p0"])."'";
if($rs = $conn->query($sql))
{
	$rowbas = $conn->fetch_assoc($rs);
	$conn->free_result($rs);
}

if(!$rowbas)
{
		phrasea::headers(500);
}

$sbasid = null;
$error = false;



if(trim($parm["cnm"]) == '' && $parm["act"]=="NEWCOLL")
	$error = _('admin:: La collection n\'a pas ete creee : vous devez donner un nom a votre collection');
	
if($parm["act"]=="NEWCOLL" && !$error)
{
	
	try{
		$idbase = collection::create_collection($rowbas['sbas_id'], $parm['cnm']);
		// Est-ce que on reprend les users ??
		if($idbase && $parm["ccusrothercoll"]=="on" && $parm["othcollsel"]!=null )
		{
			collection::duplicate_right_from_bas($parm["othcollsel"], $idbase);
		}
	}
	catch(Exception $e)
	{
		$idbase = false;
	}
		
}
?>

<html lang="<?php echo $session->usr_i18n;?>">
<head>

		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
<script type="text/javascript">
function evt_submit()
{
	return(true);
}
function saveColl()
{
	document.forms["newColl"].target = "right";
	document.forms["newColl"].submit();
}


function clk_cc_coll()
{
	if( document.getElementById("ccusrothercoll") )
	{
		if( document.getElementById("ccusrothercoll").checked )
		{
			// idspanothsel
			if( document.getElementById("idspanothsel") )
				document.getElementById("idspanothsel").style.color = "#000000";
			// othcollsel
			if( document.getElementById("othcollsel") )
				document.getElementById("othcollsel").disabled = false;
		}
		else
		{
			// idspanothsel
			if( document.getElementById("idspanothsel") )
				document.getElementById("idspanothsel").style.color = "#AAAAAA";
			// othcollsel
			if( document.getElementById("othcollsel") )
				document.getElementById("othcollsel").disabled = true;
		}
	}
}
</script>
</head>
<body>
<?php
$out = "";
$out .= "<h4>"._('admin::base:collection: Creer une collection'). "</h4>";

if($parm["act"]=="NEWCOLL")
{
	$out .= $msg;
}
else
{
	$out .= "<br>";
	$out .= "<br>";
	$out .= "<br>";
}

if($error)
	$out .= "<div style='color:red;'>".$error."</div>";
	
$out .= "	<form method=\"post\" name=\"newColl\" action=\"./newcoll.php\" onsubmit=\"return(false);\">\n";
$out .= "	<input type=\"hidden\" name=\"act\" value=\"NEWCOLL\" />\n";
$out .= "	<input type=\"hidden\" name=\"p0\" value=\"" . $parm["p0"] . "\" />\n";
$out .= "	<center>\n";
$out .= "		"._('admin::base:collection: Nom de la nouvelle collection : ')."<input type=\"text\" name=\"cnm\" value=\"\" /><br /><br />\n";
$out .= "<br>";

$databox = new databox($parm['p0']);
$colls = $databox->list_colls();
if(count($colls) > 0)
{
	$out .= "<small>";
	$out .= "<input type=\"checkbox\" id=\"ccusrothercoll\" name=\"ccusrothercoll\" onclick=\"clk_cc_coll();\">";
	$out .= "<span id=\"idspanothsel\" style=\"color:#AAAAAA\">"._('admin::base:collection: Vous pouvez choisir une collection de reference pour donenr des acces ')." : </span>";
	$out .= "<select disabled id=\"othcollsel\" name=\"othcollsel\" style=\"font-size:9px\">";
	foreach ($colls as $base_id=>$name)
		$out .= "<option  value=\"".$base_id."\">".$name;
	$out .= "</select>";
	$out .= "</small>";
}
$out .= "<br>";
$out .= "<br>";

$out .= "		<a href=\"javascript:void(0);\" onclick=\"saveColl();return(false);\">"._('boutton::valider')."</a>\n";
$out .= " ";
$out .= "		<a href='database.php?p0=".$parm['p0']."'>"._('boutton::annuler')."</a>\n";
$out .= "	</center>\n";
$out .= "</form>\n";

print($out);
?>
<script type="text/javascript">
<?php
if($parm["act"]=="NEWCOLL")
{
	print("parent.reloadTree('base:".$parm['p0']."');");
}
?>
</script>
</body>
</html>
