<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$dom = new DOMDocument('1.0', 'UTF-8');
$defaultdom = new DOMDocument('1.0', 'UTF-8');

$dom->formatOutput = true;
$defaultdom->formatOutput = true;

$root = $dom->appendChild($dom->createElement('rss'));
$defaultroot = $defaultdom->appendChild($defaultdom->createElement('rss'));

$root->setAttribute('version', '2.0');
$defaultroot->setAttribute('version', '2.0');

$root->setAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
$defaultroot->setAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');

$root->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
$defaultroot->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');

$channel = $root->appendChild($dom->createElement('channel'));
$defaultchannel = $defaultroot->appendChild($defaultdom->createElement('channel'));

$item = $defaultchannel->appendChild($defaultdom->createElement('item'));
$title = $item->appendChild($defaultdom->createElement('title'));
$title->appendChild($defaultdom->createTextNode(GV_homeTitle));

if(file_exists(GV_RootPath.'www/custom/home.jpg'))
{
	$pathPic =  GV_RootPath.'www/custom/home.jpg';
	$urlPic =  '/custom/home.jpg';
}
else
{
	$pathPic =  GV_RootPath.'www/login/img/home.jpg';	
	$urlPic =  '/login/img/home.jpg';
}

$sizes = getimagesize($pathPic);
	
$t = $item->appendChild($defaultdom->createElement('media:thumbnail'));
$t->setAttribute('url', $urlPic);
$t->setAttribute('width', $sizes[0]);
$t->setAttribute('height', $sizes[1]);

$t = $item->appendChild($defaultdom->createElement('media:content'));
$t->setAttribute('url', $urlPic);
$t->setAttribute('width', $sizes[0]);
$t->setAttribute('height', $sizes[1]);

$output = $defaultdom->saveXml();
			
$conn = connection::getInstance();

if(!$conn)
{
	exit($output);
}

$files =array();
$sitepreffxml = "";	

$sql = "SELECT preffs FROM sitepreff WHERE id=1";

if($rs = $conn->query($sql))
	if($row = $conn->fetch_assoc($rs))
		$sitepreffxml = $row["preffs"];


$ses = phrasea_create_session(0);
$sql = "SELECT s.ssel_id" .
		" FROM ssel s" .
		" WHERE (s.homelink = 1 " .
		" AND s.temporaryType=0" .
		" AND (SELECT COUNT(sselcont_id) FROM sselcont c where c.ssel_id=s.ssel_id)>0) " .
		" ORDER BY RAND() LIMIT 1";	

if($rs = $conn->query($sql))
{
	if($conn->num_rows($rs)>0)
	{
		$ssel_id = $conn->fetch_assoc($rs);
		$ssel_id = $ssel_id["ssel_id"];
		
//		$limit = '';
//		if($ret['thumbLimit'] == 4)
//			$limit = ' LIMIT 4';
			
		$sql = "SELECT s.name,s.descript,c.base_id,c.record_id,c.sselcont_id" .
				" FROM ssel s, sselcont c" .
				" WHERE s.ssel_id = '".$conn->escape_string($ssel_id)."'" .
				" AND s.ssel_id = c.ssel_id" .
				" ORDER BY RAND() ";	
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$item = $channel->appendChild($dom->createElement('item'));
				$title = $item->appendChild($dom->createElement('title'));
				$title->appendChild($dom->createTextNode(GV_homeTitle));
				
				$sd = phrasea_subdefs( $ses , $row["base_id"], $row["record_id"] );
				if(isset($sd['document']) && isset($sd['preview']) && isset($sd['thumbnail']) && isset($sd['document']['type']) && $sd['document']['type'] == 'image')
				{
					$t = $item->appendChild($dom->createElement('media:thumbnail'));
					$t->setAttribute('url', $sd['thumbnail']['baseurl'].$sd['thumbnail']['file']);
					$t->setAttribute('width', ((int)$sd["thumbnail"]["width"]));
					$t->setAttribute('height', ((int)$sd["thumbnail"]["height"]));
					
					$t = $item->appendChild($dom->createElement('media:content'));
					
					$url = GV_ServerName."document/".$row['base_id']."/".$row['record_id']."/".$sd['document']['sha256']."/";
					$t->setAttribute('url', $url);
					$t->setAttribute('width', ((int)$sd["preview"]["width"]));
					$t->setAttribute('height', ((int)$sd["preview"]["height"]));
				}
				
			}
			$output = $dom->saveXml();

		}
	}
	
}

phrasea_close_session($ses);

header('Content-Type: application/atom+xml');

exit($output);
?>
