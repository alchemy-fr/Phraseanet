<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("srt", "ord",
						 "act",
						 "p0",	// base_id
						 "str"	// si act=CHGSTRUCTURE, structure en xml
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
{
	phrasea::headers(403);
}
$conn = connection::getInstance();
if(!$conn)
{
	phrasea::headers(500);
}

$parm['p0'] = (int)$parm['p0'];

if($parm['p0']<=0)
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
		<script type="text/javascript">
		function sizeTxtArea()
		{
			//alert(document.body.clientHeight);
			t = document.body.clientHeight;
			t= t*0.8;
			document.forms["chgStructure"].str.style.height = (t)+"px";
		}
		function saveStructure()
		{
			document.forms["chgStructure"].act.value = "CHGSTRUCTURE";
			document.forms["chgStructure"].target = "";
			document.forms["chgStructure"].submit();
		}
		function restoreStructure()
		{
			document.forms["chgStructure"].act.value = "";
			document.forms["chgStructure"].target = "";
			document.forms["chgStructure"].submit();
		}
		</script>
	</head>
	<body onResize="sizeTxtArea();" onLoad="sizeTxtArea();">


<?php
$out = "";
$msg = "";
$loadit = true;

$out .= "<H4>" . p4string::MakeString(_('admin::base: structure')) . "</h4>\n";
$connbas = connection::getInstance($parm["p0"]);
if($connbas)
{
	if($parm["act"]=="CHGSTRUCTURE")
	{
		$errors = databox::get_structure_errors($parm["str"]);
		if(count($errors) == 0 && $domst = @DOMDocument::loadXML($parm["str"])) // simplexml_load_string($parm["str"]))
		{
			$domst->documentElement->setAttribute("modification_date", $now = date("YmdHis"));

			$sql = "UPDATE pref SET value='" . $conn->escape_string($parm["str"] = $domst->saveXML()) . "', updated_on='" . $now . "' WHERE prop='structure'" ;
			$connbas->query($sql);
			
			$cache_appbox = cache_appbox::getInstance();
			$cache_appbox->delete('list_bases');
			
			cache_databox::update($parm["p0"],'structure');
		}
		else
		{
			$msg .=  p4string::MakeString(_('admin::base: xml invalide, les changements ne seront pas appliques'), 'js') . "" ;
			$loadit = false;
			$out .= "<div>".implode("</div><div>",$errors)."</div>";
			$out .= "<form method=\"post\" name=\"chgStructure\" action=\"./structure.php\" onsubmit=\"return(false);\" target=\"???\">\n";
			$out .= "	<input type=\"hidden\" name=\"act\" value=\"???\" />\n";
			$out .= "	<input type=\"hidden\" name=\"p0\" value=\"" . $parm["p0"] . "\" />\n";
			$out .= "	<TEXTAREA nowrap style=\"width:95%; height:450px; white-space:pre\" name=\"str\">" . p4string::MakeString($parm["str"], "form") . "</TEXTAREA>\n";
			$out .= "	<br/>\n";
			$out .= "</form>\n";
			$out .= "<br/>\n";
			$out .= "<br/>\n";
			$out .= "<center><a href=\"javascript:void(0);\" onclick=\"saveStructure();return(false);\">".p4string::MakeString(_('boutton::valider')) ."</a></center>\n";
		
		}
	}
	else
	{
		$parm["str"] = databox::get_structure($parm["p0"]);
	}
	if($loadit)
	{	
		
		
		$errors = databox::get_structure_errors($parm["str"]);
		$out .= "<div>".implode("</div><div>",$errors)."</div>";
		$out .= "<form method=\"post\" name=\"chgStructure\" action=\"./structure.php\" onsubmit=\"return(false);\" target=\"???\">\n";
		$out .= "	<input type=\"hidden\" name=\"act\" value=\"???\" />\n";
		$out .= "	<input type=\"hidden\" name=\"p0\" value=\"" . $parm["p0"] . "\" />\n";
		$out .= "	<TEXTAREA nowrap style=\"width:95%; height:450px; white-space:pre\" name=\"str\">" . p4string::MakeString($parm["str"], "form") . "</TEXTAREA>\n";
		$out .= "	<br/>\n";
		$out .= "</form>\n";
		$out .= "<br/>\n";
		$out .= "<br/>\n";
		$out .= "<center><a href=\"javascript:void(0);\" onclick=\"saveStructure();return(false);\">".p4string::MakeString(_('boutton::valider')) ."</a></center>\n";
	}
}

print($out);
?>
<script type="text/javascript">
<?php
if($msg)
	printf("alert(\"%s \");", $msg) ;
?>
</script>
</body>
</html>
