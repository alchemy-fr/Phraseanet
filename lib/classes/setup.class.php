<?php
class setup
{
	private static $PHP_EXT = array(
			"dom","exif","fileinfo","ftp","curl","gd","gettext","hash","json","iconv","libxml","mbstring"
			,"mysql","phrasea2","SimpleXML","sockets","xml","xsl","zip","zlib");
		
		
	function create_global_values($datas=array())
	{
	
		require(dirname(__FILE__)."/../../lib/conf.d/_GV_template.inc");

                if (defined('GV_timezone'))
                  date_default_timezone_set(GV_timezone);
                else
                  date_default_timezone_set('Europe/Berlin');
    
		$debug = $log_errors = false;
		$gv_file = '';
		
		$EOL = "\n";
			
		$out = '<?php '.$EOL;
		$out .= '/*************'.$EOL.'GENERATED ON '.date("Y-m-d H:i:s").$EOL.'*************/'.$EOL;
		$error = false;
		$extra_conf = '';
		
		foreach($GV as $section)
		{
			foreach($section['vars'] as $variable)
			{
				if(isset($datas[$variable['name']]) === false )
				{
					if(isset($variable['default']))
					{
						if($variable['type'] === 'boolean')
						{
							if($variable['default'] === true)
								$datas[$variable['name']] = 'true';
							else
								$datas[$variable['name']] = 'false';
						}
						else
						{
							$datas[$variable['name']] = $variable['default'];
						}
					}
				}
					
					switch($variable['type'])
					{
						case 'string':
						case 'password':
							$datas[$variable['name']] = (string)trim($datas[$variable['name']]);
							break;
						case 'enum':
							if(!isset($variable['avalaible']))
							{
								$variable['error'] = 'avalaibility';
							}
							elseif(!is_array($variable['avalaible']))
							{
								$variable['error'] = 'avalaibility';
							}
							elseif(!in_array($datas[$variable['name']], $variable['avalaible']))
							{
								$variable['error'] = 'avalaibility';
							}
							break;
						case 'boolean':
							$datas[$variable['name']] = mb_strtolower($datas[$variable['name']]) === 'true' ? 'true' : 'false' ;
							break;
						case 'integer':
							$datas[$variable['name']] = (int)trim($datas[$variable['name']]);
							break;
						case 'text':
							$datas[$variable['name']] = trim($datas[$variable['name']]);
							break;
						case 'timezone':
							$datas[$variable['name']] = trim($datas[$variable['name']]);
							break;
						default:
							$error = true;
							break;
					}
					
					if(isset($variable['required']) && $variable['required'] === true && trim($datas[$variable['name']]) === '')
						$variable['error'] = 'required';
					
					if(isset($variable['end_slash']))
					{
						if($variable['end_slash'] === true)
						{
							$datas[$variable['name']] = p4string::addEndSlash($datas[$variable['name']]);	
						}
						if($variable['end_slash'] === false)
						{
							$datas[$variable['name']] = p4string::delEndSlash($datas[$variable['name']]);
						}
					}
					
					if($variable['name'] === 'GV_debug' && $datas[$variable['name']] === 'true')
						$debug = true;
					if($variable['name'] === 'GV_log_errors' && $datas[$variable['name']] === 'true')
						$log_errors = true;
										
					if($variable['type'] !== 'integer' && $variable['type'] !== 'boolean')
						$datas[$variable['name']] = '\'' . str_replace("'","\'",$datas[$variable['name']] ) .'\'';
						
					$out .= 'define("'.$variable['name'].'",' . $datas[$variable['name']] . ');'.$EOL;
					
			}
		}
		
		$debug_vars = array('display_errors','display_startup_errors');
		$log_vars = array('log_errors');
		
		foreach($PHP_CONF as $k=>$v)
		{
			if($debug && in_array($k,$debug_vars))
				$v = 'on';
			if($log_errors && in_array($k,$log_vars))
			{
				$v = 'on';
				
				$out .= 'ini_set(\'error_log\',\''.GV_RootPath.'logs/php_error.log\');'.$EOL;
			}
			$out .= 'ini_set(\''.$k.'\',\''.$v.'\');'.$EOL;
			$out .= $extra_conf;
		}
		
		if($error === false)
		{
			if(file_put_contents(dirname( __FILE__ ) . "/../../config/_GV.php", $out) === false) 
			{
				return false;
			}
			else
				return true;
		}
		return false;
	}
	
	function check_binaries()
	{
		$binaries = array(
		'PHP CLI'=> GV_cli,
		'ImageMagick (convert)'=> GV_imagick,
		'PDF 2 SWF'=> GV_pdf2swf,
		'Unoconv'=> GV_unoconv,
		'SWFextract'=> GV_swf_extract,
		'SWFrender'=> GV_swf_render,
		'MP4Box'=> GV_mp4box,
		'xpdf (pdf2text)'=>GV_pdftotext,
		'ImageMagick (composite)'=> GV_pathcomposite,
		'Exiftool'=> GV_exiftool,
		'FFmpeg'=> GV_ffmpeg,
		'MPlayer'=> GV_mplayer
		);
		
		
		echo '<h1>'._('setup::Executables').'</h1>';
		echo '<ul>';
		
		foreach($binaries as $name=>$binary)
		{
			if(trim($binary) == '' || (!is_file($binary)))
			{
				?>
				<li class="non-blocker"><?php echo sprintf(_('Binaire non declare ou non trouvable : %s '),$name)?></li>
				<?php
			}
			else
			{
				if(!is_executable($binary))
				{
					?>
					<li class="blocker"><?php echo sprintf(_('Binaire non executable : %s '),$name)?></li>
					<?php
				}
				else
				{
					$goods[] = $name;
				}
			}
		}
		if(count($goods) > 0)
			echo '<li>'.implode('<br/>',$goods).'</li>';
		echo '</ul>';
		return;
	}
	
	function check_mod_auth_token()
	{
		if(GV_h264_streaming !== true)
			return;
		?>
		<h1>mod_auth_token configuration </h1>
		<ul>
		<?php
		$fileName = GV_mod_auth_token_directory_path.'/test_mod_auth_token.txt';    // The file to access

		touch($fileName);

		$url = GV_ServerName.p4file::apache_tokenize($fileName);

		if(p4::getHttpCodeFromUrl($url) == 200)
			echo '<li>'._('mod_auth_token correctement configure').'</li>';
		else
			echo '<li class="blocker">'._('mod_auth_token mal configure').'</li>';
		
		?>
		</ul>
		<?php 
	}
	
	function check_apache()
	{
		?>
		<h1>Apache Server mods avalaibility</h1>
		<div style="position:relative;float:left;">
		<?php 
		echo _('Attention, seul le test de l\'activation des mods est effectue, leur bon fonctionnement ne l\'est pas ')
		?>
		</div>
		
		<ul id="apache_mods_checker">

		<li class="blocker">
			<a href="#" onclick="check_apache_mod(this,'rewrite');return false;">mod_rewrite (required)</a>
		</li>
		<li class="blocker">
			<a href="#" onclick="check_apache_mod(this,'xsendfile');return false;">mod_xsendfile (optionnal)</a>
			<?php  if(GV_modxsendfile){?>
				<div class="infos"><img style="vertical-align:middle" src="/skins/icons/alert.png"/> <?php echo _('Attention, veuillez verifier la configuration xsendfile, actuellement activee dans le setup');?></div>
			<?php }?>
		</li>
		<li class="blocker">
			<a href="#" onclick="check_apache_mod(this,'authtoken');return false;">mod_auth_token (optionnal)</a>
			<?php  if(GV_h264_streaming){?>
				<div class="infos"><img style="vertical-align:middle" src="/skins/icons/alert.png"/> <?php echo _('Attention, veuillez verifier la configuration h264_streaming, actuellement activee dans le setup');?></div>
			<?php }?>
		</li>
		<li class="blocker">
			<a href="#" onclick="check_apache_mod(this,'h264');return false;">mod_h264_streaming (optionnal)</a>
			<?php  if(GV_h264_streaming){?>
				<div class="infos"><img style="vertical-align:middle" src="/skins/icons/alert.png"/> <?php echo _('Attention, veuillez verifier la configuration h264_streaming, actuellement activee dans le setup');?></div>
			<?php }?>
		</li>
		<style type="text/css">
			#apache_mods_checker div.infos{
				display:none;
			}
			#apache_mods_checker .blocker div.infos{
				display:block;
			}
		</style>
		<script type="text/javascript">
			$(document).ready(function(){
				$('#apache_mods_checker a').trigger('click');
			});

			function check_apache_mod(el,mod)
			{
				var url = '/admin/test-';
				switch(mod)
				{
					case 'rewrite':
						url += 'rewrite';
						break;
					case 'xsendfile':
						url += 'xsendfile';
						break;
					case 'authtoken':
						url += 'authtoken';
						break;
					case 'h264':
						url += 'h264';
						break;
				}

				$.get(url, function(data) {
					 if(data == '1')
						 $(el).closest('li').removeClass('blocker');
					 else
						 $(el).closest('li').addClass('blocker');
					});
									
				
			}
		</script>
		
		<?php 
		echo '</ul>';
	}
	
	function check_phrasea()
	{
		$infos = phrasea_info();
		
		echo '<h1>'._('Phrasea Module').'</h1>';
		
		echo '<ul>';
		foreach($infos as $name=>$value)
		{
			$blocker = $name == 'temp_writable' ? ($value ? '' : 'blocker') : '';
			
			echo '<li class="'.$blocker.'">'.$name.' : '.($name == 'temp_writable' ? ($value ? 'true' : 'false') : $value).'</li>';
		}
		echo '</ul>';
		return;
		
	}
	
	function check_writability()
	{
		$root = p4string::addEndSlash(realpath(dirname(__FILE__).'/../../'));
		
		
		$pathes = array(
			$root.'config',
			$root.'config/stamp',
			$root.'config/status',
			$root.'config/minilogos',
			$root.'config/templates',
			$root.'config/topics',
			$root.'config/wm',
			$root.'logs',
			$root.'tmp',
			$root.'www/custom',
			$root.'tmp/locks',
			$root.'tmp/cache_twig',
			$root.'tmp/cache_minify',
			$root.'tmp/lazaret',
			$root.'tmp/desc_tmp',
			$root.'tmp/download',
			$root.'tmp/batches');
	
		if(defined(GV_base_datapath_web))
		{
			$pathes[] = GV_base_datapath_web;	
		}
		if(defined(GV_base_datapath_noweb))
		{
			$pathes[] = GV_base_datapath_noweb;	
		}
			 
		
			
		$goods = array();
		
		echo '<h1>'._('setup::Filesystem configuration').'</h1>';
		echo '<ul>';
		foreach($pathes as $p)
		{
			if(!is_writable($p))
			{
				?>
				<li class="blocker"><?php echo sprintf(_('Dossier non inscriptible : %s '),$p)?></li>
				<?php
			}
			else
			{
				$goods[] = $p;
			}
		}
		if(count($goods) > 0)
			echo '<li>'.implode('<br/>',$goods).'</li>';
		echo '</ul>';
		return;
	}
	
	function check_mail_form()
	{
		echo '<h1>'._('setup::Tests d\'envois d\'emails').'</h1>';
		?>
		<form method="post" action="/admin/sitestruct.php" target="_self">
			<label>Email : </label><input name="email" type="text" />
			<input type="submit" value="<?php echo _('boutton::valider');?>"/>
		</form>
		
		<?php
		return;			
	}
	
	function check_php_version()
	{
		echo '<h1>'._('setup::PHP Version').'</h1>';
		echo '<ul>';
		if(version_compare(PHP_VERSION,'5.2.4','<'))
		{
			?>
			<li class="blocker"><?php echo _('setup::Votre version de PHP est trop ancienne. PHP 5.2.4 est necessaire')?></li>
			<?php 
		}
		else
		{
			?>
			<li class="good-enough"><?php echo sprintf(_('setup::Votre version de PHP convient : PHP version %s'),PHP_VERSION)?></li>
			<?php 
		}
		echo '</ul>';
		return;
	}

	function check_php_extension()
	{
		$avalaibles_caches = array('memcache','memcached');
		
			
		echo '<h1>'._('setup::PHP extensions').'</h1>';
		echo '<ul>';
		$goods = array();
		foreach(self::$PHP_EXT as $ext)
		{
			if(in_array($ext,array('curl')))
			{
				if(extension_loaded($ext) !== true)
				{
					echo '<li class="non-blocker">'.sprintf(_('setup::Il manque l\'extension %s , recommandee'),$ext).'</li>';
				}
				else
					$goods[] = $ext;
			}
			else
			{
				if(extension_loaded($ext) !== true)
				{
					echo '<li class="blocker">'.sprintf(_('setup::Il manque l\'extension %s'),$ext).'</li>';
				}
				else
					$goods[] = $ext;
			}
		}
		
		$found = false;
		foreach($avalaibles_caches as $ext)
		{
				if(extension_loaded($ext) === true)
				{
					$goods[] = $ext;
					$found = true;
				}
		}
		if(!$found)
			echo '<li class="non-blocker">'.sprintf(_('setup::Aucun module memcached na ete detecte sur cette installation.')).'</li>';
		
		if(count($goods) > 0)
			echo '<li>'.implode('<br/>',$goods).'</li>';
		
		echo '</ul>';
		return;
	}
	
	function check_php_extension_console()
	{
		$avalaibles_caches = array('memcache','memcached');
		$error = false;
		foreach(self::$PHP_EXT as $ext)
		{
			if(in_array($ext,array('curl')))
			{
				if(extension_loaded($ext) !== true)
				{
					echo "--> ".sprintf('%1$s extension %2$s missing, recommanded','/!\\ WARNING /!\\',$ext)."\n";
				}
				else
					echo "\t--> extension loaded : ".$ext."\n";
			}
			else
			{
				if(extension_loaded($ext) !== true)
				{
					echo "--> ".sprintf('%1$s extension %2$s missing, required','/!\\ FAILED  /!\\',$ext)."\n";
					$error = true;
				}
				else
					echo "\t--> extension loaded : ".$ext."\n";
			}
		}
		
		$found = false;
		foreach($avalaibles_caches as $ext)
		{
				if(extension_loaded($ext) === true)
				{
					echo "\t--> extension loaded : ".$ext."\n";
					$found = true;
				}
		}
		if(!$found)
			echo sprintf("No memcached module detected.");
		
		
		$found = true;
		
		foreach(user::$locales as $code=>$language_name)
		{
			phrasea::use_i18n($code, 'test');
			
			echo "\n\tChecking locale support for ".html_entity_decode($language_name, ENT_QUOTES, 'UTF-8')." ... ";
			if(_('test::test') == 'test')
				echo "\tOK";
			else
			{
				$found = false;
				echo "\tNO";
			}
		}
		
		if(!$found)
		{
			echo "\n\nSome language are not supported, please install system locale packages in order to use them\n";	
		}
		
		return $error;
	}
	
	function check_cache_memcache()
	{
		
		echo '<h1>'._('setup:: Serveur Memcached').'</h1>';
		echo '<ul>';
		if(GV_use_cache)
		{
			$cache = cache::getInstance();
			
			if($cache->is_ok())
			{
				$stats = $cache->getStats();
				
				if($cache->getExtensionName() == 'memcached')
					$stats = $stats[GV_memcached.':'.GV_memcached_port];
				
				echo '<li>Memcached statistics given by `'.$cache->getExtensionName().'`</li>';
				echo '<li>'.sprintf(_('setup::Serveur actif sur %s'),GV_memcached.':'.GV_memcached_port).'</li>';
				echo '<table>';
				echo "<tr class='even'><td>Memcache Server version:</td><td> ".$stats ["version"]."</td></tr>";
		        echo "<tr><td>Process id of this server process </td><td>".$stats ["pid"]."</td></tr>";
		        echo "<tr class='even'><td>Number of seconds this server has been running </td><td>".$stats ["uptime"]."</td></tr>";
//		        echo "<tr><td>Accumulated user time for this process </td><td>".$stats ["rusage_user"]." seconds</td></tr>";
//		        echo "<tr><td>Accumulated system time for this process </td><td>".$stats ["rusage_system"]." seconds</td></tr>";
		        echo "<tr><td>Total number of items stored by this server ever since it started </td><td>".$stats ["total_items"]."</td></tr>";
		        echo "<tr class='even'><td>Number of open connections </td><td>".$stats ["curr_connections"]."</td></tr>";
		        echo "<tr><td>Total number of connections opened since the server started running </td><td>".$stats ["total_connections"]."</td></tr>";
		        echo "<tr class='even'><td>Number of connection structures allocated by the server </td><td>".$stats ["connection_structures"]."</td></tr>";
		        echo "<tr><td>Cumulative number of retrieval requests </td><td>".$stats ["cmd_get"]."</td></tr>";
		        echo "<tr class='even'><td> Cumulative number of storage requests </td><td>".$stats ["cmd_set"]."</td></tr>";
		
		        $percCacheHit= (real)$stats ["cmd_get"] > 0 ? ((real)$stats ["get_hits"]/ (real)$stats ["cmd_get"] *100) : 100;
		        $percCacheHit=round($percCacheHit,3);
		        $percCacheMiss=100-$percCacheHit;
		
		        echo "<tr><td>Number of keys that have been requested and found present </td><td>".$stats ["get_hits"]." ($percCacheHit%)</td></tr>";
		        echo "<tr class='even'><td>Number of items that have been requested and not found </td><td>".$stats ["get_misses"]."($percCacheMiss%)</td></tr>";
		
		        $MBRead= (real)$stats["bytes_read"]/(1024*1024);
		
		        echo "<tr><td>Total number of bytes read by this server from network </td><td>".round($MBRead,1)." MB</td></tr>";
		        $MBWrite=(real) $stats["bytes_written"]/(1024*1024) ;
		        echo "<tr class='even'><td>Total number of bytes sent by this server to network </td><td>".round($MBWrite,1)." MB</td></tr>";
		        $MBSize=(real) $stats["limit_maxbytes"]/(1024*1024) ;
		        echo "<tr><td>Number of bytes this server is allowed to use for storage.</td><td>".round($MBSize,1)." MB</td></tr>";
		        echo "<tr class='even'><td>Number of valid items removed from cache to free memory for new items.</td><td>".$stats ["evictions"]."</td></tr>";
		        echo '</table>';
			}
			else
			{
				echo '<li class="non-blocker">'.sprintf(_('Le serveur memcached ne repond pas, vous devriez desactiver GV_memcached')).'</li>';
			}
			
		}
		else
		{
			echo '<li class="non-blocker">'.sprintf(_('setup::Aucun serveur memcached rattache.')).'</li>';
		}
		echo '</ul>';
		
	}
	
	function check_cache_opcode()
	{
		$avalaibles = array('XCache','apc','eAccelerator','phpa','WinCache');
		
		echo '<h1>'._('setup::PHP cache system').'</h1>';
		echo '<ul>';
		foreach($avalaibles as $ext)
		{
				if(extension_loaded($ext) === true)
				{
					echo '<li>'.$ext.'';
					echo '</ul>';
					return;
				}
		}
		echo '<li class="non-blocker">'.sprintf(_('setup::Aucun cache PHP n\'a ete detecte sur cette installation.')).'';
		echo '<br/>'.sprintf(_('setup::Phraseanet recommande l\'utilisation d\'un cache comme XCache ou APC.')).'</li>';
		
		echo '</ul>';
		return;
	}
	
	function check_php_configuration()
	{
		
		include dirname( __FILE__ ) . '/../conf.d/_GV_template.inc';

		echo '<h1>'._('setup::PHP confguration').'</h1>';
		echo '<ul>';
		
		$goods = array();
		
		$nonblockers = array('log_errors','display_startup_errors','display_errors');
		
		foreach($PHP_REQ as $conf=>$value)
		{
			if(($tmp = self::test_php_conf($conf,$value)) === false)
				echo '<li class="blocker">'.sprintf(_('setup::Configuration mauvaise : pour la variable %1$s, configuration donnee : %2$s ; attendue : %3$s'),$conf,$tmp,$value).'</li>';
			else
				$goods[] = $conf.' : '.$value;
		}
		foreach($PHP_CONF as $conf=>$value)
		{
			if(($tmp = self::test_php_conf($conf,$value)) === false)
				echo '<li class="'.(in_array($conf, $nonblockers) ? 'non-':'').'blocker">'.sprintf(_('setup::Configuration mauvaise : pour la variable %1$s, configuration donnee : %2$s ; attendue : %3$s'),$conf,$tmp,$value).'</li>';
			else
				$goods[] = $conf.' : '.$value;
		}
		
		if(count($goods) > 0)
			echo '<li>'.implode('<br/>',$goods).'</li>';
			
		echo '</ul>';
		return;
	}
	
	function check_system_locales()
	{
		echo '<h1>'._('setup::Prise en charge des locales').'</h1>';
		echo '<ul>';
		
		foreach(user::$locales as $code=>$language_name)
		{
			phrasea::use_i18n($code, 'test');
			
			if(_('test::test') == 'test')
				echo "<li class=''>".$language_name."</li>";
			else
			{
				echo "<li class='non-blocker'>".$language_name."</li>";
			}
		}
		phrasea::use_i18n();
		
		echo '</ul>';
		return;
	}
	
	
	private static function test_php_conf($conf,$value)
	{
		$is_flag = false;
		$flags = array('on','off','1','0','');
		if(in_array(mb_strtolower($value),$flags))
			$is_flag = true;
		$current = ini_get($conf);
		if($is_flag)
			$current = mb_strtolower($current);
		
		
		if(($current === '' || $current === 'off' || $current === '0') && $is_flag)
			if($value==='off' || $value==='0' || $value ==='')
				return $current;
		if(($current === '1' || $current === 'on') && $is_flag)
			if($value === 'on' || $value ==='1')
				return $current;
		if($current === $value)
				return $current;
			
		return false;
	}
}