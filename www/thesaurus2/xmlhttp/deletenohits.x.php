<?php
require_once dirname( __FILE__ ) . "/../../../lib/bootstrap.php";
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
$session = session::getInstance();


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "pid"
					, 'typ'
					, 'id'
					, "piv"
					, "debug"
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
				// let's read all thits
				// $t_thits = array();
				$s_thits = '';
				$sql = "SELECT DISTINCT value FROM thit";
				if($rsbas = $connbas->query($sql))
				{
					while( ($rowbas = $connbas->fetch_assoc($rsbas)) )
					{
						// as key
						// $t_thits[str_replace('d', '.', $rowbas['value'])] = true;
						$s_thits .= (str_replace('d', '.', $rowbas['value']) . ';') ;
					}
					$connbas->free_result($rsbas);
				}
				if($parm['debug'])
					var_dump($s_thits);
					
				if($parm['typ'] == 'CT')
					$sql = "SELECT value AS xml FROM pref WHERE prop='cterms'";
				else
					$sql = "SELECT value AS xml FROM pref WHERE prop='thesaurus'";
					
				if($rsbas = $connbas->query($sql))
				{
					if($rowbas = $connbas->fetch_assoc($rsbas))
					{
						if( ($dom = @DOMDocument::loadXML($rowbas['xml'])) )
						{
							$xpath = new DOMXPath($dom);
							if($parm["id"] == "T")
								$q = "/thesaurus";
							elseif($parm["id"] == "C")
								$q = "/cterms";
							else
								$q = "//te[@id='".$parm["id"]."']";
//							$q = "//te[@id='".$parm["id"]."']";
							if( ($znode = $xpath->query($q)->item(0)) )
							{
								$nodestodel = array();
								$root->setAttribute('n_nohits', (string)(delete_nohits($znode, $s_thits, $nodestodel)) );
								foreach($nodestodel as $n)
									$n->parentNode->removeChild($n);
								
								if($parm['debug'])
									printf("<pre>%s</pre>", $dom->saveXML());
									
								$sql = 'UPDATE pref SET value=\'' . $connbas->escape_string($dom->saveXML()) . '\' WHERE prop="cterms"';
								$connbas->query($sql);
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

function delete_nohits($node, &$s_thits, &$nodestodel)
{
	global $parm;
	$ret = 0;
	if($node->nodeType == XML_ELEMENT_NODE) // && $node->nodeName=='te')
	{
		$id = $node->getAttribute('id') . '.';
		
		if((strpos($s_thits, $id)) === false && !$node->getAttribute('field'))
		{
			// this id has no hits, neither any of his children
			$nodestodel[] = $node;
			$ret = 1;
		}
		else
		{
			// this id (or a child) has hit, must check children
			for($n=$node->firstChild; $n; $n=$n->nextSibling)
			{
				if($n->nodeType==XML_ELEMENT_NODE)
					$ret += delete_nohits($n, $s_thits, $nodestodel);
			}
		}
		if($parm['debug'])
			printf("%s : %d<br/>\n", $id, $ret);
	}
	return($ret);
}
?>