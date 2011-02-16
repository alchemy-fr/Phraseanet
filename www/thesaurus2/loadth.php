<?php
require_once dirname(__FILE__) . '/../../lib/bootstrap.php';
phrasea::headers();
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );

$session = session::getInstance();
$debug = false;

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid",
					"piv",
					"repair"
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

function fixW(&$node, $depth=0)
{
	if($node && $node->nodeType==XML_ELEMENT_NODE)
	{
		if(($v = $node->getAttribute("v")) != "")
			$node->setAttribute("w", noaccent_utf8($v, PARSED));
		for($c=$node->firstChild; $c; $c=$c->nextSibling)
			fixW($c, $depth+1);
	}
}

if($hdir = opendir(GV_RootPath."www/thesaurus2/patch"))
{
	while(false !== ($file = readdir($hdir)))
	{
		if(substr($file,0,1)==".")
			continue;
		if(is_file($f = GV_RootPath."www/thesaurus2/patch/" . $file))
		{
			require_once($f);
			print("<!-- patch '$f' included -->\n");
		}
	}
	closedir($hdir);
}

function fixThesaurus(&$domct, &$domth)
{
  global $connbas;
	$oldversion = $version = $domth->documentElement->getAttribute("version");
//	$cls = "patch_th_".str_replace(".","_",$version);
	
//printf("---- %s %s %s \n", $version, $cls, class_exists($cls) );
//printf("---- %s %s \n", $version, $cls );
	while(class_exists($cls = "patch_th_".str_replace(".","_",$version),false))
	{
		print("// ==============  patching from version='$version'\n");
			
		$last_version = $version;
		$zcls = new $cls;
		print("// ----------- calling class '$cls'\n");
		$version = $zcls->patch($version, $domct, $domth, $connbas);
		print("// ----------- method 'patch' -> returned '$version'\n");
		
		if($version == $last_version)
			break;
	}
	return($version);
}

				
?>
<script language="javascript">
<?php
$th = $ct = $name = "";
$found = false;
if($parm["bid"] !== null)
{				
	$conn = connection::getInstance();
	$name = phrasea::sbas_names($parm['bid']);	
	$loaded = false;
	$connbas = connection::getInstance($parm['bid']);
	if($connbas)
	{
		$sql = "SELECT p1.value AS cterms, p2.value AS thesaurus FROM pref p1, pref p2 WHERE p1.prop='cterms' AND p2.prop='thesaurus'";
		if($rsbas = $connbas->query($sql))
		{
			if($rowbas = $connbas->fetch_assoc($rsbas))
			{
				$th = trim($rowbas["thesaurus"]);
				
				$th = str_replace("v=\"\"", "v=\"!vide/empty!\"", $th);
				
				$ct = trim($rowbas["cterms"]);
				?>
parent.currentBaseId   = <?php echo $parm["bid"]?>;
parent.currentBaseName = "<?php echo p4string::MakeString($name, "js")?>";
parent.document.title = "<?php echo p4string::MakeString(_('phraseanet:: thesaurus'), "js") ?>";
parent.document.getElementById("baseName").innerHTML = "<?php echo p4string::MakeString(_('phraseanet:: thesaurus'), "js") /* thesaurus de la base xxx */?>";
parent.thesaurusChanged = false;
<?php
				
				if($ct===null || trim($ct)=="" || (!@DOMDocument::loadXML($ct) && $parm['repair']=='on'))
				{
					$domct = DOMDocument::load("./blank_cterms.xml");
					$domct->documentElement->setAttribute("creation_date", $now = date("YmdHis"));
					$domct->documentElement->setAttribute("modification_date", $now);
					$sql = "UPDATE pref SET value='" . $connbas->escape_string($ct = $domct->saveXML()) . "'";
					$sql .= ", updated_on='"    . $domct->documentElement->getAttribute("modification_date") . "'";
					$sql .= " WHERE prop='cterms'";
					$connbas->query($sql);
				}
				// if(!($domth = @DOMDocument::loadXML($th)))
				if($th===null || trim($th)=="" || (!@DOMDocument::loadXML($th) && $parm['repair']=='on'))
				{
					$domth = DOMDocument::load("./blank_thesaurus.xml");
					$domth->documentElement->setAttribute("creation_date", $now = date("YmdHis"));
					$domth->documentElement->setAttribute("modification_date", $now);
					$sql = "UPDATE pref SET value='" . $connbas->escape_string($th = $domth->saveXML()) . "'";
					$sql .= ", updated_on='"    . $domth->documentElement->getAttribute("modification_date") . "'";
					$sql .= " WHERE prop='thesaurus'";
					if($debug)
						print("// SQL:$sql\n");
					else
						$connbas->query($sql);
						
					$cache_abox = cache_appbox::getInstance();
					$cache_abox->delete('thesaurus_'.$parm['bid']);
				}
				
				if(($domct = @DOMDocument::loadXML($ct)) && ($domth = @DOMDocument::loadXML($th)))
				{
					
					$oldversion = $domth->documentElement->getAttribute("version");
					if( ($version = fixThesaurus($domct, $domth)) != $oldversion)
					{
						print("alert('" . utf8_encode("le thesaurus a �t� converti en version $version") . "');\n");
						$sql1 = "UPDATE pref SET prop='".$connbas->escape_string($domth->saveXML())."', updated_on='".$domth->documentElement->getAttribute("modification_date")."' WHERE prop='thesaurus'";
						$sql2 = "UPDATE pref SET prop='".$connbas->escape_string($domct->saveXML())."', updated_on='".$domct->documentElement->getAttribute("modification_date")."' WHERE prop='cterms'";
						if($debug)
							print("// SQL:$sql1\n");
						else
							$connbas->query($sql1);
						if($debug)
							print("// SQL:$sql2\n");
						else
							$connbas->query($sql2);
							
						$cache_abox = cache_appbox::getInstance();
						$cache_abox->delete('thesaurus_'.$parm['bid']);
					}
					
					$xpathct = new DOMXPath($domct);
					// on cherche la branche 'deleted' dans les cterms
					$nodes = $xpathct->query("/cterms/te[@delbranch='1']");
					if($nodes && ($nodes->length > 0))
					{
						// on change le nom � la vol�e
						$nodes->item(0)->setAttribute("field", _('thesaurus:: corbeille'));
					}

					print("parent.document.getElementById(\"T0\").innerHTML='");
					print(str_replace(array("'", "\n", "\r"), array("\\'", "", ""), $html = cttohtml($domct, $name) ));
					print("';\n");

					print("parent.document.getElementById(\"T1\").innerHTML='");
					print(str_replace(array("'", "\n", "\r"), array("\\'", "", ""), $html = thtohtml($domth, "THE", $name) ));
					print("';\n");
				}
				else
				{
?>
if(confirm("Thesaurus ou CTerms invalide\n effacer (OK) ou quitter (Annuler) ?"))
{
parent.document.forms['fBase'].repair.value = "on";
parent.document.forms['fBase'].submit();
}
else
{
parent.window.close();
}
<?php
//								die();
				}

			}
			else
			{
				// print("alert('pas de th2');");
			}
			$connbas->free_result($rsbas);
		}
		else
		{
			// print("alert('pas de th3');");
		}
	}
}

function cttohtml($ctdom, $name)
{
	$html = "<DIV class='glossaire' id='CTERMS'>";
//	$html .= "	<div id='TCE_C' class='s_' style='display:none'><u id='THP_C'>-</u>STOCK</div>\n";
	$html .= "	<div id='TCE_C' class='s_' style='font-weight:900'><u id='THP_C'>-</u>".($name)."</div>\n";
//	$html .= "	<div id='THB_C' class='ctroot'>\n";
	$html .= "	<div id='THB_C' class='OB'>\n";
	for($ct = $ctdom->documentElement->firstChild; $ct; $ct=$ct->nextSibling)
	{
		if($ct->nodeName=="te")
		{
			$id = $ct->getAttribute("id");
			$t  = $ct->getAttribute("field");
			$html .= "		<div id='TCE_$id' class='s_'><u id='THP_$id'>+</u>".($t)."</div>\n";
			$html .= "		<div id='THB_$id' class='ob'>\n";
			$html .= "		</div>\n";
		}
	}
	$html .= "	</div>";
	$html .= "</DIV>";
	return($html);
}

function thtohtml($thdom, $typ, $name)
{
	$html =  "<DIV class='glossaire'>\n";
	$html .= "	<div id='".$typ."_T' class='s_' style='font-weight:900'><u id='THP_T'>+</u>".($name)."</div>\n";
	$html .= "	<div id='THB_T' class='ob'>\n";
	for($n=$thdom->documentElement->firstChild; $n; $n=$n->nextSibling)
	{
//		if($n->nodeName=="te")
//			tetohtml($n, $html);
	}
	$html .= "	</div>\n";
	$html .= "</DIV>";
	return($html);
}

function tetohtml($tenode, &$html, $depth=0)
{
	$tab = str_repeat("\t", $depth);
	$id = $tenode->getAttribute("id");
	$nextid = $tenode->getAttribute("nextid");
	$t = "";
	for($n=$tenode->firstChild; $n; $n=$n->nextSibling)
	{
		if($n->nodeName=="sy")
			$t .=  $t?" ; ":"" . $n->getAttribute("v");
	}
//	if($t=="")
//		$t = $depth==0 ? "THESAURUS" : "!vide/empty!";
	$t = str_replace(array("&", "<", ">", "\""), array("&amp;", "&lt;", "&gt;", "&quot;"), $t);
	$html .= "$tab<div id='THE_$id' class='s_'><u id='THP_$id'>+</u>" . $t . "</div>\n";
	$html .= "$tab<div id='THB_$id' class='ob'>\n";
	
	if(0 && $depth < 2)
	{
		for($n=$tenode->firstChild; $n; $n=$n->nextSibling)
		{
			if($n->nodeName=="te")
				tetohtml($n, $html, $depth+1);
		}
	}
	$html .= "$tab</div>\n";
}

?>
</script>
