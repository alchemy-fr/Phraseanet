<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

set_time_limit(60*60);

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                          // HTTP/1.0

phrasea::headers();

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "piv"
					, "id"
					, "typ"
					, "dlg"
					, "osl"
					, "iln"
					, "ilg"
					, "hit"
					, "smp"
				);

$lng = isset($_SESSION['locale'])?$_SESSION['locale']:GV_default_lng;

if(isset($_SESSION['usr_id']) && isset($_SESSION['ses_id']))
{
	$ses_id = $_SESSION['ses_id'];
	$usr_id = $_SESSION['usr_id'];
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

<html lang="<?php echo $_SESSION['usr_i18n'];?>">
<head>
	<title><?php echo p4string::MakeString(_('thesaurus:: export au format texte'))?></title>
	
	<link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand()?>" />

	<script type="text/javascript">
	function loaded()
	{
		// window.name="EXPORT2";
		self.focus();
	}
	</script>
</head>
<body id="idbody" onload="loaded();" style="background-color:#ffffff" >
<?php
$thits = array();
if($parm["typ"]=="TH" || $parm["typ"]=="CT")
{
	$loaded = false;
	$connbas = connection::getInstance($parm['bid']);
	if($connbas)
	{
		if($parm["typ"]=="TH")
			$sql = "SELECT value AS xml FROM pref WHERE prop='thesaurus'";
		else
			$sql = "SELECT value AS xml FROM pref WHERE prop='cterms'";
		if($rsbas = $connbas->query($sql))
		{
			if($rowbas = $connbas->fetch_assoc($rsbas))
			{
				if( ($domth = @DOMDocument::loadXML($rowbas["xml"])) )
				{
		//			if($parm["id"]=="T" || $parm[$])
		//			$v = str_replace('.', 'd', $parm["id"]) . "d%";
		//			$sql2 = "SELECT value, SUM(1) as hits FROM thit WHERE value LIKE '$v' GROUP BY value";
					$sql2 = "SELECT value, SUM(1) as hits FROM thit GROUP BY value";
//print($sql2 . "\n");
					
					if($rsbas2 = $connbas->query($sql2))
					{
						while($rowbas2 = $connbas->fetch_assoc($rsbas2))
							$thits[str_replace('d', '.', $rowbas2["value"])] = $rowbas2["hits"];
						$connbas->free_result($rsbas2);
					}
				
					$xpathth = new DOMXPath($domth);
					printf("<pre style='font-size: %dpx;'>\n", $parm["smp"]?9:12);
					if($parm["id"]=="T")
						$q = "/thesaurus";
					elseif($parm["id"]=="C")
						$q = "/cterms";
					else
						$q = "//te[@id='" . $parm["id"] . "']";
					export0($xpathth->query($q)->item(0));
					print("</pre>\n");
				}
			}
			$connbas->free_result($rsbas);
		}
	}
}



$tnodes = NULL;

function printTNodes()
{
	global $tnodes;
	global $thits;
	global $parm;
	
	$numlig = ($parm["iln"]=="1");
	$hits = ($parm["hit"]=="1");
	$ilg = ($parm["ilg"]=="1");
	$oneline = ($parm["osl"]=="1");
	
	$ilig=1;
	
	foreach($tnodes as $node)
	{
		$tabs = str_repeat("\t", $node["depth"]);
		switch($node["type"])
		{
			case "ROOT":
				if($numlig)
					print($ilig++ . "\t");
				if($hits && !$oneline)
					print("\t");
				print($tabs . $node["name"] . "\n");
				break;
			case "TRASH":
				if($numlig)
					print($ilig++ . "\t");
				if($hits && !$oneline)
					print("\t");
				print($tabs . "{TRASH}\n");
				break;
			case "FIELD":
				if($numlig)
					print($ilig++ . "\t");
				if($hits && !$oneline)
					print("\t");
				print($tabs . $node["name"] . "\n");
				break;
			case "TERM":
				$isyn = 0;
				if($oneline)
				{
					if($numlig)
						print($ilig++ . "\t");
					print($tabs);
					$isyn = 0;
					foreach($node["syns"] as $syn)
					{
						if($isyn > 0)
							print(" ; ");
						print($syn["v"]);
						if($ilg)
							print(" [" . $syn["lng"] . "]");
						if($hits)
							print(" [" . $syn["hits"] . "]");
						$isyn++;
					}
					print("\n");
				}
				else
				{
					$isyn = 0;
					foreach($node["syns"] as $syn)
					{
						if($numlig)
							print($ilig++ . "\t");
						if($hits)
							print( $syn["hits"] . "\t");
						print($tabs);
						if($isyn > 0)
							print("; ");
						print($syn["v"]);
						if($ilg)
							print(" [" . $syn["lng"] . "]");
						print("\n");
						$isyn++;
					}
				}
				break;
		}
		if(!$oneline)
		{
			if($numlig)
				print($ilig++ . "\t");
			print("\n");
		}
	}
}

function exportNode(&$node, $depth)
{
	global $thits;
	global $tnodes;
	if($node->nodeType == XML_ELEMENT_NODE)
	{
		if(($nname=$node->nodeName)=="thesaurus" || $nname=="cterms")
		{
			$tnodes[] = array("type"=>"ROOT", "depth"=>$depth, "name"=>$nname, "cdate"=>$node->getAttribute("creation_date"), "mdate"=>$node->getAttribute("modification_date") );
		}
		elseif( ($fld = $node->getAttribute("field")) )
		{
			if($node->getAttribute("delbranch"))
				$tnodes[] = array("type"=>"TRASH", "depth"=>$depth, "name"=>$fld );
			else
				$tnodes[] = array("type"=>"FIELD", "depth"=>$depth, "name"=>$fld );
		}
		else
		{
			$tsy = array();
			for($n=$node->firstChild; $n; $n=$n->nextSibling)
			{
				if($n->nodeName=="sy")
				{
					$id = $n->getAttribute("id");
					if(array_key_exists($id.'.', $thits))
						$hits = 0+$thits[$id.'.'];
					else
						$hits = 0;
					
					$tsy[] = array("v"=>$n->getAttribute("v"), "lng"=>$n->getAttribute("lng"), "hits"=>$hits);
				}
			}
			$tnodes[] = array("type"=>"TERM", "depth"=>$depth, "syns"=>$tsy);
		}
	}
}

function export0($znode)
{
	global $tnodes;
	$tnodes = array();
	
	$nodes = array();
	$depth = 0;
	
	for($node=$znode->parentNode; $node; $node=$node->parentNode)
	{
		if($node->nodeType == XML_ELEMENT_NODE)
			$nodes[] = $node;
	}
	$nodes = array_reverse($nodes);

	foreach($nodes as $depth=>$node)
	{
		//	print( exportNode($node, $depth) );
		exportNode($node, $depth);
	}
	
	export($znode, count($nodes));
	
	
	printTNodes();
}

function export($node, $depth=0)
{
	global $tnodes;
	if($node->nodeType == XML_ELEMENT_NODE)
	{
		// print( exportNode($node, $depth) );
		exportNode($node, $depth);
	}
	for($n=$node->firstChild; $n; $n=$n->nextSibling)
	{
		if($n->nodeName=="te")
			export($n, $depth+1);
	}
}

?>
</body>
</html>
