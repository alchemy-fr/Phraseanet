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
					, "dlg"
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
	<base target="_self">
	<title><?php echo p4string::MakeString(_('thesaurus:: Importer'))?></title>
	
	<link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand()?>" />
	<script type="text/javascript">
		function clkBut(button)
		{
			switch(button)
			{
				case "submit":
					document.forms[0].target='IFRIM';
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
		}
		function importDone(err)
		{
			if(!err)
			{
				<?php echo $opener?>.reload();
				self.close();
			}
			else
			{
				alert(err);
			}
		}
	</script>
</head>
<body onload="loaded();" class="dialog">
	<br/>
	<form onsubmit="clkBut('submit');return(false);" action="import.php" enctype="multipart/form-data" method="post">
		<input type="hidden" name="bid" value="<?php echo $parm["bid"]?>" >
		<input type="hidden" name="piv" value="<?php echo $parm["piv"]?>" >
		<input type="hidden" name="id" value="<?php echo $parm["id"]?>" >
		<input type="hidden" name="dlg" value="<?php echo $parm["dlg"]?>" >
		<div>
			<!--<div style="float:left"><?php echo p4string::MakeString(_('thesaurus:: coller ici la liste des termes a importer')); /* Coller ici la liste des termes � importer : */ ?></div>-->
			<div style="float:right"><?php echo p4string::MakeString(_('thesaurus:: langue par default')). "&nbsp;<img src='/skins/icons/flag_18.gif' />".'&nbsp;'.$parm['piv'];?></div>
		</div>
		<br/>
		<!--<textarea name="t" style="width:550px; height:200px" value=""></textarea>
		<br/>--> 
	    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo(16*1024*1024)?>" />
		<!-- OU envoyer le fichier :--> 
		<?php echo _('Fichier ASCII tabule')?>
		<input type="file" name="fil" />&nbsp;(max 16Mo)
		<br/>
		
		<div style="text-align:center">
<!--
			<div style="text-align:left; position:relative; top:0px; left:0px; display:inline; background-color:#ff0000; white-space:nowrap; xmargin-left:auto; xmargin-right:auto">
				<p style="white-space:nowrap; width:auto">
					<input type="checkbox" name="dlk" checked="1">Supprimer les liens des champs (tbranch)
				</p>
				<p style="white-space:nowrap; width:auto">
					<input type="checkbox" name="rdx" checked="1">R�indexer la base apr�s l'import
				</p>
			</div>
-->
			<table>
				<tr>
					<td style="text-align:left"><input type="checkbox" disabled="disabled" name="dlk" checked="checked"><?php echo p4string::MakeString(_('thesaurus:: supprimer les liens des champs tbranch')); /* Supprimer les liens des champs (tbranch) */ ?></td>
				</tr>
				<tr>
					<td style="text-align:left"><input type="checkbox" disabled="disabled" name="rdx"><?php echo p4string::MakeString(_('thesaurus:: reindexer la base apres l\'import')); /* R�indexer la base apr�s l'import */ ?></td>
				</tr>
			</table>
			<br/>
			<input type="button" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler'))?>" onclick="clkBut('cancel');" style="width:100px;">
			&nbsp;&nbsp;&nbsp;
			<input type="button" id="submit_button" value="<?php echo p4string::MakeString(_('boutton::valider'))?>" onclick="clkBut('submit');" style="width:100px;">
		</div>
	</form>
	<iframe style="display:none; height:50px;" name="IFRIM"></iframe>
</body>
</html>
