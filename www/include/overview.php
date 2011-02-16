<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
require_once( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );

$session = session::getInstance();


$request = httpRequest::getInstance();
$parm = $request->get_parms("bas","rec","sha");
			
if(!isset($session->usr_i18n))
{
	$session->usr_i18n = 'fr';
	$lng = GV_default_lng;
}
						

$conn = connection::getInstance();

$file = false;
						
						
$base_id = (int)$parm['bas']; //le base_id local d'une reponses est un 'coll_id' local
$sbas_id = (int)phrasea::sbasFromBas($parm['bas']);//$row['sbas_id'];	// la base de cette collection

$connSbas = connection::getInstance($sbas_id);

if($connSbas)
{
	
	
	$sql = "SELECT path, file, mime, type, xml FROM subdef s, record r WHERE r.record_id='".$connSbas->escape_string($parm['rec'])."' AND r.record_id = s.record_id AND name='preview'";
	
	
	if($rs3 = $connSbas->query($sql))
	{
		if($connSbas->num_rows($rs3) > 0)
		{
			if($row3 = $connSbas->fetch_assoc($rs3))
			{
				$file = array(
						'type'=>$row3['type']
						,'path'=>p4string::addEndSlash($row3['path'])
						,'file'=>$row3['file']
						,'mime'=>$row3['mime']
						,'xml'=>$row3['xml']
					);
			}
		}
		else
		{
			$sql = "SELECT path, file, mime, type, xml FROM subdef s, record r WHERE r.record_id='".$connSbas->escape_string($parm['rec'])."' AND r.record_id = s.record_id AND name='document'";
	
			if($rs2 = $connSbas->query($sql))
			{
				if($row2 = $connSbas->fetch_assoc($rs2))
				{
					if($row2['type'] === 'document')
					{
						$file = array(
								'type'=>$row2['type']
								,'path'=>p4string::addEndSlash($row2['path'])
								,'file'=>$row2['file']
								,'mime'=>$row2['mime']
								,'xml'=>$row2['xml']
							);
					}
				}
			}
		}
		$connSbas->free_result($rs3);
	}	
	


	$title = answer::format_title($sbas_id, $parm['rec'], $file['xml']);//sprintf("record %s", $parm['rec']);
	$caption = answer::format_caption($parm['bas'],$parm['rec'],$file['xml']);//'phraseanet::erreur : erreur de lecture de fiche XML');
	
}

if(!$file)
	phrasea::headers(404);

phrasea::headers();
			?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<title><?php echo strip_tags($title)?></title>
		<meta content="<?php echo GV_metaDescription?>" name="description"/>
		<meta content="<?php echo GV_metaKeywords?>" name="keywords"/>
		<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
		<style type="text/css">
			*,body{
				margin:0;
				padding:0;
				font-family:Helvetica, Arial, sans-serif;
				font-size:1em;
				color:white;
			}
			body{
				background-color:#212121;
				height:100%;
			}
			h1{
				font-size:26px;
				font-weight:bold;
				padding:50px 0 20px;
			}
			#page{
				width:860px;
				background-color:#414141;
				padding:0 20px;
				margin:0 auto;
				height:100%
			}
			.caption{
				padding:50px 0 20px;
			}
		</style>
		<script type="text/javascript" src="/include/flowplayer/flowplayer-3.2.2.min.js"></script>
	</head>
	<body>
	<?php
	$twig = new supertwig();
	$twig->display('common/menubar.twig', array('module'=>'overview'));
	?>
		<div id="page">
		<?php
			echo '<h1>'.$title.'</h1>';	
			$url = '/document/'.$parm['bas'].'/'.$parm['rec'].'/'.$parm['sha'].'/overview/';
			switch($file['type'])
			{
				case 'audio':
					$embed = '<object width="290" height="24" data="/include/audio-player/player.swf" type="application/x-shockwave-flash">'.
						'<param value="/include/audio-player/player.swf" name="movie"/>'.
						'<param value="playerID=1&amp;autostart=yes&amp;soundFile='.urlencode($url).'" name="FlashVars"/>'.
						'<param value="high" name="quality"/>'.
						'<param value="false" name="menu"/>'.
						'</object>';
					break;
				case 'image':
					$embed = '<img src="'.$url.'" title="" />';
					break;
				case 'video':
					$embed = '<div style="width: 600px; height: 400px;" id="flash_preview"></div><script type="text/javascript">flowplayer("flash_preview", "/include/flowplayer/flowplayer-3.2.2.swf", "'.$url.'");</script>';
					break;
				case 'document':
					$embed = '<object width="850" height="500" type="application/x-shockwave-flash" data="/include/FlexPaper_flash/FlexPaperViewer.swf" style="visibility: visible; width: 850px; height: 500px; top: 0px;">
								<param name="menu" value="false">
								<param name="flashvars" value="SwfFile='.urlencode($url).'&amp;Scale=0.6&amp;ZoomTransition=easeOut&amp;ZoomTime=0.5&amp;ZoomInterval=0.1&amp;FitPageOnLoad=true&amp;FitWidthOnLoad=true&amp;PrintEnabled=false&amp;FullScreenAsMaxWindow=false&amp;localeChain='.$session->locale.'">
								<param name="allowFullScreen" value="true">
								<param name="wmode" value="transparent">
							</object>';
					break;
				case 'image':
				default:
					$embed = '<img src="'.$url.'" title="" />';
					break;
			}
			
			echo $embed;
			
			echo '<div class="caption">'.$caption."</div>";
		?>
		</div>
	</body>
</html>