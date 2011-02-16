<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
$session = session::getInstance();


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"sbid"
					, "piv"		// pivot language
					, "cid"	// candidates
					, "tid"		// where to accept terms
					, "typ"		// "TS"=creer nouvo terme spec. ou "SY" creer simplement synonyme 
					, "debug"
				);


if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	if(!($ph_session = phrasea_open_session((int)$ses_id, $usr_id)))
	{
		header("Location: /login/?err=no-session");
		exit();
	}
}
else
{
	header("Location: /login/");
	exit();
}
				
$ret = array('refresh'=>array());
$refresh = array();
				
$sbas_id = (int)$parm["sbid"];

if($sbas_id > 0)
{			
	$loaded = false;
	
	$connbas = connection::getInstance($sbas_id);
	
	if($connbas)
	{
		$sql = "SELECT p1.value AS cterms, p2.value AS thesaurus FROM pref p1, pref p2 WHERE p1.prop='cterms' AND p2.prop='thesaurus'";
		if($rsbas = $connbas->query($sql))
		{
			if($rowbas = $connbas->fetch_assoc($rsbas))
			{
				if(($domct = @DOMDocument::loadXML($rowbas["cterms"])) && ($domth = @DOMDocument::loadXML($rowbas["thesaurus"])))
				{
					$xpathth = new DOMXPath($domth);
					if($parm["tid"] == "T")
						$q = "/thesaurus";
					else
						$q = "/thesaurus//te[@id='".$parm["tid"]."']";
					if($parm["debug"])
						printf("qth: %s<br/>\n", $q);
					$parentnode = $xpathth->query($q)->item(0);
					if($parentnode)
					{
						$xpathct = new DOMXPath($domct);
						$ctchanged = $thchanged = false;
						
						foreach($parm["cid"] as $cid)
						{
							$q = "//te[@id='".$cid."']";
							if($parm["debug"])
								printf("qct: %s<br/>\n", $q);
							$ct = $xpathct->query($q)->item(0);
							if($ct)
							{
								if($parm["typ"] == "TS")
								{
									// importer tt la branche candidate comme nouveau ts
									$nid = $parentnode->getAttribute("nextid");
									$parentnode->setAttribute("nextid", (int)$nid + 1);

									$oldid = $ct->getAttribute("id");
									$te = $domth->importNode($ct, true);
									$chgids = array();
									if( ($pid=$parentnode->getAttribute("id")) == "" )
										$pid  = "T".$nid;	// racine
									else
										$pid .= ".".$nid;
										
									renum($te, $pid, $chgids);
									$te = $parentnode->appendChild($te);
									
									if($parm["debug"])
										printf("newid=%s<br/>\n", $te->getAttribute("id"));
									
									$soldid = str_replace(".", "d", $oldid)."d";
									$snewid = str_replace(".", "d", $pid)."d";
									$l = strlen($soldid)+1;
									$sql = "UPDATE thit SET value=CONCAT('$snewid', SUBSTRING(value FROM $l)) WHERE value LIKE '$soldid%'";
									if($parm["debug"])
									{
										printf("soldid=%s ; snewid=%s<br/>\nsql=%s<br/>\n", $soldid, $snewid, $sql);
									}
									else 
									{
										$connbas->query($sql);
									}
										
									$refreshid = $parentnode->getAttribute('id');
									$refresh['T'.$refreshid] = array('type'=>'T', 'sbid'=>$sbas_id, 'id'=>$refreshid);
									$thchanged = true;

									$refreshid = $ct->parentNode->getAttribute("id");
									$refresh['C'.$refreshid] = array('type'=>'C', 'sbid'=>$sbas_id, 'id'=>$refreshid);
									
									$ct->parentNode->removeChild($ct);

									$ctchanged = true;
								}
								elseif ($parm["typ"] == "SY")
								{
									// importer tt le contenu de la branche sous la destination
									for($ct2=$ct->firstChild; $ct2; $ct2=$ct2->nextSibling)
									{
										if($ct2->nodeType != XML_ELEMENT_NODE || $ct2->nodeName != 'sy')
											continue;
if($parm['debug'])
printf("ct2:%s \n", var_export($ct2, true));
										$nid = $parentnode->getAttribute("nextid");
										$parentnode->setAttribute("nextid", (int)$nid + 1);
	
										$oldid = $ct2->getAttribute("id");
										$te = $domth->importNode($ct2, true);
										$chgids = array();
										if( ($pid=$parentnode->getAttribute("id")) == "" )
											$pid  = "T".$nid;	// racine
										else
											$pid .= ".".$nid;
											
										renum($te, $pid, $chgids);
										$te = $parentnode->appendChild($te);
										
										if($parm["debug"])
											printf("newid=%s<br/>\n", $te->getAttribute("id"));
										
										$soldid = str_replace(".", "d", $oldid)."d";
										$snewid = str_replace(".", "d", $pid)."d";
										$l = strlen($soldid)+1;
										$sql = "UPDATE thit SET value=CONCAT('$snewid', SUBSTRING(value FROM $l)) WHERE value LIKE '$soldid%'";
										if($parm["debug"])
										{
											printf("soldid=%s ; snewid=%s<br/>\nsql=%s<br/>\n", $soldid, $snewid, $sql);
										}
										else 
										{
											$connbas->query($sql);
										}
											
										$thchanged = true;
									}
									
									$refreshid = $parentnode->parentNode->getAttribute("id");
									$refresh['T'.$refreshid] = array('type'=>'T', 'sbid'=>$sbas_id, 'id'=>$refreshid);
									
									$refreshid = $ct->parentNode->getAttribute("id");
									$refresh['C'.$refreshid] = array('type'=>'C', 'sbid'=>$sbas_id, 'id'=>$refreshid);

									$ct->parentNode->removeChild($ct);
									$ctchanged = true;
								}
							}
						}
						$sql = array();
						if($ctchanged)
						{
							$domct->documentElement->setAttribute("modification_date", $now = date("YmdHis"));
							$sql[] = "UPDATE pref SET value='".$connbas->escape_string($domct->saveXML())."', updated_on = '".$now."' WHERE prop='cterms'";
						}
						if($thchanged)
						{
							$domth->documentElement->setAttribute("modification_date", $now = date("YmdHis"));
							$sql[] = "UPDATE pref SET value='".$connbas->escape_string($domth->saveXML())."', updated_on = '".$now."' WHERE prop='thesaurus'";
							
							
						}
						foreach($sql as $s)
						{				
							if($parm["debug"])
							{
								printf("sql : %s<br/>\n", $s);
							}
							else
							{
								$connbas->query($s);
							}
						}
						if($thchanged)
						{
							$cache_abox = cache_appbox::getInstance();
							$cache_abox->delete('thesaurus_'.$sbas_id);
						}
						// print("<pre>" . htmlentities($domth->saveXML()) . "</pre>");
						// print("<pre>" . htmlentities($domct->saveXML()) . "</pre>");
					}
				}
			}
			$connbas->free_result($rsbas);
		}
	}
}

foreach($refresh as $r)
	$ret['refresh'][] = $r;
	
if($parm["debug"])
	print("<pre>" . p4string::jsonencode($ret) . "</pre>");
else
	print(p4string::jsonencode($ret));

function renum($node, $id, &$chgids, $depth=0)
{
  global $parm;
	if($parm["debug"])
		printf("renum('%s' -> '%s')<br/>\n", $node->getAttribute("id"), $id);
	$node->setAttribute("id", $id);
	if($node->nodeType==XML_ELEMENT_NODE && $node->nodeName=="sy")
		$node->setAttribute("lng", $parm['piv']);

	$nchild = 0;
	for($n=$node->firstChild; $n; $n=$n->nextSibling)
	{
		if($n->nodeType==XML_ELEMENT_NODE && ($n->nodeName=="te" || $n->nodeName=="sy"))
		{
			renum($n, $id.".".$nchild, $chgids, $depth+1);
			$nchild++;
		}
	}
	$node->setAttribute("nextid", $nchild);
}
	

?>
