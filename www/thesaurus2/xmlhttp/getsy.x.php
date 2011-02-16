<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "id"
					, "typ"		// "TH" (thesaurus) ou "CT" (cterms)
					, "piv"
					, "debug"
				);
				
if($parm["debug"])
{
	header("Content-Type: text/html; charset=UTF-8");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
	header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");                          // HTTP/1.0
	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 FRAMESET//EN" "http://www.w3.org/TR/REC-html40/strict.dtd">
<META http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<?php
}
else
{
	header("Content-Type: text/xml; charset=UTF-8");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
	header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");                          // HTTP/1.0
}
				
				
				
$ret = new DOMDocument("1.0", "UTF-8");
$ret->standalone = true;
$ret->preserveWhiteSpace = false;
$root = $ret->appendChild($ret->createElement("result"));
$root->appendChild($ret->createCDATASection( var_export($parm, true) ));
if($parm["bid"] !== null)
{				
	$loaded = false;
	$connbas = connection::getInstance($parm['bid']);
	if($connbas)
	{
		if($parm["typ"]=="CT")
			$xqroot = "cterms";
		else
			$xqroot = "thesaurus";

		$sql = "SELECT value AS xml FROM pref WHERE prop='".$xqroot."'";
		
		
		if($rsbas = $connbas->query($sql))
		{
			if($rowbas = $connbas->fetch_assoc($rsbas))
			{
				$xml = trim($rowbas["xml"]);
				
				if(($dom = @DOMDocument::loadXML($xml)))
				{
					$xpath = new DOMXPath($dom);
					if($parm["id"] == "T")
					{
						$q = "/thesaurus";
					}
					elseif($parm["id"] == "C")
					{
						$q = "/cterms";
					}
					else
					{
						$q = "/$xqroot//sy[@id='".$parm["id"]."']";
					}
					if($parm["debug"])
						print("q:".$q."<br/>\n");
						
					$nodes = $xpath->query($q);
					if($nodes->length > 0)
					{
						$t = $nodes->item(0)->getAttribute("v");
						if( ($k = $nodes->item(0)->getAttribute("k")) )
							$t .= " (" . $k . ")";
						
						$fullpath_html = "<span class='path_separator'> / </span><span class='main_term'>" . $t . "</span>";
						$fullpath      = " / " . $t ;

						// $sy->appendChild($ret->importNode($nodes->item(0), false));
						$sy = $root->appendchild($ret->createElement("sy"));
						$sy->setAttribute("t", $t);
						foreach(array("v", "w", "k", "lng", "id") as $a)
						{
							if($nodes->item(0)->hasAttribute($a))
								$sy->setAttribute($a, $nodes->item(0)->getAttribute($a));
						}

						for($depth=-1, $n=$nodes->item(0)->parentNode->parentNode; $n; $n=$n->parentNode, $depth--)
						{
							if($n->nodeName=="te")
							{
								if($parm["debug"])
									printf("parent:%s<br/>\n", $n->nodeName);
								if($parm["typ"]=="CT" && ($fld=$n->getAttribute("field"))!="")
								{
									$fullpath = " / " . $fld . $fullpath;
									if($depth==0)
										$fullpath_html = "<span class='path_separator'> / </span><span class='main_term'>" . $fld . "</span>" . $fullpath_html;
									else
										$fullpath_html = "<span class='path_separator'> / </span>" . $fld . $fullpath_html;
									break;
								}
								$firstsy = $goodsy = null;
								for($n2=$n->firstChild; $n2; $n2=$n2->nextSibling)
								{
									if($n2->nodeName=="sy")
									{
										$t = $n2->getAttribute("v");
										if( ($k = $n2->getAttribute("k")) )
										{
			//								$t .= " (" . $k . ")";
										}

										if(!$firstsy)
											$firstsy = $t;
										if($n2->getAttribute("lng") == $parm["piv"])
										{
											if($parm["debug"])
												printf("fullpath : found '%s' in %s<br/>\n", $sy, $n2->getAttribute("lng"));
											$goodsy = $t;
											break;
										}
									}
								}
								if(!$goodsy)
									$goodsy = $firstsy;
								$fullpath = " / " . $goodsy . $fullpath;
								$fullpath_html = "<span class='path_separator'> / </span>" . $goodsy . $fullpath_html;
							}
						}
						$fp = $root->appendchild($ret->createElement("fullpath"));
						$fp->appendChild($ret->createTextNode($fullpath));
						
						$fp = $root->appendchild($ret->createElement("fullpath_html"));
						$fp->appendChild($ret->createTextNode($fullpath_html));
						
						// $id = "S" . str_replace(".", "d", substr($nodes->item(0)->getAttribute("id"), 1)) . "d";
						$id = str_replace(".", "d", $nodes->item(0)->getAttribute("id")) . "d";
						$hits = "0";
						$sql = "SELECT COUNT(DISTINCT(record_id)) AS hits FROM thit WHERE value='$id'";
						if($parm["debug"])
							printf("sql: %s<br/>\n", $sql);
						if($rsbas2 = $connbas->query($sql))
						{
							if($rowbas2 = $connbas->fetch_assoc($rsbas2))
								$hits = $rowbas2["hits"];
							$connbas->free_result($rsbas2);
						}
						$n = $root->appendchild($ret->createElement("hits"));
						$n->appendChild($ret->createTextNode($hits));
					}
				}
			}
			$connbas->free_result($rsbas);
		}
	}
}
if($parm["debug"])
	print("<pre>" . $ret->saveXML(). "</pre>");
else
	print($ret->saveXML());
?>