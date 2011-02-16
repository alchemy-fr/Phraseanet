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
					, "tid"
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


?>
<html lang="<?php echo $session->usr_i18n;?>">
<head>
	<title><?php echo p4string::MakeString(_('thesaurus:: Lier la branche de thesaurus au champ'))?></title>
	
	<link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand()?>" />

	<script type="text/javascript">
	function ckField()
	{
		fields = document.getElementsByName("field[]");
		changed = false;
		for(i=0; i<fields.length && !changed; i++)
		{
			if( (fields[i].checked?"1":"0") != fields[i].ck0)
				changed = true;
		}
		document.getElementById("submit_button").disabled = !changed;
		return(true);
	}
	function clkBut(button)
	{
		switch(button)
		{
			case "submit":
				// document.forms[0].target="LINKFIELD";
				document.forms[0].submit();
				break;
			case "cancel":
				self.close();
				break;
		}
	}
	function loaded()
	{
		window.name="LINKFIELD";
		ckField();
	}
	</script>
</head>
<body onload="loaded();" class="dialog">
<?php
if($parm["bid"] !== null)
{				

	$loaded = false;
	$connbas = connection::getInstance($parm['bid']);
	if($connbas)
	{
		$sql = "SELECT p1.value AS struct, p2.value AS thesaurus FROM pref p1, pref p2 WHERE p1.prop='structure' AND p2.prop='thesaurus'";
		if($rsbas = $connbas->query($sql))
		{
			if($rowbas = $connbas->fetch_assoc($rsbas))
			{
				if(($domstruct = @DOMDocument::loadXML($rowbas["struct"])) && ($domth = @DOMDocument::loadXML($rowbas["thesaurus"])))
				{
					$xpathth = new DOMXPath($domth);
					$xpathstruct = new DOMXPath($domstruct);
					
					if($parm["tid"]!=="")
						$q = "//te[@id='" . $parm["tid"] . "']";
					else
						$q = "//te[not(@id)]";
					$nodes = $xpathth->query($q);
					$fullBranch = "";
					if($nodes->length == 1)
					{
						for($n=$nodes->item(0); $n && $n->nodeType==XML_ELEMENT_NODE && $n->getAttribute("id")!==""; $n=$n->parentNode)
						{
							$sy = $xpathth->query("sy", $n)->item(0);
							$sy = $sy ? $sy->getAttribute("v") : "";
							if(!$sy)
								$sy = $sy = "...";
							$fullBranch = " / " .$sy .  $fullBranch;
						}
					}
?>
<center>
<form action="linkfield2.php" method="post" target="LINKFIELD">
<input type="hidden" name="piv" value="<?php echo $parm["piv"]?>">
<input type="hidden" name="bid" value="<?php echo $parm["bid"]?>">
<input type="hidden" name="tid" value="<?php echo $parm["tid"]?>">
<?php
$fbhtml = "<br/><b>" . $fullBranch . "</b><br/>";
printf(_('thesaurus:: Lier la branche de thesaurus au champ %s'), $fbhtml );
?>
<div style="width:70%; height:200px; overflow:scroll;" class="x3Dbox">
<?php
					$nodes = $xpathstruct->query("/record/description/*");
					for($i=0; $i<$nodes->length; $i++)
					{
						$fieldname = $nodes->item($i)->nodeName;
						$tbranch = $nodes->item($i)->getAttribute("tbranch");
						$ck = "";
						if($tbranch)
						{
							// ce champ a d�j� un tbranch, est-ce qu'il pointe sur la branche s�lectionn�e ?
							$thnodes = $xpathth->query($tbranch);
							for($j=0; $j<$thnodes->length; $j++)
							{
								if($thnodes->item($j)->getAttribute("id") == $parm["tid"])
								{
									$ck = "checked";
								}
							}
						}
						printf("\t\t<input type=\"checkbox\" name=\"field[]\" value=\"%s\" %s ck0=\"%s\" onclick=\"return(ckField());\">%s<br/>\n"
																, $fieldname, $ck, $ck?"1":"0", $fieldname);
					}
?>
	</div>
	<br/>
	<input type="button" id="submit_button" value="<?php echo p4string::MakeString(_('boutton::valider'))?>" onclick="clkBut('submit');">
	&nbsp;&nbsp;&nbsp;
	<input type="button" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler'))?>" onclick="clkBut('cancel');">
</form>
</center>
<?php
				}
			}
			$connbas->free_result($rsbas);
		}
	}
}
				
?>
</body>
</html>
