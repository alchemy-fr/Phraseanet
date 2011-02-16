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
					, "f2unlk"
					, "fbranch"
					, "reindex"
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
	<title><?php echo p4string::MakeString(_('thesaurus:: Lier la branche de thesaurus'))?></title>
	
	<link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand()?>" />
	
</head>
<body  class="dialog">
<center>
<br/>
<br/>
<br/>
<form onsubmit="return(false);">
	<div style="width:70%; height:200px; overflow:scroll;" class="x3Dbox">
<?php
if($parm["f2unlk"]==NULL)
	$parm["f2unlk"] = array();
if($parm["fbranch"]==NULL)
	$parm["fbranch"] = array();

if($parm["bid"] !== null)
{				
	$loaded = false;
	$connbas = connection::getInstance($parm['bid']);
	if($connbas)
	{
		$stchanged = $ctchanged = false;
		$sql = "SELECT p1.value AS cterms, p2.value AS struct FROM pref p1, pref p2 WHERE p1.prop='cterms' AND p2.prop='structure'";
		if($rsbas = $connbas->query($sql))
		{
			if($rowbas = $connbas->fetch_assoc($rsbas))
			{
				if( ($domct = @DOMDocument::loadXML($rowbas["cterms"])) && ($domst = @DOMDocument::loadXML($rowbas["struct"])))
				{
					$xpathct = new DOMXPath($domct);
					$xpathst = new DOMXPath($domst);

					$candidates2del = array();
					foreach($parm["f2unlk"] as $f2unlk)
					{
						$q = "/cterms/te[@field='".thesaurus::xquery_escape($f2unlk)."']";
						$nodes = $xpathct->query($q);
						// printf("delct : %s<br/>\n", $q);
						for($i=0; $i<$nodes->length; $i++)
						{
							$candidates2del[] = array("field"=>$f2unlk, "node"=>$nodes->item($i));
						}

						$q = "/record/description/" . $f2unlk;
						if( ($field = $xpathst->query($q)->item(0)) )
						{
							echo p4string::MakeString(sprintf(_('thesaurus:: suppression du lien du champ %s'),$field->nodeName));
							print("<br/>\n");
							$field->removeAttribute("tbranch");
							$stchanged = true;	
						}
					}
					foreach($candidates2del as $candidate2del)
					{
						echo p4string::MakeString(sprintf(_('thesaurus:: suppression de la branche de mot candidats pour le champ %s'),$candidate2del["field"]));
						print("<br/>\n");
						$candidate2del["node"]->parentNode->removeChild($candidate2del["node"]);
						$ctchanged = true;
					}
						
					foreach($parm["fbranch"] as $fbranch)
					{
						$p = strpos($fbranch, "<");
						if($p > 1)
						{
							$fieldname = substr($fbranch, 0, $p);
							$tbranch = substr($fbranch, $p+1);
							$q = "/record/description/" . $fieldname;
							if( ($field = $xpathst->query($q)->item(0)) )
							{
								echo p4string::MakeString(sprintf(_('thesaurus:: suppression de la branche de mot candidats pour le champ %s'),$field->nodeName));
								print("<br/>\n");
								$field->setAttribute("tbranch", $tbranch);
								$stchanged = true;
							}
						}
					}
					
					if($ctchanged || $stchanged)
					{
						$sql = array();
						if($stchanged)
						{
							$domst->documentElement->setAttribute("modification_date", $now = date("YmdHis"));
							$sql[] = 'UPDATE pref SET value="'.$connbas->escape_string($domst->saveXML()).'", updated_on="'.$connbas->escape_string($now).'" WHERE prop="structure"';
							echo p4string::MakeString(_('thesaurus:: enregistrement de la structure modifiee'));
							print("<br/>\n");
						}
						if($ctchanged)
						{
							$domct->documentElement->setAttribute("modification_date", $now = date("YmdHis"));
							$sql[] = 'UPDATE pref SET value="'.$connbas->escape_string($domct->saveXML()).'", updated_on="'.$connbas->escape_string($now).'" WHERE prop="cterms"';
							print(p4string::MakeString(_('thesaurus:: enregistrement de la liste modifiee des mots candidats.')));
							print("<br/>\n");
						}
						foreach($sql as $s)
							$connbas->query($s);
							
						if($stchanged)
						{
							$cache_appbox = cache_appbox::getInstance();
							$cache_appbox->delete('list_bases');
							cache_databox::update($parm['bid'],'structure');
						}
					}
				}
			}
			$connbas->free_result($rsbas);
		}
		foreach($parm["f2unlk"] as $f2unlk)
		{
			$sql = "DELETE FROM thit WHERE name='".$connbas->escape_string($f2unlk)."'";

			echo p4string::MakeString(_('thesaurus:: suppression des indexes vers le thesaurus pour le champ'). " <b>".$f2unlk."</b>");
			print("<br/>\n");
			$connbas->query($sql);
		}
		if($parm["reindex"])
		{
			$sql = "UPDATE record SET status=status & ~2";
			echo p4string::MakeString(_('thesaurus:: reindexer tous les enregistrements'));
			print("<br/>\n");
			$connbas->query($sql);
		}
	}
}
				
?>
	</div>
	<br/>
	<input type="button" value="<?php echo p4string::MakeString(_('boutton::fermer'))?>" onclick="self.close();">
</form>
</center>
</body>
</html>
