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
					"piv"
					, "typ"		// type de dlg : "TS"=nouvo terme sp�cifique ; "SY"=nouvo synonyme
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


switch ($parm["typ"])
{
	case "TS":
		$tstr = array( p4string::MakeString(_('thesaurus:: Nouveau terme')), p4string::MakeString(_('thesaurus:: terme')));
		break;
	case "SY":
		$tstr = array( p4string::MakeString(_('thesaurus:: Nouveau synonyme')) , p4string::MakeString(_('thesaurus:: synonyme')));
		break;
	default:
		$tstr = array( "", "");
		break;
}
				
?>
<html lang="<?php echo $session->usr_i18n;?>">
<head>
	<title><?php echo $tstr[0]?></title>
	
	<link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand()?>" />

	<script type="text/javascript">
	self.returValue = null;
	function clkBut(button)
	{
		switch(button)
		{
			case "submit":
				t = document.forms[0].term.value;
				k = document.forms[0].context.value;
				if(k != "")
					t += " ("+k+")";
				self.returnValue = {"t":t, "lng":null };
				for(i=0; i<(n=document.getElementsByName("lng")).length; i++)
				{
					if(n[i].checked)
					{
						self.returnValue.lng = n[i].value;
						break;
					}
				}
//				self.setTimeout('self.close();', 3000);
				self.close();
				break;
			case "cancel":
				self.close();
				break;
		}
	}
	</script>
</head>

<body class="dialog" onload="self.document.forms[0].term.focus();">
	<br/>
	<form onsubmit="return(false);">
		<table cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td style="text-align:right; width:80px;"><?php echo $tstr[1]?> :&nbsp;</td>
				<td></td>
				<td><input type="text" style="width:250px;" name="term"></td>
			</tr>
			<tr>
				<td style="text-align:right"><?php echo p4string::MakeString(_('thesaurus:: contexte')) /* Contexte */?> :&nbsp;</td>
				<td><b>(</b>&nbsp;</td>
				<td><input type="text" style="width:250px;" name="context">&nbsp;<b>)</b></td>
			</tr>
			<tr>
				<td valign="bottom" style="text-align:right"><?php echo p4string::MakeString(_('phraseanet:: language')) /* Langue */?> :&nbsp;</td>
				<td></td>
				<td valign="bottom">
<?php
	$tlng = user::avLanguages();
	foreach($tlng as $lng_code=>$lng)
	{
		$ck = $lng_code==$parm["piv"] ? " checked" : "";
?>
		<span style="display:inline-block">
			<input type="radio" <?php echo $ck?> name="lng" value="<?php echo $lng_code?>" id="lng_<?php echo $lng_code?>">
			<label for="lng_<?php echo $lng_code?>"><img src="/skins/lng/<?php echo $lng_code?>_flag_18.gif" />(<?php echo $lng_code?>)</label>
		</span>
		&nbsp;&nbsp;
<?php
	}
?>
				</td>
			</tr>
		</table>
		<br/>
		<div style="position:absolute; left:0px; bottom:0px; width:100%; text-align:center">
			<input type="button" style="width:80px;" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler'))?>" onclick="clkBut('cancel');" style="width:80px">
			&nbsp;&nbsp;
			<input type="button" style="width:80px;" id="submit_button" value="<?php echo p4string::MakeString(_('boutton::valider'))?>" onclick="clkBut('submit');" style="width:80px">
			<br/>
			<br/>
		</div>
	</form>
</body>
</html>
