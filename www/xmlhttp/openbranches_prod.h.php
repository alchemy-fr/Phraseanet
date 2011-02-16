<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
$session = session::getInstance();

// sleep(1);


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					'bid'
					, 't'
					, 'mod'
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
				
if(!$parm['mod'])
	$parm['mod'] = 'TREE';
	
header('Content-Type: text/html; charset=UTF-8');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');    // Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');  // always modified
header('Cache-Control: no-store, no-cache, must-revalidate');  // HTTP/1.1
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');                          // HTTP/1.0


$zhtml = '';

if($parm['bid'] !== null)
{	
	$loaded = false;
	
	$dom = databox::get_dom_thesaurus($parm['bid']);
	if($dom)
	{
		$xpath = databox::get_xpath_thesaurus($parm['bid']);//new DOMXPath($dom);
		$q = '/thesaurus';
			
		if($parm['debug'])
			print('q:'.$q.'<br/>\n');
		if( ($znode = $xpath->query($q)->item(0)) )
		{
			$q2 = '//sy';
			if($parm['t'])
			{
				$t = splitTermAndContext($parm['t']);
				$q2 = 'starts-with(@w, \'' . thesaurus::xquery_escape(noaccent_utf8($t[0], PARSED)) . '\')';
				if($t[1])
					$q2 .= ' and starts-with(@k, \'' . thesaurus::xquery_escape(noaccent_utf8($t[1], PARSED)) . '\')';
				$q2 = '//sy[' . $q2 . ']';
			}
			if($parm['debug'])
				print('q2:'.$q2.'<br/>\n');
			$nodes = $xpath->query($q2, $znode);
			if($parm['mod'] == 'TREE')
			{
				for($i=0; $i<$nodes->length; $i++)
				{
					$nodes->item($i)->setAttribute('bold', '1');
					for($n=$nodes->item($i)->parentNode; $n && $n->nodeType==XML_ELEMENT_NODE && $n->nodeName=='te'; $n=$n->parentNode)
					{
						$n->setAttribute('open', '1');
						if($parm['debug'])
							printf('opening node te id=%s<br/>\n', $n->getAttribute('id'));
					}
				}
					
				$zhtml = '';
				getHTML($znode, $zhtml);
			}
			else
			{
				$zhtml = '';
				$bid = $parm['bid'];
				for($i=0; $i<$nodes->length; $i++)
				{
					$n = $nodes->item($i);
					$t = $n->getAttribute('v');
					$tid = $n->getAttribute('id');
					
					$zhtml .= '<p id=\'TH_T.'.$bid.'.'.$tid.'\'>';
					$zhtml .= '<b id=\'TH_W.'.$bid.'.'.$tid.'\'>' .$t. '</b>';
					$zhtml .= '</p>';
				}									
			}
			if($parm['debug'])
				printf('zhtml=%s<br/>\n', $zhtml);
		}
	}
}
if($parm['debug'])
{
	print('<pre>' . htmlentities($zhtml) . '</pre>');
}
else
{
	print($zhtml);
}
	
	
function getHTML($srcnode, &$html)
{
	getHTML2($srcnode, &$html, 0);
}

function getHTML2($srcnode, &$html, $depth)
{
  global $parm;
	// printf('in: depth:%s<br/>\n', $depth);
	$bid = $parm['bid'];
	$tid = $srcnode->getAttribute('id');
	$class = 'h';
	if($depth > 0)
	{
		$nts = 0;
		$allsy = '';
		for($n=$srcnode->firstChild; $n; $n=$n->nextSibling)
		{
			if($n->nodeName=='sy')
			{
				$t = $n->getAttribute('v');
				if($n->getAttribute('bold'))
				{
					// $allsy .= ($allsy?' ; ':'') . '<b><a id=\'TH_W.'.$bid.'.'.$n->getAttribute('id').'\' href=\'javascript:void(0);\'>' . $t. '</a></b>';
					$allsy .= ($allsy?' ; ':'') . '<b id=\'TH_W.'.$bid.'.'.$n->getAttribute('id').'\'>' . $t. '</b>';
				}
				else
				{ 
					//$allsy .= ($allsy?' ; ':'') . '<a id=\'TH_W.'.$bid.'.'.$n->getAttribute('id').'\' href=\'javascript:void(0);\'>' . $t. '</a>';
					$allsy .= ($allsy?' ; ':'') . '<i id=\'TH_W.'.$bid.'.'.$n->getAttribute('id').'\' >' . $t. '</i>';
				}
			}
			elseif($n->nodeName=='te')
			{
				$nts++;
			}
		}
		if($allsy=='')
		{
			//$allsy = '<a id=\'TH_W.'.$bid.'.'.$tid.'\' href=\'javascript:void(0);\'>THESAURUS</a>';
			$allsy = '<i id=\'TH_W.'.$bid.'.'.$tid.'\'>THESAURUS</i>';
		}

		if($nts > 0)
		{
			$html .= '<p id=\'TH_T.'.$bid.'.'.$tid.'\'>';
			$html .= '<u id=\'TH_P.'.$bid.'.'.$tid.'\'>...</u>';
			$html .= $allsy;
			$html .= '</p>';
			$class='h';
		}
		else
		{
			$html .= '<p id=\'TH_T.'.$bid.'.'.$tid.'\'>';
			$html .= '<u class=\'w\'> </u>';
			$html .= $allsy;
			$html .= '</p>';
			$class='c';
		}
		$html .= '<div id=\'TH_K.'.$bid.'.'.$tid.'\' class=\''.$class.'\'>';
	}
	
	for($n=$srcnode->firstChild; $n; $n=$n->nextSibling)
	{
		if($n->nodeName=='te')
		{
			if($n->getAttribute('open'))
			{
				getHTML2($n, $html, $depth+1);

				if($parm['debug'])
					printf('explored node te id=%s<br/>\n', $n->getAttribute('id'));
			}
		}
	}
	
	if($depth > 0)
		$html .= '</div>';
}


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

















