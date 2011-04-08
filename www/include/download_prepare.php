<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms('token', 'get', 'type');

$datas = ((random::helloToken($parm['token'])));


if(!$datas)
	phrasea::headers(204);

if(!is_string($datas['datas']))
	phrasea::headers(204);
	
if(($list = @unserialize($datas['datas'])) == false)
{
	phrasea::headers(500);
}

if(!isset($session->usr_id) || !isset($session->ses_id))
{
	$sign_on = p4::signOnWithToken($parm['token']);
	
	if($sign_on['error'] || !$sign_on['usr_id'])
		phrasea::headers(204);
}
if(!($ph_session = phrasea_open_session($session->ses_id,$session->usr_id)))
{
	phrasea::headers(403);
}

$unique_file = false;
$n_files = $list['count'];

$zip_done = $zip_building = false;

$export_name = $list['export_name'];

if($n_files == 1)
{
	$u_file = $list['files'];
	$u_file = array_pop($u_file);
	$export_name =  $u_file["export_name"]; 
	$u_file = array_pop($u_file['subdefs']);
	$unique_file = true;
	$export_name .= $u_file["ajout"].'.'. $u_file["exportExt"];
	$zipFile = p4string::addEndSlash($u_file['path']).$u_file['file'];
  $mime = $u_file['mime'];
	$zip_done = true;
}
else
{
	$zipFile = GV_RootPath.'tmp/download/'.$datas['value'].'.zip';
  $mime = 'application/zip';
}

$files = $list['files'];

if(isset($parm['get']) && $parm['get'] == '1')
{
	if(export::stream_file($zipFile, $export_name, $mime))
		export::log_download($list,$parm['type']);exit;
	exit;
}



if(isset($list['complete']))
{
	if($list['complete'] == true)
		$zip_done = true;
	else
		$zip_building = true;
}

phrasea::headers();
?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<title><?php echo _('phraseanet:: Telechargement de documents');?></title>
		<meta content="<?php echo GV_metaDescription?>" name="description"/>
		<meta content="<?php echo GV_metaKeywords?>" name="keywords"/>
		<link rel="shortcut icon" type="image/x-icon" href="/prod/favicon.ico" />
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
			p{
				margin:10px 0;
			}
			.loader{
				width:100%;
				height:40px;
				background-image:url(/skins/icons/loader414141.gif);
				background-position:center center;
				background-repeat:no-repeat;
			}
		</style>
		<script type="text/javascript" src="/include/minify/?f=include/jslibs/jquery-1.4.4.js"></script>
	</head>
	<body>
		<div id="page">
			<h1><?php echo _('phraseanet:: Telechargement de documents');?></h1>
			<div style="display:<?php echo $zip_done ? 'none' : 'block' ?>;" id="wait">
			<p><?php echo _('telechargement::Veuillez patienter, vos fichiers sont en train d\'etre rassembles pour le telechargement, cette operation peut prendre quelques minutes.');?></p>
			<div class="loader"></div>
			</div>
			<?php 
			?>
			<div style="display:<?php echo $zip_done ? 'block' : 'none' ?>;" id="ready">
			<p><?php echo sprintf(_('telechargement::Vos documents sont prets. Si le telechargement ne demarre pas, %s cliquez ici %s'),'<a href="/download/'.$parm['token'].'/get" target="_self">','</a>');?></p>
			</div>
			<div style="margin:20px 0;">
				<p><?php echo _('telechargement::Le fichier contient les elements suivants');?></p>
				<table style="width:90%;margin:10px auto;text-align:center;">
					<thead>
						<tr>
							<th><?php echo _('phrseanet:: base');?></th>
							<th><?php echo _('document:: nom');?></th>
							<th><?php echo _('phrseanet:: sous definition');?></th>
							<th><?php echo _('poids');?></th>
							<th style="width:200px;"></th>
						</tr>
					</thead>
				<?php
					$total_size = 0;
					foreach($files as $file)
					{
						$size = 0;
						?>
						<tr valign="middle">
							<td><?php echo phrasea::sbas_names(phrasea::sbasFromBas($file['base_id']))?> (<?php echo phrasea::bas_names($file['base_id'])?>)</td>
							<td><?php echo $file['original_name']?></td>
							<td><?php foreach($file['subdefs'] as $k=>$v){ echo $v['label'].'<br/>'; $size += $v['size'];}?></td>
							<td><?php echo p4string::format_octets($size)?></td>
							<td style="text-align:center;"><?php 
								$thumb = answer::getThumbnail($session->ses_id,$file['base_id'],$file['record_id']);
								
								if($thumb['w']>$thumb['h'])
								{
									$w = 140;
									$h = round($w/($thumb['w']/$thumb['h']));
								}
								else
								{
									$h = 105;
									$w = round($h*($thumb['w']/$thumb['h']));
								}
								
								echo '<img style="height:'.$h.'px;width:'.$w.'px;" src="'.$thumb['thumbnail'].'"/>';
							?></td>
						</tr>
						<?php
						$total_size += $size;
					}
					
					$time = round($total_size/(1024*1024*3));
					$time = $time < 1 ? 2 : ($time > 10 ? 10 : $time);
					
				?>
				</table>
				<script type="text/javascript">
					$(document).ready(function(){
					<?php
					if($zip_done === false && $zip_building === false && !$unique_file)
					{
						?>
							$.post("/include/download_prepare.exe.php", {
								token: "<?php echo $parm['token']; ?>"
							}, function(data){
								if(data == '1')
								{
									$('#wait').hide();
									$('#ready').show();
									get_file();
								}
								return;
							});
						<?php
					}
					elseif($zip_done === true)
					{
						?>
						
						get_file();
						<?php
					}
					else
					{
						?>
						setTimeout("document.location.href = document.location.href",<?php echo $time;?>000);
						<?php
					}
									
					?>
					});
				</script>
				<form name="download" action="/download/<?php echo $parm['token'] ?>/get" method="post" target="get_file">
	
				</form>
				<iframe style="display:none;" name="get_file"></iframe>

				<script type="text/javascript">
					function get_file(){
						$('form[name=download]').submit();
					}
				</script>

			</div>
		</div>
	</body>
</html>

