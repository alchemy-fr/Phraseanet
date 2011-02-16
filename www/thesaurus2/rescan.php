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
					, "dct"		// delete candidates terms
					, "drt"		// delete rejected terms
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
</head>

<body onload="loaded();" class="dialog">
<?php
if($parm["bid"] !== null)
{			
	$loaded = false;
	$connbas = connection::getInstance($parm['bid']);
			if($connbas)
			{
				$sql = "SELECT value AS cterms FROM pref WHERE prop='cterms'";
				if($rsbas = $connbas->query($sql))
				{
					if($rowbas = $connbas->fetch_assoc($rsbas))
					{
						if( ($domct = DOMDocument::loadXML($rowbas["cterms"])) )
						{
							$nodestodel = array();
							removeCandidates($domct->documentElement, $nodestodel);
							
							foreach($nodestodel as $nodetodel)
							{
								// $id = str_replace(".", "d", $nodetodel->getAttribute("id")) . "d";
							//	$sql = "DELETE FROM thit WHERE value LIKE '$id%'";
							//	$connbas->query($sql); 
								// printf("sql : %s<br/>\n", $sql);
								$nodetodel->parentNode->removeChild($nodetodel);
							}
							if($parm["dct"])
							{
								$sql = "DELETE FROM thit WHERE value LIKE 'C%'";
								$connbas->query($sql);
							}
							if($parm["drt"])
							{
								$sql = "DELETE FROM thit WHERE value LIKE 'R%'";
								$connbas->query($sql);
							}
								
							$domct->documentElement->setAttribute("modification_date", $now = date("YmdHis"));
							$sql  = "UPDATE pref SET";
							$sql .= "  value='" . $connbas->escape_string($domct->saveXML()) . "'" ;
							$sql .= ", updated_on='" .$connbas->escape_string($now). "'";
							$sql .= " WHERE prop='cterms'";
							
							$connbas->query($sql);
							// printf("sql : %s<br/>\n", $sql);
							
							$sql = "UPDATE record SET status=status & ~2";	// marquer les records comme 'reindex thesaurus'
							$connbas->query($sql);
							// printf("sql : %s<br/>\n", $sql);
?>						
<form onsubmit="return(false);">
<div style="padding:50px; text-align:center">
	<?php echo utf8_encode("Termin�")?>
	<br/>
	<br/>
	<input type="button" style="width:120px;" id="submit_button" value="<?php echo utf8_encode("Fermer la fen�tre")?>" onclick="refreshCterms();self.close();">
</div>
</form>
<?php
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

function removeCandidates(&$node, &$nodestodel)
{
	global $parm;
	if($node->nodeType==XML_ELEMENT_NODE && $node->nodeName=="te" && $node->getAttribute("field")=="")
	{
		$id0 = substr($node->getAttribute("id"), 0, 1);
		if( ($parm["dct"] && $id0=="C") || ($parm["drt"] && $id0=="R"))
			$nodestodel[] = $node;
	}
	else
	{
		for($n=$node->firstChild; $n; $n=$n->nextSibling)
			removeCandidates($n, $nodestodel);
	}
}

?>
</body>
<script type="text/javascript">
function refreshCterms()
{
	if( (thb = <?php echo $opener?>.document.getElementById("THB_C")) )
		thb.className = thb.className.replace(/OB/, "ob");
	if( (thp = <?php echo $opener?>.document.getElementById("THP_C")) )
		thp.innerHTML = "+";
}

</script>
</html>
