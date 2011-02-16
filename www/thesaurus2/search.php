<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache"); 

phrasea::headers();
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"dlg"
					, "piv"
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
	<title>Chercher</title>
	
	<link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand()?>" />
	<script type="text/javascript">
		function clkBut(button)
		{
			switch(button)
			{
				case "submit":
					m = null;
					for(i=0; !m && document.forms[0].m[i]; i++)
						m = document.forms[0].m[i].checked ? document.forms[0].m[i].value : null;
					self.returnValue = { t:document.forms[0].t.value, method:m };
					self.close();
					break;
				case "cancel":
					self.returnValue = null;
					self.close();
					break;
			}
		}
		function loaded()
		{
			document.forms[0].t.focus();
		}
		function ckis()
		{
			document.getElementById("submit_button").disabled = document.forms[0].t.value=="";
		}
	</script>
</head>
<body onload="loaded();" class="dialog">
	<center>
	<br/>
	<br/>
	<form onsubmit="clkBut('submit');return(false);">
		<table>
			<tr>
				<td><?php echo p4string::MakeString(_('thesaurus:: le terme')) ?></td>
				<td><input type="radio" name="m" value="equal"><?php echo p4string::MakeString(_('thesaurus:: est egal a ')) /* est �gal � */ ?></td>
			</tr>
			<tr>
				<td />
				<td><input type="radio" checked name="m" value="begins"><?php echo p4string::MakeString(_('thesaurus:: commence par')) /* commence par */ ?></td>
			</tr>
			<tr>
				<td />
				<td><input type="radio" name="m" value="contains"><?php echo p4string::MakeString(_('thesaurus:: contient')) /* contient */ ?></td>
			</tr>
			<!--
			<tr>
				<td />
				<td><input type="radio" name="m" value="ends"><?php echo p4string::MakeString(_('thesaurus:: fini par')) /* finit par */ ?></td>
			</tr>
			-->
		</table>
		<br/>
		<input type="text" name="t" value="" style="width:200px" onkeyup="ckis();return(true);">
		<br/>
		<br/>
		<br/>
		<input type="button" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler'))?>" onclick="clkBut('cancel');" style="width:80px;">
		&nbsp;&nbsp;&nbsp;
		<input type="button" id="submit_button" value="<?php echo p4string::MakeString(_('boutton::chercher'))?>" onclick="clkBut('submit');" disabled style="width:80px;">
	</form>
	</center>
</body>
</html>
