<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
phrasea::headers();
require(GV_RootPath."lib/PHPShortener/phpshortener.class.php");
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms("bas","rec");

$lng = isset($session->locale)?$session->locale:GV_default_lng;

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

$conn = connection::getInstance();

$right = false;
?>

<html lang="<?php echo $session->usr_i18n;?>">
	<head>
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/jquery-ui.css" />
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/prodcolor.css" />
	
	<script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js,include/jslibs/jquery-ui-1.7.2.js"></script>
	
	<script language="javascript">
		
	$(document).ready(function(){
		$('#tabs').tabs();
		$('input.ui-state-default').hover(
				function(){$(this).addClass('ui-state-hover')},
				function(){$(this).removeClass('ui-state-hover')}
		);
		
	});
	</script>
		</head>
		
		<body class="bodyprofile">
			<div id="tabs">
				<ul><li><a href="#share"><?php echo _('reponses:: partager'); ?></a></li></ul>
				
				<div id="share">
					<?php 
					$sql = 'SELECT b.base_id, sb.sbas_id FROM sbasusr su,sbas sb,  basusr bu, bas b WHERE b.base_id="'.$conn->escape_string($parm['bas']).'" AND bu.base_id = b.base_id AND bu.usr_id="'.$conn->escape_string($usr_id).'" AND su.usr_id="'.$conn->escape_string($usr_id).'" AND su.sbas_id = b.sbas_id AND sb.sbas_id=su.sbas_id AND  su.bas_chupub = "1" AND (bu.canpreview="1" or bu.canhd="1" OR bu.candwnldhd="1" OR bu.candwnldpreview="1")';
					if($rs = $conn->query($sql))
					{
						if($conn->num_rows($rs)>0)//j'ai le droit de publier sur cette base
						{
							$right = true;
							if($row = $conn->fetch_assoc($rs))
								$connSbas = connection::getInstance($row['sbas_id']);
						}
						$conn->free_result($rs);
					}
					if(!$right)
						exit('ERROR<br><input class="input-button" type="button" value="'._('boutton::fermer').'" onclick="parent.hideDwnl();" /> </body></html>');
					if(!$connSbas)
						exit('ERROR<br><input class="input-button" type="button" value="'._('boutton::fermer').'" onclick="parent.hideDwnl();" /> </body></html>');
					
					$type = 'unknown';
					$sha256 = $bitly = $ext = '';
					
					$sql = 'SELECT sha256, type, bitly FROM record WHERE record_id = "'.$connSbas->escape_string($parm['rec']).'" ';
					
					$url = '';
					
					if($rs = $connSbas->query($sql))
					{
						if($row = $connSbas->fetch_assoc($rs))
						{
							$sha256 = $row['sha256'];
							$type = $row['type'];
						
							$url = GV_ServerName."document/".$parm['bas']."/".$parm['rec']."/".$sha256."/";
							if(trim($row['bitly']) == '')
							{
								$short = new PHPShortener;
								$bitly = $short->encode($url.'view/');
								
								if (preg_match('/^(http:\/\/)?(www\.)?([^\/]*)\/(.*)$/', $bitly, $results)){
									if ($results[3] && $results[4]){
										$row['bitly'] = $hash = $results[4];
										$sql = 'UPDATE record SET bitly="'.$connSbas->escape_string($hash).'" WHERE record_id="'.$connSbas->escape_string($parm['rec']).'"';
										$connSbas->query($sql);
									}
								}
								
							}
							$bitly = 'http://bit.ly/'.$row['bitly'];
						}
						$connSbas->free_result($rs);
					}
					
					$embed = '';
					
					if($url != '')
					{
						switch($type)
						{
							case 'video':
								$embed = '<object width="100%" height="100%" type="application/x-shockwave-flash" data="'.GV_ServerName.'include/flowplayer/flowplayer-3.2.2.swf">'.
									'<param value="true" name="allowfullscreen">'.
									'<param value="always" name="allowscriptaccess">'.
									'<param value="high" name="quality">'.
									'<param value="false" name="cachebusting">'.
									'<param value="#000000" name="bgcolor">'.
									'<param value="config={&quot;clip&quot;:{&quot;url&quot;:&quot;'.GV_ServerName.'document/160/89/439b4909408b9651db90c8af6e44aa10aab6881a02820c3801108d1f3b0b0dfd/overview/&quot;},&quot;playlist&quot;:[{&quot;url&quot;:&quot;'.GV_ServerName.'document/160/89/439b4909408b9651db90c8af6e44aa10aab6881a02820c3801108d1f3b0b0dfd/overview/&quot;}]}" name="flashvars">'.
								'</object>';
								break;
							case 'document':
								$embed = '<object width="600" height="500" type="application/x-shockwave-flash" data="'.GV_ServerName.'include/FlexPaper_flash/FlexPaperViewer.swf" style="visibility: visible; width: 600px; height: 500px; top: 0px;">'.
									'<param name="menu" value="false">'.
									'<param name="flashvars" value="SwfFile='.urlencode($url).'&amp;Scale=0.6&amp;ZoomTransition=easeOut&amp;ZoomTime=0.5&amp;ZoomInterval=0.1&amp;FitPageOnLoad=true&amp;FitWidthOnLoad=true&amp;PrintEnabled=false&amp;FullScreenAsMaxWindow=false&amp;localeChain='.$session->locale.'">'.
									'<param name="allowFullScreen" value="true">'.
									'<param name="wmode" value="transparent">'.
								'</object>';
								break;
							case 'audio':
								$embed = '<object width="290" height="24" data="'.GV_ServerName.'include/audio-player/player.swf" type="application/x-shockwave-flash">'.
									'<param value="'.GV_ServerName.'include/audio-player/player.swf" name="movie"/>'.
									'<param value="playerID=1&amp;autostart=yes&amp;soundFile='.urlencode($url).'" name="FlashVars"/>'.
									'<param value="high" name="quality"/>'.
									'<param value="false" name="menu"/>'.
									'</object>';
								break;
							case 'image':
							default:
								$embed = '<a href="'.$url.'view/"><img src="'.$url.'" title="" /></a>';
								break;
						}
					}
					?>
					<div class="boxCloser" onclick="parent.hideDwnl();"><?php echo _('boutton::fermer')?></div>
					<div id="tweet">
						<div style="margin-left:20px;padding:10px 0 5px;"><a href="http://www.twitter.com/home/?status=<?php echo $bitly?>" target="_blank"><img src="/skins/icons/twitter.ico" title="share this on twitter" style="vertical-align:middle;padding:0 5px;"/> Send to Twitter</a></div>  
						<div style="margin-left:20px;padding:5px 0 10px;"><a href="http://www.facebook.com/sharer.php?u=<?php echo $url.'view/'?>" target="_blank"><img src="/skins/icons/facebook.ico" title="share on facebook" style="vertical-align:middle;padding:0 5px;"/> Send to Facebook</a></div>
					</div>
					<div id="embed" style="text-align:center;padding:10px 0;">
						<div style="text-align:left;margin-left:20px;padding:10px 0;">URL : </div>
						<?php 
						if($url != '')
						{
						?>
						<input style="width:90%;" readonly="true" type="text"  value="<?php echo $url?>view/" onfocus="this.focus();this.select();" onclick="this.focus();this.select();" />
						<?php 
						}
						else
						{
							?>
							<div><?php  echo _('Aucune URL disponible');?></div>	
							<?php 
						}
						?>
						<div style="text-align:left;margin-left:20px;padding:10px 0;">Embed :</div>
						<?php 
						if($embed != '')
						{
						?>
						<textarea onfocus="this.focus();this.select();" onclick="this.focus();this.select();" style="width:90%;height:50px;" readonly="true" ><?php echo $embed?></textarea>
						<?php 
						}
						else
						{
							?>
							<div><?php  echo _('Aucun code disponible');?></div>
							<?php 
						}
						?>
					</div>
				
				
				<div style="text-align:center;padding:20px 0;">
					<input class="input-button" type="button" value="<?php echo _('boutton::fermer')?>" onclick="parent.hideDwnl();" /> 
				</div>
			 </div>
		</div>
	</body>
</html>