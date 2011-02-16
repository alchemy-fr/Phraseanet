<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                          // HTTP/1.0
phrasea::headers();
require(GV_RootPath."www/thesaurus2/xmlhttp.php");
$session = session::getInstance();


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "piv"
					, "src"
					, "tgt"
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
	<title><?php echo p4string::MakeString(_('thesaurus:: accepter...'))?></title>
	
	<link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand()?>" />
	<style type="text/css">
		.path_separator
		{
			color:#ffff00;
		}
		.main_term
		{
			font-weight:900;
		}
	</style>

	<script type="text/javascript" src="./xmlhttp.js"></script>
	<script type="text/javascript">
	function loaded()
	{
		window.name="ACCEPT";
		self.focus();
	}
	function ok()
	{
		as = "";
		if((n=document.forms[0].as.length) > 0)
		{
			for(i=0; i<n && as==""; i++)
			{
				if(document.forms[0].as[i].checked)
					as = document.forms[0].as[i].value;
			}
		}
		else
		{
			as = document.forms[0].as.value;
		}
		if(as == "TS")
		{
			url = "xmlhttp/acceptcandidates.x.php";
			parms  = "bid=<?php echo urlencode($parm["bid"])?>";
			parms += "&piv=<?php echo urlencode($parm["piv"])?>";
			parms += "&cid[]=<?php echo urlencode($parm["src"])?>";
			parms += "&pid=<?php echo urlencode($parm["tgt"])?>";
			parms += "&typ=TS";
//alert(url + "?" + parms);
			
			// return;
			
			ret = loadXMLDoc(url, parms, true);
//alert(ret);
			
			refresh = ret.getElementsByTagName("refresh");
//alert(refresh.length);
			for(i=0; i<refresh.length; i++)
			{
//alert(i + " : '" + refresh.item(i).getAttribute("type") + "' id='"+refresh.item(i).getAttribute("id")+"'");
				switch(refresh.item(i).getAttribute("type"))
				{
					case "CT":
						<?php echo $opener?>.reloadCtermsBranch(refresh.item(i).getAttribute("id"));
						break;
					case "TH":
						<?php echo $opener?>.reloadThesaurusBranch(refresh.item(i).getAttribute("id"));
						break;
				}
			}
			self.close();
		}
		else if(as == "SY")
		{
			url = "xmlhttp/acceptcandidates.x.php";
			parms  = "bid=<?php echo urlencode($parm["bid"])?>";
			parms += "&piv=<?php echo urlencode($parm["piv"])?>";
			parms += "&cid[]=<?php echo urlencode($parm["src"])?>";
			parms += "&pid=<?php echo urlencode($parm["tgt"])?>";
			parms += "&typ=SY";
			
			ret = loadXMLDoc(url, parms, true);
			
			refresh = ret.getElementsByTagName("refresh");
			for(i=0; i<refresh.length; i++)
			{
				switch(refresh.item(i).getAttribute("type"))
				{
					case "CT":
						<?php echo $opener?>.reloadCtermsBranch(refresh.item(i).getAttribute("id"));
						break;
					case "TH":
						<?php echo $opener?>.reloadThesaurusBranch(refresh.item(i).getAttribute("id"));
						break;
				}
			}
			self.close();
		}
	}
	</script>
</head>
<body id="desktop" onload="loaded();" class="dialog">

<?php
if($parm["bid"] !== null)
{
	$url = "./xmlhttp/getterm.x.php";
	$url .= "?bid=" . urlencode($parm["bid"]);
	$url .= "&piv=" . urlencode($parm["piv"]);
	$url .= "&sortsy=0";
	$url .= "&id=" . urlencode($parm["src"]);
	$url .= "&typ=CT";
	$url .= "&nots=1";
 //print("URL='$url'<br/>\n");
	$dom = xmlhttp($url);
	
	if((int)($dom->documentElement->getAttribute('found')) == 0)
	{
		
?>
	<center>
	<br/>
	<br/>
	<br/>
	<?php echo p4string::MakeString(_('thesaurus:: removed_src'), "html")?>
	<br/>
	<br/>
	<?php echo p4string::MakeString(_('thesaurus:: refresh'), "html")?>
	<br/>
	<br/>
	<br/>
	<br/>
	<br/>
	<input style="position:relative; z-index:2; width:100px" type="button" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::fermer'))?>" onclick="self.close();">
<?php
	}
	else
	{
		$fullpath_src = $dom->getElementsByTagName("fullpath_html")->item(0)->firstChild->nodeValue;
		$nts = $dom->getElementsByTagName("ts_list")->item(0)->getAttribute("nts");
		
		if( ($cfield = $dom->getElementsByTagName("cfield")->item(0)) )
		{
			if($cfield->getAttribute("delbranch") )
				$cfield = '*';
			else
				$cfield = $cfield->getAttribute("field");
		}
		else
		{
			$cfield = NULL;
		}
	//	{
	//		if( ($cfield_tbranch = $cfield->getAttribute("tbranch")) )
	//		{
	//		}
	//	}
		
	
	
// print("cfield='$cfield'<br/>\n");
//	print($fullpath);
	
		$url = "./xmlhttp/getterm.x.php";
		$url .= "?bid=" . urlencode($parm["bid"]);
		$url .= "&piv=" . urlencode($parm["piv"]);
		$url .= "&sortsy=0";
		$url .= "&id=" . urlencode($parm["tgt"]);
		$url .= "&typ=TH";
		if($cfield)
			$url .=  "&acf=" . urlencode($cfield);
		$url .= "&nots=1";
		
	// print("URL='$url'<br/>\n");
		// print($url. "<br/>\n");
		$dom = xmlhttp($url);
		
		if((int)($dom->documentElement->getAttribute('found')) == 0)
		{
			// on n'a pas trouv� le node de destination (il a �t� d�plac� par qqun d'autre)
?>
	<center>
	<br/>
	<br/>
	<br/>
	<?php echo p4string::MakeString(_('thesaurus:: removed tgt'), "html")?>
	<br/>
	<br/>
	<?php echo p4string::MakeString(_('thesaurus:: refresh'), "html")?>
	<br/>
	<br/>
	<br/>
	<br/>
	<br/>
	<input style="position:relative; z-index:2; width:100px" type="button" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::fermer'))?>" onclick="self.close();">
<?php
		}
		else
		{
			// printf("%s", $dom->saveXML());
			
			$fullpath_tgt = $dom->getElementsByTagName("fullpath_html")->item(0)->firstChild->nodeValue;
			
			$acceptable = 0 + $dom->getElementsByTagName("cfield")->item(0)->getAttribute("acceptable");
	
	// print("acceptable=$acceptable<br/>\n");
	 		if($acceptable)
	 		{
?>
	<center>
	<br/>

	<form method="?" action="?" target="?" onsubmit="return(false);">
		<input type="hidden" name="bid" value="<?php echo urlencode($parm["bid"])?>">
		<input type="hidden" name="piv" value="<?php echo urlencode($parm["piv"])?>">
		<input type="hidden" name="src" value="<?php echo urlencode($parm["src"])?>">
		<input type="hidden" name="tgt" value="<?php echo urlencode($parm["tgt"])?>">
		<input type="hidden" name="tgt" value="<?php echo urlencode($parm["tgt"])?>">
<?php
				if($nts == 0)
				{
					print(p4string::MakeString(_('thesaurus:: Accepter le terme comme'), "html"));
					print('<br/><br/><h4>'.$fullpath_src.'</h4><br/><br/>');
					print("<br/>&nbsp;&nbsp;<input type='radio' name='as' value='TS' checked>" . p4string::MakeString(_('thesaurus:: comme terme specifique')));
					print("&nbsp;&nbsp;&nbsp;");
					print("<input type='radio' name='as' value='SY'>");
				//	print("<br/><br/>\n");
					printf(p4string::MakeString(_('thesaurus:: comme synonyme de %s'), "html"),"<br/><br/>\n<h4>".$fullpath_tgt."</h4><br/>\n");
				}
				else
				{
					printf("<br/><br/><h4>".$fullpath_src."</h4><br/><br/>\n");
					print(p4string::MakeString(_('thesaurus:: Accepter la branche comme'), "html") . '<br/>');
					print("&nbsp;" . p4string::MakeString(_('thesaurus:: comme terme specifique')));
					printf("<br/><br/>\n<h4>".$fullpath_tgt."</h4><br/><br/>\n");
					print("<input type='hidden' name='as' value='TS'>\n");
				}
		//	print($fullpath);
?>
		<br/>
		<br/>
		<input style="position:relative; z-index:2; width:100px" type="button" id="ok_button" value="<?php echo p4string::MakeString(_('boutton::valider'))?>" onclick="ok();">
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input style="position:relative; z-index:2; width:100px" type="button" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler'))?>" onclick="self.close();">
	</form>
	</center>
<?php
		 	}
	 		else
	 		{
	 			// non acceptable
?>
	<center>
	<br/>
	<br/>
	<br/>
	<?php printf(_('thesaurus:: A cet emplacement du thesaurus , un candidat du champ %s ne peut etre accepte'), "<br/><br/><b>".$cfield."</b><br/><br/>") ?>
	<br/>
	<br/>
	<br/>
	<br/>
	<br/>
	<br/>
	<br/>
	<input style="position:relative; z-index:2; width:100px" type="button" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler'))?>" onclick="self.close();">
<?php
		 	}
		}
	}
}

?>
</body>
</html>
