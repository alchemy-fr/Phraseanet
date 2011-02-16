<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("act" ,"i","label","view","order","link", 'wmprev', 'thumbLimit');

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

if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
{
		phrasea::headers(403);
}
?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
		<style type="text/css">
		body
		{
		}
		A,  A:link, A:visited, A:active
		{
			
			color : #000000; 
			padding-bottom : 15px;
			text-decoration:none;
		}
		
		A:hover
		{ 
			COLOR : #ba36bf;
			text-decoration:underline;
		}
		</style>
	</head>
<body>
<center><div style="text-decoration:underline;font-size:14px"><?php echo _('admin::paniers: parametres de publications des paniers de page d\'accueil')?></div></center>

<form method="post" action="./statchu.php" style="display:none; visibility:hidden" target="_self">
	<input type="hidden" name="i" value="" />
	<input type="hidden" name="act" value="" />
</form>

<script type="text/javascript">
 
function modstat(i)
{
	document.forms[0].i.value = i ;
	document.forms[0].submit();
}

function delstat(i)
{
	document.forms[0].i.value = i ;
	document.forms[0].act.value = "DEL" ;
	document.forms[0].action = "paramchu.php" ;
	document.forms[0].submit();
}

</script>

<?php

$conn = connection::getInstance();

$sql = "SELECT issuperu FROM usr WHERE usr_id='" . $conn->escape_string($usr_id)."'";
if($rs = $conn->query($sql))
{
	if( $conn->num_rows($rs)!=1)
	{
		die();
	}
}


$sitepreff = "";
$version   = "";
$lastmaj   = ""; 
$sql = "SELECT * FROM sitepreff WHERE id='1'";
if($rs = $conn->query($sql))
{
	if($row = $conn->fetch_assoc($rs))
	{
		$sitepreff = $row["preffs"]; 
		$version   = $row["version"]; 
		$lastmaj   = $row["maj"]; 
	}
}

$bits = NULL ; 

$sxe = simplexml_load_string($sitepreff);


 
if($parm["act"]=="DEL")
{
	// pas de bol, on doit faire du dom
	$doc = new DOMDocument;
	if($doc->loadXML($sitepreff))
	{
		$xpath = new DOMXPath($doc);
		$entries = $xpath->query($q="/paramsite/statuschu/bit[@n=".$parm["i"]."]");

		foreach($entries as $bit)
		{
			if($p = $bit->previousSibling)
			{
				if($p->nodeType==XML_TEXT_NODE && $p->nodeValue=="\n\t\t")
					$p->parentNode->removeChild($p);
			}
			$bit->parentNode->removeChild($bit);
		}
		$sql = "UPDATE sitepreff SET preffs='".$conn->escape_string($doc->saveXML())."'";
		$sxe = simplexml_import_dom($doc);
		$conn->query($sql);
	}	
}
 

if($parm["act"]=="UPD")
{
 
	// changement du nom ?
	$i = 0;
	$found = false;
	if($sxe->statuschu && $sxe->statuschu->bit)
	{
		foreach($sxe->statuschu->bit as $sb)
		{
			if($sb["n"] == $parm["i"])
			{
				$found = true;
				$needUpd = false;
				// changement du label  ??
				if((string)($sb->attributes()->label) != $parm["label"])
				{
					$needUpd = true;
					$sb["label"] = $parm["label"];
				}
				if($parm["i"] == '-1')
				{
					$needUpd = true;
					// changement du order  ?? (aleatoire, par date ...)
					if((string)($sb->attributes()->order) != $parm["order"])
					{
						$sb["order"] = $parm["order"];
					}
			
					$sb["link"] = 1;
			
				
					// changement du view  ?? (page inter ou pas )
					if((string)($sb->attributes()->view) != $parm["view"])
					{
						$needUpd = true;
						$sb["view"] = $parm["view"];
					}
				
					// mettre le watermark sur les preview   
					if((string)($sb->attributes()->wmprev) != $parm["wmprev"])
					{
						$needUpd = true;
						$sb["wmprev"] = $parm["wmprev"];
					}
				
					// mettre max 4 vignettes  
					if((string)($sb->attributes()->thumbLimit) != $parm["thumbLimit"])
					{
						$needUpd = true;
						$sb["thumbLimit"] = $parm["thumbLimit"];
					}
				}
				
				
				if($needUpd)
				{
					$sql = "UPDATE sitepreff SET preffs='".$conn->escape_string($sxe->asXML())."'";
					
					 $conn->query($sql);
				}
				break;
			}
			$i++;
		}
	}
 
	if(!$found)	// on doit creer un nouveau statbit
	{
		// pas de bol, on doit faire du dom
		$doc = new DOMDocument;
		if($doc->loadXML($sitepreff))
		{
			$xpath = new DOMXPath($doc);
			$entries = $xpath->query("/paramsite/statuschu");
			if($entries->length == 0)
			{
				$doc->documentElement->appendChild($doc->createTextNode("\t"));
				if($statuschu = $doc->documentElement->appendChild($doc->createElement("statuschu")))
					$statuschu->appendChild($doc->createTextNode("\n\t"));
				$doc->documentElement->appendChild($doc->createTextNode("\n"));
			}
			else
			{
				$statuschu = $entries->item(0);
			}
			if($statuschu)
			{
				$statuschu->appendChild($doc->createTextNode("\t"));
				if($bit = $statuschu->appendChild($doc->createElement("bit")))
				{
					if($n = $bit->appendChild($doc->createAttribute("n")))
						$n->value = $parm["i"];
					if($parm["i"] == '-1')
					{
						if($link= $bit->appendChild($doc->createAttribute("link")))
							$link->value = 1; 
						if($ord = $bit->appendChild($doc->createAttribute("order")))
							$ord->value = $parm["order"]; 
						if($view = $bit->appendChild($doc->createAttribute("view")))
							$view->value = $parm["view"]; 
						if($lab = $bit->appendChild($doc->createAttribute("label")))
							$lab->value = $parm["label"]; 
						if($lab = $bit->appendChild($doc->createAttribute("wmprev")))
							$lab->value = $parm["wmprev"]; 
						if($lab = $bit->appendChild($doc->createAttribute("thumbLimit")))
							$lab->value = $parm["thumbLimit"]; 
					}
					
				}
				$statuschu->appendChild($doc->createTextNode("\n\t"));
			}
			// $doc->normalize();
			$sql = "UPDATE sitepreff SET preffs='".$conn->escape_string($doc->saveXML())."'";
			$sxe = simplexml_import_dom($doc);
			$conn->query($sql);
		}
	}

}

if($sxe)
{
	if($sxe->statuschu->bit)
	{
		foreach($sxe->statuschu->bit as $sb)
		{
			$num = (int)($sb["n"]);
			$bits[$num]["label"]  = (string)($sb["label"]);
			$bits[$num]["order"] = (int)($sb["order"]);			
			$bits[$num]["link"] = (int)($sb["link"]);			
			$bits[$num]["wmprev"] = (int)($sb["wmprev"]);			
			$bits[$num]["thumbLimit"] = (int)($sb["thumbLimit"]);			
		}
	}
}

?>
<br>
<br>
<center>
<table style="text-align:center;border:#AAAAAA 1px solid;table-layout:fixed">
	<tr>
		<td colspan="4" style="border-bottom:#AAAAAA 1px solid;width:320px;">
			<a href="javascript:void();return(false);" onclick="modstat('-1');return(false);"><?php echo _('admin::paniers: parametres de publications des paniers de page d\'accueil')?></a>
		</td>
	</tr>
	<tr>
		<td style="background-color:#CFCFCF;width:50px"><?php echo _('admin::paniers: edition du status')?></td>
		<td style="background-color:#CFCFCF;width:150px"><?php echo _('admin::paniers: label status : ')?></td>
		<td style="background-color:#CFCFCF;width:60px">&nbsp;</td>	
		<td style="background-color:#CFCFCF;width:60px">&nbsp;</td>	
	</tr>
<?php
	for($i=0; $i<8; $i++)
	{
		if( isset($bits[$i]) )
		{
?>
	<tr>
		<td style="border-bottom :#CCCCCC 1px solid;"><?php echo $i?></td>
		<td style="border-bottom :#CCCCCC 1px solid;"><?php echo $bits[$i]["label"]?></td>
		<td style="border-bottom :#CCCCCC 1px solid;"><a href="javascript:void();return(false);" onclick="modstat('<?php echo $i?>');return(false);"><?php echo _('boutton::modifier')?></a></td>  
		<td style="border-bottom :#CCCCCC 1px solid;"><a href="javascript:void();return(false);" onclick="delstat('<?php echo $i?>');return(false);"><?php echo _('boutton::supprimer')?></a></td>  
	</tr>
<?php
		}
		else
		{
?>
	<tr>
		<td style="border-bottom :#CCCCCC 1px solid;"><?php echo $i?></td>
		<td style="border-bottom :#CCCCCC 1px solid;">-</td>
		<td style="border-bottom :#CCCCCC 1px solid;"><a href="javascript:void();return(false);" onclick="modstat('<?php echo $i?>');return(false);"><?php echo _('boutton::modifier')?></a> </td>  
		<td style="border-bottom :#CCCCCC 1px solid;">&nbsp;</td>  
	</tr>
<?php
		}
	}
?>	
	
</table>
</center>

</body>
</html>

