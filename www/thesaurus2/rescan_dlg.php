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
	<title>Relire les candidats</title>
	
	<link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand()?>" />

	<script type="text/javascript">
	function loaded()
	{
		window.name="RESCAN";
		self.focus();
		ckbut();
	}
	
	function clkBut(but)
	{
		switch(but)
		{
			case 'cancel':
				self.close();
				break;
			case 'submit':
				self.document.forms[0].submit();
				break;
		}
	}
	function ckbut()
	{
		if(document.forms[0].dct.checked || document.forms[0].drt.checked)
			document.getElementById("submit_button").disabled = false;
		else
			document.getElementById("submit_button").disabled = true;
	}
	</script>
</head>
<body onload="loaded();" class="dialog">
<div style="padding:30px">

<?php
if($parm["bid"] !== null)
{			
	$loaded = false;
	$connbas = connection::getInstance($parm['bid']);
	if($connbas)
	{
		$nrec = 0;
		$sql = "SELECT COUNT(*) AS nrec FROM record";
		if($rsbas = $connbas->query($sql))
		{
			if($rowbas = $connbas->fetch_assoc($rsbas))
				$nrec = $rowbas["nrec"];
			$connbas->free_result($rsbas);
		}
		$sql = "SELECT value AS cterms FROM pref WHERE prop='cterms'";
		if($rsbas = $connbas->query($sql))
		{
			if($rowbas = $connbas->fetch_assoc($rsbas))
			{
				if( ($domct = DOMDocument::loadXML($rowbas["cterms"])) )
				{
					$r = countCandidates($domct->documentElement);
		//			if($r["nc"] > 0)
		//			{
						printf(utf8_encode("%s termes candidats, %s termes refus�s<br/><br/>\n"), $r["nc"], $r["nr"]);
?>
<form onsubmit="return(false);" action="./rescan.php" method="post">
<input type="hidden" name="bid" value="<?php echo $parm["bid"]?>">
<input type="hidden" name="piv" value="<?php echo $parm["piv"]?>">
<input type="hidden" name="dlg" value="<?php echo $parm["dlg"]?>">
<input type="checkbox" name="dct" onchange="ckbut();"><?php echo utf8_encode("Supprimer les ".$r["nc"]." candidats...")?><br/>
<input type="checkbox" name="drt" onchange="ckbut();"><?php echo utf8_encode("Supprimer les ".$r["nr"]." termes refus�s...")?><br/>
<br/>
<?php echo utf8_encode("...et placer les $nrec fiches en r�indexation-th�saurus ?<br/>\n");?>
<br/>
</div>
<div style="position:absolute; left:0px; bottom:0px; width:100%; text-align:center">
	<input type="button" style="width:80px;" id="cancel_button" value="Annuler" onclick="clkBut('cancel');">
	&nbsp;&nbsp;
	<input type="button" style="width:80px;" id="submit_button" value="Ok" onclick="clkBut('submit');">
	<br/>
	<br/>
</div>
</form>
<?php								
				//			}
						}
					}
					else
					{
					}
					$connbas->free_result($rsbas);
				}
				else
				{
				}
			}
}

function countCandidates(&$node)
{
	global $parm;
	$ret = array("nc"=>0, "nr"=>0);
	if($node->nodeType==XML_ELEMENT_NODE && $node->nodeName=="sy" && strlen($id = $node->getAttribute("id")) > 1 )
	{
		if(substr($id, 0, 1) == "C")
			$ret["nc"]++;
		elseif(substr($id, 0, 1) == "R")
			$ret["nr"]++;
	}
	for($n=$node->firstChild; $n; $n=$n->nextSibling)
	{
		$r = countCandidates($n);
		$ret["nc"] += $r["nc"];
		$ret["nr"] += $r["nr"];
	}
	return($ret);
}

?>
</body>
<script type="text/javascript">
</script>
</html>
