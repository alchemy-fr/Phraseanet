<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                          // HTTP/1.0

phrasea::headers();
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bid"
					, "piv"
					, "id"
					, "typ"
					, "dlg"
					, 'obr'		// liste des branches ouvertes
					, 'ofm'		// 'toscreen' ; 'tofiles'
					, 'srt'		// tri�
					, 'sth'		// recherche 'thesaurus'
					, 'sand'	// full query, with 'and's
					, 'obrf'	// opened br format 
				);
if($parm['ofm']=='toscreen')
{
	//header('Content-type: text/xml');
	//header('Content-Disposition: attachment; filename="topics.xml"');
}

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

if($parm["dlg"])
{
	$opener = "window.dialogArguments.win";
}
else
{
	$opener = "opener";
}

$obr = explode(';', $parm['obr']);

$t_lng = array();	

if($parm['ofm']=='tofiles')
{
	$t = user::avLanguages();
	foreach($t as $lng_code=>$lng)
		$t_lng[] = $lng_code;
}
else
{
	$t_lng[] = $parm['piv'];
}

switch($parm['obrf'])
{
	case 'from_itf_closable':
		$default_display = 'closed';
		$opened_display = 'opened';
		break;
	case 'from_itf_static':
		$default_display = 'closed';
		$opened_display = 'static';
		break;
	case 'all_opened_closable':
		$default_display = 'opened';
		$opened_display = '';
		break;
	case 'all_opened_static':
		$default_display = 'static';
		$opened_display = '';
		break;
	case 'all_closed':
		$default_display = 'closed';
		$opened_display = '';
		break;
}

$now = date('YmdHis');
?>
<html lang="<?php echo $session->usr_i18n;?>">
<head>
	<title><?php echo p4string::MakeString(_('thesaurus:: export en topics'))?></title>
	
	<link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand()?>" />
	
	<style>
	.toscreen
	{
		background-color:#ffffff;
	}
	.tofiles
	{
		margin: 20px;
	}
	</style>

	<script type="text/javascript">
	function loaded()
	{
		// window.name="EXPORT2";
		self.focus();
	}
	</script>
</head>
<body id="idbody" onload="loaded();" class="dialog">
<div class="<?php echo $parm['ofm']?>">
<?php

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
					$xpathth = new DOMXPath($domth);
					if($parm["id"]=="T")
						$q = "/thesaurus";
					elseif($parm["id"]=="C")
						$q = "/cterms";
					else
						$q = "//te[@id='" . $parm["id"] . "']";
						
					if($parm['ofm'] == 'toscreen')
						printf("<pre style='font-size: 12px;'>\n");
					foreach($t_lng as $lng)
					{	
						$dom = new DOMDocument("1.0", "UTF-8");
						$dom->standalone = true;
						$dom->preserveWhiteSpace = false;
						$dom->formatOutput = true;
						$root = $dom->appendChild($dom->createElementNS('www.phraseanet.com', 'phraseanet:topics'));
						
						$root->appendChild($dom->createComment(sprintf(_('thesaurus:: fichier genere le %s'), $now)));
						
						$root->appendChild($dom->createElement('display'))->appendChild($dom->createElement('defaultview'))->appendChild($dom->createTextNode($default_display));
						
						export0($xpathth->query($q)->item(0), $dom, $root, $lng);
						
						if($parm['ofm'] == 'toscreen')
						{
							print(str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $dom->saveXML()));
						}
						elseif($parm['ofm'] == 'tofiles')
						{
							$fname = 'topics_' . $lng . '.xml';
							
							@rename(GV_RootPath.'config/topics/'.$fname, GV_RootPath.'config/topics/topics_'.$lng.'_BKP_'.$now.'.xml');
							
							if($dom->save(GV_RootPath.'config/topics/'.$fname))
								echo p4string::MakeString(sprintf(_('thesaurus:: fichier genere : %s'),$fname));
							else
								echo p4string::MakeString(_('thesaurus:: erreur lors de l\'enregsitrement du fichier'));
							print("<br/><br/>\n");
						}
					}
					if($parm['ofm'] == 'toscreen')
						print("</pre>\n");
				}
			}
			$connbas->free_result($rsbas);
		}
	}
}

if($parm['ofm'] == 'tofiles')
{
?>
	<center>
		<br/>
		<br/>
		<br/>
		<input type="button" value="<?php echo p4string::MakeString(_('boutton::fermer'))?>" onclick="self.close();" style="width:100px;">
	</center>
<?php
}
?>
	</div>
</body>
</html>

<?php

function export0($znode, &$dom, &$root, $lng)
{
	$topics = $root->appendChild($dom->createElement('topics'));	
	export($znode, $dom, $topics, '', $lng, 0);
}

function export($node, &$dom, &$topics, $prevQuery, $lng, $depth=0)
{
	global $parm;
	global $tnodes;
	global $obr;
	global $opened_display;
	$ntopics = 0;
	if($node->nodeType == XML_ELEMENT_NODE)
	{
		$t_node = array();
		$t_sort = array();
		$i = 0;
		for($n=$node->firstChild; $n; $n=$n->nextSibling)
		{
			if($n->nodeName=="te")
			{
				$ntopics++;
				$label0 = $label = "";
				$query0 = $query = "";
				for($n2=$n->firstChild; $n2; $n2=$n2->nextSibling)
				{
					if($n2->nodeName=="sy")
					{
						if(!$query0)
						{
							$query0 = $n2->getAttribute("w");
							if($n2->getAttribute("k"))
								$query0 .= (' (' . $n2->getAttribute("k") . ')');
							$label0 = $n2->getAttribute("v");
						}
						if($n2->getAttribute("lng") == $lng)
						{
							$query = $n2->getAttribute("w");
							if($n2->getAttribute("k"))
								$query .= (' (' . $n2->getAttribute("k") . ')');
							$label = $n2->getAttribute("v");
							break;
						}
					}
				}
				if(!$query)
					$query = $query0;
				if(!$label)
					$label = $label0;
					
				$t_sort[$i] = $query;	// tri sur w
				$t_node[$i] = array('label'=>$label, 'node'=>$n);
				
				$i++;
			}
		}

		if($parm['srt'])
			natcasesort($t_sort);

		foreach($t_sort as $i=>$query)
		{
			$topic = $topics->appendChild($dom->createElement('topic'));
			// $topic->setAttribute('id', $n->getAttribute('id'));
			if($opened_display != '' && in_array($t_node[$i]['node']->getAttribute('id'), $obr))
				$topic->setAttribute('view', $opened_display);
			$topic->appendChild($dom->createElement('label'))->appendChild($dom->createTextNode($t_node[$i]['label']));
			
			$query = '"' . $query . '"';
			if($parm['sth'])
			{
				$query = '*:' . $query;
				if($parm['sand'])
					$query = '(' . $query . ')';
			}
				
			if($parm['sand'] && $prevQuery != '')
				$query = $prevQuery . ' ' . _('phraseanet::technique:: et') . ' ' . $query . '';
			
			$topic->appendChild($dom->createElement('query'))->appendChild($dom->createTextNode('' . $query . ''));

			$topics2 = $dom->createElement('topics');
			
			if(export($t_node[$i]['node'], $dom, $topics2, $query, $lng, $depth+1) > 0)
				$topic->appendChild($topics2);
		}
	}
	return($ntopics);
}

?>
