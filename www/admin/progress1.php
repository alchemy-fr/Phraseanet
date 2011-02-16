<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("type","htmlname", "formname");

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
		#barMv
		{
			POSITION:absolute;
			LEFT:0px;
			TOP:0px;
			BACKGROUND-COLOR:#0000FF;
		}
		#barBg
		{
		POSITION:absolute;
		LEFT:0px;
		TOP:0px;
		BACKGROUND-COLOR:#ffffff;
		BORDER:#0000FF 1px solid;
		COLOR:#0000FF;
		FONT-WEIGHT:bold;
		FONT-SIZE: 13px;
		}
		#prct
		{
		COLOR:#FFFFFF;
		FONT-WEIGHT:bold;
		FONT-SIZE: 13px;
		}
		</style>

<script language="javascript">
self.focus();
function initializebar( newtraite , newnbtotal )
{
	if(newtraite < 0)
		newtraite = 0;
	else
		if(newtraite > newnbtotal)
			newtraite = newnbtotal;

	percent = Math.round( (newtraite/newnbtotal) *100 );
	widthIE = barBg.style.pixelWidth;
	clipright = Math.floor(widthIE * (newtraite/newnbtotal) );

	baranchor.style.visibility="visible";
	document.getElementById("barMv").style.clip="rect(0px "+clipright+"px auto 0px)";
	document.getElementById("barBg").innerHTML = percent + "%";
	document.getElementById("prct").innerHTML = percent + "%";
	document.getElementById("barCptr").innerHTML = "( " + newtraite + " / " + newnbtotal + ")";
}

function rnbout(nn)
{
	if( document.getElementById("closebout") )
	{
		document.getElementById("closebout").innerHTML = nn ;
	}
}

function loaded()
{
<?php if($parm["formname"]) { ?>
	parent.opener.document.forms["<?php echo $parm["formname"]?>"].target = parent.frames[1].name;
	parent.opener.document.forms["<?php echo $parm["formname"]?>"].submit();
<?php } ?>
	parent.init = true;
}


</script>
</head>
	<body onload="loaded();">
	<br>
	<center>
<?php
	switch($parm["type"])
	{
		case "eb" : // empty base
			$s = _('admin::base: vidage de base').' : ' .$parm["htmlname"];
			print(p4string::MakeString($s, "html"));
		break;

		case "ec" : //empty coll
			$s = _('admin::base:collection: supression des enregistrements de la collection _collection_').' ' .$parm["htmlname"];
			print(p4string::MakeString($s, "html"));
		break;

		case "re" : // reindex
			$s = _('admin::base: reindexation').' :  ' .$parm["htmlname"];
			print(p4string::MakeString($s, "html"));
		break;

		case "di" : // dispatch
			$s = _('admin::base: ventilation des documents').'  : ' .$parm["htmlname"];
			print(p4string::MakeString($s, "html"));
		break;

		case "repair_disp" :
			$s = sprintf("Repair all display %s " ,$parm["htmlname"]);
			print($s);//print(p4string::MakeString($s, "html"));
		break;

		case "repair_thumb" :
			$s = sprintf("Repair all thumbnails %s" ,$parm["htmlname"]);
			print($s);//print(p4string::MakeString($s, "html"));
		break;

		case "repair_prev" :
			$s = sprintf("Repair all previews %s" ,$parm["htmlname"]);
			print($s);//print(p4string::MakeString($s, "html"));
		break;

		case "repair_all" :
			$s = sprintf("Repair all subdefs %s" ,$parm["htmlname"]);
			print($s);//print(p4string::MakeString($s, "html"));
		break;

		case "redraw_th" :
			$s = sprintf("Redraw all thumbnails %s"  ,$parm["htmlname"]);
			print($s);//print(p4string::MakeString($s, "html"));
		break;

		case "redraw_prev" :
			$s = sprintf("Redraw all previews %s"  ,$parm["htmlname"]);
			print($s);//print(p4string::MakeString($s, "html"));
		break;
		case "redraw_all" :
			$s = sprintf("Redraw all subdefs %s"  ,$parm["htmlname"]);
			print($s);//print(p4string::MakeString($s, "html"));
		break;

		case "iptc" :
			$s = sprintf("Rewrite Iptc fields %s"  ,$parm["htmlname"]);
			print($s);//print(p4string::MakeString($s, "html"));
		break;


		case "reset_W_H_xml" :
			$s = sprintf("Reset height and width into xml %s"  ,$parm["htmlname"]);
			print($s);
		break;

	}

?>
		<br>
		<br>

		<DIV id="baranchor" style="position:relative; width:400px; height:18px; visibility:hidden;">
			<div id="barBg" align="center" style="width:400px; height:18px; z-index:9">0%</div>
			<div id="barMv" align="center" style="width:400px; height:18px; z-index:10">
				<TABLE cellSpacing="0" cellPadding="0" border="0" width="400" height=20>
					<TBODY>
						<TR HEIGHT="20"><TD valign="middle" ALIGN="center" ID="prct">0%</TD></TR>
					</TBODY>
				</TABLE>
			</DIV>
		</DIV>
		<div id="barCptr"></div>

		<br>
		<br>
		<a href="javascript:void();return(false);" id="closebout" style="color:#000000;text-decoration:none;" onClick="parent.self.close();return(false);" ><?php echo p4string::MakeString(_('boutton::annuler'), "html")?></a></a>
	</center>
	</body>
</html>