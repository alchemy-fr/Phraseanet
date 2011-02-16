<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "pid"
					, "t"
					, "k"
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
$ctlist = $root->appendChild($ret->createElement("candidates_list"));

if($parm["bid"] !== null)
{		
	$loaded = false;
	$connbas = connection::getInstance($parm['bid']);
			if($connbas)
			{
				$sql = "SELECT p1.value AS cterms, p2.value AS thesaurus, p3.value AS struct FROM pref p1, pref p2, pref p3 WHERE p1.prop='cterms' AND p2.prop='thesaurus' AND p3.prop='structure'";
				if($rsbas = $connbas->query($sql))
				{
					if($rowbas = $connbas->fetch_assoc($rsbas))
					{
						if(($domstruct = @DOMDocument::loadXML($rowbas["struct"])) && ($domth = @DOMDocument::loadXML($rowbas["thesaurus"])) && ($domct = @DOMDocument::loadXML($rowbas["cterms"])))
						{
							$xpathth = new DOMXPath($domth);
							$xpathct = new DOMXPath($domct);
									
							// on cherche les champs d'o� peut provenir un candidat, en fct de l'endroit o� on veut ins�rer le nouveau terme
							$fields = array();
							$xpathstruct = new DOMXPath($domstruct);
							$nodes = $xpathstruct->query("/record/description/*[@tbranch]");
							for($i=0; $i<$nodes->length; $i++)
							{
								$fieldname = $nodes->item($i)->nodeName;
								$tbranch = $nodes->item($i)->getAttribute("tbranch");
								if($parm["pid"]!="")
									$q = "(" . $tbranch . ")/descendant-or-self::te[@id='" . $parm["pid"] . "']";
								else
									$q = "(" . $tbranch . ")/descendant-or-self::te[not(@id)]";
									
						//		printf("q=%s\n", $q);
						//		$nodes2 = $xpathth->evaluate("count(" . $tbranch . "//te[@id='" . $parm["pid"] . "'])"); // php5.1 !
						
								$fields[$fieldname] = array("name"=>$fieldname, "tbranch"=>$tbranch, "cid"=>null, "sourceok"=>false );
								
								$l = $xpathth->query($q)->length;
								if($parm["debug"])
									printf("field '%s' : %s --: %d nodes<br/>\n", $fieldname, $q, $l);
									
								if($l > 0)
								{
									// le pt d'insertion du nvo terme se trouve dans la tbranch du champ,
									// donc ce champ peut �tre source de candidats
									$fields[$fieldname]["sourceok"] = true;
									/*
									// on cherche le terme dans les candidats, pour ce champ
									$q = "@w='" . thesaurus::xquery_escape(noaccent_utf8($parm["t"], PARSED)) . "'";
									if($parm["k"])
									{
										if($parm["k"]!="*")
											$q .= " and @k='" . thesaurus::xquery_escape(noaccent_utf8($parm["k"], PARSED)) . "'";
									}
									else
									{
										$q .= " and not(@k)";
									}
									$q = "/cterms/te[@field='$fieldname']//te[./sy[$q]]";
	
									$nodes2 = $xpathct->query($q);
									$l = $nodes2->length;
	
									if($parm["debug"])
										printf("q : %s --: %d nodes<br/>\n", $q, $l);
										
									if($l > 0)
									{
										$fields[$fieldname]["sourceok"] = true;
										$fields[$fieldname]["cid"] = $nodes2->item(0)->getAttribute("id");
									}
									*/
								}
								else
								{
									// le pt d'insertion du nvo terme ne se trouve PAS dans la tbranch du champ,
									// donc ce champ ne peut pas �tre source de candidats
								}
							}
							// on consid�re que la source 'deleted' est toujours valide
							$fields["[deleted]"] = array("name"=>_('thesaurus:: corbeille'), "tbranch"=>null, "cid"=>null, "sourceok"=>true );
							
							if(count($fields) > 0)
							{
								// on cherche le terme dans les candidats
								if($domct = @DOMDocument::loadXML($rowbas["cterms"]))
								{
									$xpathct = new DOMXPath($domct);

									$q = "@w='" . thesaurus::xquery_escape(noaccent_utf8($parm["t"], PARSED)) . "'";
							//		if($parm["k"] && $parm["k"]!="*")
									if($parm["k"])
									{
										if($parm["k"]!="*")
											$q .= " and @k='" . thesaurus::xquery_escape(noaccent_utf8($parm["k"], PARSED)) . "'";
									}
									else
									{
										$q .= " and not(@k)";
									}
									$q = "/cterms//te[./sy[$q]]";

									if($parm["debug"])
										printf("xquery : %s<br/>\n", $q);

									// $root->appendChild($ret->createCDATASection( $q ));
									$nodes = $xpathct->query($q);
									// le terme peut �tre pr�sent dans plusieurs candidats
									for($i=0; $i<$nodes->length; $i++)
									{
										// on a trouv� le terme dans les candidats, mais en provenance de quel champ ?.. on remonte au champ candidat
										for($n=$nodes->item($i)->parentNode; $n && $n->parentNode && $n->parentNode->nodeName!="cterms"; $n=$n->parentNode)
											;
										if($parm["debug"])
											printf("proposed in field %s<br/>\n", $n->getAttribute("field"));
										if($n && array_key_exists($f = $n->getAttribute("field"), $fields))
											$fields[$f]["cid"] = $nodes->item($i)->getAttribute("id");
									}
								}
								if($parm["debug"])
									printf("fields:<pre>%s</pre><br/>\n", var_export($fields, true));
							}
								
							foreach($fields as $kfield=>$field)
							{
//								if(!$field["sourceok"] && $field["cid"] === null)
								if($field["cid"] === null)
									continue;
								$ct = $ctlist->appendChild($ret->createElement("ct"));
								$ct->setAttribute("field", $field["name"]);
								$ct->setAttribute("sourceok", $field["sourceok"] ? "1" : "0");
								if($field["cid"] !== null)
									$ct->setAttribute("id", $field["cid"]);
							}
						}
					}
					$connbas->free_result($rsbas);
				}
			}
}
if($parm["debug"])
	print("<pre>" . $ret->saveXML() . "</pre>");
else
	print($ret->saveXML());
?>