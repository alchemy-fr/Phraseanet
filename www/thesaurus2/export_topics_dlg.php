<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                          // HTTP/1.0

phrasea::headers();
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "piv"
					, "id"
					, "typ"
					, "dlg"
					, 'obr'	// liste des branches ouvertes
				);

$lng = isset($session->locale)?$session->locale:GV_default_lng;
if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
}
else
{
	header("Location: /login/?error=auth&lng=".$lng);
	exit();
}


if($parm["dlg"])
{
	$opener = "window.dialogArguments.win";
}
else
{
	$opener = "opener";
}

?>
<html lang="<?php echo $session->usr_i18n;?>">
<head>
	<title><?php echo p4string::MakeString(_('thesaurus:: export en topics'))?></title>
	
	<link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand()?>" />
	<script type="text/javascript">
		var format = '???';
		function clkBut(button)
		{
			switch(button)
			{
				case "submit":
					document.forms[0].target = (format == 'tofiles' ? "_self" : "EXPORT2");
					document.forms[0].submit();
					break;
				case "cancel":
					self.returnValue = null;
					self.close();
					break;
			}
		}
		function loaded()
		{
//			document.forms[0].t.focus();
			chgFormat();
		}
		function ckis()
		{
			document.getElementById("submit_button").disabled = document.forms[0].t.value=="";
		}
		function enable_inputs(o, stat)
		{
			if(o.nodeType==1)	// element
			{
				if(o.nodeName=='INPUT')
				{
					if(stat)
						o.removeAttribute('disabled');
					else
						o.setAttribute('disabled', true);
				}
				for(var oo=o.firstChild; oo; oo=oo.nextSibling)
					enable_inputs(oo, stat)
			}
		}
		function chgFormat()
		{
			var i, f;
			for(i=0; i<document.forms[0].ofm.length; i++)
			{
				f = document.forms[0].ofm[i].value;
				if(document.forms[0].ofm[i].checked)
				{
					// enable_inputs(document.getElementById('subform_'+f), true);
					format = f;
				}
				else
				{
					// enable_inputs(document.getElementById('subform_'+f), false);
				}
			}
		}
	</script>
</head>
<body onload="loaded();" class="dialog">
	<center>
	<form onsubmit="clkBut('submit');return(false);" action="export_topics.php">
		<input type="hidden" name="bid" value="<?php echo $parm["bid"]?>" >
		<input type="hidden" name="piv" value="<?php echo $parm["piv"]?>" >
		<input type="hidden" name="id" value="<?php echo $parm["id"]?>" >
		<input type="hidden" name="typ" value="<?php echo $parm["typ"]?>" >
		<input type="hidden" name="dlg" value="<?php echo $parm["dlg"]?>" >
		<input type="hidden" name="obr" value="<?php echo $parm["obr"]?>" >

		<div style="padding:10px;">
			<div class="x3Dbox">
				<span class="title"><?php echo p4string::MakeString(_('thesaurus:: exporter')) /* export */ ?></span>
				<div style="white-space:nowrap">
					<input type='radio' name='ofm' checked value='tofiles' onclick="chgFormat();">
					<?php echo p4string::MakeString(_('thesaurus:: exporter vers topics pour toutes les langues')) /* vers les topics, pour toutes les langues */ ?>
				</div>
				<!--
				<div id='subform_tofiles' style="margin-left:10px;">
				</div>
				-->	
				<div style="white-space:nowrap">
					<input type='radio' name='ofm' value='toscreen' onclick="chgFormat();">
					<?php echo p4string::MakeString(_('thesaurus:: exporter a l\'ecran pour la langue _langue_')) . $parm['piv']; ?>
				</div>
			</div>
			
			<br/>
			
			<div class="x3Dbox">
				<span class="title"><?php echo p4string::MakeString(_('phraseanet:: tri')) /* tri */ ?></span>
				<div style="white-space:nowrap">
					<input type='checkbox' name='srt' checked onclick="chgFormat();">
					<?php echo p4string::MakeString(_('phraseanet:: tri par date')) /* tri� */ ?>
				</div>
			</div>
			
			<br/>
			
			<div class="x3Dbox">
				<span class="title"><?php echo p4string::MakeString(_('thesaurus:: recherche')) /* recherche */ ?></span>
				<div style="white-space:nowrap">
					<input type='radio' name='sth' value="1" checked onclick="chgFormat();">
					<?php echo p4string::MakeString(_('thesaurus:: recherche thesaurus *:"query"')) /* recherche thesaurus */ ?>
				</div>
				<div style="white-space:nowrap">
					<input type='radio' name='sth' value="0" onclick="chgFormat();">
					<?php echo p4string::MakeString(_('thesaurus:: recherche fulltext')) /* recherche thesaurus */ ?>
				</div>
				<div style="white-space:nowrap">
					<input type='checkbox' name='sand' onclick="chgFormat();">
					<?php echo p4string::MakeString(_('thesaurus:: question complete (avec operateurs)')) /* full query, with 'and's */ ?>
				</div>
			</div>
			
			<br/>
			
			<div class="x3Dbox">
				<span class="title"><?php echo p4string::MakeString(_('thesaurus:: presentation')) ?></span>
				<div style="white-space:nowrap">
					<input type='radio' name='obrf' value="from_itf_closable" checked onclick="chgFormat();">
					<?php echo p4string::MakeString(_('thesaurus:: presentation : branches refermables'))?>
				</div>
				<div style="white-space:nowrap">
					<input type='radio' name='obrf' value="from_itf_static" onclick="chgFormat();">
					<?php echo p4string::MakeString(_('thesaurus:: presentation : branche ouvertes')) ?>
				</div>
				<div style="white-space:nowrap">
					<input type='radio' name='obrf' value="all_opened_closable" onclick="chgFormat();">
					<?php echo p4string::MakeString(_('thesaurus:: tout deployer - refermable')) /* Tout d�ployer (refermable) */ ?>
				</div>
				<div style="white-space:nowrap">
					<input type='radio' name='obrf' value="all_opened_static" onclick="chgFormat();">
					<?php echo p4string::MakeString(_('thesaurus:: tout deployer - statique')) /* Tout d�ployer (statique) */ ?>
				</div>
				<div style="white-space:nowrap">
					<input type='radio' name='obrf' value="all_closed" onclick="chgFormat();">
					<?php echo p4string::MakeString(_('thesaurus:: tout fermer')) /* Tout fermer */ ?>
				</div>
			</div>
		</div>
		<input type="button" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler'))?>" onclick="clkBut('cancel');" style="width:100px;">
		&nbsp;&nbsp;&nbsp;
		<input type="button" id="submit_button" value="<?php echo p4string::MakeString(_('boutton::valider'))?>" onclick="clkBut('submit');" style="width:100px;">
	</form>
	</center>
</body>
</html>
