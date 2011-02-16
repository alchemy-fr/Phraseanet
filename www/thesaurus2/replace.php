<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                          // HTTP/1.0

phrasea::headers();
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
require(GV_RootPath."www/thesaurus2/xmlhttp.php");
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "piv"
					, "pid"		// id du p�re (te)
					, "id"		// id du synonyme (sy)
					, "typ"
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

$url = "./xmlhttp/getsy.x.php";
$url .= "?bid=" . urlencode($parm["bid"]);
$url .= "&piv=" . urlencode($parm["piv"]);
$url .= "&sortsy=0";
$url .= "&id=" . urlencode($parm["id"]);
$url .= "&typ=" . urlencode($parm["typ"]);

//print($url. "<br/>\n");
$dom = xmlhttp($url);
$fullpath = $dom->getElementsByTagName("fullpath_html")->item(0)->firstChild->nodeValue;
$zterm = $dom->getElementsByTagName("sy")->item(0)->getAttribute("t");
$hits = $dom->getElementsByTagName("hits")->item(0)->firstChild->nodeValue;
?>

<html lang="<?php echo $session->usr_i18n;?>">
<head>
	<title>Corriger...</title>
	
	<link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand()?>" />
	<style type="text/css">
		a
		{
			text-decoration:none;
			font-size: 10px;
		}
		.path_separator
		{
			color:#ffff00;
		}
		.main_term
		{
			font-weight:900;
			xcolor:#ff0000;
		}
	</style>

	<script type="text/javascript" src="./xmlhttp.js"></script>
	<script type="text/javascript">
	function loaded()
	{
		window.name="REPLACE";
		self.focus();
		ckField();
	}
	function ckField()
	{
		fields = document.getElementsByName("field[]");
		chk = false;
		for(i=0; i<fields.length && !chk; i++)
		{
			if( fields[i].checked )
				chk = true;
		}
		// document.getElementById("submit_button").disabled = (!chk) || (document.forms[0].rpl.value==document.forms[0].src.value);
		document.getElementById("submit_button").disabled = (document.forms[0].rpl.value==document.forms[0].src.value);
		document.getElementById("rplrec").disabled = !chk;
		document.getElementById("rplrec").checked = chk;
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
	function clkRepl()
	{
		var o;
		if(!(o=document.getElementById("rplrec")).checked)
		{
			fields = document.getElementsByName("field[]");
			for(i=0; i<fields.length; i++)
				fields[i].checked = false;
			o.disabled = true;
		}
	}
	</script>
</head>
<body onload="loaded();" class="dialog">
<div style='text-align:right'><b>id:</b>&nbsp;<?php echo $parm["id"]?></div>
<H4><?php echo $fullpath?></H4><br/><br/>
<?php

// printf("present dans %s fiche(s).<br/>\n", $dom->getElementsByTagName("hits")->item(0)->firstChild->nodeValue );

if($parm["typ"]=="TH")
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
				if( ($domstruct = @DOMDocument::loadXML($rowbas["struct"])) && ($domth = @DOMDocument::loadXML($rowbas["thesaurus"])) )
				{
					$xpathth = new DOMXPath($domth);
					$xpathstruct = new DOMXPath($domstruct);
?>
<center>
<form action="replace2.php" method="post" target="REPLACE">
<input type="hidden" name="bid" value="<?php echo $parm["bid"]?>">
<input type="hidden" name="piv" value="<?php echo $parm["piv"]?>">
<input type="hidden" name="dlg" value="<?php echo $parm["dlg"]?>">
<input type="hidden" name="pid" value="<?php echo $parm["pid"]?>">
<input type="hidden" name="id"  value="<?php echo $parm["id"]?>">
<?php echo utf8_encode("Corriger le terme")?>
<?php if(1) { ?>
<b><?php echo $zterm?></b><input type="hidden" name="src" value="<?php echo p4string::MakeString($zterm, "js")?>"> 
<?php } else { ?>
<input type="text" name="src" onkeyup="ckField();return(true);" value="<?php echo p4string::MakeString($zterm, "js")?>"> 
<?php } ?> 
<?php echo utf8_encode("par : ")?><input type="text" name="rpl" style="width:150px;" onkeyup="ckField();return(true);" value="<?php echo p4string::MakeString($zterm, "js")?>"> 
<br/>
<br/>
<input type="checkbox" id="rplrec" name="rplrec" onclick="clkRepl();return(true);" disabled>
<label for="rplrec"><?php echo utf8_encode("et corriger �galement dans le champ :")?></label>
<br/>
<br/>
<div style="width:70%; height:110px; overflow:scroll;" class="x3Dbox">
<?php
					$fields = $xpathstruct->query("/record/description/*");
					for($i=0; $i<$fields->length; $i++)
					{
						$fieldname = $fields->item($i)->nodeName;
						$tbranch = $fields->item($i)->getAttribute("tbranch");
						$ck = "";
						if($tbranch)
						{
							// ce champ a un tbranch, est-ce qu'il permet d'atteindre le terme s�lectionn� ?
							$branches = $xpathth->query($tbranch);
							for($j=0; $j<$branches->length; $j++)
							{
								$q = ".//sy[@id='".$parm["id"]."']";
								// printf("searching %s against id=%s<br/>\n", $q, $branches->item($j)->getAttribute("id"));
								if($xpathth->query($q, $branches->item($j) )->length > 0)
								{
									// oui
									$ck = true;
								}
								else
								{
									// non
								}
							}
						}
						if($ck)
						{
							printf("\t\t<input type=\"radio\" name=\"field[]\" value=\"%s\" onclick=\"return(ckField());\"><b>%s</b><br/>\n"
																, $fieldname, $fieldname);
						}
						else
						{
							printf("\t\t<input type=\"radio\" name=\"field[]\" value=\"%s\" onclick=\"return(ckField());\">%s<br/>\n"
																, $fieldname, $fieldname);
						}
					}
?>
</div>
<br/>
<input type="button" id="cancel_button" value="Annuler" onclick="clkBut('cancel');" style="width:80px;">
&nbsp;&nbsp;&nbsp;
<input type="button" id="submit_button" value="Corriger" onclick="clkBut('submit');" style="width:80px;">
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
