<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );



$request = httpRequest::getInstance();
$parm = $request->get_parms(
					'id'
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
				
$tsbas = array();

$ret = array();

	$conn = connection::getInstance();
	$sql = "SELECT * FROM sbas";
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$tsbas['b'.$row['sbas_id']] = array('sbas'=>$row, 'tids'=>array());
		}
		$conn->free_result($rs);
	}

foreach($parm['id'] as $id)
{
	$id = explode('.', $id);
	$sbas = array_shift($id);
	if(array_key_exists('b'.$sbas, $tsbas))
		$tsbas['b'.$sbas]['tids'][] = implode('.', $id);
}

foreach($tsbas as $sbas)
{
	if(count($sbas['tids']) <= 0)
		continue;
		
	$connbas = connection::getInstance($sbas['sbas']['sbas_id']);
	if($connbas)
	{
		$rowbas = null;
		$sql = 'SELECT p1.value AS cterms, p2.value AS thesaurus FROM pref p1, pref p2 WHERE p1.prop="cterms" AND p2.prop="thesaurus"';
		if($rsbas = $connbas->query($sql))
		{
			$rowbas = $connbas->fetch_assoc($rsbas);
			$connbas->free_result($rsbas);
		}
			
		if($rowbas && ($domth = @DOMDocument::loadXML($rowbas['thesaurus'])) && ($domct = @DOMDocument::loadXML($rowbas['cterms'])) )
		{
			$lid = '';
			$tsyid = array();
			$xpathct = new DOMXPath($domct);
			foreach($sbas['tids'] as $tid)
			{
				$xp = '//te[@id="'.$tid.'"]/sy';
				$nodes = $xpathct->query($xp);
				if($nodes->length == 1)
				{
					$sy   = $term = $nodes->item(0);
					$w    = $sy->getAttribute('w');
					$syid = str_replace('.', 'd', $sy->getAttribute('id')) . 'd';
					$lid .= ($lid?',':'') . "'" . $syid . "'";
					$tsyid[$syid] = array('w'=>$w, 'field'=>$sy->parentNode->parentNode->getAttribute('field'));
					
					// remove candidate from cterms
					$te = $sy->parentNode;
					$te->parentNode->removeChild($te);
				}
			}
			// save cterms
			$domct->documentElement->setAttribute('modification_date', date('YmdHis'));
			$sql = 'UPDATE pref SET value=\'' . $connbas->escape_string($domct->saveXML()) . '\', updated_on=NOW() WHERE prop="cterms"';
			$connbas->query($sql);
			
			$sql = 'SELECT t.record_id, r.xml, t.value FROM thit AS t INNER JOIN record AS r USING(record_id) WHERE value IN ('.$lid.') ORDER BY record_id';
			if($rsbas = $connbas->query($sql))
			{
				$t_rid = array();
				while($rowbas = $connbas->fetch_assoc($rsbas))
				{
					$rid = $rowbas['record_id'];
					if(!array_key_exists(''.$rid, $t_rid))
						$t_rid[''.$rid] = array('xml'=>$rowbas['xml'], 'hits'=>array());
					$t_rid[''.$rid]['hits'][] = $rowbas['value'];
				}
// var_dump($t_rid);
				$connbas->free_result($rsbas);
				
				foreach($t_rid as $rid=>$record)
				{
// printf("%s %s \n", __LINE__, $rid);
					$dom = new  DOMDocument();
					$dom->preserveWhiteSpace = false;
					$dom->formatOutput = true;
					if( $dom->loadXML($record['xml']) )
					{
						$nodetodel = array();
						$xp = new DOMXPath($dom);
						foreach($record['hits'] as $value)
						{
							$field = $tsyid[$value];
							$x = '/record/description/'.$field['field'];
//printf("%s %s \n", __LINE__, $x);
							$nodes = $xp->query($x);
							foreach($nodes as $n)
							{
//printf("%s %s \n", __LINE__, $n->nodeName);
								if(noaccent_utf8($n->textContent, PARSED) == $field['w'])
								{
//printf("found %s in record %s \n", noaccent_utf8($n->textContent, PARSED), $rid);
									$nodetodel[] = $n;
								}
							}
						}
//printf("AVANT:\n%s \n", $dom->saveXML());
						foreach($nodetodel as $n)
						{
							$n->parentNode->removeChild($n);
						}
//printf("APRES:\n%s \n", $dom->saveXML());
						
						$sql = 'DELETE FROM idx  WHERE record_id="' . $connbas->escape_string($rid).'"';
						$connbas->query($sql);
						
						$sql = 'DELETE FROM prop WHERE record_id="' . $connbas->escape_string($rid).'"';
						$connbas->query($sql);
						
						$sql = 'DELETE FROM thit WHERE record_id="' . $connbas->escape_string($rid).'"';
						$connbas->query($sql);
						
						$sql = 'UPDATE record SET status=(status & ~3)|4, jeton='.(JETON_WRITE_META_DOC|JETON_WRITE_META_SUBDEF).', xml=\''.$connbas->escape_string($dom->saveXML()).'\' WHERE record_id="' . $connbas->escape_string($rid).'"';
						$connbas->query($sql);
						
					}
				}
			}
		}
	}
}

$ret = $parm['id'];

// todo : respecter les droits d'editing par collections ?

print(p4string::jsonencode($ret));


?>