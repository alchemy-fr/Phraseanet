<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					  'sbid'
					, 'cid'		// candidate (id) to replace
					, 't'		// replacing term
					, 'debug'
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
				
header("Content-Type: text/html; charset=UTF-8");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                          // HTTP/1.0

if($parm['debug'])
	print("<pre>");

$dbname = null;

$result = array('n_recsChanged'=>0); // , 'n_termsDeleted'=>0, 'n_termsReplaced'=>0);
	

$connbas = connection::getInstance($parm['sbid']);

		if($connbas)
		{

			$rowbas = null;
			$sql = 'SELECT p1.value AS cterms, p2.value AS thesaurus, p3.value AS struct FROM pref p1, pref p2, pref p3 WHERE p1.prop="cterms" AND p2.prop="thesaurus" AND p3.prop="structure"';
			if($rsbas = $connbas->query($sql))
			{
				$rowbas = $connbas->fetch_assoc($rsbas);
				$connbas->free_result($rsbas);
			}
				
			if($rowbas && ($domth = @DOMDocument::loadXML($rowbas['thesaurus'])) && ($domct = @DOMDocument::loadXML($rowbas['cterms'])) )
			{
				$xpathct = new DOMXPath($domct);
				
				$field = null;
				$x = null;
				
				$xp = '//te[@id="'.$parm['cid'].'"]/sy';
				$nodes = $xpathct->query($xp);
				if($nodes->length == 1)
				{
					$sy   = $term = $nodes->item(0);
					
					$candidate = array('a'=>$sy->getAttribute('v'), 'u'=>$sy->getAttribute('w'));
					if( ($k = $sy->getAttribute('k')) )
						$candidate['u'] .= ' ('.$k.')';
if($parm['debug'])
	printf("%s : candidate = %s \n", __LINE__, var_export($candidate, true));
						
					$syid = str_replace('.', 'd', $sy->getAttribute('id')) . 'd';
					$field = $sy->parentNode->parentNode->getAttribute('field');
					
					// remove candidate from cterms
					$te = $sy->parentNode;
					$te->parentNode->removeChild($te);
					
					// save cterms
					$domct->documentElement->setAttribute('modification_date', date('YmdHis'));
					$sql = 'UPDATE pref SET value=\'' . $connbas->escape_string($domct->saveXML()) . '\', updated_on=NOW() WHERE prop="cterms"';
					
					if(!$parm['debug'])
						$connbas->query($sql);
					
					$sql = 'SELECT t.record_id, r.xml FROM thit AS t INNER JOIN record AS r USING(record_id) WHERE t.value=\''.$connbas->escape_string($syid).'\' ORDER BY record_id';
if($parm['debug'])
	printf("%s : %s \n", __LINE__, $sql);
					if($rsbas = $connbas->query($sql))
					{
						$t_rid = array();
						while($rowbas = $connbas->fetch_assoc($rsbas))
						{
							$rid = $rowbas['record_id'];
							if(!array_key_exists(''.$rid, $t_rid))
								$t_rid[''.$rid] = $rowbas['xml'];
						}
if($parm['debug'])
	printf("%s : %s \n", __LINE__, var_export($t_rid, true));
						$connbas->free_result($rsbas);
						
						$replacing = array();
						$parm['t'] = explode(';', $parm['t']);
						foreach($parm['t'] as $t)
							$replacing[] = simplified($t);
if($parm['debug'])
	printf("%s : replacing=%s \n", __LINE__, var_export($replacing, true));
						
						
						foreach($t_rid as $rid=>$xml)
						{
if($parm['debug'])
	printf("%s rid=%s \n", __LINE__, $rid);
							$dom = new DOMDocument();
							$dom->preserveWhiteSpace = false;
							$dom->formatOutput = true;
							if( $dom->loadXML($xml) )
							{
if($parm['debug'])
	printf("AVANT:\n%s \n", htmlentities($dom->saveXML()));
		
								// $existed = false;
								$nodetoreplace = null;
								$nodestodelete  = array();
								$xp = new DOMXPath($dom);

								$x = '/record/description/'.$field;
if($parm['debug'])
	printf("%s x=%s \n", __LINE__, $x);
								$nodes = $xp->query($x);
								
								$insertBefore = null;
								if($nodes->length > 0)
								{
									$insertBefore = $nodes->item($nodes->length - 1);
if($parm['debug'])
	printf("%s nodes->length=%s  - insertBefore=%s, nn=%s\n", __LINE__, $nodes->length, var_export($insertBefore, true), $insertBefore->nodeName);
									while( ($insertBefore = $insertBefore->nextSibling) && $insertBefore->nodeType != XML_ELEMENT_NODE)
										;
if($parm['debug'] && $insertBefore)
	printf("%s insertBefore=%s , nn=%s \n", __LINE__, var_export($insertBefore, true), $insertBefore->nodeName);
								
									$t_mval = array();
									foreach($nodes as $n)
									{
										$value = simplified($n->textContent);
										if(in_array($value['a'], $t_mval))		// a chance to delete doubles
											continue;
										for($i=0; $i<9999 && array_key_exists($value['u'].'_'.$i, $t_mval); $i++)
											;
										$t_mval[$value['u'].'_'.$i] = $value['a'];
										$nodestodelete[] = $n;
									}
if($parm['debug'])
	printf("%s : t_mval AVANT = %s \n", __LINE__, var_export($t_mval, true));
								
									if( ($k = array_search($candidate['a'], $t_mval)) !== false)
									{
										unset($t_mval[$k]);
if($parm['debug'])
	printf("%s : after unset %s from t_mval %s \n", __LINE__, $k, var_export($t_mval, true));
										foreach($replacing as $r)
										{
											if(in_array($r['a'], $t_mval))
												continue;
											for($i=0; $i<9999 && array_key_exists($r['u'].'_'.$i, $t_mval); $i++)
												;
											$t_mval[$r['u'].'_'.$i] = $r['a'];
										}
if($parm['debug'])
	printf("%s : after replace to t_mval %s \n", __LINE__, var_export($t_mval, true));
									}
									
									foreach($nodestodelete as $n)
										$n->parentNode->removeChild($n);
									
									ksort($t_mval, SORT_STRING);
									
									if($insertBefore)
									{
										array_reverse($t_mval);
										foreach($t_mval as $t)
											$insertBefore->parentNode->insertBefore($dom->createElement($field), $insertBefore)->appendChild($dom->createTextNode($t));
									}
									else
									{
										$desc = $xp->query('/record/description')->item(0);
										foreach($t_mval as $t)
											$desc->appendChild($dom->createElement($field))->appendChild($dom->createTextNode($t));
									}
								
								
if($parm['debug'])
	printf("%s : t_mval APRES = %s \n", __LINE__, var_export($t_mval, true));
								

if($parm['debug'])
	printf("APRES:\n%s \n", htmlentities($dom->saveXML()));
							
if(!$parm['debug'])
{
									$sql = 'DELETE FROM idx  WHERE record_id="' . $connbas->escape_string($rid).'"';
									$connbas->query($sql);
									
									$sql = 'DELETE FROM prop WHERE record_id="' . $connbas->escape_string($rid).'"';
									$connbas->query($sql);
									
									$sql = 'DELETE FROM thit WHERE record_id="' . $connbas->escape_string($rid).'"';
									$connbas->query($sql);
									
									$sql = 'UPDATE record SET status=(status & ~3)|4, jeton='.(JETON_WRITE_META_DOC|JETON_WRITE_META_SUBDEF).', xml=\''.$connbas->escape_string($dom->saveXML()).'\' WHERE record_id="' . $connbas->escape_string($rid).'"';
									$connbas->query($sql);
}
									$result['n_recsChanged']++;
								}
							}
						}
					}
				}
			}
		}


function simplified($t)
{
	$t = splitTermAndContext($t);
	$su = noaccent_utf8($sa=$t[0], PARSED);
	if($t[1])
	{
		$sa .= ' ('.($t[1]).')';
		$su .= ' ('.noaccent_utf8($t[1], PARSED).')';
	}
	return(array('a'=>$sa, 'u'=>$su));
}


if($parm['debug'])
	var_dump($result);
else
	print(p4string::jsonencode( array('parm'=>$parm, 'result'=>$result)));

if($parm['debug'])
	print("</pre>");

	

function splitTermAndContext($word)
{
	$term = trim($word);
	$context = '';
	if(($po = strpos($term, '(')) !== false)
	{
		if(($pc = strpos($term, ')', $po)) !== false)
		{
			$context = trim(substr($term, $po+1, $pc-$po-1));
			$term = trim(substr($term, 0, $po));
		}
		else
		{
			$context = trim(substr($term, $po+1));
			$term = trim(substr($term, 0, $po));
		}
	}
	return(array($term, $context));
}

?>