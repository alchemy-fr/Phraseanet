<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "id"
					, "typ"		// "TH" (thesaurus) ou "CT" (cterms)
					, "piv"
					, "sortsy"	// trier la liste des sy (="1") ou pas
					, "sel"		// selectionner ce synonyme
					, "nots"	// ne pas lister les ts
					, "acf"		// si TH, v�rifier si on accepte les candidats en provenance de ce champ
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
$cfield =  $root->appendChild($ret->createElement("cfield"));
$ts_list = $root->appendChild($ret->createElement("ts_list"));
$sy_list = $root->appendChild($ret->createElement("sy_list"));
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
		
		$sql = 'SELECT p1.value AS struct, p2.value AS xml
 FROM pref p1, pref p2 WHERE p1.prop=\'structure\' AND p2.prop=\''.$xqroot.'\'';
		
		if($rsbas = $connbas->query($sql))
		{
			if($rowbas = $connbas->fetch_assoc($rsbas))
			{
				$xml = trim($rowbas["xml"]);
				
				if(($dom = @DOMDocument::loadXML($xml)))
				{
					$xpath = new DOMXPath($dom);
					if($parm["typ"]=="TH" && $parm["acf"])
					{
						$cfield->setAttribute("field", $parm["acf"]);
						
						// on doit v�rifier si le terme demand� est accessible � partir de ce champ acf
						if($parm["acf"] == '*')
						{
							// le champ "*" est la corbeille, il est toujours accept�
							$cfield->setAttribute("acceptable", "1");
						}
						else
						{
							// le champ est test� d'apr�s son tbranch
							$sxstruct = simplexml_load_string($rowbas["struct"]);
							if($sxstruct && ($sxfld = $sxstruct->description->{$parm["acf"]}) && ($tbranch = $sxfld["tbranch"]))
							{
				//				$q = "(".$tbranch.")//te[@id='".$parm["id"]."']";
								$q = "(".$tbranch.")/descendant-or-self::te[@id='".$parm["id"]."']";
								
								if($parm["debug"])
									printf("tbranch-q = \" $q \" <br/>\n");
									
								$nodes = $xpath->query($q);
								$cfield->setAttribute("acceptable", ($nodes->length > 0) ? "1" : "0");
							}
						}
	/*				
	*/				
					}
					
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
						$q = "/$xqroot//te[@id='".$parm["id"]."']";
					}
					if($parm["debug"])
						print("q:".$q."<br/>\n");
						
					$nodes = $xpath->query($q);
					$root->setAttribute('found', ''.$nodes->length);
					if($nodes->length > 0)
					{
						$nts = 0;
						$tts = array();
						// on dresse la liste des termes sp�cifiques avec comme cl� le synonyme dans la langue pivot
						for($n=$nodes->item(0)->firstChild; $n; $n=$n->nextSibling)
						{
							if($n->nodeName=="te")
							{
								$nts++;
								if(!$parm["nots"])
								{
									if($parm["typ"]=="CT" && $parm["id"]=="C")
									{
										$realksy = $allsy = $n->getAttribute("field");
									}
									else
									{
										$allsy = "";
										$firstksy = null;
										$ksy = $realksy = null;
										// on liste les sy pour fabriquer la cl�
										for($n2=$n->firstChild; $n2; $n2=$n2->nextSibling)
										{
											if($n2->nodeName=="sy")
											{
												$lng = $n2->getAttribute("lng");
												$t = $n2->getAttribute("v");
												$ksy = $n2->getAttribute("w");
												if($k = $n2->getAttribute("k"))
												{
									//				$t .= " ($k)";
									//				$ksy .= " ($k)";
												}
												if(!$firstksy)
													$firstksy = $ksy;
												if(!$realksy && $parm["piv"] && $lng==$parm["piv"])
												{
													$realksy = $ksy;
													// $allsy = "<b>" . $t . "</b>" . ($allsy ? " ; ":"") . $allsy;
													$allsy = $t . ($allsy ? " ; ":"") . $allsy;
												}
												else
												{
													$allsy .= ($allsy?" ; ":"") . $t;
												}
											}
										}
										if(!$realksy)
											$realksy = $firstksy;
									}
									if($parm["sortsy"] && $parm["piv"])
									{
										for($uniq=0; $uniq<9999; $uniq++)
										{
											if(!isset($tts[$realksy . "_" . $uniq]))
												break;
										}
										$tts[$realksy . "_" . $uniq] = array("id"=>$n->getAttribute("id"), "allsy"=>$allsy, "nchild"=>$xpath->query("te", $n)->length);
									}
									else
									{
										$tts[] = array("id"=>$n->getAttribute("id"), "allsy"=>$allsy, "nchild"=>$xpath->query("te", $n)->length);
									}
								}
							}
							
							elseif($n->nodeName=="sy")
							{
								$id = str_replace(".", "d", $n->getAttribute("id")) . "d";
								$hits = "";
								$sql = "SELECT COUNT(DISTINCT(record_id)) AS hits FROM thit WHERE value='$id'";
								if($parm["debug"])
									printf("sql: %s<br/>\n", $sql);
								if($rsbas2 = $connbas->query($sql))
								{
									if($rowbas2 = $connbas->fetch_assoc($rsbas2))
										$hits = $rowbas2["hits"];
									$connbas->free_result($rsbas2);
								}
								$sy = $sy_list->appendChild($ret->createElement("sy"));

								$sy->setAttribute("id", $n->getAttribute("id"));
								$sy->setAttribute("v",  $t = $n->getAttribute("v"));
								$sy->setAttribute("w",  $n->getAttribute("w"));
								$sy->setAttribute("hits", $hits);
								$sy->setAttribute("lng", $lng = $n->getAttribute("lng"));
								if( ($k = $n->getAttribute("k")) )
								{
									$sy->setAttribute("k", $k);
					//				$t .= " (" . $k . ")";
								}
								$sy->setAttribute("t", $t);
								if($n->getAttribute("id") == $parm["sel"])
									$sy->setAttribute("sel", "1");
							}
						}
						$ts_list->setAttribute("nts", $nts);
						
						if($parm["sortsy"] && $parm["piv"])
							ksort($tts, SORT_STRING);
						if($parm["debug"])
							printf("tts : <pre>%s</pre><br/>\n", var_export($tts, true));
						foreach($tts as $ts)
						{
							$newts = $ts_list->appendChild($ret->createElement("ts"));
							$newts->setAttribute("id", $ts["id"]);
							$newts->setAttribute("nts", $ts["nchild"]);
							$newts->appendChild($ret->createTextNode($ts["allsy"]));
						}
							
								
						$fullpath_html = $fullpath = "";
						for($depth=0, $n=$nodes->item(0); $n; $n=$n->parentNode, $depth--)
						{
							if($n->nodeName=="te")
							{
								if($parm["debug"])
									printf("parent:%s<br/>\n", $n->nodeName);
								if($parm["typ"]=="CT" && ($fld=$n->getAttribute("field"))!="")
								{
									// la source provient des candidats pour ce champ
									if($parm["debug"])
										printf("field:%s<br/>\n", $fld);
										
									$cfield->setAttribute("field", $fld);
									$cfield->setAttribute("delbranch", $n->getAttribute("delbranch"));
									
			//						// on en profite pour retourner des infos sur ce champ
			//						$sxstruct = simplexml_load_string($rowbas["struct"]);
			//						if( ($sxfld = $sxstruct->description->$fld) )
			//							$cfield->setAttribute("tbranch", $sxfld["tbranch"]);

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
										$sy = $n2->getAttribute("v");
										if(!$firstsy)
										{
											$firstsy = $sy;
											if($parm["debug"])
												printf("fullpath : firstsy='%s' in %s<br/>\n", $firstsy, $n2->getAttribute("lng"));
										}
										if($n2->getAttribute("lng") == $parm["piv"])
										{
											if($parm["debug"])
												printf("fullpath : found '%s' in %s<br/>\n", $sy, $n2->getAttribute("lng"));
											$goodsy = $sy;
											break;
										}
									}
								}
								if(!$goodsy)
									$goodsy = $firstsy;
								$fullpath = " / " . $goodsy . $fullpath;
								if($depth==0)
									$fullpath_html = "<span class='path_separator'> / </span><span class='main_term'>" . $goodsy . "</span>" . $fullpath_html;
								else
									$fullpath_html = "<span class='path_separator'> / </span>" . $goodsy . $fullpath_html;
							}
						}
						if($fullpath == "")
						{
							$fullpath = "/";
							$fullpath_html = "<span class='path_separator'> / </span>";
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

						$hits = "0";
						$sql = "SELECT COUNT(DISTINCT(record_id)) AS hits FROM thit WHERE value LIKE '$id%'";
						if($parm["debug"])
							printf("sql: %s<br/>\n", $sql);
						if($rsbas2 = $connbas->query($sql))
						{
							if($rowbas2 = $connbas->fetch_assoc($rsbas2))
								$hits = $rowbas2["hits"];
							$connbas->free_result($rsbas2);
						}
						$n = $root->appendchild($ret->createElement("allhits"));
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